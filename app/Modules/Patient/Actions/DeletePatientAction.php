<?php

declare(strict_types=1);

namespace App\Modules\Patient\Actions;

use App\Modules\Patient\Models\Paciente;

final class DeletePatientAction
{
    public function execute(Paciente $patient): void
    {
        $patient->delete();
    }
}
