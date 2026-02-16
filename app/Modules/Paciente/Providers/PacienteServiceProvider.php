<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Providers;

use App\Modules\Paciente\Models\Paciente;
use App\Modules\Paciente\Policies\PacientePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class PacienteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Paciente::class, PacientePolicy::class);
    }
}
