<?php

declare(strict_types=1);

namespace App\Modules\Appointment\DTOs;

use App\Modules\Appointment\Http\Requests\BookPublicAppointmentRequest;

final readonly class BookPublicAppointmentDTO
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $email,
        public ?string $notes,
        public string $date,
        public string $time,
    ) {}

    public static function fromRequest(BookPublicAppointmentRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['nome'],
            phone: $validated['telefone'],
            email: $validated['email'],
            notes: $validated['observacoes'] ?? null,
            date: $validated['data'],
            time: $validated['horario'],
        );
    }
}
