<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Enums\MedicalRecordType;
use App\Modules\MedicalRecord\Http\Requests\StoreMedicalRecordRequest;

final readonly class CreateMedicalRecordDTO
{
    /**
     * @param  array<string, mixed>|null  $anthropometry
     * @param  array<string, mixed>|null  $physicalExam
     * @param  array<string, mixed>|null  $problemList
     * @param  array<string, mixed>|null  $riskScores
     * @param  array<string, mixed>|null  $conduct
     */
    public function __construct(
        public int $patientId,
        public MedicalRecordType $type,
        public ?array $anthropometry = null,
        public ?array $physicalExam = null,
        public ?array $problemList = null,
        public ?array $riskScores = null,
        public ?array $conduct = null,
        public ?int $basedOnRecordId = null,
    ) {}

    public static function fromRequest(StoreMedicalRecordRequest $request): self
    {
        return new self(
            patientId: (int) $request->validated('patient_id'),
            type: MedicalRecordType::from($request->validated('type')),
            anthropometry: $request->validated('anthropometry'),
            physicalExam: $request->validated('physical_exam'),
            problemList: $request->validated('problem_list'),
            riskScores: $request->validated('risk_scores'),
            conduct: $request->validated('conduct'),
            basedOnRecordId: $request->validated('based_on_record_id') !== null
                ? (int) $request->validated('based_on_record_id')
                : null,
        );
    }
}
