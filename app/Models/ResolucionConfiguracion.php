<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResolucionConfiguracion extends Model
{
    protected $table = 'resolucion_configuraciones';

    protected $fillable = [
        'facultad_id',
        'tipo',
        'fields',
        'updated_by',
    ];

    protected $casts = [
        'fields' => 'array',
    ];
}
