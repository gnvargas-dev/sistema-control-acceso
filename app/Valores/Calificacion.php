<?php

declare(strict_types=1);

namespace App\Valores;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Objeto de valor inmutable: una nota entera de 1 a 5 (escala basada en la del sistema educativo paraguayo).
 *
 * Las notas que se cargan son siempre enteras (Calificacion::de). El promedio de varias
 * (Calificacion::promedioDe) puede tener decimales, pero también queda entre 1 y 5.
 *
 * El constructor es privado: la única forma de obtener una calificación válida
 * es Calificacion::de(), así que nunca existe una instancia fuera de rango.
 */
final class Calificacion implements JsonSerializable, Stringable
{
    public const MINIMA = 1.0;

    public const MAXIMA = 5.0;

    /** Nota mínima para aprobar una materia: 2 en la escala de 1 a 5. */
    public const APROBACION = 2.0;

    private function __construct(private readonly float $valor)
    {
        if ($valor < self::MINIMA || $valor > self::MAXIMA) {
            throw new InvalidArgumentException(
                'La calificación debe estar entre ' . self::MINIMA . ' y ' . self::MAXIMA . ". Recibido: {$valor}."
            );
        }
    }

    /**
     * Simulación de sobrecarga: PHP no la tiene, así que un único método
     * acepta varios tipos (union type) y los normaliza a una Calificacion.
     * Solo admite números enteros de 1 a 5.
     */
    public static function de(int|float|string|self $valor): self
    {
        if ($valor instanceof self) {
            return $valor;
        }

        if (is_string($valor) && ! is_numeric(trim($valor))) {
            throw new InvalidArgumentException("La calificación no es numérica: '{$valor}'.");
        }

        $numero = (float) $valor;

        if (floor($numero) !== $numero) {
            throw new InvalidArgumentException("La calificación debe ser un número entero. Recibido: {$numero}.");
        }

        return new self($numero);
    }

    /** Promedio simple de varias calificaciones (puede tener decimales); null si no hay ninguna. */
    public static function promedioDe(self ...$calificaciones): ?self
    {
        if ($calificaciones === []) {
            return null;
        }

        $suma = array_sum(array_map(fn (self $c) => $c->valor, $calificaciones));

        return new self(round($suma / count($calificaciones), 2));
    }

    public function valor(): float
    {
        return $this->valor;
    }

    public function aprueba(): bool
    {
        return $this->valor >= self::APROBACION;
    }

    public function igualA(self $otra): bool
    {
        return $this->valor === $otra->valor;
    }

    public function texto(int $decimales = 2): string
    {
        return number_format($this->valor, $decimales, '.', '');
    }

    public function jsonSerialize(): float
    {
        return $this->valor;
    }

    public function __toString(): string
    {
        return $this->texto();
    }
}
