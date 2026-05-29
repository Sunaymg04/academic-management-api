<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{
    use HasFactory;

    protected $table = 'calificacion';

    protected $fillable = [
        'nombre',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function programaFormacion()
    {
        return $this->hasOne(ProgFormacion::class, 'id_calificacion');
    }

    public function planesEstudio()
    {
        return $this->hasMany(PlanEstudio::class, 'id_calificacion');
    }
}
