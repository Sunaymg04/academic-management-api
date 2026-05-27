<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class AlumnoAyudante extends Model
{
    protected $table = 'alumno_ayudante';

    protected $fillable = [
        'id_estudiante',
        'id_asignatura',
        'id_curso',
        'nombre_tutor',
        'etapa',
        'fecha_inicio',
        'fecha_fin',
        'tipo',
        'habilitado'
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'id_curso');
    }

    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class, 'id_asignatura');
    }
}
