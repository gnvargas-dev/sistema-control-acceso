<?php

declare(strict_types=1);

namespace App\Valores;

use App\Enums\EstadoAsistencia;
use App\Reglas\ReglaAsistencia;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Objeto de valor inmutable: asistencias contadas sobre el total de registros.
 *
 * El redondeo replica el del sistema: round(($contadas / $total) * 100, $decimales).
 */
final class PorcentajeAsistencia implements JsonSerializable
{
    private function __construct(
        private readonly int $contadas,
        private readonly int $total,
    ) {
        if ($contadas < 0 || $total < 0 || $contadas > $total) {
            throw new InvalidArgumentException(
                "Conteo de asistencias inválido: {$contadas} de {$total}."
            );
        }
    }

    /*
     * Simulación de sobrecarga: PHP no admite varios constructores, así que
     * cada forma de crear el objeto es un constructor estático con nombre.
     */

    public static function deConteos(int $contadas, int $total): self
    {
        return new self($contadas, $total);
    }

    /**
     * @param  iterable<EstadoAsistencia|string>  $estados
     */
    public static function deEstados(iterable $estados, ReglaAsistencia $regla): self
    {
        $contadas = 0;
        $total = 0;

        foreach ($estados as $estado) {
            $total++;

            if ($regla->cuenta(EstadoAsistencia::desde($estado))) {
                $contadas++;
            }
        }

        return new self($contadas, $total);
    }

    public static function sinRegistros(): self
    {
        return new self(0, 0);
    }

    public function contadas(): int
    {
        return $this->contadas;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function esVacio(): bool
    {
        return $this->total === 0;
    }

    /** null si no hay registros (pantallas que muestran "sin datos"). */
    public function redondeado(int $decimales = 1): ?float
    {
        if ($this->esVacio()) {
            return null;
        }

        return round(($this->contadas / $this->total) * 100, $decimales);
    }

    /** 0 (entero) si no hay registros, como hacen hoy los controladores. */
    public function redondeadoOCero(int $decimales = 1): int|float
    {
        return $this->redondeado($decimales) ?? 0;
    }

    public function jsonSerialize(): ?float
    {
        return $this->redondeado(2);
    }
}
