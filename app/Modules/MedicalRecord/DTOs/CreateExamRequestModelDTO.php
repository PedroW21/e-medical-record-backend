<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\StoreExamRequestModelRequest;

final readonly class CreateExamRequestModelDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $itens
     */
    public function __construct(
        public string $nome,
        public array $itens,
        public ?string $categoria = null,
    ) {}

    public static function fromRequest(StoreExamRequestModelRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            nome: $validated['name'],
            itens: $validated['items'],
            categoria: $validated['category'] ?? null,
        );
    }
}
