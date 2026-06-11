<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Asignatura extends Model
{
    use HasFactory;
    protected $table = 'asignatura';
    protected $fillable = [
        'nombre',
        'fondo_tiempo',
        'horas_clase',
        'horas_practica_laboral',
        'tiene_examen_final',
        'tiene_trabajo_curso',
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'tiene_examen_final' => 'boolean',
        'tiene_trabajo_curso' => 'boolean',
    ];

    public function disciplinas(): BelongsToMany
    {
        return $this->belongsToMany(
            Disciplina::class,
            'disciplina_asignatura',
            'id_asignatura',
            'id_disciplina'
        );
    }

    public function aniosAcademicos(): BelongsToMany
    {
        return $this->belongsToMany(
            AnoAcademico::class,
            'asignatura_agno',
            'id_asignatura',
            'id_a_academico'
        );
    }
}
