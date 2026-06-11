<?php

namespace App\Http\Controllers;

use App\Models\UserAppAccess;
use App\Services\ExternalUserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(Request $request, ExternalUserService $users)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'application' => ['nullable', Rule::in(UserAppAccess::applications())],
        ]);

        $validation = $users->validateCredentials($data['username'], $data['password']);

        if ($validation === null) {
            return response()->json([
                'message' => 'No se pudo validar el usuario en la API de usuarios.',
            ], 503);
        }

        if (($validation['valid'] ?? false) !== true) {
            return response()->json([
                'valid' => false,
                'message' => $validation['message'] ?? 'Usuario o contrasena incorrectos.',
            ], 401);
        }

        $applicationCode = $data['application'] ?? UserAppAccess::APPLICATION_GESTION_ROLES;
        $access = UserAppAccess::query()
            ->where('username', $data['username'])
            ->where('application_code', $applicationCode)
            ->where('active', true)
            ->orderBy('id')
            ->get(['role', 'facultad_id', 'departamento_id', 'active']);

        return response()->json([
            'valid' => true,
            'user' => $validation['user'] ?? [
                'username' => $data['username'],
            ],
            'application_code' => $applicationCode,
            'can_access' => $access->isNotEmpty(),
            'access' => $access,
        ]);
    }

    public function logout()
    {
        return response()->json([
            'message' => 'Sesion cerrada.',
        ]);
    }

    public function user(Request $request)
    {
        $username = $request->header('X-User') ?? $request->query('username');

        if (! $username) {
            return response()->json([
                'message' => 'Usuario no enviado.',
            ], 401);
        }

        return response()->json([
            'username' => $username,
        ]);
    }
}
