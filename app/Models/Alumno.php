<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alumno extends Persona
{
    protected $table = 'alumnos';

    // usuario_id y activo se heredan de Persona.
    protected $fillable = [
        'curso_id',
        'legajo',
        'fecha_nacimiento',
        'telefono',
        'foto',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'fecha_nacimiento' => 'date',
        ];
    }

    public function identificador(): string
    {
        return (string) $this->legajo;
    }

    public function descripcion(): string
    {
        $curso = $this->curso
            ? ($this->curso->anio ?? '') . (isset($this->curso->anio) ? '° ' : '') . $this->curso->nombre
            : 'Sin curso';

        return $this->legajo ? "{$curso} · Legajo {$this->legajo}" : $curso;
    }

    /**
     * Genera un número de legajo único con formato AÑO-NNNN (ej: 2026-0154),
     * que es lo que se usa para identificar al alumno en el resto del sistema.
     */
    public static function nuevoLegajo(): string
    {
        $anio = now()->year;
        $prefijo = $anio . '-';

        $usados = static::query()
            ->where('legajo', 'like', $prefijo . '%')
            ->pluck('legajo')
            ->map(fn ($legajo) => (int) substr((string) $legajo, strlen($prefijo)))
            ->all();

        return static::legajoLibre($anio, $usados);
    }

    /**
     * Elige al azar un número libre del año (1 a 9999). Siempre termina: si ya no queda
     * ninguno lanza una excepción en lugar de buscar para siempre.
     *
     * @param  array<int, int>  $usados  números ya tomados en ese año
     */
    public static function legajoLibre(int $anio, array $usados): string
    {
        $libres = array_values(array_diff(range(1, 9999), $usados));

        if ($libres === []) {
            throw new \RuntimeException("No quedan números de legajo disponibles para {$anio}.");
        }

        $numero = $libres[random_int(0, count($libres) - 1)];

        return $anio . '-' . str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function notas(): HasMany
    {
        return $this->hasMany(Nota::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    public function historialAcademico(): HasMany
    {
        return $this->hasMany(HistorialAcademico::class);
    }

    public function boletinesGenerados(): HasMany
    {
        return $this->hasMany(BoletinGenerado::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoAlumno::class);
    }
}