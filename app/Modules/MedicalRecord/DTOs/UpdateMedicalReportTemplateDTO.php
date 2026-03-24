<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\UpdateMedicalReportTemplateRequest;

final readonly class UpdateMedicalReportTemplateDTO
{
    public function __construct(
        public ?string $nome = null,
        public ?string $corpoTemplate = null,
    ) {}

    public static function fromRequest(UpdateMedicalReportTemplateRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            nome: $validated['name'] ?? null,
            corpoTemplate: $validated['body_template'] ?? null,
        );
    }
}
