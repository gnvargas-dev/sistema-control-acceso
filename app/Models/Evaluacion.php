<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoEvaluacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una instancia de evaluación de una materia en un semestre: un parcial (hasta 5)
 * o el examen final (uno), cada uno con su puntaje máximo (20, 30, 40 o 50).
 */
class Evaluacion extends Model
{
    protected $table = 'evaluaciones';

    protected $fillable = [
        'materia_id',
        'semestre',
        'tipo',
        'orden',
        'nombre',
        'puntos_max',
    ];

    protected function casts(): array
    {
        return [
            'semestre' => 'integer',
            'orden' => 'integer',
            'puntos_max' => 'integer',
            'tipo' => TipoEvaluacion::class,
        ];
    }

    public function esParcial(): bool
    {
        return $this->tipo === TipoEvaluacion::Parcial;
    }

    public function scopeDelSemestre(Builder $query, int $materiaId, int $semestre): Builder
    {
        return $query->where('materia_id', $materiaId)->where('semestre', $semestre);
    }

    public function scopeParciales(Builder $query): Builder
    {
        return $query->where('tipo', TipoEvaluacion::Parcial->value);
    }

    public function scopeExamenes(Builder $query): Builder
    {
        return $query->where('tipo', TipoEvaluacion::Examen->value);
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoEvaluacion::class, 'evaluacion_id');
    }
}
