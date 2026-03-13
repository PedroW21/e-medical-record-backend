<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Providers;

use App\Modules\MedicalRecord\Models\ModeloPrescricao;
use App\Modules\MedicalRecord\Models\Prescricao;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;
use App\Modules\MedicalRecord\Policies\LabResultPolicy;
use App\Modules\MedicalRecord\Policies\MedicalRecordPolicy;
use App\Modules\MedicalRecord\Policies\PrescriptionPolicy;
use App\Modules\MedicalRecord\Policies\PrescriptionTemplatePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class MedicalRecordServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Prontuario::class, MedicalRecordPolicy::class);
        Gate::policy(Prescricao::class, PrescriptionPolicy::class);
        Gate::policy(ModeloPrescricao::class, PrescriptionTemplatePolicy::class);
        Gate::policy(ValorLaboratorial::class, LabResultPolicy::class);
    }
}
