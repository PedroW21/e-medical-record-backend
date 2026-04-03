<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\ModeloRelatorioMedico;

final class MedicalReportTemplatePolicy
{
    public function view(User $user, ModeloRelatorioMedico $template): bool
    {
        if ($template->user_id === null) {
            return true;
        }

        return $user->id === $template->user_id;
    }

    public function update(User $user, ModeloRelatorioMedico $template): bool
    {
        return $user->id === $template->user_id;
    }

    public function delete(User $user, ModeloRelatorioMedico $template): bool
    {
        return $user->id === $template->user_id;
    }
}
