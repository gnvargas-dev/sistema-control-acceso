<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    use HasFactory;

    protected $table = 'cursos';

    protected $fillable = [
        'nombre',
        'anio',
    ];

    /**
     * Todas las consultas a Curso quedan ordenadas automáticamente
     * de forma natural (1°A, 1°B, 2°A, 2°B, ...) sin tener que repetir
     * el orderBy en cada controlador o vista.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('orden_natural', function ($query) {
            $query->orderBy('anio')->orderBy('nombre');
        });
    }

    /**
     * Evita cursos "duplicados" por espacios en blanco o diferencias de
     * mayúsculas al guardar (por ejemplo "A" y "A " o "a" quedarían
     * como filas distintas aunque representen el mismo curso).
     */
    protected function nombre(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => is_string($value) ? strtoupper(trim($value)) : $value,
        );
    }

    public function materias(): HasMany
    {
        return $this->hasMany(Materia::class);
    }

    public function alumnos(): HasMany
    {
        return $this->hasMany(Alumno::class);
    }
}