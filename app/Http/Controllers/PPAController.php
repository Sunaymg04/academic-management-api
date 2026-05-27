<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\PPA;
use App\Models\PpaHistorial;
use App\Models\AgnoAcademico_Curso;
use App\Models\AnoAcademico;
use App\Models\Profesor;
use App\Models\Curso;
use App\Models\CatDocente;
use App\Models\CatCientifica;
use App\Models\ProgFormacion;
use App\Http\Controllers\LogController;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpWord\Shared\Html;
use Carbon\Carbon;
use App\Models\Decano;
use App\Models\Documento;
use App\Models\ResolucionConfiguracion;
use Illuminate\Support\Facades\Storage;
use app\Models\Departamento;
use app\Models\MiembroDepartamento;
class PPAController extends Controller
{
    // 🟢 DESIGNAR
  public function designar(Request $request)
{
    $fechaAccion = now();
    $cursoAccion = $this->courseForActionDate($fechaAccion);

    if (!$cursoAccion) {
        return $this->courseErrorForActionDate($fechaAccion);
    }

    $request->validate([
        'id_profesor' => 'required|exists:profesor,id',
        'id_a_academico' => 'required|exists:a_academico,id',
        'id_curso' => 'nullable|integer|exists:curso,id',
    ]);

    if ($request->filled('id_curso') && (int) $request->id_curso !== (int) $cursoAccion->id) {
        return response()->json([
            'error' => 'El PPA debe designarse en el curso académico presente.',
            'id_curso_presente' => $cursoAccion->id,
            'curso_presente' => $cursoAccion->curso,
        ], 422);
    }

     $profesor = Profesor::find($request->id_profesor);
    // 🟡 VALIDAR PROFESOR
    if (!Profesor::where('id', $request->id_profesor)->exists()) {
        return response()->json([
            'error' => 'El profesor no existe'
        ], 400);
    }

    // 🔍 OBTENER CATEGORÍAS
$catDocente = CatDocente::find($profesor->idCatDocente);
$catCientifica = CatCientifica::find($profesor->idCatCientifica);
if (!$catDocente || !$catCientifica) {
    return response()->json([
        'error' => 'El profesor no tiene categorías definidas'
    ], 400);
}

// 🔒 VALIDAR CATEGORÍA CIENTÍFICA
$validaCientifica = in_array($catCientifica->nombre, [
    'Investigador Auxiliar',
    'Investigador Titular'
]);

// 🔒 VALIDAR CATEGORÍA DOCENTE
$validaDocente = in_array($catDocente->nombre, [
    'Instructor',
    'Auxiliar',
    'Titular'
]);

if (!$validaCientifica || !$validaDocente) {
    return response()->json([
        'error' => 'El profesor no cumple con los requisitos para ser PPA'
    ], 400);
}
   $ano = AnoAcademico::find($request->id_a_academico);

if (!$ano) {
    return response()->json([
        'error' => 'Ano académico inválido'
    ], 400);
}
$carrera = ProgFormacion::find($ano->id_prog_form);

if (!$carrera) {
    return response()->json([
        'error' => 'Programa de formación no válido'
    ], 400);
}


    $ppaExistente = PPA::where('id_curso', $cursoAccion->id)
        ->where('id_a_academico', $request->id_a_academico)
        ->first();

    if ($ppaExistente && (int) $ppaExistente->id_profesor !== (int) $request->id_profesor) {
        return response()->json([
            'error' => 'Ya existe otro PPA asignado para ese curso en ese ano académico'
        ], 400);
    }
    // 🟡 VALIDAR ANO ACADÉMICO
    if (!AnoAcademico::where('id', $request->id_a_academico)->exists()) {
        return response()->json([
            'error' => 'El ano académico no existe'
        ], 400);
    }

    // 🟢 VALIDACIÓN PRINCIPAL (curso pertenece al ano)
    $valido = AgnoAcademico_Curso::where('id_curso', $cursoAccion->id)
        ->where('id_a_academico', $request->id_a_academico)
        ->exists();

    if (!$valido) {
        return response()->json([
            'error' => 'El curso no corresponde a ese ano académico'
        ], 400);
    }

    $ppa = $ppaExistente ?: PPA::create([
        'id_profesor' => $request->id_profesor,
        'id_a_academico' => $request->id_a_academico,
        'id_curso' => $cursoAccion->id
    ]);

    $this->guardarAccionPpa(
        $request->id_profesor,
        $request->id_a_academico,
        $cursoAccion->id,
        'designado',
        $fechaAccion
    );


$profesor = Profesor::find($request->id_profesor);
$carrera = ProgFormacion::find($ano->id_prog_form);
if (!$carrera) {
    return response()->json(['error' => 'Carrera null'], 500);
}

if (!$profesor) {
    return response()->json(['error' => 'Profesor null'], 500);
}
$profesor = Profesor::find($request->id_profesor);
$carrera = ProgFormacion::find($ano->id_prog_form);

if (!$profesor || !$carrera) {
    return response()->json([
        'error' => 'Datos insuficientes para registrar log'
    ], 400);
}
$descripcion = "Se designó a {$profesor->nombre} {$profesor->apellidos} como PPA en la carrera {$carrera->nombre}, {$ano->identificador}";

$usuario = $request->header('X-User') ?? 'desconocido';

LogController::registrar(
    $usuario,
    'designar_ppa',
    $descripcion
);

return response()->json($ppa->fresh('curso'));
}

    // 🔵 RATIFICAR
    public function ratificar(Request $request)
{
    $fechaAccion = now();
    $cursoAccion = $this->courseForActionDate($fechaAccion);

    if (!$cursoAccion) {
        return $this->courseErrorForActionDate($fechaAccion);
    }

    if (!$this->puedeAccionarPpa($request->id_profesor, $request->id_a_academico, $cursoAccion->id)) {
        return response()->json([
            'error' => 'Solo se puede ratificar un PPA designado en cursos anteriores o con acción previa en este curso.'
        ], 400);
    }

    $ppaExistente = PPA::where('id_curso', $cursoAccion->id)
        ->where('id_a_academico', $request->id_a_academico)
        ->first();

    if ($ppaExistente && (int) $ppaExistente->id_profesor !== (int) $request->id_profesor) {
        return response()->json([
            'error' => 'Ya existe otro PPA asignado para ese curso en ese ano académico'
        ], 400);
    }

    if (!$ppaExistente) {
        PPA::create([
            'id_profesor' => $request->id_profesor,
            'id_a_academico' => $request->id_a_academico,
            'id_curso' => $cursoAccion->id
        ]);
    }

    $this->guardarAccionPpa(
        $request->id_profesor,
        $request->id_a_academico,
        $cursoAccion->id,
        'ratificado',
        $fechaAccion
    );

    // 🔥 MOVER TODO ESTO ARRIBA
    $ano = AnoAcademico::find($request->id_a_academico);
    $profesor = Profesor::find($request->id_profesor);
    $carrera = ProgFormacion::find($ano->id_prog_form);

    $descripcion = "Se ratificó a {$profesor->nombre} {$profesor->apellidos} como PPA en la carrera {$carrera->nombre}, {$ano->identificador}";

    $usuario = $request->header('X-User') ?? 'desconocido';

    LogController::registrar(
        $usuario,
        'ratificar_ppa',
        $descripcion
    );

    // ✅ AHORA SÍ RETURN AL FINAL
    return response()->json(['message' => 'Ratificado']);
}

    // 🔴 DESNOMBRAR
  public function desnombrar(Request $request)
{
    $fechaAccion = now();
    $cursoAccion = $this->courseForActionDate($fechaAccion);

    if (!$cursoAccion) {
        return $this->courseErrorForActionDate($fechaAccion);
    }

    if (!$this->puedeAccionarPpa($request->id_profesor, $request->id_a_academico, $cursoAccion->id)) {
        return response()->json([
            'error' => 'Solo se puede desnombrar un PPA designado o ratificado en cursos anteriores o con acción previa en este curso.'
        ], 400);
    }

    $ppa = PPA::where('id_profesor', $request->id_profesor)
        ->where('id_curso', $cursoAccion->id)
        ->where('id_a_academico', $request->id_a_academico)
        ->first();

    if ($ppa) {
        $ppa->delete();
    }

    $this->guardarAccionPpa(
        $request->id_profesor,
        $request->id_a_academico,
        $cursoAccion->id,
        'desnombrado',
        $fechaAccion
    );

    // 🔥 LOG ANTES DEL RETURN
    $ano = AnoAcademico::find($request->id_a_academico);
    $profesor = Profesor::find($request->id_profesor);
    $carrera = ProgFormacion::find($ano->id_prog_form);

    $descripcion = "Se eliminó a {$profesor->nombre} {$profesor->apellidos} como PPA en la carrera {$carrera->nombre}, {$ano->identificador}";

    $usuario = $request->header('X-User') ?? 'desconocido';

    LogController::registrar(
        $usuario,
        'desnombrar_ppa',
        $descripcion
    );

    return response()->json([
        'message' => 'PPA eliminado correctamente'
    ]);
}

private function guardarAccionPpa(int $profesorId, int $anoAcademicoId, int $cursoId, string $accion, $fechaAccion): PpaHistorial
{
    $query = PpaHistorial::where('id_profesor', $profesorId)
        ->where('id_a_academico', $anoAcademicoId)
        ->where('id_curso', $cursoId);

    $historial = $query->orderBy('id')->first();

    if ($historial) {
        $query->where('id', '<>', $historial->id)->delete();
        $historial->update([
            'accion' => $accion,
            'fecha_accion' => $fechaAccion,
        ]);

        return $historial;
    }

    return PpaHistorial::create([
        'id_profesor' => $profesorId,
        'id_a_academico' => $anoAcademicoId,
        'id_curso' => $cursoId,
        'accion' => $accion,
        'fecha_accion' => $fechaAccion,
    ]);
}

private function puedeAccionarPpa(int $profesorId, int $anoAcademicoId, int $cursoId): bool
{
    $mismaAccionCurso = PpaHistorial::where('id_profesor', $profesorId)
        ->where('id_a_academico', $anoAcademicoId)
        ->where('id_curso', $cursoId)
        ->exists();

    if ($mismaAccionCurso) {
        return true;
    }

    return PpaHistorial::where('id_profesor', $profesorId)
        ->where('id_a_academico', $anoAcademicoId)
        ->where('id_curso', '<', $cursoId)
        ->whereIn('accion', ['designado', 'ratificado'])
        ->exists();
}

public function index()
{
    $ppa = PPA::with([
        'profesor.catDocente',
        'profesor.catCientifica',
        'curso'
    ])->get();

    return response()->json(
        $ppa->map(function ($item) {

            // 🔥 OBTENER ANO
            $anio = \App\Models\AnoAcademico::find($item->id_a_academico);

            // 🔥 OBTENER CARRERA
            $carrera = $anio
                ? \App\Models\ProgFormacion::find($anio->id_prog_form)
                : null;

            // 🔥 OBTENER DEPARTAMENTO

$departamento = DB::table('departamento_prog_d_form')
    ->join('departamento', 'departamento_prog_d_form.id_departamento', '=', 'departamento.id')
    ->where('departamento_prog_d_form.id_prog_form', $carrera->id)
    ->select('departamento.nombre')
    ->first();

            return [
                'id' => $item->profesor->id,
                'nombre' => $item->profesor->nombre,
                'apellidos' => $item->profesor->apellidos,
                'catDocente' => $item->profesor->catDocente->nombre ?? 'No definida',
                'catCientifica' => $item->profesor->catCientifica->nombre ?? 'No definida',

                // 🔥 ESTO YA LO TENÍAS (NO SE TOCA)
                'id_curso' => $item->id_curso,
                'curso' => $item->curso->curso ?? null,
                'id_a_academico' => $item->id_a_academico,

                // ✅ NUEVO (LO QUE QUIERES MOSTRAR)
                'departamento' => $departamento->nombre ?? null,
                'carrera' => $carrera->nombre ?? '',
                'anio' => $anio->identificador ?? ''
            ];
        })
    );
}

public function historialPorCurso(Request $request)
{
    $facultadId = $this->documentFacultyId();
    $departamentoId = $this->documentDepartmentId();
    $cursoId = $request->query('id_curso') ?? $request->query('curso_id');

    $query = DB::table('ppa_historial as ph')
        ->join('profesor as p', 'ph.id_profesor', '=', 'p.id')
        ->leftJoin('categoria_docente as cd', 'p.idCatDocente', '=', 'cd.id')
        ->leftJoin('categoria_cientifica as cc', 'p.idCatCientifica', '=', 'cc.id')
        ->leftJoin('curso as c', 'ph.id_curso', '=', 'c.id')
        ->leftJoin('a_academico as a', 'ph.id_a_academico', '=', 'a.id')
        ->leftJoin('programa_de_formacion as pf', 'a.id_prog_form', '=', 'pf.id')
        ->leftJoin('departamento_prog_d_form as dpf', 'pf.id', '=', 'dpf.id_prog_form')
        ->leftJoin('departamento as d', 'dpf.id_departamento', '=', 'd.id')
        ->leftJoin('facultad_departamento as fd', 'd.id', '=', 'fd.id_departamento')
        ->leftJoin('ppa as ppa_actual', function ($join) {
            $join->on('ppa_actual.id_profesor', '=', 'ph.id_profesor')
                ->on('ppa_actual.id_a_academico', '=', 'ph.id_a_academico')
                ->on('ppa_actual.id_curso', '=', 'ph.id_curso');
        });

    if ($cursoId) {
        $query->where('ph.id_curso', $cursoId);
    }

    if ($departamentoId) {
        $query->where('d.id', $departamentoId);
    } elseif ($facultadId) {
        $query->where('fd.id_facultad', $facultadId);
    }

    return response()->json(
        $query
            ->orderByDesc('ph.id_curso')
            ->orderBy('pf.nombre')
            ->orderBy('a.identificador')
            ->orderBy('p.apellidos')
            ->select(
                'ph.id',
                'ph.id_profesor',
                'ph.id_curso',
                'c.curso',
                'ph.id_a_academico',
                'a.identificador as anio',
                'p.nombre',
                'p.apellidos',
                DB::raw("CONCAT(p.nombre, ' ', p.apellidos) as nombre_completo"),
                'cd.nombre as catDocente',
                'cc.nombre as catCientifica',
                'pf.id as carrera_id',
                'pf.nombre as carrera',
                'd.id as departamento_id',
                'd.nombre as departamento',
                'fd.id_facultad',
                'ph.accion',
                'ph.fecha_accion',
                DB::raw('ppa_actual.uuid is not null as habilitado')
            )
            ->distinct()
            ->get()
    );
}



public function exportPDF()
{
    $data = $this->getPPAData();

    $pdf = Pdf::loadView('exports.ppa', ['data' => $data]);

    // 🔥 nombre bonito
    $fecha = now()->format('Y-m-d_H-i-s_u');
    $nombreArchivo = $this->documentFileName('Listado_PPA', 'pdf');
    $ruta = "documentos/{$nombreArchivo}";

    // 🔥 🔥 🔥 FIX CLAVE
    Storage::disk('public')->put($ruta, $pdf->output());

    // 🔥 guardar en BD
    Documento::create([
        'nombre' => "Listado PPA {$fecha}",
        'tipo' => 'ppa',
        'tipo_documento' => 'listado',
        'periodo' => now()->year,
        'ruta' => $ruta,
        'facultad_id' => $this->documentFacultyId(),
    ]);
    $this->logDocumentGenerated('Listado PPA', $fecha);

    // 🔥 descarga
    return $pdf->download($nombreArchivo);
}
public function exportWord()
{
    $data = $this->getPPAData();

    $phpWord = new PhpWord();
    $section = $phpWord->addSection([
        'marginLeft' => 720,
        'marginRight' => 720,
    ]);

    // 🔹 Título
    $section->addText(
        'Listado de PPA',
        ['name' => 'Arial', 'size' => 14, 'bold' => true]
    );

    // 🔹 Tabla
    $table = $section->addTable([
        'borderSize' => 6,
        'borderColor' => '000000',
        'cellMargin' => 50
    ]);

    // HEADER
    $table->addRow();

    $table->addCell(2300)->addText('Profesor', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
    $table->addCell(1000)->addText('Cat. Docente', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
    $table->addCell(1100)->addText('Cat. Científica', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
    $table->addCell(1800)->addText('Departamento', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
    $table->addCell(2200)->addText('Carrera', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
    $table->addCell(800)->addText('Año', ['bold' => true, 'name' => 'Arial', 'size' => 10]);

    foreach ($data as $item) {
        $table->addRow();

        $table->addCell(2300)->addText(
            $item['nombre'].' '.$item['apellidos'],
            ['name' => 'Arial', 'size' => 10]
        );

        $table->addCell(1000)->addText($item['catDocente'], ['name' => 'Arial', 'size' => 10]);
        $table->addCell(1100)->addText($item['catCientifica'], ['name' => 'Arial', 'size' => 10]);
        $table->addCell(1800)->addText($item['departamento'], ['name' => 'Arial', 'size' => 10]);
        $table->addCell(2200)->addText($item['carrera'], ['name' => 'Arial', 'size' => 10]);
        $table->addCell(800)->addText($item['anio'], ['name' => 'Arial', 'size' => 10]);
    }

    // 🔥 nombre dinámico
    $fecha = now()->format('Y-m-d_H-i-s_u');
    $nombreArchivo = $this->documentFileName('Listado_PPA', 'docx');

    // 🔥 ruta física
  $ruta = "documentos/{$nombreArchivo}";
$file = storage_path("app/public/{$ruta}");

    $directorio = dirname($file);
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }

    // 🔥 guardar archivo
    IOFactory::createWriter($phpWord, 'Word2007')->save($file);

    // 🔥 🔥 🔥 AQUI ESTA LO QUE TE FALTABA
    \App\Models\Documento::create([
        'nombre' => "Listado PPA {$fecha}",
        'tipo' => 'ppa',
        'tipo_documento' => 'listado',
        'periodo' => now()->year,
        'ruta' => $ruta,
        'facultad_id' => $this->documentFacultyId(),
    ]);
    $this->logDocumentGenerated('Listado PPA', $fecha);

    // 🔥 descarga normal (NO TOCAR)
   return response()->download($file, $nombreArchivo);
}
private function getPPAData()
{
    $facultadId = $this->documentFacultyId();
    $departamentoId = $this->documentDepartmentId();

    $ppa = PPA::with([
        'profesor.catDocente',
        'profesor.catCientifica'
    ])->get();

    return $ppa->map(function ($item) use ($facultadId, $departamentoId) {

        $anio = \App\Models\AnoAcademico::find($item->id_a_academico);

        $carrera = $anio
            ? \App\Models\ProgFormacion::find($anio->id_prog_form)
            : null;

        $ubicacion = $this->ubicacionAcademicaPpa($item->id_a_academico, $facultadId, $departamentoId);

        if (($facultadId || $departamentoId) && !$ubicacion) {
            return null;
        }

        return [
            'nombre' => $item->profesor->nombre,
            'apellidos' => $item->profesor->apellidos,
            'catDocente' => $item->profesor->catDocente->nombre ?? '',
            'catCientifica' => $item->profesor->catCientifica->nombre ?? '',
            'departamento' => $ubicacion->departamento ?? '',
            'carrera' => $carrera->nombre ?? '',
            'anio' => $anio->identificador ?? ''
        ];
    })->filter()->values();
}

private function ubicacionAcademicaPpa($anoAcademicoId, ?int $facultadId, ?int $departamentoId)
{
    $query = DB::table('a_academico as aa')
        ->join('departamento_prog_d_form as dpf', 'aa.id_prog_form', '=', 'dpf.id_prog_form')
        ->join('departamento as d', 'dpf.id_departamento', '=', 'd.id')
        ->join('facultad_departamento as fd', 'd.id', '=', 'fd.id_departamento')
        ->where('aa.id', $anoAcademicoId);

    if ($departamentoId) {
        $query->where('d.id', $departamentoId);
    } elseif ($facultadId) {
        $query->where('fd.id_facultad', $facultadId);
    }

    return $query
        ->select(
            'd.id as departamento_id',
            'd.nombre as departamento',
            'fd.id_facultad'
        )
        ->first();
}

private function camposResolucionPpaPermitidos(): array
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

private function camposResolucionPpaFrontend(Request $request, Carbon $fecha, array $guardados = []): array
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

    $guardados = array_intersect_key($guardados, array_flip($this->camposResolucionPpaPermitidos()));
    $enviados = array_intersect_key($request->all(), array_flip($this->camposResolucionPpaPermitidos()));

    return array_merge($defaults, $guardados, $enviados);
}

private function camposGuardadosResolucionPpa(?int $facultadId): array
{
    if (!$facultadId) {
        return [];
    }

    $configuracion = ResolucionConfiguracion::where('facultad_id', $facultadId)
        ->where('tipo', 'ppa')
        ->first();

    return $configuracion->fields ?? [];
}

private function camposEditablesResolucionPpa(Request $request, Carbon $fecha, array $guardados = []): array
{
    $fields = $this->camposResolucionPpaFrontend($request, $fecha, $guardados);
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

private function logoResolucionPpa(array $camposEditables, string $field, bool $previewHtml): string
{
    $default = $field === 'logoIzq' ? 'images/logo_izq.png' : 'images/logo_der.png';
    $value = $camposEditables[$field] ?? null;

    if ($value) {
        if (str_starts_with($value, 'data:image/')) {
            return $value;
        }

        if (preg_match('/^https?:\/\//', $value)) {
            return $value;
        }

        $path = str_starts_with($value, 'storage/')
            ? substr($value, strlen('storage/'))
            : $value;

        if (Storage::disk('public')->exists($path)) {
            return $previewHtml
                ? asset('storage/'.$path)
                : storage_path('app/public/'.$path);
        }
    }

    return $previewHtml ? asset($default) : public_path($default);
}

public function configuracionResolucionPpa(Request $request)
{
    $facultadId = $this->documentFacultyId();

    if (!$facultadId) {
        return response()->json([
            'error' => 'Debe enviar X-Facultad, facultad_id o id_facultad.'
        ], 422);
    }

    Carbon::setLocale('es');
    $fecha = Carbon::now();
    $configuracion = ResolucionConfiguracion::where('facultad_id', $facultadId)
        ->where('tipo', 'ppa')
        ->first();

    return response()->json([
        'facultad_id' => $facultadId,
        'tipo' => 'ppa',
        'fields' => $this->camposResolucionPpaFrontend($request, $fecha, $configuracion->fields ?? []),
        'updated_by' => $configuracion->updated_by ?? null,
        'updated_at' => optional($configuracion)->updated_at,
    ]);
}

public function guardarConfiguracionResolucionPpa(Request $request)
{
    $facultadId = $this->documentFacultyId();

    if (!$facultadId) {
        return response()->json([
            'error' => 'Debe enviar X-Facultad, facultad_id o id_facultad.'
        ], 422);
    }

    $data = $request->validate([
        'fields' => 'required|array',
    ]);

    $configuracionActual = ResolucionConfiguracion::where('facultad_id', $facultadId)
        ->where('tipo', 'ppa')
        ->first();

    $fields = array_merge(
        $configuracionActual->fields ?? [],
        array_intersect_key($data['fields'], array_flip($this->camposResolucionPpaPermitidos()))
    );

    $configuracion = ResolucionConfiguracion::updateOrCreate(
        [
            'facultad_id' => $facultadId,
            'tipo' => 'ppa',
        ],
        [
            'fields' => $fields,
            'updated_by' => $request->header('X-User', 'desconocido'),
        ]
    );

    Carbon::setLocale('es');
    $fecha = Carbon::now();

    return response()->json([
        'facultad_id' => $facultadId,
        'tipo' => 'ppa',
        'fields' => $this->camposResolucionPpaFrontend($request, $fecha, $configuracion->fields ?? []),
        'updated_by' => $configuracion->updated_by,
        'updated_at' => $configuracion->updated_at,
    ]);
}

public function guardarLogoConfiguracionResolucionPpa(Request $request)
{
    $facultadId = $this->documentFacultyId();

    if (!$facultadId) {
        return response()->json([
            'error' => 'Debe enviar X-Facultad, facultad_id o id_facultad.'
        ], 422);
    }

    $request->validate([
        'field' => 'required|in:logo_izq,logo_der',
        'file' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        'logo_izq' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        'logo_der' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
    ]);

    $field = $request->input('field');
    $file = $request->file('file')
        ?? $request->file('logo')
        ?? $request->file($field);

    if (!$file) {
        return response()->json([
            'error' => 'Debe enviar la imagen en file, logo o '.$field.'.'
        ], 422);
    }

    $extension = $file->getClientOriginalExtension() ?: $file->extension();
    $path = $file->storeAs(
        "resoluciones/ppa/facultad_{$facultadId}",
        "{$field}.{$extension}",
        'public'
    );

    $configuracionActual = ResolucionConfiguracion::where('facultad_id', $facultadId)
        ->where('tipo', 'ppa')
        ->first();

    $fields = array_merge($configuracionActual->fields ?? [], [
        $field => $path,
    ]);

    $configuracion = ResolucionConfiguracion::updateOrCreate(
        [
            'facultad_id' => $facultadId,
            'tipo' => 'ppa',
        ],
        [
            'fields' => $fields,
            'updated_by' => $request->header('X-User', 'desconocido'),
        ]
    );

    Carbon::setLocale('es');
    $fecha = Carbon::now();

    return response()->json([
        'facultad_id' => $facultadId,
        'tipo' => 'ppa',
        'field' => $field,
        'path' => $path,
        'url' => asset('storage/'.$path),
        'fields' => $this->camposResolucionPpaFrontend($request, $fecha, $configuracion->fields ?? []),
        'updated_by' => $configuracion->updated_by,
        'updated_at' => $configuracion->updated_at,
    ]);
}

public function getDataResolucion()
{
    $ppa = PPA::with([
        'profesor.catDocente',
        'profesor.catCientifica'
    ])->get();

    return $ppa->map(function ($item) {

        $anio = AnoAcademico::find($item->id_a_academico);
        $carrera = $anio
            ? ProgFormacion::find($anio->id_prog_form)
            : null;

        $departamento = DB::table('departamento_prog_d_form')
            ->join('departamento', 'departamento_prog_d_form.id_departamento', '=', 'departamento.id')
            ->where('departamento_prog_d_form.id_prog_form', $carrera->id ?? 0)
            ->select('departamento.nombre')
            ->first();

        return [
            'nombre' => $item->profesor->nombre . ' ' . $item->profesor->apellidos,
            'carrera' => $carrera->nombre ?? '',
            'anio' => $anio->identificador ?? '',
            'catDocente' => $item->profesor->catDocente->nombre ?? '',
            'catCientifica' => $item->profesor->catCientifica->nombre ?? '',
            'departamento' => $departamento->nombre ?? ''
        ];
    });
}


public function exportResolucionPDF(Request $request)
{
    $facultadId = $this->documentFacultyId();

    if (!$facultadId) {
        return response()->json([
            'error' => 'Debe enviar X-Facultad, facultad_id o id_facultad para generar la resolución.'
        ], 422);
    }

    Carbon::setLocale('es');
    $fecha = Carbon::now();

    $dia = $fecha->day;
    $mes = $fecha->translatedFormat('F');
    $anio = $fecha->year;
    $revolucion = $anio - 1958;
    $camposEditables = $this->camposEditablesResolucionPpa($request, $fecha, $this->camposGuardadosResolucionPpa($facultadId));
    $logoIzq = $this->logoResolucionPpa($camposEditables, 'logoIzq', false);
    $logoDer = $this->logoResolucionPpa($camposEditables, 'logoDer', false);
    $nombreFacultad = $this->documentFacultyName($facultadId);
    $nombreFacultadMayus = $this->documentFacultyNameUpper($facultadId);

    // 🔹 DECANO (igual que ya te funciona)
    $decano = Decano::where('id_facultad', $facultadId)->first();
    $profesor = $decano ? Profesor::find($decano->id_profesor) : null;

    $nombreDecano = $profesor
        ? $profesor->nombre . ' ' . $profesor->apellidos
        : '';

    // 🔥 NUEVO: HISTORIAL FILTRADO POR AÑO
    $historial = PpaHistorial::whereYear('fecha_accion', $anio)
        ->with(['profesor.catDocente', 'profesor.catCientifica'])
        ->get();

    // 🔥 SEPARAR ACCIONES
    $ratificados = $historial->where('accion', 'ratificado');
    $desnombrados = $historial->where('accion', 'desnombrado');
    $designados = $historial->where('accion', 'designado');

    // 🔥 MAPEAR (MISMO FORMATO QUE YA USABAS)
    $mapear = function ($items) use ($facultadId) {
        return $items->map(function ($item) use ($facultadId) {

            if (!$this->ubicacionAcademicaPpa($item->id_a_academico, $facultadId, null)) {
                return null;
            }

            $anio = \App\Models\AnoAcademico::find($item->id_a_academico);
            $carrera = $anio
                ? \App\Models\ProgFormacion::find($anio->id_prog_form)
                : null;

            return [
                'carrera' => $carrera->nombre ?? '',
                'anio' => $anio->identificador ?? '',
                'nombre' => $item->profesor->nombre . ' ' . $item->profesor->apellidos,
                'catDocente' => $item->profesor->catDocente->nombre ?? '',
                'catCientifica' => $item->profesor->catCientifica->nombre ?? '',
            ];
        })->filter()->values();
    };

    $ratificados = $mapear($ratificados);
    $desnombrados = $mapear($desnombrados);
    $designados = $mapear($designados);

    // 🔴 IMPORTANTE: quitamos $data, ahora mandamos listas separadas
    $pdf = Pdf::loadView('resolucion', compact(
        'ratificados',
        'desnombrados',
        'designados',
        'dia',
        'mes',
        'anio',
        'revolucion',
        'nombreDecano',
        'nombreFacultad',
        'nombreFacultadMayus',
        'camposEditables',
        'logoIzq',
        'logoDer'
    ));
$fechaTexto = $fecha->format('d-m-Y_H-i-s');

// 🔥 nombre correcto
$nombreArchivo = $this->documentFileName('Resolucion_PPA', 'pdf');

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
    'nombre' => "Resolución PPA {$fechaTexto}",
    'tipo' => 'ppa',
    'tipo_documento' => 'resolucion',
    'periodo' => $anio, // 👈 usa tu variable ya calculada
    'ruta' => $ruta,
    'facultad_id' => $facultadId,
]);
$this->logDocumentGenerated('Resolución PPA', $fechaTexto);

// 🔥 descargar
return response()->download($rutaCompleta, $nombreArchivo);
}

public function exportResolucionHtml(Request $request)
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
    $anio = $fecha->year;
    $revolucion = $anio - 1958;
    $camposEditables = $this->camposEditablesResolucionPpa($request, $fecha, $this->camposGuardadosResolucionPpa($facultadId));
    $logoIzq = $this->logoResolucionPpa($camposEditables, 'logoIzq', true);
    $logoDer = $this->logoResolucionPpa($camposEditables, 'logoDer', true);
    $nombreFacultad = $this->documentFacultyName($facultadId);
    $nombreFacultadMayus = $this->documentFacultyNameUpper($facultadId);

    $decano = Decano::where('id_facultad', $facultadId)->first();
    $profesor = $decano ? Profesor::find($decano->id_profesor) : null;

    $nombreDecano = $profesor
        ? $profesor->nombre . ' ' . $profesor->apellidos
        : '';

    $historial = PpaHistorial::whereYear('fecha_accion', $anio)
        ->with(['profesor.catDocente', 'profesor.catCientifica'])
        ->get();

    $mapear = function ($items) use ($facultadId) {
        return $items->map(function ($item) use ($facultadId) {
            if (!$this->ubicacionAcademicaPpa($item->id_a_academico, $facultadId, null)) {
                return null;
            }

            $anio = \App\Models\AnoAcademico::find($item->id_a_academico);
            $carrera = $anio
                ? \App\Models\ProgFormacion::find($anio->id_prog_form)
                : null;

            return [
                'carrera' => $carrera->nombre ?? '',
                'anio' => $anio->identificador ?? '',
                'nombre' => $item->profesor->nombre . ' ' . $item->profesor->apellidos,
                'catDocente' => $item->profesor->catDocente->nombre ?? '',
                'catCientifica' => $item->profesor->catCientifica->nombre ?? '',
            ];
        })->filter()->values();
    };

    $ratificados = $mapear($historial->where('accion', 'ratificado'));
    $desnombrados = $mapear($historial->where('accion', 'desnombrado'));
    $designados = $mapear($historial->where('accion', 'designado'));
    $editableResolucion = true;
    $previewHtml = true;

    return response()
        ->view('resolucion', compact(
            'ratificados',
            'desnombrados',
            'designados',
            'dia',
            'mes',
            'anio',
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


public function exportResolucionWord(Request $request)
{
    $facultadId = $this->documentFacultyId();

    if (!$facultadId) {
        return response()->json([
            'error' => 'Debe enviar X-Facultad, facultad_id o id_facultad para generar la resolución.'
        ], 422);
    }

    Carbon::setLocale('es');
    $fecha = Carbon::now();

    $dia = $fecha->day;
    $mes = $fecha->translatedFormat('F');
    $anio = $fecha->year;
    $revolucion = $anio - 1958;
    $camposEditables = $this->camposEditablesResolucionPpa($request, $fecha, $this->camposGuardadosResolucionPpa($facultadId));
    $logoIzq = $this->logoResolucionPpa($camposEditables, 'logoIzq', false);
    $logoDer = $this->logoResolucionPpa($camposEditables, 'logoDer', false);
    $nombreFacultad = $this->documentFacultyName($facultadId);
    $nombreFacultadMayus = $this->documentFacultyNameUpper($facultadId);

    // 🔹 DECANO
    $decano = Decano::where('id_facultad', $facultadId)->first();
    $profesor = $decano ? Profesor::find($decano->id_profesor) : null;

    $nombreDecano = $profesor
        ? $profesor->nombre . ' ' . $profesor->apellidos
        : '';

    // 🔥 HISTORIAL
    $historial = PpaHistorial::whereYear('fecha_accion', $anio)
        ->with(['profesor.catDocente', 'profesor.catCientifica'])
        ->get();

    $ratificados = $historial->where('accion', 'ratificado');
    $desnombrados = $historial->where('accion', 'desnombrado');
    $designados = $historial->where('accion', 'designado');

    // 🔥 MAPEAR (NO TOCAR)
    $mapear = function ($items) use ($facultadId) {
        return $items->map(function ($item) use ($facultadId) {

            if (!$this->ubicacionAcademicaPpa($item->id_a_academico, $facultadId, null)) {
                return null;
            }

            $anio = \App\Models\AnoAcademico::find($item->id_a_academico);
            $carrera = $anio
                ? \App\Models\ProgFormacion::find($anio->id_prog_form)
                : null;

            return [
                'carrera' => $carrera->nombre ?? '',
                'anio' => $anio->identificador ?? '',
                'nombre' => $item->profesor->nombre . ' ' . $item->profesor->apellidos,
                'catDocente' => $item->profesor->catDocente->nombre ?? '',
                'catCientifica' => $item->profesor->catCientifica->nombre ?? '',
            ];
        })->filter()->values();
    };

    $ratificados = $mapear($ratificados);
    $desnombrados = $mapear($desnombrados);
    $designados = $mapear($designados);

    // 🔥 CREAR WORD
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

    // Línea


    // ============================
    // ✅ TU HTML (NO LO ROMPEMOS)
    // ============================

    $html = view('resolucion_word', compact(
        'ratificados',
        'desnombrados',
        'designados',
        'dia',
        'mes',
        'anio',
        'revolucion',
        'nombreDecano',
        'nombreFacultad',
        'nombreFacultadMayus',
        'camposEditables'
    ))->render();

    // limpiar etiquetas que rompen PhpWord
    $html = preg_replace('/<!DOCTYPE.*?>/', '', $html);
    $html = str_replace(['<html>', '</html>', '<body>', '</body>'], '', $html);

    $addPpaResolucionTable = function ($items) use ($section) {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50
        ]);

        $table->addRow();
        $table->addCell(2100)->addText('Carrera', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
        $table->addCell(800)->addText('Año', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
        $table->addCell(3300)->addText('Nombre del PPA', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
        $table->addCell(1500)->addText('Categoría Docente', ['bold' => true, 'name' => 'Arial', 'size' => 10]);
        $table->addCell(1500)->addText('Categoría Científica', ['bold' => true, 'name' => 'Arial', 'size' => 10]);

        collect($items)->groupBy('carrera')->each(function ($grupo, $carrera) use ($table) {
            $grupo = $grupo->values();

            foreach ($grupo as $index => $item) {
                $table->addRow();

                $table->addCell(2100, ['valign' => 'center'])
                    ->addText($index === 0 ? $carrera : '', ['name' => 'Arial', 'size' => 10]);

                $table->addCell(800)->addText($item['anio'], ['name' => 'Arial', 'size' => 10]);
                $table->addCell(3300)->addText($item['nombre'], ['name' => 'Arial', 'size' => 10]);
                $table->addCell(1500)->addText($item['catDocente'], ['name' => 'Arial', 'size' => 10]);
                $table->addCell(1500)->addText($item['catCientifica'], ['name' => 'Arial', 'size' => 10]);
            }
        });
    };

   // ============================
// PARTIR HTML
// ============================

$partes1 = explode('__TABLA_RATIFICADOS__', $html);

// PRIMER BLOQUE (ANTES DE PRIMERO TABLA)
Html::addHtml($section, $partes1[0], false, false);

// ============================
// TABLA RATIFICADOS
// ============================
$addPpaResolucionTable($ratificados);

// ============================
// SEGUNDA PARTE
// ============================

$partes2 = explode('__TABLA_DESNOMBRADOS__', $partes1[1]);

Html::addHtml($section, $partes2[0]);

// ============================
// TABLA DESNOMBRADOS ✅
// ============================
$addPpaResolucionTable($desnombrados);

$partes3 = explode('__TABLA_DESIGNADOS__', $partes2[1]);

Html::addHtml($section, $partes3[0]);

// ============================
// TABLA DESIGNADOS
// ============================
$addPpaResolucionTable($designados);
Html::addHtml($section, $partes3[1]);
    // ============================
    // DESCARGA
    // ============================

   // 🔥 nombre dinámico
$fecha = now();
$fechaTexto = $fecha->format('d-m-Y_H-i-s');
$nombreArchivo = $this->documentFileName('Resolucion_PPA', 'docx');

// 🔥 asegurar carpeta
$directorio = storage_path('app/public/documentos');
if (!file_exists($directorio)) {
    mkdir($directorio, 0777, true);
}

// 🔥 rutas
$ruta = "documentos/{$nombreArchivo}";
$rutaCompleta = storage_path("app/public/{$ruta}");

// 🔥 guardar archivo
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($rutaCompleta);

// 🔥 guardar en BD
\App\Models\Documento::create([
    'nombre' => "Resolución PPA {$fechaTexto}",
    'tipo' => 'ppa',
    'tipo_documento' => 'resolucion',
    'periodo' => $anio,
    'ruta' => $ruta,
    'facultad_id' => $facultadId,
]);
$this->logDocumentGenerated('Resolución PPA', $fechaTexto);

// 🔥 descargar
return response()->download($rutaCompleta, $nombreArchivo);
}
public function historial(Request $request)
{
    $desde = $request->desde;
    $hasta = $request->hasta;

    $historial = \App\Models\PpaHistorial::with([
            'profesor.catDocente',
            'profesor.catCientifica',
            'curso'
        ])
        ->whereIn('accion', ['designado', 'ratificado', 'desnombrado'])
        ->whereNotNull('fecha_accion')
        ->whereYear('fecha_accion', '>=', $desde)
        ->whereYear('fecha_accion', '<=', $hasta)
        ->get()
        ->unique(function ($item) {
            return $item->id_profesor . '-' . $item->id_a_academico . '-' . ($item->id_curso ?? date('Y', strtotime($item->fecha_accion)));
        });

    // 🔥 departamentos
    $miembros = \App\Models\MiembroDepartamento::whereIn(
        'id_profesor',
        $historial->pluck('id_profesor')
    )->get()->keyBy('id_profesor');

    $departamentos = \App\Models\Departamento::whereIn(
        'id',
        $miembros->pluck('id_departamento')
    )->get()->keyBy('id');

    // 🔥 años y carreras (si quieres mantenerlos)
    $anos = \App\Models\AnoAcademico::whereIn(
        'id',
        $historial->pluck('id_a_academico')
    )->get()->keyBy('id');

    $progForms = \App\Models\ProgFormacion::whereIn(
        'id',
        $anos->pluck('id_prog_form')
    )->get()->keyBy('id');

   $data = $historial->map(function ($item) use ($anos, $progForms, $miembros, $departamentos) {

    $profesor = $item->profesor;
    if (!$profesor) return null;

    $anio = $anos[$item->id_a_academico] ?? null;
    $carrera = $anio ? ($progForms[$anio->id_prog_form] ?? null) : null;

    $miembro = $miembros[$item->id_profesor] ?? null;
    $departamento = $miembro
        ? ($departamentos[$miembro->id_departamento]->nombre ?? '')
        : '';

    return [
        'nombre' => $profesor->nombre ?? '',
        'apellidos' => $profesor->apellidos ?? '',
        'accion' => $item->accion ?? '',
        'curso' => optional($item->curso)->curso ?? $this->courseNameForActionDate($item->fecha_accion) ?? '',
        'catDocente' => optional($profesor->catDocente)->nombre ?? '',
        'catCientifica' => optional($profesor->catCientifica)->nombre ?? '',
        'departamento' => $departamento,
        'carrera' => $carrera->nombre ?? '',
        'anio_calendario' => date('Y', strtotime($item->fecha_accion)), // 🔥 año real
        'anio_academico' => $anio->identificador ?? '' // 🔥 1ro, 2do
    ];
})->filter();


    if ($data->isEmpty()) {
        return response()->json(['error' => 'No hay datos'], 400);
    }

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
        'exports.historial_ppa',
        [
            'data' => $data,
            'desde' => $desde,
            'hasta' => $hasta
        ]
    );

    $nombreArchivo = $this->documentFileName("Historial_PPA_{$desde}_{$hasta}", 'pdf');
    $ruta = "documentos/{$nombreArchivo}";

    \Illuminate\Support\Facades\Storage::disk('public')->put($ruta, $pdf->output());

    \App\Models\Documento::create([
        'nombre' => "Historial PPA {$desde}-{$hasta}",
        'tipo' => 'ppa',
        'tipo_documento' => 'historial',
        'periodo' => $hasta,
        'ruta' => $ruta,
        'facultad_id' => $this->documentFacultyId(),
    ]);
    $this->logDocumentGenerated('Historial PPA', "{$desde}-{$hasta}");

    return $pdf->download($nombreArchivo);
}
}
