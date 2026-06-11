<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PlanEstudio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlanEstudioExcelController extends Controller
{
    public function preview(string $id)
    {
        return response()->json([
            'res' => true,
            'data' => $this->buildData((int) $id),
        ]);
    }

    public function download(string $id)
    {
        $data = $this->buildData((int) $id);
        $html = $this->renderExcelHtml($data);
        $filename = 'plan_estudio_' . $data['plan']['id'] . '.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function buildData(int $id): array
    {
        $plan = PlanEstudio::with([
            'programaFormacion',
            'curso',
            'modalidad',
            'calificacion',
            'curriculos.disciplinas.asignaturas.aniosAcademicos',
        ])->findOrFail($id);

        $anios = DB::table('a_academico')
            ->where('id_prog_form', $plan->id_prog_form)
            ->orderBy('identificador')
            ->get(['id', 'identificador'])
            ->map(fn ($anio) => [
                'id' => (int) $anio->id,
                'identificador' => (string) $anio->identificador,
            ])
            ->values();

        $rows = collect();

        foreach ($plan->curriculos as $curriculo) {
            $rows->push([
                'type' => 'curriculo',
                'nombre' => mb_strtoupper((string) $curriculo->nombre),
                'fondo_tiempo' => 0,
                'horas_clase' => 0,
                'horas_practica_laboral' => 0,
                'tiene_examen_final' => false,
                'tiene_trabajo_curso' => false,
                'anios' => [],
            ]);

            foreach ($curriculo->disciplinas->sortBy('nombre') as $disciplina) {
                $asignaturas = $disciplina->asignaturas
                    ->filter(fn ($asignatura) => $this->asignaturaPerteneceAlPrograma($asignatura, (int) $plan->id_prog_form))
                    ->sortBy('nombre')
                    ->values();

                if ($asignaturas->isEmpty()) {
                    continue;
                }

                $disciplinaRowIndex = $rows->count();
                $rows->push([
                    'type' => 'disciplina',
                    'nombre' => $disciplina->nombre,
                    'fondo_tiempo' => 0,
                    'horas_clase' => 0,
                    'horas_practica_laboral' => 0,
                    'tiene_examen_final' => false,
                    'tiene_trabajo_curso' => false,
                    'anios' => [],
                ]);

                foreach ($asignaturas as $asignatura) {
                    $row = [
                        'type' => 'asignatura',
                        'nombre' => $asignatura->nombre,
                        'fondo_tiempo' => (int) ($asignatura->fondo_tiempo ?? 0),
                        'horas_clase' => (int) ($asignatura->horas_clase ?? $asignatura->fondo_tiempo ?? 0),
                        'horas_practica_laboral' => (int) ($asignatura->horas_practica_laboral ?? 0),
                        'tiene_examen_final' => (bool) $asignatura->tiene_examen_final,
                        'tiene_trabajo_curso' => (bool) $asignatura->tiene_trabajo_curso,
                        'anios' => $this->horasPorAnio($asignatura, $anios, (int) $plan->id_prog_form),
                    ];

                    $rows->push($row);
                }

                $asignaturasRows = $rows
                    ->slice($disciplinaRowIndex + 1)
                    ->filter(fn ($row) => $row['type'] === 'asignatura');

                $rows[$disciplinaRowIndex] = [
                    ...$rows[$disciplinaRowIndex],
                    'fondo_tiempo' => $asignaturasRows->sum('fondo_tiempo'),
                    'horas_clase' => $asignaturasRows->sum('horas_clase'),
                    'horas_practica_laboral' => $asignaturasRows->sum('horas_practica_laboral'),
                    'tiene_examen_final' => (bool) $asignaturasRows->sum(fn ($row) => $row['tiene_examen_final'] ? 1 : 0),
                    'tiene_trabajo_curso' => (bool) $asignaturasRows->sum(fn ($row) => $row['tiene_trabajo_curso'] ? 1 : 0),
                    'anios' => $this->sumarAnios($asignaturasRows, $anios),
                ];
            }
        }

        return [
            'plan' => [
                'id' => $plan->id,
                'nombre' => $plan->nombre,
                'programa' => $plan->programaFormacion?->nombre,
                'curso' => $plan->curso?->nombre ?? $plan->curso?->curso,
                'modalidad' => $plan->modalidad?->nombre,
                'calificacion' => $plan->calificacion?->nombre,
            ],
            'anios' => $anios,
            'rows' => $rows->values(),
            'totales' => [
                'fondo_tiempo' => $rows->where('type', 'asignatura')->sum('fondo_tiempo'),
                'horas_clase' => $rows->where('type', 'asignatura')->sum('horas_clase'),
                'horas_practica_laboral' => $rows->where('type', 'asignatura')->sum('horas_practica_laboral'),
                'examenes_finales' => $rows->where('type', 'asignatura')->sum(fn ($row) => $row['tiene_examen_final'] ? 1 : 0),
                'trabajos_curso' => $rows->where('type', 'asignatura')->sum(fn ($row) => $row['tiene_trabajo_curso'] ? 1 : 0),
                'anios' => $this->sumarAnios($rows->where('type', 'asignatura'), $anios),
            ],
        ];
    }

    private function asignaturaPerteneceAlPrograma($asignatura, int $programaId): bool
    {
        return $asignatura->aniosAcademicos
            ->contains(fn ($anio) => (int) $anio->id_prog_form === $programaId);
    }

    private function horasPorAnio($asignatura, Collection $anios, int $programaId): array
    {
        $ids = $asignatura->aniosAcademicos
            ->filter(fn ($anio) => (int) $anio->id_prog_form === $programaId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $horas = (int) ($asignatura->fondo_tiempo ?? 0);
        $cantidad = max($ids->count(), 1);
        $horasPorAnio = intdiv($horas, $cantidad);
        $resto = $horas % $cantidad;

        return $anios->mapWithKeys(function ($anio) use ($ids, $horasPorAnio, &$resto) {
            if (! $ids->contains((int) $anio['id'])) {
                return [$anio['identificador'] => 0];
            }

            $valor = $horasPorAnio + ($resto > 0 ? 1 : 0);
            $resto = max(0, $resto - 1);

            return [$anio['identificador'] => $valor];
        })->all();
    }

    private function sumarAnios(Collection $rows, Collection $anios): array
    {
        return $anios->mapWithKeys(function ($anio) use ($rows) {
            $key = $anio['identificador'];

            return [$key => $rows->sum(fn ($row) => (int) ($row['anios'][$key] ?? 0))];
        })->all();
    }

    private function renderExcelHtml(array $data): string
    {
        $columnCount = 8 + count($data['anios']);
        $rows = collect($data['rows'])->map(fn ($row) => $this->renderRow($row, $data['anios']))->implode('');
        $totals = $this->renderTotals($data);

        return '<html><head><meta charset="UTF-8">' . $this->excelStyles() . '</head><body>'
            . '<table>'
            . '<tr><th class="title" colspan="' . $columnCount . '">Plan del Proceso Docente</th></tr>'
            . '<tr><td class="meta" colspan="' . $columnCount . '">Plan: ' . e($data['plan']['nombre']) . '</td></tr>'
            . '<tr><td class="meta" colspan="' . $columnCount . '">Programa: ' . e($data['plan']['programa']) . ' | Curso: ' . e($data['plan']['curso']) . ' | Modalidad: ' . e($data['plan']['modalidad']) . '</td></tr>'
            . $this->renderHeader($data['anios'])
            . $rows
            . $totals
            . '</table></body></html>';
    }

    private function renderHeader(Collection $anios): string
    {
        $yearHeaders = $anios->map(fn ($anio) => '<th>' . e($anio['identificador']) . '</th>')->implode('');

        return '<tr>'
            . '<th>DISCIPLINA Y ASIGNATURA</th>'
            . '<th>CANT. DE HORAS</th>'
            . $yearHeaders
            . '<th>TOTAL</th>'
            . '<th>PRACT. LABORAL</th>'
            . '<th>EXAMEN FINAL DE ASIGNAT.</th>'
            . '<th>TRABAJO DE CURSO</th>'
            . '<th>CLASE</th>'
            . '</tr>';
    }

    private function renderRow(array $row, Collection $anios): string
    {
        $class = $row['type'];
        $yearCells = $anios->map(fn ($anio) => '<td class="num">' . ((int) ($row['anios'][$anio['identificador']] ?? 0) ?: '') . '</td>')->implode('');

        return '<tr class="' . $class . '">'
            . '<td>' . e($row['nombre']) . '</td>'
            . '<td class="num">' . ((int) $row['fondo_tiempo'] ?: '') . '</td>'
            . $yearCells
            . '<td class="num">' . ((int) $row['fondo_tiempo'] ?: '') . '</td>'
            . '<td class="num">' . ((int) $row['horas_practica_laboral'] ?: '') . '</td>'
            . '<td class="center">' . ($row['tiene_examen_final'] ? 'X' : '') . '</td>'
            . '<td class="center">' . ($row['tiene_trabajo_curso'] ? 'X' : '') . '</td>'
            . '<td class="num">' . ((int) $row['horas_clase'] ?: '') . '</td>'
            . '</tr>';
    }

    private function renderTotals(array $data): string
    {
        $yearCells = collect($data['anios'])->map(function ($anio) use ($data) {
            return '<td class="num">' . ((int) ($data['totales']['anios'][$anio['identificador']] ?? 0) ?: '') . '</td>';
        })->implode('');

        return '<tr class="total">'
            . '<td>TOTAL DE HORAS DEL CURRICULO POR FORMAS Y ANOS</td>'
            . '<td class="num">' . (int) $data['totales']['fondo_tiempo'] . '</td>'
            . $yearCells
            . '<td class="num">' . (int) $data['totales']['fondo_tiempo'] . '</td>'
            . '<td class="num">' . (int) $data['totales']['horas_practica_laboral'] . '</td>'
            . '<td class="num">' . (int) $data['totales']['examenes_finales'] . '</td>'
            . '<td class="num">' . (int) $data['totales']['trabajos_curso'] . '</td>'
            . '<td class="num">' . (int) $data['totales']['horas_clase'] . '</td>'
            . '</tr>';
    }

    private function excelStyles(): string
    {
        return '<style>
            table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px; }
            th, td { border: 1px solid #000; padding: 4px 6px; vertical-align: middle; }
            th { background: #d9ead3; font-weight: bold; text-align: center; }
            .title { background: #93c47d; font-size: 16px; text-align: center; }
            .meta { font-weight: bold; background: #f3f6ef; }
            .curriculo td { background: #b6d7a8; font-weight: bold; text-align: center; }
            .disciplina td { background: #d9ead3; font-weight: bold; }
            .asignatura td:first-child { padding-left: 24px; }
            .total td { background: #ffe599; font-weight: bold; }
            .num { text-align: right; }
            .center { text-align: center; }
        </style>';
    }
}
