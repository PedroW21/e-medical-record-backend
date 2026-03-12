<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class RiskScoresData
{
    /**
     * @param array{
     *     rcri: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, criteria_met?: string[]|null, score_points?: int|null},
     *     acp_detsky: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, criteria_met?: string[]|null, score_points?: int|null},
     *     aub_has2: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, criteria_met?: string[]|null, score_points?: int|null},
     *     asa: string,
     *     nyha: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, criteria_met?: string[]|null, score_points?: int|null},
     *     met: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, criteria_met?: string[]|null, score_points?: int|null}
     * } $cardiovascular
     * @param array{
     *     respiratory_failure_risk: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, score_points?: int|null},
     *     pneumonia_risk: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, score_points?: int|null},
     *     ariscat: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, score_points?: int|null},
     *     stop_bang: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, score_points?: int|null},
     *     stop_bang_criteria?: array{snoring: bool, tired: bool, observed_apnea: bool, high_pressure: bool, bmi_over_35: bool, age_over_50: bool, neck_over_40: bool, male_gender: bool}|null
     * } $pulmonary
     * @param array{
     *     ckd_epi: array{method: string, creatinine?: float|null, cystatin_c?: float|null, gfr?: float|null, gfr_stage?: string|null, albuminuria?: float|null, albuminuria_category?: string|null, kdigo_risk?: string|null}
     * } $renal
     */
    public function __construct(
        public ?string $primaryDisease,
        public ?string $plannedSurgery,
        public array $cardiovascular,
        public array $pulmonary,
        public array $renal,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            primaryDisease: $data['primary_disease'] ?? null,
            plannedSurgery: $data['planned_surgery'] ?? null,
            cardiovascular: $data['cardiovascular'],
            pulmonary: $data['pulmonary'],
            renal: $data['renal'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'primary_disease' => $this->primaryDisease,
            'planned_surgery' => $this->plannedSurgery,
            'cardiovascular' => $this->cardiovascular,
            'pulmonary' => $this->pulmonary,
            'renal' => $this->renal,
        ];
    }
}
