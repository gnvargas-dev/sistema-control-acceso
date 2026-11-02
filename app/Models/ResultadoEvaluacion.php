<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Puntos que logró un alumno en una evaluación (la nota final del semestre
 * se calcula a partir de estos resultados y se guarda en notas).
 */
class ResultadoEvaluacion extends Model
{
    protected $table = 'resultados_evaluacion';

    protected $fillable = [
        'evaluacion_id',
        'alumno_id',
        'puntos',
        'cargado_por',
    ];

    protected function casts(): array
    {
        return [
            'puntos' => 'decimal:2',
        ];
    }

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class, 'evaluacion_id');
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    public function cargador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cargado_por');
    }
}
