<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoAsistencia;
use App\Models\Alumno;
use App\Models\Asistencia;
use App\Reglas\PresentesYTardanzas;
use App\Reglas\ReglaAsistencia;
use App\Reglas\SoloPresentes;
use App\Valores\PorcentajeAsistencia;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Métricas de asistencia para los gráficos de "Mi asistencia" (alumno) y del
 * historial de asistencias (profesor).
 *
 * SOLO LECTURA: únicamente hace consultas SELECT agrupadas; no escribe nada.
 *
 * Cada pantalla cuenta como "asistida" lo mismo que ya cuenta hoy (Strategy):
 *  - alumno:   PresentesYTardanzas, igual que la pantalla "Mi asistencia".
 *  - profesor: SoloPresentes, igual que el resumen de "Cargar asistencias".
 *
 * El porcentaje mínimo de asistencia es 70 % (no 80 %).
 *
 * Devuelve siempre la misma forma:
 *  - hayDatos  bool
 *  - minimo    int, porcentaje mínimo requerido (70)
 *  - regla     texto que explica qué cuenta como asistida
 *  - anillo    totales por estado + porcentaje (2 decimales, como "Mi asistencia")
 *  - semanas   las últimas 8 semanas (lunes a domingo), de la más antigua a la actual
 *  - dias      de lunes a viernes, sobre todos los registros
 */
final class AsistenciaMetricasService
{
    /** Porcentaje mínimo de asistencia requerido. Regla inamovible: 70 %. */
    public const MINIMO = 70;

    /** Cantidad de semanas de la línea de evolución. */
    public const SEMANAS = 8;

    private const SEGUNDOS_POR_SEMANA = 7 * 86400;

    /** @var array<int, array{0: string, 1: string}> ISO-8601: 1 = lunes … 5 = viernes */
    private const DIAS = [
        1 => ['Lun', 'Lunes'],
        2 => ['Mar', 'Martes'],
        3 => ['Mié', 'Miércoles'],
        4 => ['Jue', 'Jueves'],
        5 => ['Vie', 'Viernes'],
    ];

    /** @return array<string, mixed> */
    public function deAlumno(Alumno $alumno): array
    {
        $filas = $this->agrupadas(Asistencia::query()->where('alumno_id', $alumno->id));

        return $this->calcular(
            $filas,
            new PresentesYTardanzas(),
            now(),
            'Cuenta como asistidas las presencias y las tardanzas.'
        );
    }

    /**
     * Mismo alcance que AsistenciaService::historial(): solo las materias del profesor
     * y, si se pide, una sola (0 o null = todas, como en el listado).
     *
     * @param  Collection<int, \App\Models\Materia>  $materias
     * @return array<string, mixed>
     */
    public function deProfesor(Collection $materias, ?int $materiaId): array
    {
        $filas = $this->agrupadas(
            Asistencia::query()
                ->whereIn('materia_id', $materias->pluck('id'))
                ->when($materiaId, fn ($query) => $query->where('materia_id', $materiaId))
        );

        return $this->calcular(
            $filas,
            new SoloPresentes(),
            now(),
            'Cuenta como asistidas solo las presencias; las tardanzas no suman.'
        );
    }

    /** Una fila por fecha y estado, con su cantidad: pocas filas aunque haya miles de registros. */
    private function agrupadas(Builder $consulta): Collection
    {
        return $consulta
            ->selectRaw('fecha, estado, COUNT(*) as cantidad')
            ->groupBy('fecha', 'estado')
            ->get();
    }

    /**
     * Cálculo puro: no toca la base de datos (por eso se puede probar sin Laravel).
     *
     * @param  iterable<array{fecha: DateTimeInterface|string, estado: string, cantidad: int|string}>  $filas
     * @return array<string, mixed>
     */
    public function calcular(iterable $filas, ReglaAsistencia $regla, DateTimeInterface $hoy, string $descripcionRegla): array
    {
        $utc = new DateTimeZone('UTC');
        $lunesActual = $this->lunesDe(new DateTimeImmutable($hoy->format('Y-m-d'), $utc));

        $porEstado = [];
        foreach (EstadoAsistencia::cases() as $estado) {
            $porEstado[$estado->value] = 0;
        }

        $total = 0;
        $contadas = 0;
        $porSemana = array_fill(0, self::SEMANAS, ['contadas' => 0, 'total' => 0]);
        $porDia = array_fill_keys(array_keys(self::DIAS), ['contadas' => 0, 'total' => 0]);

        foreach ($filas as $fila) {
            $estado = EstadoAsistencia::desde((string) $fila['estado']);
            $cantidad = (int) $fila['cantidad'];
            $cuenta = $regla->cuenta($estado);
            $fecha = new DateTimeImmutable($this->fechaTexto($fila['fecha']), $utc);

            $total += $cantidad;
            $porEstado[$estado->value] += $cantidad;
            $contadas += $cuenta ? $cantidad : 0;

            $dia = (int) $fecha->format('N');
            if (isset($porDia[$dia])) {
                $porDia[$dia]['total'] += $cantidad;
                $porDia[$dia]['contadas'] += $cuenta ? $cantidad : 0;
            }

            // 0 = esta semana, 1 = la anterior… (negativo = fecha futura: no entra en la línea)
            $haceSemanas = intdiv(
                $lunesActual->getTimestamp() - $this->lunesDe($fecha)->getTimestamp(),
                self::SEGUNDOS_POR_SEMANA
            );

            if ($haceSemanas >= 0 && $haceSemanas < self::SEMANAS) {
                $porSemana[$haceSemanas]['total'] += $cantidad;
                $porSemana[$haceSemanas]['contadas'] += $cuenta ? $cantidad : 0;
            }
        }

        $semanas = [];
        for ($haceSemanas = self::SEMANAS - 1; $haceSemanas >= 0; $haceSemanas--) {
            $inicio = $lunesActual->modify("-{$haceSemanas} weeks");

            $semanas[] = [
                'inicio' => $inicio->format('Y-m-d'),
                'etiqueta' => $inicio->format('d/m'),
                'total' => $porSemana[$haceSemanas]['total'],
                'porcentaje' => $this->porcentaje($porSemana[$haceSemanas]),
            ];
        }

        $dias = [];
        foreach (self::DIAS as $numero => [$corto, $largo]) {
            $dias[] = [
                'dia' => $corto,
                'nombre' => $largo,
                'total' => $porDia[$numero]['total'],
                'porcentaje' => $this->porcentaje($porDia[$numero]),
            ];
        }

        return [
            'hayDatos' => $total > 0,
            'minimo' => self::MINIMO,
            'regla' => $descripcionRegla,
            'anillo' => [
                'total' => $total,
                'presentes' => $porEstado[EstadoAsistencia::Presente->value],
                'tardanzas' => $porEstado[EstadoAsistencia::Tarde->value],
                'ausentes' => $porEstado[EstadoAsistencia::Ausente->value],
                'justificados' => $porEstado[EstadoAsistencia::Justificado->value],
                'porcentaje' => PorcentajeAsistencia::deConteos($contadas, $total)->redondeadoOCero(2),
            ],
            'semanas' => $semanas,
            'dias' => $dias,
        ];
    }

    /** @param  array{contadas: int, total: int}  $acumulado */
    private function porcentaje(array $acumulado): ?float
    {
        if ($acumulado['total'] === 0) {
            return null; // sin registros: el gráfico deja un hueco, no un 0 %
        }

        return PorcentajeAsistencia::deConteos($acumulado['contadas'], $acumulado['total'])->redondeado(1);
    }

    /** Lunes de la semana de una fecha (a medianoche UTC, para que las cuentas de días sean exactas). */
    private function lunesDe(DateTimeImmutable $fecha): DateTimeImmutable
    {
        $retroceso = (int) $fecha->format('N') - 1;

        return $retroceso === 0 ? $fecha : $fecha->modify("-{$retroceso} days");
    }

    private function fechaTexto(DateTimeInterface|string $fecha): string
    {
        return $fecha instanceof DateTimeInterface ? $fecha->format('Y-m-d') : substr($fecha, 0, 10);
    }
}
