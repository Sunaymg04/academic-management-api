<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PlanEstudio;
use App\Models\ProgFormacion;
use App\Models\PlanEstudioProgForm;
use App\Models\PlanEstudio_Curriculo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PlanEstudioController extends Controller
{
    public function index()
    {
        $planes = PlanEstudio::with([
            'programaFormacion',
            'curso',
            'modalidad',
            'calificacion',
        ])->get();

        return response()->json([
            'res' => true,
            'data' => $planes
        ], 200);
    }

    public function store(Request $request)
    {
        $request->merge($this->normalizePlanEstudioInput($request));

        $validator = Validator::make($request->all(), [
            'nombre' => ['nullable', 'string', 'max:255'],
            'id_prog_form' => ['required', 'integer', 'exists:programa_de_formacion,id'],
            'id_curso' => ['required', 'integer', 'exists:curso,id'],
            'id_modalidad' => ['required', 'integer', 'exists:modalidad_carrera,id'],
            'id_calificacion' => ['required', 'integer', 'exists:calificacion,id'],
            'id_curriculo' => ['sometimes', 'array'],
            'id_curriculo.*' => ['integer', 'exists:curriculo,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'res' => false,
                'message' => $validator->errors()
            ], 400);
        }

        if ($this->calificacionAsignadaAOtroPrograma($request->id_calificacion, $request->id_prog_form)) {
            return response()->json([
                'res' => false,
                'message' => 'La calificación ya está asociada a otro programa de formación.'
            ], 400);
        }

        $programa = ProgFormacion::find($request->id_prog_form);
        $nombrePlan = $request->filled('nombre')
            ? $request->nombre
            : 'Plan de Estudio ' . $programa->nombre;

        $plan = DB::transaction(function () use ($request, $nombrePlan) {
            $plan = PlanEstudio::create([
                'nombre'=> $nombrePlan,
                'id_prog_form' => $request->id_prog_form,
                'id_curso' => $request->id_curso,
                'id_modalidad' => $request->id_modalidad,
                'id_calificacion' => $request->id_calificacion,
            ]);

            ProgFormacion::where('id', $request->id_prog_form)
                ->update(['id_calificacion' => $request->id_calificacion]);

            PlanEstudioProgForm::firstOrCreate([
                'plan_estudio_id' => $plan->id,
                'programa_de_formacion_id' => $request->id_prog_form,
            ]);

            $curriculos = array_values(array_unique($request->input('id_curriculo', [])));

            foreach ($curriculos as $idCurriculo) {
                PlanEstudio_Curriculo::firstOrCreate([
                    'id_plan_estudio' => $plan->id,
                    'id_curriculo' => $idCurriculo,
                ]);
            }

            return $plan;
        });

        return response()->json([
            'res' => true,
            'message' => 'Plan de estudio creado correctamente',
            'data' => $plan->load(['programaFormacion', 'curso', 'modalidad', 'calificacion', 'curriculos'])
        ], 200);
    }

    public function show(string $id)
    {
        $plan = PlanEstudio::find($id);

        if (!$plan) {
            return response()->json([
                'res' => false,
                'message' => 'No se encontró el plan de estudio'
            ], 400);
        }

        return response()->json([
            'res' => true,
            'data' => $plan->load(['programaFormacion', 'curso', 'modalidad', 'calificacion', 'curriculos'])
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $request->merge($this->normalizePlanEstudioInput($request));
        $plan = PlanEstudio::find($id);

        if (!$plan) {
            return response()->json([
                'res' => false,
                'message' => 'No se encontró el plan de estudio'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => ['sometimes', 'nullable', 'string', 'max:255'],
            'id_prog_form' => ['sometimes', 'integer', 'exists:programa_de_formacion,id'],
            'id_curso' => ['sometimes', 'integer', 'exists:curso,id'],
            'id_modalidad' => ['sometimes', 'integer', 'exists:modalidad_carrera,id'],
            'id_calificacion' => ['sometimes', 'integer', 'exists:calificacion,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'res' => false,
                'message' => $validator->errors()
            ], 400);
        }

        $programaId = $request->id_prog_form ?? $plan->id_prog_form;
        $calificacionId = $request->id_calificacion ?? $plan->id_calificacion;

        if ($programaId && $calificacionId && $this->calificacionAsignadaAOtroPrograma($calificacionId, $programaId)) {
            return response()->json([
                'res' => false,
                'message' => 'La calificación ya está asociada a otro programa de formación.'
            ], 400);
        }

        $data = [];
        foreach (['nombre', 'id_prog_form', 'id_curso', 'id_modalidad', 'id_calificacion'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->$field;
            }
        }

        DB::transaction(function () use ($plan, $data, $programaId, $calificacionId) {
            $plan->update($data);
    
            if ($programaId && $calificacionId) {
                ProgFormacion::where('id', $programaId)->update(['id_calificacion' => $calificacionId]);
            }

            if ($programaId) {
                PlanEstudioProgForm::firstOrCreate([
                    'plan_estudio_id' => $plan->id,
                    'programa_de_formacion_id' => $programaId,
                ]);
            }
        });

        return response()->json([
            'res' => true,
            'message' => 'Plan de estudio actualizado',
            'data' => $plan->fresh()->load(['programaFormacion', 'curso', 'modalidad', 'calificacion', 'curriculos'])
        ], 200);
    }

    private function normalizePlanEstudioInput(Request $request): array
    {
        $aliases = [
            'id_prog_form' => 'programa_de_formacion_id',
            'id_curso' => 'curso_id',
            'id_modalidad' => 'modalidad_id',
            'id_calificacion' => 'calificacion_id',
        ];

        $data = [];

        foreach ($aliases as $field => $alias) {
            if ($request->filled($field)) {
                $data[$field] = $request->input($field);
            } elseif ($request->filled($alias)) {
                $data[$field] = $request->input($alias);
            }
        }

        return $data;
    }

    private function calificacionAsignadaAOtroPrograma(int $calificacionId, int $programaId): bool
    {
        return ProgFormacion::where('id_calificacion', $calificacionId)
            ->where('id', '<>', $programaId)
            ->exists();
    }
    
    public function destroy(string $id)
    {
        $plan = PlanEstudio::find($id);

        if (!$plan) {
            return response()->json([
                'res' => false,
                'message' => 'No se encontró el plan de estudio'
            ], 400);
        }

        $plan->delete();

        return response()->json([
            'res' => true,
            'message' => 'Plan de estudio eliminado'
        ], 200);
    }

}
