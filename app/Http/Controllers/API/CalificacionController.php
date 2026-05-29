<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Calificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CalificacionController extends Controller
{
    public function index()
    {
        return response()->json([
            'res' => true,
            'data' => Calificacion::all(),
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:calificacion,nombre',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'res' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        $calificacion = Calificacion::create([
            'nombre' => $request->nombre,
        ]);

        return response()->json([
            'res' => true,
            'message' => 'Calificación creada correctamente',
            'data' => $calificacion,
        ], 200);
    }

    public function show(string $id)
    {
        $calificacion = Calificacion::find($id);

        if (!$calificacion) {
            return response()->json([
                'res' => false,
                'message' => 'No se encontró la calificación',
            ], 400);
        }

        return response()->json([
            'res' => true,
            'data' => $calificacion,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $calificacion = Calificacion::find($id);

        if (!$calificacion) {
            return response()->json([
                'res' => false,
                'message' => 'No se encontró la calificación',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255|unique:calificacion,nombre,'.$id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'res' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        if ($request->has('nombre')) {
            $calificacion->update(['nombre' => $request->nombre]);
        }

        return response()->json([
            'res' => true,
            'message' => 'Calificación actualizada correctamente',
            'data' => $calificacion,
        ], 200);
    }

    public function destroy(string $id)
    {
        $calificacion = Calificacion::find($id);

        if (!$calificacion) {
            return response()->json([
                'res' => false,
                'message' => 'No se encontró la calificación',
            ], 400);
        }

        $calificacion->delete();

        return response()->json([
            'res' => true,
            'message' => 'Calificación eliminada correctamente',
        ], 200);
    }
}
