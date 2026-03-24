<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\UpdateExamRequestRequest;

final readonly class UpdateExamRequestDTO
{
    /**
     * @param  array<int, array<string, mixed>>|null  $itens
     * @param  array<string, mixed>|null  $relatorioMedico
     */
    public function __construct(
        public ?string $modeloId = null,
        public ?string $cid10 = null,
        public ?string $indicacaoClinica = null,
        public ?array $itens = null,
        public ?array $relatorioMedico = null,
    ) {}

    public static function fromRequest(UpdateExamRequestRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            modeloId: $validated['model_id'] ?? null,
            cid10: $validated['cid_10'] ?? null,
            indicacaoClinica: $validated['clinical_indication'] ?? null,
            itens: $validated['items'] ?? null,
            relatorioMedico: $validated['medical_report'] ?? null,
        );
    }
}
