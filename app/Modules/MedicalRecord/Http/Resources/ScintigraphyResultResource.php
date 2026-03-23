<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use App\Modules\MedicalRecord\Enums\ExamType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\ResultadoCintilografia
 */
final class ScintigraphyResultResource extends JsonResource
{
    use ExamResultFieldMap;

    /**
     * Builds a nullable territory perfusion object from flat DB columns.
     *
     * @return array{stress: string|null, rest: string|null, reversibility: string|null}|null
     */
    private function territoryPerfusion(
        ?string $stress,
        ?string $rest,
        ?string $reversibility,
    ): ?array {
        if ($stress === null && $rest === null && $reversibility === null) {
            return null;
        }

        return [
            'stress' => $stress,
            'rest' => $rest,
            'reversibility' => $reversibility,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $perfusionKeys = [
            'perfusion_da.stress', 'perfusion_da.rest', 'perfusion_da.reversibility',
            'perfusion_cx.stress', 'perfusion_cx.rest', 'perfusion_cx.reversibility',
            'perfusion_cd.stress', 'perfusion_cd.rest', 'perfusion_cd.reversibility',
        ];

        $map = self::dbToApiMap(ExamType::Scintigraphy);

        $base = [
            'id' => $this->id,
            'medical_record_id' => $this->prontuario_id,
            'patient_id' => $this->paciente_id,
            'date' => $this->data->format('Y-m-d'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        foreach ($map as $dbCol => $apiField) {
            if ($apiField === 'date' || in_array($apiField, $perfusionKeys, true)) {
                continue;
            }
            $base[$apiField] = $this->{$dbCol};
        }

        $base['perfusion_da'] = $this->territoryPerfusion(
            $this->perfusao_da_estresse,
            $this->perfusao_da_repouso,
            $this->perfusao_da_reversibilidade,
        );

        $base['perfusion_cx'] = $this->territoryPerfusion(
            $this->perfusao_cx_estresse,
            $this->perfusao_cx_repouso,
            $this->perfusao_cx_reversibilidade,
        );

        $base['perfusion_cd'] = $this->territoryPerfusion(
            $this->perfusao_cd_estresse,
            $this->perfusao_cd_repouso,
            $this->perfusao_cd_reversibilidade,
        );

        return $base;
    }
}
