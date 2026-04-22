<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Providers;

use App\Modules\MedicalRecord\Enums\ExamType;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\ModeloPrescricao;
use App\Modules\MedicalRecord\Models\ModeloRelatorioMedico;
use App\Modules\MedicalRecord\Models\ModeloSolicitacaoExame;
use App\Modules\MedicalRecord\Models\Prescricao;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\SolicitacaoExame;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;
use App\Modules\MedicalRecord\Policies\AttachmentPolicy;
use App\Modules\MedicalRecord\Policies\ExamRequestModelPolicy;
use App\Modules\MedicalRecord\Policies\ExamRequestPolicy;
use App\Modules\MedicalRecord\Policies\ExamResultPolicy;
use App\Modules\MedicalRecord\Policies\LabResultPolicy;
use App\Modules\MedicalRecord\Policies\MedicalRecordPolicy;
use App\Modules\MedicalRecord\Policies\MedicalReportTemplatePolicy;
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
        Gate::policy(SolicitacaoExame::class, ExamRequestPolicy::class);
        Gate::policy(ModeloSolicitacaoExame::class, ExamRequestModelPolicy::class);
        Gate::policy(ModeloRelatorioMedico::class, MedicalReportTemplatePolicy::class);
        Gate::policy(Anexo::class, AttachmentPolicy::class);

        foreach (ExamType::cases() as $examType) {
            Gate::policy($examType->modelClass(), ExamResultPolicy::class);
        }
    }
}
