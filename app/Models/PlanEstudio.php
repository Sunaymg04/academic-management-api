<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanEstudio extends Model
{
    use HasFactory;
    protected $table = 'plan-estudio';
    protected $fillable = [
        'id_prog_form',
        'id_curso',
        'id_modalidad',
        'id_calificacion',
        'nombre',
        'estado',
        'tipo_plan',
        'plan_origen_id',
        'estructura_snapshot',
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'estructura_snapshot' => 'array',
    ];

    public function versiones()
    {
        return $this->hasMany(Version::class, 'plan_estudio_id');
    }

    public function programaFormacion()
    {
        return $this->belongsTo(ProgFormacion::class, 'id_prog_form');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'id_curso');
    }

    public function modalidad()
    {
        return $this->belongsTo(ModalidadCarrera::class, 'id_modalidad');
    }

    public function calificacion()
    {
        return $this->belongsTo(Calificacion::class, 'id_calificacion');
    }

    public function curriculos()
    {
        return $this->belongsToMany(
            Curriculo::class,
            'plan-estudio_curriculo',
            'id_plan_estudio',
            'id_curriculo'
        );
    }

    public function planOrigen()
    {
        return $this->belongsTo(PlanEstudio::class, 'plan_origen_id');
    }

    public function modificacionesComoOrigen()
    {
        return $this->hasMany(Modificacion::class, 'plan_origen_id');
    }

    public function modificacion()
    {
        return $this->hasOne(Modificacion::class, 'plan_modificado_id');
    }
   
}
