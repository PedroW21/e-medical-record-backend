<?php

declare(strict_types=1);

namespace App\Modules\Appointment\DTOs;

use App\Modules\Appointment\Enums\AppointmentType;
use App\Modules\Appointment\Http\Requests\StoreAppointmentRequest;

final readonly class CreateAppointmentDTO
{
    public function __construct(
        public ?int $patientId,
        public string $date,
        public string $time,
        public AppointmentType $type,
        public ?string $notes,
        public ?int $doctorId,
    ) {}

    public static function fromRequest(StoreAppointmentRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            patientId: $validated['patient_id'] ?? null,
            date: $validated['date'],
            time: $validated['time'],
            type: AppointmentType::from($validated['type']),
            notes: $validated['notes'] ?? null,
            doctorId: $validated['doctor_id'] ?? null,
        );
    }
}
