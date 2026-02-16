<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Actions;

use App\Modules\Appointment\DTOs\UpdateAppointmentDTO;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Services\AppointmentService;

final class UpdateAppointmentAction
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    public function execute(Consulta $appointment, UpdateAppointmentDTO $dto): Consulta
    {
        $date = $dto->date ?? $appointment->data;
        $time = $dto->time ?? $appointment->horario;

        if ($dto->date !== null || $dto->time !== null) {
            $this->appointmentService->checkWorkingHours(
                $appointment->user_id,
                $date,
                $time,
            );
            $this->appointmentService->checkTimeConflict(
                $appointment->user_id,
                $date,
                $time,
                $appointment->id,
            );
        }

        $appointment->update(array_filter([
            'paciente_id' => $dto->patientId,
            'data' => $dto->date,
            'horario' => $dto->time,
            'tipo' => $dto->type,
            'observacoes' => $dto->notes,
        ], fn ($value) => $value !== null));

        return $appointment->refresh();
    }
}
