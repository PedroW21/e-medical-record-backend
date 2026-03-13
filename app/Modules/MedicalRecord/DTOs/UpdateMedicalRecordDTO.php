<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\UpdateMedicalRecordRequest;

final readonly class UpdateMedicalRecordDTO
{
    /**
     * @param  array<string, mixed>|null  $anthropometry
     * @param  array<string, mixed>|null  $physicalExam
     * @param  array<string, mixed>|null  $problemList
     * @param  array<string, mixed>|null  $riskScores
     * @param  array<string, mixed>|null  $conduct
     */
    public function __construct(
        public ?array $anthropometry = null,
        public ?array $physicalExam = null,
        public ?array $problemList = null,
        public ?array $riskScores = null,
        public ?array $conduct = null,
    ) {}

    public static function fromRequest(UpdateMedicalRecordRequest $request): self
    {
        return new self(
            anthropometry: $request->validated('anthropometry'),
            physicalExam: $request->validated('physical_exam'),
            problemList: $request->validated('problem_list'),
            riskScores: $request->validated('risk_scores'),
            conduct: $request->validated('conduct'),
        );
    }
}
