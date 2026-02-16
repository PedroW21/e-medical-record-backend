<?php

declare(strict_types=1);

namespace App\Modules\Appointment\DTOs;

use App\Modules\Appointment\Enums\AppointmentType;
use App\Modules\Appointment\Http\Requests\UpdateAppointmentRequest;

final readonly class UpdateAppointmentDTO
{
    public function __construct(
        public ?int $patientId,
        public ?string $date,
        public ?string $time,
        public ?AppointmentType $type,
        public ?string $notes,
    ) {}

    public static function fromRequest(UpdateAppointmentRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            patientId: array_key_exists('patient_id', $validated) ? $validated['patient_id'] : null,
            date: $validated['date'] ?? null,
            time: $validated['time'] ?? null,
            type: isset($validated['type']) ? AppointmentType::from($validated['type']) : null,
            notes: array_key_exists('notes', $validated) ? $validated['notes'] : null,
        );
    }
}
