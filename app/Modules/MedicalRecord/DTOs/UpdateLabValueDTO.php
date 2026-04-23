<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\UpdateLabValueRequest;

final readonly class UpdateLabValueDTO
{
    public function __construct(
        public ?string $value = null,
        public ?string $unit = null,
        public ?string $referenceRange = null,
        public ?string $collectionDate = null,
        public bool $hasReferenceRange = false,
        public ?int $anexoId = null,
        public bool $hasAnexoId = false,
    ) {}

    public static function fromRequest(UpdateLabValueRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            value: $validated['value'] ?? null,
            unit: $validated['unit'] ?? null,
            referenceRange: $validated['reference_range'] ?? null,
            collectionDate: $validated['collection_date'] ?? null,
            hasReferenceRange: $request->has('reference_range'),
            anexoId: $validated['anexo_id'] ?? null,
            hasAnexoId: $request->has('anexo_id'),
        );
    }
}
