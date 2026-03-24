<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\StoreMedicalReportTemplateRequest;

final readonly class CreateMedicalReportTemplateDTO
{
    public function __construct(
        public string $nome,
        public string $corpoTemplate,
    ) {}

    public static function fromRequest(StoreMedicalReportTemplateRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            nome: $validated['name'],
            corpoTemplate: $validated['body_template'],
        );
    }
}
