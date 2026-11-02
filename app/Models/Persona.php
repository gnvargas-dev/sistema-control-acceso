<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Perfil de una persona del sistema (Alumno, Profesor).
 *
 * Reúne lo que comparten los perfiles: el vínculo con su Usuario y la marca
 * "activo". Cada hija mantiene su propia tabla y sus datos específicos.
 */
abstract class Persona extends Model
{
    use HasFactory;

    /** Atributos comunes; se suman al $fillable de cada hija. */
    private const ATRIBUTOS_COMUNES = ['usuario_id', 'activo'];

    public function __construct(array $attributes = [])
    {
        $this->mergeFillable(self::ATRIBUTOS_COMUNES);

        parent::__construct($attributes);
    }

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /** Dato propio de cada perfil que lo identifica (legajo, cédula...). */
    abstract public function identificador(): string;

    /** Texto breve que describe al perfil en el menú y los encabezados. */
    abstract public function descripcion(): string;

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function nombre(): string
    {
        return (string) $this->usuario?->nombre;
    }

    public function cedula(): string
    {
        return (string) $this->usuario?->cedula;
    }

    public function estaActivo(): bool
    {
        return (bool) $this->activo;
    }

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('activo', true);
    }

    public function scopeInactivos(Builder $consulta): Builder
    {
        return $consulta->where('activo', false);
    }
}
