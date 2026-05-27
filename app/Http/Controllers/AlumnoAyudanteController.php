<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AlumnoAyudante;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use App\Models\Decano;
use App\Models\Profesor;
use Carbon\Carbon;
use App\Models\EstudianteGrupo;
use App\Models\Grupo;
use App\Models\AnoGrupo;
use App\Models\AnoAcademico;
use App\Models\ResolucionConfiguracion;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Storage;
class AlumnoAyudanteController extends Controller
{
    // 🔹 listar
    public function index()
    {
        return AlumnoAyudante::with(['estudiante', 'curso', 'asignatura'])->get();
    }

    // =====================================
    // 🔥 DESIGNAR
    // =====================================
    public function designar(Request $request)
    {
        $fechaAccion = now();
        $cursoAccion = $this->courseForActionDate($fechaAccion);

        if (!$cursoAccion) {
            return $this->courseErrorForActionDate($fechaAccion);
        }

        $request->validate([
            'id_estudiante' => 'required|exists:estudiantes,id',
            'id_asignatura' => 'required|exists:asignatura,id',
            'nombre_tutor' => 'required|string',
            'etapa' => 'required|string'
        ]);

        $aaExistenteCurso = AlumnoAyudante::where('id_estudiante', $request->id_estudiante)
            ->where('id_curso', $cursoAccion->id)
            ->orderBy('id')
            ->first();

        AlumnoAyudante::where('id_estudiante', $request->id_estudiante)
            ->where('habilitado', true)
            ->when($aaExistenteCurso, function ($query) use ($aaExistenteCurso) {
                $query->where('id', '<>', $aaExistenteCurso->id);
            })
            ->update([
                'habilitado' => false,
                'fecha_fin' => $fechaAccion
            ]);

        if ($aaExistenteCurso) {
            AlumnoAyudante::where('id_estudiante', $request->id_estudiante)
                ->where('id_curso', $cursoAccion->id)
                ->where('id', '<>', $aaExistenteCurso->id)
                ->delete();

            $aaExistenteCurso->update([
                'id_asignatura' => $request->id_asignatura,
                'nombre_tutor' => $request->nombre_tutor,
                'etapa' => $request->etapa,
                'fecha_inicio' => $fechaAccion,
                'fecha_fin' => null,
                'tipo' => 'designado',
                'habilitado' => true,
            ]);

            $aa = $aaExistenteCurso->fresh();
        } else {
            $aa = AlumnoAyudante::create([
                'id_estudiante' => $request->id_estudiante,
                'id_asignatura' => $request->id_asignatura,
                'id_curso' => $cursoAccion->id,
                'nombre_tutor' => $request->nombre_tutor,
                'etapa' => $request->etapa,
                'fecha_inicio' => $fechaAccion,
                'fecha_fin' => null,
                'tipo' => 'designado',
                'habilitado' => true
            ]);
        }
$usuario = $request->header('X-User') ?? 'desconocido';
// 🔥 LOG ANTES DEL RETURN
$estudiante = $aa->estudiante;

\App\Http\Controllers\LogController::registrar(
    $usuario,
    'designar_aa',
    'Se designó a ' . $estudiante->nombre . ' ' . $estudiante->apellidos .
    ' como Alumno Ayudante (Tutor: ' . $aa->nombre_tutor .
    ', Etapa: ' . $aa->etapa .
    ', Asignatura ID: ' . $aa->id_asignatura . ')'
);

return $aa;
    }

    // =====================================
    // 🔥 RATIFICAR
    // =====================================
public function ratificar(Request $request, $id)
{
    $fechaAccion = now();
    $cursoAccion = $this->courseForActionDate($fechaAccion);

    if (!$cursoAccion) {
        return $this->courseErrorForActionDate($fechaAccion);
    }

    $registro = AlumnoAyudante::findOrFail($id);

    if (!$this->puedeAccionarAA($registro->id_estudiante, $cursoAccion->id)) {
        return response()->json([
            'error' => 'Solo se puede ratificar un alumno ayudante designado en cursos anteriores o con acción previa en este curso.'
        ], 400);
    }

    $nuevo = $this->guardarAccionAA($registro, $cursoAccion->id, 'ratificado', $fechaAccion);

    // 🔥 obtener estudiante
    $estudiante = $nuevo->estudiante;

    // 🔥 usuario (igual que designar)
    $usuario = $request->header('X-User') ?? 'desconocido';

    // 🔥 log
    LogController::registrar(
        $usuario,
        'ratificar_aa',
        'Se ratificó a ' . $estudiante->nombre . ' ' . $estudiante->apellidos . ' como Alumno Ayudante'
    );

    return response()->json([
        'message' => 'Ratificado',
        'data' => $nuevo
    ]);
}

    // =====================================
    // 🔥 DESNOMBRAR
    // =====================================
public function desnombrar(Request $request, $id)
{
    $fechaAccion = now();
    $cursoAccion = $this->courseForActionDate($fechaAccion);

    if (!$cursoAccion) {
        return $this->courseErrorForActionDate($fechaAccion);
    }

    $registro = AlumnoAyudante::findOrFail($id);

    if (!$this->puedeAccionarAA($registro->id_estudiante, $cursoAccion->id)) {
        return response()->json([
            'error' => 'Solo se puede desnombrar un alumno ayudante designado o ratificado en cursos anteriores o con acción previa en este curso.'
        ], 400);
    }

    $nuevo = $this->guardarAccionAA($registro, $cursoAccion->id, 'desnombrado', $fechaAccion);

    // 🔥 obtener estudiante
    $estudiante = $nuevo->estudiante;

    // 🔥 usuario (igual que designar)
    $usuario = $request->header('X-User') ?? 'desconocido';

    // 🔥 log
    LogController::registrar(
        $usuario,
        'desnombrar_aa',
        'Se desnombró a ' . $estudiante->nombre . ' ' . $estudiante->apellidos . ' como Alumno Ayudante'
    );

    return response()->json([
        'message' => 'Desnombrado',
        'data' => $nuevo
    ]);
}

private function guardarAccionAA(AlumnoAyudante $registroBase, int $cursoId, string $tipo, $fechaAccion): AlumnoAyudante
{
    $registroCurso = AlumnoAyudante::where('id_estudiante', $registroBase->id_estudiante)
        ->where('id_curso', $cursoId)
        ->orderBy('id')
        ->first();

    AlumnoAyudante::where('id_estudiante', $registroBase->id_estudiante)
        ->where('habilitado', true)
        ->when($registroCurso, function ($query) use ($registroCurso) {
            $query->where('id', '<>', $registroCurso->id);
        })
        ->update([
            'habilitado' => false,
            'fecha_fin' => $fechaAccion,
        ]);

    $data = [
        'id_curso' => $cursoId,
        'id_asignatura' => $registroBase->id_asignatura,
        'nombre_tutor' => $registroBase->nombre_tutor,
        'etapa' => $registroBase->etapa,
        'fecha_inicio' => $fechaAccion,
        'fecha_fin' => $tipo === 'desnombrado' ? $fechaAccion : null,
        'habilitado' => $tipo !== 'desnombrado',
        'tipo' => $tipo,
    ];

    if ($registroCurso) {
        AlumnoAyudante::where('id_estudiante', $registroBase->id_estudiante)
            ->where('id_curso', $cursoId)
            ->where('id', '<>', $registroCurso->id)
            ->delete();

        $registroCurso->update($data);

        return $registroCurso->fresh();
    }

    return AlumnoAyudante::create(array_merge($data, [
        'id_estudiante' => $registroBase->id_estudiante,
    ]));
}

private function puedeAccionarAA(int $estudianteId, int $cursoId): bool
{
    $mismaAccionCurso = AlumnoAyudante::where('id_estudiante', $estudianteId)
        ->where('id_curso', $cursoId)
        ->exists();

    if ($mismaAccionCurso) {
        return true;
    }

    return AlumnoAyudante::where('id_estudiante', $estudianteId)
        ->where('id_curso', '<', $cursoId)
        ->whereIn('tipo', ['designado', 'ratificado'])
        ->exists();
}

    // =====================================
    // 🔥 ACTUAL POR ESTUDIANTE
    // =====================================
    public function actual($estudianteId)
    {
        return AlumnoAyudante::where('id_estudiante', $estudianteId)
            ->where('habilitado', true)
            ->with(['curso', 'asignatura'])
            ->first();
    }

    // =====================================
    // 🔥 HISTORIAL
    // =====================================
    public function historial($estudianteId)
    {
        return AlumnoAyudante::where('id_estudiante', $estudianteId)
            ->with(['curso', 'asignatura'])
            ->orderBy('fecha_inicio', 'desc')
            ->get();
    }


public function activos()
{
    return DB::table('alumno_ayudante as aa')
        ->join('estudiantes as e', 'aa.id_estudiante', '=', 'e.id')

        // estudiante → grupo
        ->leftJoin('estudiante_grupo as eg', 'e.id', '=', 'eg.estudiante_id')

        // grupo
        ->leftJoin('grupos as g', 'eg.grupo_id', '=', 'g.id')

        // 🔥 NUEVO: tabla intermedia correcta
        ->leftJoin('ano_grupo as ag', 'g.id', '=', 'ag.grupo_id')

        // año académico
        ->leftJoin('a_academico as a', 'ag.ano_academico_id', '=', 'a.id')
        ->leftJoin('curso as c', 'aa.id_curso', '=', 'c.id')
        ->leftJoin('asignatura as asi', 'aa.id_asignatura', '=', 'asi.id')

        ->where('aa.habilitado', true)

        ->select(
            'aa.id',
            'aa.id_estudiante',
            'aa.id_asignatura',
            'asi.nombre as asignatura',
            'aa.id_curso',
            'c.curso',

            'e.nombre',
            'e.apellidos',
            'e.numero_carnet',

            DB::raw("CONCAT(e.nombre, ' ', e.apellidos) as nombre_completo"),

            'aa.nombre_tutor as tutor',
            'aa.etapa',

            // 🔥 esto ahora sí funciona
            'a.identificador as ano_academico'
        )
        ->get();
}

public function historialPorCurso(Request $request)
{
    $facultadId = $this->documentFacultyId();
    $departamentoId = $this->documentDepartmentId();
    $cursoId = $request->query('id_curso') ?? $request->query('curso_id');

    $query = DB::table('alumno_ayudante as aa')
        ->join('estudiantes as e', 'aa.id_estudiante', '=', 'e.id')
        ->leftJoin('asignatura as asi', 'aa.id_asignatura', '=', 'asi.id')
        ->leftJoin('curso as c', 'aa.id_curso', '=', 'c.id')
        ->leftJoin('estudiante_grupo as eg', 'e.id', '=', 'eg.estudiante_id')
        ->leftJoin('grupos as g', 'eg.grupo_id', '=', 'g.id')
        ->leftJoin('ano_grupo as ag', 'g.id', '=', 'ag.grupo_id')
        ->leftJoin('a_academico as a', 'ag.ano_academico_id', '=', 'a.id')
        ->leftJoin('programa_de_formacion as pf', 'a.id_prog_form', '=', 'pf.id')
        ->leftJoin('departamento_prog_d_form as dpf', 'pf.id', '=', 'dpf.id_prog_form')
        ->leftJoin('departamento as d', 'dpf.id_departamento', '=', 'd.id')
        ->leftJoin('facultad_departamento as fd', 'd.id', '=', 'fd.id_departamento');

    if ($cursoId) {
        $query->where('aa.id_curso', $cursoId);
    }

    if ($departamentoId) {
        $query->where('d.id', $departamentoId);
    } elseif ($facultadId) {
        $query->where('fd.id_facultad', $facultadId);
    }

    return response()->json(
        $query
            ->orderByDesc('aa.id_curso')
            ->orderBy('d.nombre')
            ->orderBy('e.apellidos')
            ->select(
                'aa.id',
                'aa.id_estudiante',
                'aa.id_asignatura',
                'asi.nombre as asignatura',
                'aa.id_curso',
                'c.curso',
                'e.nombre',
                'e.apellidos',
                'e.numero_carnet',
                DB::raw("CONCAT(e.nombre, ' ', e.apellidos) as nombre_completo"),
                'aa.nombre_tutor as tutor',
                'aa.etapa',
                'aa.tipo as accion',
                'aa.habilitado',
                'aa.fecha_inicio',
                'aa.fecha_fin',
                'a.id as id_a_academico',
                'a.identificador as ano_academico',
                'pf.id as carrera_id',
                'pf.nombre as carrera',
                'd.id as departamento_id',
                'd.nombre as departamento',
                'fd.id_facultad'
            )
            ->distinct()
            ->get()
    );
}

private function camposResolucionAaPermitidos(): array
{
    return [
        'anio_resolucion',
        'resolucion_ministerial',
        'fecha_resolucion_ministerial',
        'capitulo',
        'articulo_colectivo',
        'articulo_conduccion',
        'curso_resolucion',
        'fecha_archivese',
        'dia_archivese',
        'mes_archivese',
        'anio_archivese',
        'revolucion_texto',
        'logo_izq',
        'logo_der',
    ];
}

private function camposResolucionAaFrontend(Request $request, Carbon $fecha, array $guardados = []): array
{
    $defaults = [
        'anio_resolucion' => (string) $fecha->year,
        'resolucion_ministerial' => '47/2022',
        'fecha_resolucion_ministerial' => '27 de mayo de 2022',
        'capitulo' => 'IX',
        'articulo_colectivo' => '153',
        'articulo_conduccion' => '156',
        'curso_resolucion' => (string) $fecha->year,
        'dia_archivese' => (string) $fecha->day,
        'mes_archivese' => $fecha->translatedFormat('F'),
        'anio_archivese' => (string) $fecha->year,
        'revolucion_texto' => 'AÑO '.($fecha->year - 1958).' DE LA REVOLUCION',
        'logo_izq' => null,
        'logo_der' => null,
    ];

    $permitidos = array_flip($this->camposResolucionAaPermitidos());
    $guardados = array_intersect_key($guardados, $permitidos);
    $enviados = array_intersect_key($request->all(), $permitidos);

    return array_merge($defaults, $guardados, $enviados);
}

private function camposGuardadosResolucionAa(?int $facultadId): array
{
    if (!$facultadId) {
        return [];
    }

    $configuracion = ResolucionConfiguracion::where('facultad_id', $facultadId)
        ->where('tipo', 'aa')
        ->first();

    return $configuracion->fields ?? [];
}

private function camposEditablesResolucionAa(Request $request, Carbon $fecha, array $guardados = []): array
{
    $fields = $this->camposResolucionAaFrontend($request, $fecha, $guardados);
    $anioResolucion = $fields['anio_resolucion'];
    $fechaArchivese = $fecha->copy();

    if (!empty($fields['fecha_archivese'])) {
        try {
            $fechaArchivese = Carbon::parse($fields['fecha_archivese']);
        } catch (\Throwable $e) {
            $fechaArchivese = $fecha->copy();
        }
    }

    $diaArchivese = $fields['dia_archivese'] ?? $fechaArchivese->day;
    $mesArchivese = $fields['mes_archivese'] ?? $fechaArchivese->translatedFormat('F');
    $anioArchivese = $fields['anio_archivese'] ?? $fechaArchivese->year;

    return [
        'anioResolucion' => $anioResolucion,
        'resolucionMinisterial' => $fields['resolucion_ministerial'],
        'fechaResolucionMinisterial' => $fields['fecha_resolucion_ministerial'],
        'capitulo' => $fields['capitulo'],
        'articuloColectivo' => $fields['articulo_colectivo'],
        'articuloConduccion' => $fields['articulo_conduccion'],
        'cursoResolucion' => $fields['curso_resolucion'],
        'diaArchivese' => $diaArchivese,
        'mesArchivese' => $mesArchivese,
        'anioArchivese' => $anioArchivese,
        'revolucionTexto' => $fields['revolucion_texto'] ?? 'AÑO '.((int) $anioArchivese - 1958).' DE LA REVOLUCION',
        'logoIzq' => $fields['logo_izq'] ?? null,
        'logoDer' => $fields['logo_der'] ?? null,
    ];
}

private function logoResolucionAa(array $camposEditables, string $field, bool $previewHtml): string
{
    $default = $field === 'logoIzq' ? 'images/logo_izq.png' : 'images/logo_der.png';
    $value = $camposEditables[$field] ?? null;

    if ($value) {
        if (str_starts_with($value, 'data:image/') || preg_match('/^https?:\/\//', $value)) {
            return $value;
        }

        $path = str_starts_with($value, 'storage/')
            ? substr($value, strlen('storage/'))
            : $value;

        if (Storage::disk('public')->exists($path)) {
            return $previewHtml ? asset('storage/'.$path) : storage_path('app/public/'.$path);
        }
    }

    return $previewHtml ? asset($default) : public_path($default);
}

public function configuracionResolucionAa(Request $request)
{
    $facultadId = $this->documentFacultyId();

    if (!$facultadId) {
        return response()->json(['error' => 'Debe enviar X-Facultad, facultad_id o id_facultad.'], 422);
    }

    Carbon::setLocale('es');
    $fecha = Carbon::now();
    $configuracion = ResolucionConfiguracion::where('facultad_id', $facultadId)
        ->where('tipo', 'aa')
        ->first();

    return response()->json([
        'facultad_id' => $facultadId,
        'tipo' => 'aa',
        'fields' => $this->camposResolucionAaFrontend($request, $fecha, $configuracion->fields ?? []),
        'updated_by' => $configuracion->updated_by ?? null,
        'updated_at' => optional($configuracion)->updated_at,
    ]);
}

public function guardarConfiguracionResolucionAa(Request $request)
{
    $facultadId = $this->documentFacultyId();

    if (!$facultadId) {
        return response()->json(['error' => 'Debe enviar X-Facultad, facultad_id o id_facultad.'], 422);
    }

    $data = $request->validate([
        'fields' => 'required|array',
    ]);

    $configuracionActual = ResolucionConfiguracion::where('facultad_id', $facultadId)
        ->where('tipo', 'aa')
        ->first();

    $fields = array_merge(
        $configuracionActual->fields ?? [],
        array_intersect_key($data['fields'], array_flip($this->camposResolucionAaPermitidos()))
    );

    $configuracion = ResolucionConfiguracion::updateOrCreate(
        ['facultad_id' => $facultadId, 'tipo' => 'aa'],
        ['fields' => $fields, 'updated_by' => $request->header('X-User', 'desconocido')]
    );

    Carbon::setLocale('es');
    $fecha = Carbon::now();

    return response()->json([
        'facultad_id' => $facultadId,
        'tipo' => 'aa',
        'fields' => $this->camposResolucionAaFrontend($request, $fecha, $configuracion->fields ?? []),
        'updated_by' => $configuracion->updated_by,
        'updated_at' => $configuracion->updated_at,
    ]);
}

public function guardarLogoConfiguracionResolucionAa(Request $request)
{
    $facultadId = $this->documentFacultyId();

    if (!$facultadId) {
        return response()->json(['error' => 'Debe enviar X-Facultad, facultad_id o id_facultad.'], 422);
    }

    $request->validate([
        'field' => 'required|in:logo_izq,logo_der',
        'file' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        'logo_izq' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        'logo_der' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
    ]);

    $field = $request->input('field');
    $file = $request->file('file') ?? $request->file('logo') ?? $request->file($field);

    if (!$file) {
        return response()->json(['error' => 'Debe enviar la imagen en file, logo o '.$field.'.'], 422);
    }

    $extension = $file->getClientOriginalExtension() ?: $file->extension();
    $path = $file->storeAs("resoluciones/aa/facultad_{$facultadId}", "{$field}.{$extension}", 'public');

    $configuracionActual = ResolucionConfiguracion::where('facultad_id', $facultadId)
        ->where('tipo', 'aa')
        ->first();

    $fields = array_merge($configuracionActual->fields ?? [], [$field => $path]);

    $configuracion = ResolucionConfiguracion::updateOrCreate(
        ['facultad_id' => $facultadId, 'tipo' => 'aa'],
        ['fields' => $fields, 'updated_by' => $request->header('X-User', 'desconocido')]
    );

    Carbon::setLocale('es');
    $fecha = Carbon::now();

    return response()->json([
        'facultad_id' => $facultadId,
        'tipo' => 'aa',
        'field' => $field,
        'path' => $path,
        'url' => asset('storage/'.$path),
        'fields' => $this->camposResolucionAaFrontend($request, $fecha, $configuracion->fields ?? []),
        'updated_by' => $configuracion->updated_by,
        'updated_at' => $configuracion->updated_at,
    ]);
}


public function aaPdf()
{
    $facultadId = $this->documentFacultyId();

    if (!$facultadId) {
        return response()->json([
            'error' => 'Debe enviar X-Facultad, facultad_id o id_facultad para generar la resolución.'
        ], 422);
    }

    $anioActual = date('Y');

$datos = AlumnoAyudante::with('estudiante')
    ->whereYear('fecha_inicio', $anioActual)
    ->get();
    $designados = $datos->where('tipo', 'designado');
$ratificados = $datos->where('tipo', 'ratificado');
$desnombrados = $datos->where('tipo', 'desnombrado');

 $decano = Decano::with('profesor')
    ->where('id_facultad', $facultadId)
    ->first();

$nombreDecano = $decano && $decano->profesor
    ? $decano->profesor->nombre . ' ' . $decano->profesor->apellidos
    : '';
 $fecha = Carbon::now();
 $dia = $fecha->day;
    $mes = $fecha->translatedFormat('F');
    $ano = $fecha->year;
    $revolucion = $ano - 1958;
    $camposEditables = $this->camposEditablesResolucionAa(request(), $fecha, $this->camposGuardadosResolucionAa($facultadId));
    $logoIzq = $this->logoResolucionAa($camposEditables, 'logoIzq', false);
    $logoDer = $this->logoResolucionAa($camposEditables, 'logoDer', false);
    $nombreFacultad = $this->documentFacultyName($facultadId);
    $nombreFacultadMayus = $this->documentFacultyNameUpper($facultadId);
$designados = $this->mapAAResolucionPorDepartamento($designados, $facultadId);
$ratificados = $this->mapAAResolucionPorDepartamento($ratificados, $facultadId);
$desnombrados = $this->mapAAResolucionPorDepartamento($desnombrados, $facultadId);

    // 🔥 AQUÍ EL FIX
   $anio = date('Y');

$pdf = Pdf::loadView('aa_pdf', compact(
    'designados',
    'ratificados',
    'desnombrados',
    'anio',
    'dia',
        'mes',
       'ano',
        'revolucion',
    'nombreDecano',
    'nombreFacultad',
    'nombreFacultadMayus',
    'camposEditables',
    'logoIzq',
    'logoDer'
));

    // 🔥 fecha completa
$fechaTexto = $fecha->format('d-m-Y_H-i-s');

// 🔥 nombre correcto
$nombreArchivo = $this->documentFileName('Resolucion_AA', 'pdf');

// 🔥 asegurar carpeta
$directorio = storage_path('app/public/documentos');
if (!file_exists($directorio)) {
    mkdir($directorio, 0777, true);
}

// 🔥 rutas
$ruta = "documentos/{$nombreArchivo}";
$rutaCompleta = storage_path("app/public/{$ruta}");

// 🔥 guardar archivo
file_put_contents($rutaCompleta, $pdf->output());

// 🔥 guardar en BD
\App\Models\Documento::create([
    'nombre' => "Resolución AA {$fechaTexto}",
    'tipo' => 'aa',
    'tipo_documento' => 'resolucion',
    'periodo' => $ano, // 👈 usa tu variable ya calculada
    'ruta' => $ruta,
    'facultad_id' => $this->documentFacultyId(),
]);
$this->logDocumentGenerated('Resolución AA', $fechaTexto);

// 🔥 descargar
return response()->download($rutaCompleta, $nombreArchivo);
}

public function aaHtml(Request $request)
{
    $facultadId = $this->documentFacultyId();

    if (!$facultadId) {
        return response()->json([
            'error' => 'Debe enviar X-Facultad, facultad_id o id_facultad para previsualizar la resolución.'
        ], 422);
    }

    Carbon::setLocale('es');
    $fecha = Carbon::now();
    $dia = $fecha->day;
    $mes = $fecha->translatedFormat('F');
    $ano = $fecha->year;
    $anio = $ano;
    $revolucion = $ano - 1958;
    $camposEditables = $this->camposEditablesResolucionAa($request, $fecha, $this->camposGuardadosResolucionAa($facultadId));
    $logoIzq = $this->logoResolucionAa($camposEditables, 'logoIzq', true);
    $logoDer = $this->logoResolucionAa($camposEditables, 'logoDer', true);
    $nombreFacultad = $this->documentFacultyName($facultadId);
    $nombreFacultadMayus = $this->documentFacultyNameUpper($facultadId);

    $decano = Decano::with('profesor')
        ->where('id_facultad', $facultadId)
        ->first();

    $nombreDecano = $decano && $decano->profesor
        ? $decano->profesor->nombre . ' ' . $decano->profesor->apellidos
        : '';

    $datos = AlumnoAyudante::with('estudiante')
        ->whereYear('fecha_inicio', $ano)
        ->get();

    $designados = $this->mapAAResolucionPorDepartamento($datos->where('tipo', 'designado'), $facultadId);
    $ratificados = $this->mapAAResolucionPorDepartamento($datos->where('tipo', 'ratificado'), $facultadId);
    $desnombrados = $this->mapAAResolucionPorDepartamento($datos->where('tipo', 'desnombrado'), $facultadId);
    $editableResolucion = true;
    $previewHtml = true;

    return response()
        ->view('aa_pdf', compact(
            'designados',
            'ratificados',
            'desnombrados',
            'anio',
            'dia',
            'mes',
            'ano',
            'revolucion',
            'nombreDecano',
            'nombreFacultad',
            'nombreFacultadMayus',
            'camposEditables',
            'logoIzq',
            'logoDer',
            'editableResolucion',
            'previewHtml'
        ))
        ->header('Content-Type', 'text/html; charset=UTF-8');
}


public function aaWord(Request $request)
{
    $facultadId = $this->documentFacultyId();

    if (!$facultadId) {
        return response()->json([
            'error' => 'Debe enviar X-Facultad, facultad_id o id_facultad para generar la resolución.'
        ], 422);
    }

    // =====================================
    // 🔥 FECHA (igual que PDF)
    // =====================================
    Carbon::setLocale('es');
    $fecha = Carbon::now();

    $dia = $fecha->day;
    $mes = $fecha->translatedFormat('F');
    $anio = $fecha->year;
    $revolucion = $anio - 1958;
    $camposEditables = $this->camposEditablesResolucionAa($request, $fecha, $this->camposGuardadosResolucionAa($facultadId));
    $logoIzq = $this->logoResolucionAa($camposEditables, 'logoIzq', false);
    $logoDer = $this->logoResolucionAa($camposEditables, 'logoDer', false);
    $nombreFacultad = $this->documentFacultyName($facultadId);
    $nombreFacultadMayus = $this->documentFacultyNameUpper($facultadId);

    // =====================================
    // 🔥 DECANO (igual que PDF)
    // =====================================
    $decano = Decano::with('profesor')
        ->where('id_facultad', $facultadId)
        ->first();

    $nombreDecano = $decano && $decano->profesor
        ? $decano->profesor->nombre . ' ' . $decano->profesor->apellidos
        : '';

    // =====================================
    // 🔥 DATOS (SIN FILTRAR POR habilitado)
    // =====================================
    $anioActual = date('Y');

$datos = AlumnoAyudante::with('estudiante')
    ->whereYear('fecha_inicio', $anioActual)
    ->get();

    $designados = $datos->where('tipo', 'designado');
    $ratificados = $datos->where('tipo', 'ratificado');
    $desnombrados = $datos->where('tipo', 'desnombrado');

    $designados = $this->mapAAResolucionPorDepartamento($designados, $facultadId);
    $ratificados = $this->mapAAResolucionPorDepartamento($ratificados, $facultadId);
    $desnombrados = $this->mapAAResolucionPorDepartamento($desnombrados, $facultadId);

    // =====================================
    // 🔥 RENDERIZAR BLADE (IGUAL QUE PDF)
    // =====================================
    $html = view('aa_word', compact(
        'anio',
        'dia',
        'mes',
        'revolucion',
        'nombreDecano',
        'nombreFacultad',
        'nombreFacultadMayus',
        'camposEditables'
    ))->render();

    // =====================================
    // 🔥 CREAR WORD
    // =====================================
    $phpWord = new PhpWord();
    $section = $phpWord->addSection([
        'marginLeft' => 720,
        'marginRight' => 720,
    ]);
// ============================
    // ✅ HEADER BIEN HECHO (CLAVE)
    // ============================

    $table = $section->addTable();

    $table->addRow();

    // Logo izquierdo
    $table->addCell(2000)->addImage(
        $logoIzq,
        ['width' => 60]
    );

    // Texto central
    $cellText = $table->addCell(8000, ['valign' => 'center']);

$textrun = $cellText->addTextRun([
    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
    'spaceBefore' => 300 // 👈 ESTE es el que baja TODO el bloque
]);
    $cellText->addText(
    'UNIVERSIDAD CENTRAL “MARTA ABREU” DE LAS VILLAS',
    ['bold' => false, 'name' => 'Arial', 'size' => 10], // 👈 sin negrita + más pequeño
    ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
);

    $cellText->addText(
        $nombreFacultadMayus,
        ['bold' => true, 'name' => 'Arial', 'size' => 10],
        ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
    );

    // Logo derecho
    $table->addCell(2000)->addImage(
        $logoDer,
        ['width' => 60]
    );

    $addAAResolucionTables = function ($grupos) use ($section) {
        foreach ($grupos as $grupo) {
            $section->addText(
                'Departamento Docente: '.$grupo['departamento'],
                ['bold' => true, 'name' => 'Arial', 'size' => 10]
            );

            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 50
            ]);

            $table->addRow();
            $table->addCell(500)->addText('N°', ['bold' => true, 'name' => 'Arial', 'size' => 9]);
            $table->addCell(1600)->addText('C. DE IDENTIDAD', ['bold' => true, 'name' => 'Arial', 'size' => 9]);
            $table->addCell(2700)->addText('NOMBRES Y APELLIDOS', ['bold' => true, 'name' => 'Arial', 'size' => 9]);
            $table->addCell(800)->addText('AÑO', ['bold' => true, 'name' => 'Arial', 'size' => 9]);
            $table->addCell(2700)->addText('TUTOR', ['bold' => true, 'name' => 'Arial', 'size' => 9]);
            $table->addCell(900)->addText('ETAPA', ['bold' => true, 'name' => 'Arial', 'size' => 9]);

            foreach ($grupo['items'] as $fila) {
                $table->addRow();
                $table->addCell(500)->addText($fila['no'], ['name' => 'Arial', 'size' => 9]);
                $table->addCell(1600)->addText($fila['carnet'], ['name' => 'Arial', 'size' => 9]);
                $table->addCell(2700)->addText($fila['nombre'], ['name' => 'Arial', 'size' => 9]);
                $table->addCell(800)->addText($fila['anio'], ['name' => 'Arial', 'size' => 9]);
                $table->addCell(2700)->addText($fila['tutor'], ['name' => 'Arial', 'size' => 9]);
                $table->addCell(900)->addText($fila['etapa'], ['name' => 'Arial', 'size' => 9]);
            }

            $section->addTextBreak();
        }
    };

    $partes1 = explode('__TABLA_DESIGNADOS__', $html);

    Html::addHtml($section, $partes1[0], false, false);

    if (isset($partes1[1])) {
        $addAAResolucionTables($designados);

        $partes2 = explode('__TABLA_DESNOMBRADOS__', $partes1[1]);

        Html::addHtml($section, $partes2[0], false, false);

        if (isset($partes2[1])) {
            $addAAResolucionTables($desnombrados);
            Html::addHtml($section, $partes2[1], false, false);
        }
    }

    // =====================================
    // 🔥 DESCARGA
    // =====================================
    // 🔥 fecha
$fecha = now();
$fechaTexto = $fecha->format('d-m-Y_H-i-s');

// 🔥 nombre correcto
$nombreArchivo = $this->documentFileName('Resolucion_AA', 'docx');

// 🔥 asegurar carpeta
$directorio = storage_path('app/public/documentos');
if (!file_exists($directorio)) {
    mkdir($directorio, 0777, true);
}

// 🔥 rutas
$ruta = "documentos/{$nombreArchivo}";
$rutaCompleta = storage_path("app/public/{$ruta}");

// 🔥 guardar archivo REAL
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($rutaCompleta);

// 🔥 guardar en BD
\App\Models\Documento::create([
    'nombre' => "Resolución AA {$fechaTexto}",
    'tipo' => 'aa',
    'tipo_documento' => 'resolucion',
    'periodo' => $fecha->year,
    'ruta' => $ruta,
    'facultad_id' => $this->documentFacultyId(),
]);
$this->logDocumentGenerated('Resolución AA', $fechaTexto);

// 🔥 descargar
return response()->download($rutaCompleta, $nombreArchivo);
}
public function exportPDF()
{
    $data = $this->getAAData();

    $pdf = Pdf::loadView('exports.aa', ['data' => $data]);

   // 🔥 fecha completa
$fecha = now();
$fechaTexto = $fecha->format('d-m-Y_H-i-s');

// 🔥 nombre dinámico
$nombreArchivo = $this->documentFileName('Listado_AA', 'pdf');

// 🔥 asegurar carpeta
$directorio = storage_path('app/public/documentos');
if (!file_exists($directorio)) {
    mkdir($directorio, 0777, true);
}

// 🔥 rutas
$ruta = "documentos/{$nombreArchivo}";
$rutaCompleta = storage_path("app/public/{$ruta}");

// 🔥 guardar archivo
file_put_contents($rutaCompleta, $pdf->output());

// 🔥 guardar en BD
\App\Models\Documento::create([
    'nombre' => "Listado AA {$fechaTexto}",
    'tipo' => 'aa',
    'tipo_documento' => 'listado',
    'periodo' => $fecha->year,
    'ruta' => $ruta,
    'facultad_id' => $this->documentFacultyId(),
]);
$this->logDocumentGenerated('Listado AA', $fechaTexto);

// 🔥 descargar
return response()->download($rutaCompleta, $nombreArchivo);
}
public function exportWord()
{
    $data = $this->getAAData();

    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection([
        'marginLeft' => 720,
        'marginRight' => 720,
    ]);

    // 🔹 Título
    $section->addText(
        'Listado de Alumnos Ayudantes',
        ['name' => 'Arial', 'size' => 14, 'bold' => true]
    );

    // 🔹 Tabla
    $table = $section->addTable([
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 50
    ]);

    // 🔹 HEADER
    $table->addRow();

    $table->addCell(1700)->addText('Carnet', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
    $table->addCell(3100)->addText('Nombre', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
    $table->addCell(1200)->addText('Año Académico', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
    $table->addCell(2600)->addText('Tutor', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
    $table->addCell(800)->addText('Etapa', ['bold' => true, 'name' => 'Arial', 'size' => 10]);

    // 🔹 DATA
    foreach ($data as $item) {
        $table->addRow();

        $table->addCell(1700)->addText($item['carnet'], ['name' => 'Arial', 'size' => 10]);

        $table->addCell(3100)->addText(
            $item['nombre'],
            ['name' => 'Arial', 'size' => 10]
        );

        $table->addCell(1200)->addText($item['anio'], ['name' => 'Arial', 'size' => 10]);

        $table->addCell(2600)->addText($item['tutor'], ['name' => 'Arial', 'size' => 10]);

        $table->addCell(800)->addText($item['etapa'], ['name' => 'Arial', 'size' => 10]);
    }

   // 🔥 fecha completa
$fecha = now();
$fechaTexto = $fecha->format('d-m-Y_H-i-s');

// 🔥 nombre dinámico
$nombreArchivo = $this->documentFileName('Listado_AA', 'docx');

// 🔥 asegurar carpeta
$directorio = storage_path('app/public/documentos');
if (!file_exists($directorio)) {
    mkdir($directorio, 0777, true);
}

// 🔥 rutas
$ruta = "documentos/{$nombreArchivo}";
$rutaCompleta = storage_path("app/public/{$ruta}");

// 🔥 guardar archivo
\PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($rutaCompleta);

// 🔥 guardar en BD
\App\Models\Documento::create([
    'nombre' => "Listado AA {$fechaTexto}",
    'tipo' => 'aa',
    'tipo_documento' => 'listado',
    'periodo' => $fecha->year,
    'ruta' => $ruta,
    'facultad_id' => $this->documentFacultyId(),
]);
$this->logDocumentGenerated('Listado AA', $fechaTexto);

// 🔥 descargar
return response()->download($rutaCompleta, $nombreArchivo);
}
private function getAAData()
{
    $facultadId = $this->documentFacultyId();
    $departamentoId = $this->documentDepartmentId();

    $aa = \App\Models\AlumnoAyudante::with('estudiante')
        ->where('habilitado', true)
        ->get();

    return $aa->map(function ($item) use ($facultadId, $departamentoId) {
        $ubicacion = $this->ubicacionAcademicaEstudianteExport($item->id_estudiante, $facultadId, $departamentoId);

        if (($facultadId || $departamentoId) && !$ubicacion) {
            return null;
        }

        return [
            'carnet' => $item->estudiante->numero_carnet,
            'nombre' => $item->estudiante->nombre . ' ' . $item->estudiante->apellidos,
            'anio' => $ubicacion->anio ?? 'N/A',
            'tutor' => $item->nombre_tutor,
            'etapa' => $this->formatEtapaDocumento($item->etapa)
        ];
    })->filter()->values();
}
private function formatEtapaDocumento($etapa): string
{
    $valor = trim((string) $etapa);
    $normalizado = mb_strtolower($valor);

    $mapa = [
        '1' => '|',
        'etapa 1' => '|',
        '2' => '||',
        'etapa 2' => '||',
        '3' => '|||',
        'etapa 3' => '|||',
    ];

    return $mapa[$normalizado] ?? $valor;
}
private function mapAAResolucionPorDepartamento($coleccion, int $facultadId)
{
    return $coleccion->values()
        ->map(function ($aa) use ($facultadId) {
            $ubicacion = $this->ubicacionAcademicaEstudiante($aa->id_estudiante, $facultadId);

            if (!$ubicacion || !$aa->estudiante) {
                return null;
            }

            return [
                'departamento_id' => $ubicacion->departamento_id,
                'departamento' => $ubicacion->departamento,
                'carnet' => $aa->estudiante->numero_carnet,
                'nombre' => $aa->estudiante->nombre . ' ' . $aa->estudiante->apellidos,
                'anio' => $ubicacion->anio,
                'tutor' => $aa->nombre_tutor,
                'etapa' => $this->formatEtapaDocumento($aa->etapa),
            ];
        })
        ->filter()
        ->groupBy('departamento_id')
        ->map(function ($items) {
            $items = $items->values();

            return [
                'departamento' => $items->first()['departamento'],
                'items' => $items->map(function ($item, $index) {
                    unset($item['departamento_id'], $item['departamento']);
                    $item['no'] = $index + 1;

                    return $item;
                })->values(),
            ];
        })
        ->values();
}

private function ubicacionAcademicaEstudiante(int $estudianteId, int $facultadId)
{
    return DB::table('estudiante_grupo as eg')
        ->join('ano_grupo as ag', 'eg.grupo_id', '=', 'ag.grupo_id')
        ->join('a_academico as aa', 'ag.ano_academico_id', '=', 'aa.id')
        ->join('departamento_prog_d_form as dpf', 'aa.id_prog_form', '=', 'dpf.id_prog_form')
        ->join('departamento as d', 'dpf.id_departamento', '=', 'd.id')
        ->join('facultad_departamento as fd', 'd.id', '=', 'fd.id_departamento')
        ->where('eg.estudiante_id', $estudianteId)
        ->where('fd.id_facultad', $facultadId)
        ->orderByDesc('eg.fecha')
        ->select(
            'd.id as departamento_id',
            'd.nombre as departamento',
            'aa.identificador as anio'
        )
        ->first();
}

private function ubicacionAcademicaEstudianteExport(int $estudianteId, ?int $facultadId, ?int $departamentoId)
{
    $query = DB::table('estudiante_grupo as eg')
        ->join('ano_grupo as ag', 'eg.grupo_id', '=', 'ag.grupo_id')
        ->join('a_academico as aa', 'ag.ano_academico_id', '=', 'aa.id')
        ->join('departamento_prog_d_form as dpf', 'aa.id_prog_form', '=', 'dpf.id_prog_form')
        ->join('departamento as d', 'dpf.id_departamento', '=', 'd.id')
        ->join('facultad_departamento as fd', 'd.id', '=', 'fd.id_departamento')
        ->where('eg.estudiante_id', $estudianteId);

    if ($departamentoId) {
        $query->where('d.id', $departamentoId);
    } elseif ($facultadId) {
        $query->where('fd.id_facultad', $facultadId);
    }

    return $query
        ->orderByDesc('eg.fecha')
        ->select(
            'd.id as departamento_id',
            'd.nombre as departamento',
            'fd.id_facultad',
            'aa.identificador as anio'
        )
        ->first();
}
public function historialAA(Request $request)
{
    $desde = $request->desde;
    $hasta = $request->hasta;

    // 🔥 traer AA en rango de años
    $aa = \App\Models\AlumnoAyudante::with([
            'estudiante',
            'curso',
        ])
        ->whereNotNull('fecha_inicio')
        ->whereYear('fecha_inicio', '>=', $desde)
        ->whereYear('fecha_inicio', '<=', $hasta)
        ->whereIn('tipo', ['designado', 'ratificado', 'desnombrado'])
        ->get()
        ->unique(function ($item) {
            // 🔥 evita repetir mismo estudiante en el mismo año
            return $item->id_estudiante . '-' . ($item->id_curso ?? date('Y', strtotime($item->fecha_inicio)));
        });

    // 🔥 departamentos (igual que hiciste en PPA)
    $miembros = \App\Models\MiembroDepartamento::whereIn(
        'id_profesor',
        $aa->pluck('id_tutor') // 👈 tutor es profesor
    )->get()->keyBy('id_profesor');

    $departamentos = \App\Models\Departamento::whereIn(
        'id',
        $miembros->pluck('id_departamento')
    )->get()->keyBy('id');

    // 🔥 MAP FINAL
    $data = $aa->map(function ($item) use ($miembros, $departamentos) {

        $est = $item->estudiante;
        if (!$est) return null;

        // 🔥 departamento del tutor
        $miembro = $miembros[$item->id_tutor] ?? null;
        $departamento = $miembro
            ? ($departamentos[$miembro->id_departamento]->nombre ?? '')
            : '';

        return [
            'carnet' => $est->numero_carnet ?? '',
            'nombre' => trim(($est->nombre ?? '') . ' ' . ($est->apellidos ?? '')),
            'accion' => $item->tipo ?? '',
            'curso' => optional($item->curso)->curso ?? $this->courseNameForActionDate($item->fecha_inicio) ?? '',
            'tutor' => $item->nombre_tutor ?? '',
            'departamento' => $departamento,
            'anio' => date('Y', strtotime($item->fecha_inicio)) // 🔥 AÑO REAL
        ];
    })->filter();

    // 🔥 validación
    if ($data->isEmpty()) {
        return response()->json(['error' => 'No hay datos'], 400);
    }

    // 🔥 PDF
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
        'exports.historial_aa',
        [
            'data' => $data,
            'desde' => $desde,
            'hasta' => $hasta
        ]
    );

    $nombreArchivo = $this->documentFileName("Historial_AA_{$desde}_{$hasta}", 'pdf');
    $ruta = "documentos/{$nombreArchivo}";

    // 🔥 guardar
    \Illuminate\Support\Facades\Storage::disk('public')->put($ruta, $pdf->output());

    // 🔥 guardar en BD
    \App\Models\Documento::create([
        'nombre' => "Historial AA {$desde}-{$hasta}",
        'tipo' => 'aa',
        'tipo_documento' => 'historial',
        'periodo' => $hasta,
        'ruta' => $ruta,
        'facultad_id' => $this->documentFacultyId(),
    ]);
    $this->logDocumentGenerated('Historial AA', "{$desde}-{$hasta}");

    return $pdf->download($nombreArchivo);
}
}
