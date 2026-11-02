<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialAcademico extends Model
{
    protected $table = 'historial_academico';

    protected $fillable = [
        'alumno_id',
        'detalle',
        'periodo',
    ];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }
}
