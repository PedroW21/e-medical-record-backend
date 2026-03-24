<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\StoreExamRequestRequest;

final readonly class CreateExamRequestDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $itens
     * @param  array<string, mixed>|null  $relatorioMedico
     */
    public function __construct(
        public array $itens,
        public ?string $modeloId = null,
        public ?string $cid10 = null,
        public ?string $indicacaoClinica = null,
        public ?array $relatorioMedico = null,
    ) {}

    public static function fromRequest(StoreExamRequestRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            itens: $validated['items'],
            modeloId: $validated['model_id'] ?? null,
            cid10: $validated['cid_10'] ?? null,
            indicacaoClinica: $validated['clinical_indication'] ?? null,
            relatorioMedico: $validated['medical_report'] ?? null,
        );
    }
}
