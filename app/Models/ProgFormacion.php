<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgFormacion extends Model
{
    use HasFactory;
    protected $table = 'programa_de_formacion';
    protected $fillable = [
        'nombre',
        'abreviatura',
        'id_calificacion'
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function calificacion()
    {
        return $this->belongsTo(Calificacion::class, 'id_calificacion');
    }

    public function planEstudio()
    {
        return $this->hasOne(PlanEstudio::class, 'id_prog_form');
    }
    
    
}
