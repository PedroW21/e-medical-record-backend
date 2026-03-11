<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Http\Requests\UpdatePrescriptionTemplateRequest;

final readonly class UpdatePrescriptionTemplateDTO
{
    /**
     * @param  array<int, array<string, mixed>>|null  $itens
     * @param  array<int, string>|null  $tags
     */
    public function __construct(
        public ?string $nome = null,
        public ?PrescriptionSubType $subtipo = null,
        public ?array $itens = null,
        public ?array $tags = null,
        public bool $hasTags = false,
    ) {}

    public static function fromRequest(UpdatePrescriptionTemplateRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            nome: $validated['name'] ?? null,
            subtipo: isset($validated['subtype']) ? PrescriptionSubType::from($validated['subtype']) : null,
            itens: $validated['items'] ?? null,
            tags: array_key_exists('tags', $validated) ? $validated['tags'] : null,
            hasTags: array_key_exists('tags', $validated),
        );
    }
}
