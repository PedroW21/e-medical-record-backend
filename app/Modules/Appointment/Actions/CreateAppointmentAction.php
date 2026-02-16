<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Actions;

use App\Modules\Appointment\DTOs\CreateAppointmentDTO;
use App\Modules\Appointment\Enums\AppointmentOrigin;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Services\AppointmentService;

final class CreateAppointmentAction
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    public function execute(int $doctorId, CreateAppointmentDTO $dto): Consulta
    {
        $this->appointmentService->checkTimeConflict($doctorId, $dto->date, $dto->time);

        return Consulta::query()->create([
            'user_id' => $doctorId,
            'paciente_id' => $dto->patientId,
            'data' => $dto->date,
            'horario' => $dto->time,
            'tipo' => $dto->type,
            'status' => AppointmentStatus::Pending,
            'observacoes' => $dto->notes,
            'origem' => AppointmentOrigin::Internal,
        ]);
    }
}
