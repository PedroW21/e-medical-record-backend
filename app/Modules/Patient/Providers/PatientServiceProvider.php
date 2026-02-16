<?php

declare(strict_types=1);

namespace App\Modules\Patient\Providers;

use App\Modules\Patient\Models\Paciente;
use App\Modules\Patient\Policies\PatientPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class PatientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Paciente::class, PatientPolicy::class);
    }
}
