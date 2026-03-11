<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Http\Requests\UpdatePrescriptionRequest;

final readonly class UpdatePrescriptionDTO
{
    /**
     * @param  array<int, array<string, mixed>>|null  $itens
     */
    public function __construct(
        public ?PrescriptionSubType $subtipo = null,
        public ?array $itens = null,
        public ?string $observacoes = null,
        public bool $hasObservacoes = false,
        public ?bool $tipoReceitaOverride = null,
        public ?string $tipoReceitaManual = null,
    ) {}

    public static function fromRequest(UpdatePrescriptionRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            subtipo: isset($validated['subtype']) ? PrescriptionSubType::from($validated['subtype']) : null,
            itens: $validated['items'] ?? null,
            observacoes: array_key_exists('notes', $validated) ? $validated['notes'] : null,
            hasObservacoes: array_key_exists('notes', $validated),
            tipoReceitaOverride: isset($validated['recipe_type_override']) ? (bool) $validated['recipe_type_override'] : null,
            tipoReceitaManual: $validated['recipe_type'] ?? null,
        );
    }
}
