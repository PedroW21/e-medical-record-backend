<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Actions;

use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Services\AppointmentService;

final class UpdateAppointmentStatusAction
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    public function execute(Consulta $appointment, AppointmentStatus $newStatus): Consulta
    {
        if (in_array($newStatus, AppointmentStatus::blockingStatuses(), true)
            && ! in_array($appointment->status, AppointmentStatus::blockingStatuses(), true)) {
            $this->appointmentService->checkWorkingHours(
                $appointment->user_id,
                $appointment->data,
                $appointment->horario,
            );
            $this->appointmentService->checkTimeConflict(
                $appointment->user_id,
                $appointment->data,
                $appointment->horario,
                $appointment->id,
            );
        }

        $appointment->update(['status' => $newStatus]);

        return $appointment->refresh();
    }
}
