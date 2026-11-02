<?php

declare(strict_types=1);

namespace App\Models;

use App\Valores\Calificacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nota extends Model
{
    /** Los períodos de las notas finales por semestre son "Semestre 1" y "Semestre 2". */
    public const PREFIJO_SEMESTRE = 'Semestre';

    protected $table = 'notas';

    protected $fillable = [
        'alumno_id',
        'materia_id',
        'valor',
        'periodo',
        'cargado_por',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
        ];
    }

    /** La nota como objeto de valor; null si todavía no tiene valor. */
    public function calificacion(): ?Calificacion
    {
        return $this->valor === null ? null : Calificacion::de($this->valor);
    }

    /**
     * Setter con validación (entero de 1 a 5). Acepta int, float, string numérico o una
     * Calificacion: es la simulación de sobrecarga de Calificacion::de().
     */
    public function asignarCalificacion(Calificacion|int|float|string $calificacion): static
    {
        $this->valor = Calificacion::de($calificacion)->valor();

        return $this;
    }

    /** Solo las notas finales por semestre ("Semestre 1", "Semestre 2"). */
    public function scopeDeSemestres(Builder $query): Builder
    {
        return $query->where('periodo', 'like', self::PREFIJO_SEMESTRE . ' %');
    }

    /**
     * Notas que entran en el promedio de un alumno: si ya tiene notas por semestre,
     * solo esas (las viejas por Bimestre quedan guardadas pero no promedian);
     * si no las tiene, todas, como hasta ahora.
     */
    public function scopeParaPromedio(Builder $query, int $alumnoId): Builder
    {
        $tieneSemestres = static::query()->where('alumno_id', $alumnoId)->deSemestres()->exists();

        return $tieneSemestres ? $query->deSemestres() : $query;
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function cargador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cargado_por');
    }
}
