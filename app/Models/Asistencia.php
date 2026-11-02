<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoAsistencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        'alumno_id',
        'materia_id',
        'fecha',
        'estado',
        'observacion',
        'cargado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /** El estado como enum; null si todavía no tiene. */
    public function estadoAsistencia(): ?EstadoAsistencia
    {
        return $this->estado === null ? null : EstadoAsistencia::desde($this->estado);
    }

    /**
     * Setter con validación: acepta el enum o su texto (union type, simulación
     * de sobrecarga) y falla si el estado no existe.
     */
    public function marcar(EstadoAsistencia|string $estado): static
    {
        $this->estado = EstadoAsistencia::desde($estado)->value;

        return $this;
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