<?php

namespace App\Http\Controllers;
use App\Models\Curso;
use App\Models\Log;
use App\Services\ExternalUserService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public static function registrar($usuario, $accion, $descripcion, $facultadId = null)
    {
        try {
            $usuario = app(ExternalUserService::class)->resolveLogUsername($usuario);
            $facultadId = request()->header('X-Facultad')
                ?? $facultadId
                ?? request('facultad_id')
                ?? request('id_facultad');
            $curso = self::cursoParaFechaAccion(now());

            Log::create([
                'usuario' => $usuario,
                'accion' => $accion,
                'descripcion' => $descripcion,
                'facultad_id' => is_numeric($facultadId) ? (int) $facultadId : null,
                'id_curso' => $curso?->id,
            ]);
        } catch (\Exception $e) {
            // 🔥 NO romper flujo principal
            \Log::error('Error guardando log: ' . $e->getMessage());
        }
    }

private static function cursoParaFechaAccion($date): ?Curso
{
    $date = Carbon::parse($date);
    $month = (int) $date->format('n');
    $year = (int) $date->format('Y');

    if ($month >= 9) {
        $courseName = $year.'-'.($year + 1);
    } elseif ($month <= 7) {
        $courseName = ($year - 1).'-'.$year;
    } else {
        return null;
    }

    return Curso::where('curso', $courseName)->first();
}

public function index(Request $request)
{
    $usuario = $request->query('usuario');

    $query = Log::query();

    // 🔥 SOLO filtra si te mandan usuario
    if ($usuario) {
        $query->where('usuario', $usuario);
    }

    $facultadId = $this->documentFacultyId();

    if ($facultadId) {
        $query->where('facultad_id', $facultadId);
    }

    $cursoId = $request->query('id_curso') ?? $request->query('curso_id');

    if ($cursoId) {
        $query->where('id_curso', $cursoId);
    }

    $logs = $query
        ->orderBy('created_at', 'desc')
        ->limit(10) // 🔥 siempre 10
        ->get(['usuario', 'accion', 'descripcion', 'facultad_id', 'id_curso', 'created_at']);

    return response()->json($logs);
}
}
