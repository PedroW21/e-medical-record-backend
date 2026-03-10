<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\UpdatePrescriptionTemplateRequest;

final readonly class UpdatePrescriptionTemplateDTO
{
    /**
     * @param  array<int, array<string, mixed>>|null  $itens
     * @param  array<int, string>|null  $tags
     */
    public function __construct(
        public ?string $nome = null,
        public ?array $itens = null,
        public ?array $tags = null,
    ) {}

    public static function fromRequest(UpdatePrescriptionTemplateRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            nome: $validated['name'] ?? null,
            itens: $validated['items'] ?? null,
            tags: array_key_exists('tags', $validated) ? $validated['tags'] : null,
        );
    }
}
