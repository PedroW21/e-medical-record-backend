<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\ListMedicationRequest;

final readonly class MedicationFilterDTO
{
    public function __construct(
        public ?string $search = null,
        public ?bool $controlado = null,
        public int $perPage = 15,
    ) {}

    public static function fromRequest(ListMedicationRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            search: $validated['search'] ?? null,
            controlado: isset($validated['controlled']) ? (bool) $validated['controlled'] : null,
            perPage: (int) ($validated['per_page'] ?? 15),
        );
    }
}
