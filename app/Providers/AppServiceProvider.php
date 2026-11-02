<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Nota;
use App\Models\Alumno;
use App\Models\Asistencia;
use App\Policies\NotaPolicy;
use App\Policies\AlumnoPolicy;
use App\Policies\AsistenciaPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Nota::class, NotaPolicy::class);
        Gate::policy(Asistencia::class, AsistenciaPolicy::class);
        Gate::policy(Alumno::class, AlumnoPolicy::class);
    }
}
