<?php

declare(strict_types=1);

namespace App\Models;

use App\Roles\Rol;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'cedula',
        'password',
        'nombre',
        'rol',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function alumno(): HasOne
    {
        return $this->hasOne(Alumno::class);
    }

    public function profesor(): HasOne
    {
        return $this->hasOne(Profesor::class);
    }

    public function notasCargadas(): HasMany
    {
        return $this->hasMany(Nota::class, 'cargado_por');
    }

    public function asistenciasCargadas(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'cargado_por');
    }

    /**
     * Mantiene la comparación exacta de siempre; ahora la resuelve el objeto Rol.
     */
    public function es(string ...$roles): bool
    {
        return Rol::tryDesde((string) $this->rol)?->esUnoDe(...$roles) ?? false;
    }

    /**
     * El rol como objeto (polimorfismo). Se llama rolObjeto() y no rol() para no
     * chocar con el atributo `rol`: si falta ese atributo, Eloquent buscaría
     * una relación con ese nombre.
     *
     * @throws \ValueError si usuarios.rol no es un rol válido
     */
    public function rolObjeto(): Rol
    {
        return Rol::desde((string) $this->rol);
    }

    /** Perfil del usuario según su rol (Alumno, Profesor o null para el administrador). */
    public function perfil(): ?Persona
    {
        return $this->rolObjeto()->perfilDe($this);
    }
}
