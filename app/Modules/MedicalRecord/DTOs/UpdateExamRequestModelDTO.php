<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\UpdateExamRequestModelRequest;

final readonly class UpdateExamRequestModelDTO
{
    /**
     * @param  array<int, array<string, mixed>>|null  $itens
     */
    public function __construct(
        public ?string $nome = null,
        public ?string $categoria = null,
        public ?array $itens = null,
    ) {}

    public static function fromRequest(UpdateExamRequestModelRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            nome: $validated['name'] ?? null,
            categoria: $validated['category'] ?? null,
            itens: $validated['items'] ?? null,
        );
    }
}
