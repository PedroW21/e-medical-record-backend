<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Http\Requests\StorePrescriptionRequest;

final readonly class CreatePrescriptionDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $itens
     */
    public function __construct(
        public PrescriptionSubType $subtipo,
        public array $itens,
        public ?string $observacoes = null,
        public bool $tipoReceitaOverride = false,
        public ?string $tipoReceitaManual = null,
    ) {}

    public static function fromRequest(StorePrescriptionRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            subtipo: PrescriptionSubType::from($validated['subtype']),
            itens: $validated['items'],
            observacoes: $validated['notes'] ?? null,
            tipoReceitaOverride: (bool) ($validated['recipe_type_override'] ?? false),
            tipoReceitaManual: $validated['recipe_type'] ?? null,
        );
    }
}
