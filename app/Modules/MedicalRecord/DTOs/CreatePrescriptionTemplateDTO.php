<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Http\Requests\StorePrescriptionTemplateRequest;

final readonly class CreatePrescriptionTemplateDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $itens
     * @param  array<int, string>|null  $tags
     */
    public function __construct(
        public string $nome,
        public PrescriptionSubType $subtipo,
        public array $itens,
        public ?array $tags = null,
    ) {}

    public static function fromRequest(StorePrescriptionTemplateRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            nome: $validated['name'],
            subtipo: PrescriptionSubType::from($validated['subtype']),
            itens: $validated['items'],
            tags: $validated['tags'] ?? null,
        );
    }
}
