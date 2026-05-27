<?php

namespace App\Http\Middleware;

use App\Models\UserAppAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ValidateGestionRolesContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = trim((string) $request->header('X-User', ''));

        if ($username === '') {
            return response()->json([
                'message' => 'Debe enviar X-User.',
            ], 401);
        }

        $facultadId = $this->contextId($request, 'X-Facultad', ['facultad_id', 'id_facultad']);
        $departamentoId = $this->contextId($request, 'X-Departamento', ['departamento_id', 'id_departamento']);

        if ($facultadId === false || $departamentoId === false) {
            return response()->json([
                'message' => 'X-Facultad y X-Departamento deben ser valores numéricos.',
            ], 422);
        }

        $access = UserAppAccess::query()
            ->where('username', $username)
            ->where('application_code', UserAppAccess::APPLICATION_GESTION_ROLES)
            ->where('active', true)
            ->get();

        if ($access->isEmpty()) {
            return response()->json([
                'message' => 'El usuario no tiene accesos activos para gestion_roles.',
            ], 403);
        }

        if ($this->hasGlobalAccess($access)) {
            return $this->validExistingContext($facultadId, $departamentoId)
                ? $next($request)
                : $this->invalidContextResponse();
        }

        if (!$facultadId) {
            return response()->json([
                'message' => 'Debe enviar X-Facultad para validar el alcance del usuario.',
            ], 422);
        }

        if ($departamentoId && !$this->departmentAccessAllowed($access, $facultadId, $departamentoId)) {
            return $this->forbiddenContextResponse();
        }

        if (!$departamentoId && !$this->facultyWideAccessAllowed($access, $facultadId)) {
            return response()->json([
                'message' => 'Debe enviar X-Departamento para validar el alcance departamental del usuario.',
            ], 422);
        }

        return $this->validExistingContext($facultadId, $departamentoId)
            ? $next($request)
            : $this->invalidContextResponse();
    }

    private function contextId(Request $request, string $header, array $fallbackKeys): int|false|null
    {
        $value = $request->headers->has($header) ? $request->header($header) : null;

        foreach ($fallbackKeys as $key) {
            if ($value !== null) {
                break;
            }

            $value = $request->input($key);
        }

        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : false;
    }

    private function hasGlobalAccess($access): bool
    {
        return $access->contains(function (UserAppAccess $item) {
            return in_array($item->role, ['admin', 'rector'], true)
                && !$item->facultad_id
                && !$item->departamento_id;
        });
    }

    private function facultyWideAccessAllowed($access, int $facultadId): bool
    {
        return $access->contains(function (UserAppAccess $item) use ($facultadId) {
            return (int) $item->facultad_id === $facultadId
                && !$item->departamento_id;
        });
    }

    private function departmentAccessAllowed($access, int $facultadId, int $departamentoId): bool
    {
        return $access->contains(function (UserAppAccess $item) use ($facultadId, $departamentoId) {
            if ((int) $item->facultad_id !== $facultadId) {
                return false;
            }

            if ($item->role === 'jefe_departamento') {
                return (int) $item->departamento_id === $departamentoId;
            }

            return !$item->departamento_id
                && $this->departmentBelongsToFaculty($departamentoId, $facultadId);
        });
    }

    private function validExistingContext(?int $facultadId, ?int $departamentoId): bool
    {
        if ($facultadId && !DB::table('facultad')->where('id', $facultadId)->exists()) {
            return false;
        }

        if ($departamentoId && !DB::table('departamento')->where('id', $departamentoId)->exists()) {
            return false;
        }

        if ($facultadId && $departamentoId) {
            return $this->departmentBelongsToFaculty($departamentoId, $facultadId);
        }

        return true;
    }

    private function departmentBelongsToFaculty(int $departamentoId, int $facultadId): bool
    {
        return DB::table('facultad_departamento')
            ->where('id_facultad', $facultadId)
            ->where('id_departamento', $departamentoId)
            ->exists();
    }

    private function forbiddenContextResponse()
    {
        return response()->json([
            'message' => 'El contexto enviado no pertenece a los accesos activos del usuario en gestion_roles.',
        ], 403);
    }

    private function invalidContextResponse()
    {
        return response()->json([
            'message' => 'La facultad o el departamento enviado no existe, o no coinciden entre sí.',
        ], 422);
    }
}
