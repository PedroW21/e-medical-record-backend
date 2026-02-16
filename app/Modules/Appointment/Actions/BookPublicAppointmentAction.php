<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Actions;

use App\Modules\Appointment\DTOs\BookPublicAppointmentDTO;
use App\Modules\Appointment\Enums\AppointmentOrigin;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Enums\AppointmentType;
use App\Modules\Appointment\Events\PublicAppointmentRequested;
use App\Modules\Appointment\Models\Consulta;

final class BookPublicAppointmentAction
{
    public function execute(int $doctorId, BookPublicAppointmentDTO $dto): Consulta
    {
        $appointment = Consulta::query()->create([
            'user_id' => $doctorId,
            'paciente_id' => null,
            'data' => $dto->date,
            'horario' => $dto->time,
            'tipo' => AppointmentType::FirstConsultation,
            'status' => AppointmentStatus::Requested,
            'observacoes' => $dto->notes,
            'nome_solicitante' => $dto->name,
            'telefone_solicitante' => $dto->phone,
            'email_solicitante' => $dto->email,
            'origem' => AppointmentOrigin::Public,
        ]);

        event(new PublicAppointmentRequested($appointment));

        return $appointment;
    }
}
