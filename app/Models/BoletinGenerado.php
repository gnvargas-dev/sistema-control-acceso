<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoletinGenerado extends Model
{
    protected $table = 'boletines_generados';

    protected $fillable = [
        'alumno_id',
        'curso_id',
        'periodo',
        'archivo',
        'generado_por',
        'descargas',
    ];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'generado_por');
    }
}