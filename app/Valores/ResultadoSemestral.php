<?php

declare(strict_types=1);

namespace App\Valores;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Objeto de valor inmutable: el resultado de una materia en un semestre.
 *
 * Parciales aportan hasta 60 y el examen final hasta 40 (total sobre 100).
 * Los parciales se suman en bloque (puntos logrados sobre puntos posibles), así
 * un mal parcial se remonta con los siguientes.
 *
 * Candado: cada bloque debe alcanzar el 70% por separado (42 de 60 y 28 de 40);
 * si alguno queda debajo, la nota es 1. Con el candado superado, el total
 * redondeado al entero más cercano da: 70-77 = 2, 78-85 = 3, 86-93 = 4, 94-100 = 5.
 *
 * Todo el cálculo es con enteros (puntos en centésimas): los límites son exactos,
 * sin errores de coma flotante.
 */
final class ResultadoSemestral implements JsonSerializable
{
    public const PESO_PARCIALES = 60;

    public const PESO_EXAMEN = 40;

    /** Porcentaje que debe lograrse en cada bloque por separado. */
    public const MINIMO_POR_BLOQUE = 70;

    /**
     * @param int $logradoParciales centésimas de punto logradas en los parciales
     * @param int $maximoParciales  puntos posibles en los parciales
     * @param int $logradoExamen    centésimas de punto logradas en el examen
     * @param int $maximoExamen     puntos posibles en el examen
     */
    private function __construct(
        private readonly int $logradoParciales,
        private readonly int $maximoParciales,
        private readonly int $logradoExamen,
        private readonly int $maximoExamen,
    ) {
    }

    /**
     * @param list<array{0: int|float|string, 1: int}> $parciales pares [puntos logrados, puntos máximos]
     *
     * @throws InvalidArgumentException si no hay parciales o algún puntaje es inválido
     */
    public static function calcular(array $parciales, int|float|string $puntosExamen, int $maximoExamen): self
    {
        if ($parciales === []) {
            throw new InvalidArgumentException('Hace falta al menos un parcial para calcular el semestre.');
        }

        $logrado = 0;
        $maximo = 0;

        foreach ($parciales as [$puntos, $max]) {
            $logrado += self::centesimas($puntos, $max);
            $maximo += $max;
        }

        return new self($logrado, $maximo, self::centesimas($puntosExamen, $maximoExamen), $maximoExamen);
    }

    /** Puntos (con hasta 2 decimales) a centésimas; valida el rango 0 a máximo. */
    private static function centesimas(int|float|string $puntos, int $max): int
    {
        if ($max <= 0) {
            throw new InvalidArgumentException("El puntaje máximo debe ser mayor que cero. Recibido: {$max}.");
        }

        if (! is_numeric($puntos)) {
            throw new InvalidArgumentException("Los puntos no son numéricos: '{$puntos}'.");
        }

        $centesimas = (int) round(((float) $puntos) * 100);

        if (abs(((float) $puntos) * 100 - $centesimas) > 1e-6) {
            throw new InvalidArgumentException("Los puntos admiten hasta 2 decimales. Recibido: {$puntos}.");
        }

        if ($centesimas < 0 || $centesimas > $max * 100) {
            throw new InvalidArgumentException("Los puntos deben estar entre 0 y {$max}. Recibido: {$puntos}.");
        }

        return $centesimas;
    }

    public function puntosParciales(): float
    {
        return $this->logradoParciales / 100;
    }

    public function maximoParciales(): int
    {
        return $this->maximoParciales;
    }

    public function puntosExamen(): float
    {
        return $this->logradoExamen / 100;
    }

    public function maximoExamen(): int
    {
        return $this->maximoExamen;
    }

    /** Rendimiento del bloque de parciales, de 0 a 100. */
    public function porcentajeParciales(): float
    {
        return round($this->logradoParciales / $this->maximoParciales, 2);
    }

    /** Rendimiento del examen final, de 0 a 100. */
    public function porcentajeExamen(): float
    {
        return round($this->logradoExamen / $this->maximoExamen, 2);
    }

    /** Lo que aportan los parciales al total (hasta 60). */
    public function aporteParciales(): float
    {
        return round(self::PESO_PARCIALES * $this->logradoParciales / (100 * $this->maximoParciales), 2);
    }

    /** Lo que aporta el examen al total (hasta 40). */
    public function aporteExamen(): float
    {
        return round(self::PESO_EXAMEN * $this->logradoExamen / (100 * $this->maximoExamen), 2);
    }

    /** Total sobre 100, con 2 decimales (para mostrar). */
    public function totalExacto(): float
    {
        return round($this->numerador() / $this->denominador(), 2);
    }

    /** Total sobre 100 redondeado al entero más cercano (la mitad sube). */
    public function total(): int
    {
        $numerador = $this->numerador();
        $denominador = $this->denominador();

        return intdiv(2 * $numerador + $denominador, 2 * $denominador);
    }

    public function parcialesSuperan(): bool
    {
        return $this->logradoParciales >= self::MINIMO_POR_BLOQUE * $this->maximoParciales;
    }

    public function examenSupera(): bool
    {
        return $this->logradoExamen >= self::MINIMO_POR_BLOQUE * $this->maximoExamen;
    }

    public function candadoSuperado(): bool
    {
        return $this->parcialesSuperan() && $this->examenSupera();
    }

    public function nota(): Calificacion
    {
        if (! $this->candadoSuperado()) {
            return Calificacion::de(1);
        }

        $total = $this->total();

        return Calificacion::de(match (true) {
            $total >= 94 => 5,
            $total >= 86 => 4,
            $total >= 78 => 3,
            default => 2,
        });
    }

    public function aprueba(): bool
    {
        return $this->nota()->aprueba();
    }

    public function jsonSerialize(): array
    {
        return [
            'puntos_parciales' => $this->puntosParciales(),
            'maximo_parciales' => $this->maximoParciales,
            'porcentaje_parciales' => $this->porcentajeParciales(),
            'puntos_examen' => $this->puntosExamen(),
            'maximo_examen' => $this->maximoExamen,
            'porcentaje_examen' => $this->porcentajeExamen(),
            'total' => $this->total(),
            'candado_superado' => $this->candadoSuperado(),
            'nota' => $this->nota()->valor(),
        ];
    }

    private function numerador(): int
    {
        return self::PESO_PARCIALES * $this->logradoParciales * $this->maximoExamen
            + self::PESO_EXAMEN * $this->logradoExamen * $this->maximoParciales;
    }

    private function denominador(): int
    {
        return 100 * $this->maximoParciales * $this->maximoExamen;
    }
}
