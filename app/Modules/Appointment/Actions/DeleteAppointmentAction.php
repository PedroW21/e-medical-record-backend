<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Actions;

use App\Modules\Appointment\Models\Consulta;

final class DeleteAppointmentAction
{
    public function execute(Consulta $appointment): void
    {
        $appointment->delete();
    }
}
