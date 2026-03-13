<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use App\Modules\MedicalRecord\Models\MedidaAntropometrica;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MedidaAntropometrica
 */
final class AnthropometryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'blood_pressure' => [
                'right_arm' => [
                    'sitting' => $this->bpReading($this->pa_sentado_d_pas, $this->pa_sentado_d_pad),
                    'standing' => $this->bpReading($this->pa_em_pe_d_pas, $this->pa_em_pe_d_pad),
                    'supine' => $this->bpReading($this->pa_deitado_d_pas, $this->pa_deitado_d_pad),
                ],
                'left_arm' => [
                    'sitting' => $this->bpReading($this->pa_sentado_e_pas, $this->pa_sentado_e_pad),
                    'standing' => $this->bpReading($this->pa_em_pe_e_pas, $this->pa_em_pe_e_pad),
                    'supine' => $this->bpReading($this->pa_deitado_e_pas, $this->pa_deitado_e_pad),
                ],
                'heart_rate' => $this->fc,
                'oxygen_sat' => $this->spo2,
                'temperature' => $this->temperatura,
            ],
            'measures' => [
                'weight' => $this->peso,
                'height' => $this->altura,
                'bmi' => $this->imc,
                'bmi_classification' => $this->classificacao_imc,
                'abdominal_circumference' => $this->circunferencia_abdominal,
                'hip_circumference' => $this->circunferencia_quadril,
                'waist_hip_ratio' => $this->relacao_cintura_quadril,
                'waist_height_ratio' => $this->relacao_cintura_altura,
                'cervical_circumference' => $this->circunferencia_pescoco,
                'waist_circumference' => $this->circunferencia_cintura,
                'calf_measurement_left' => $this->circunferencia_panturrilha_e,
                'calf_measurement_right' => $this->circunferencia_panturrilha_d,
                'mouth_opening' => $this->abertura_bucal,
                'thyromental_distance' => $this->distancia_tireomentual,
                'mentosternal_distance' => $this->distancia_mentoesternal,
                'mandible_displacement' => $this->deslocamento_mandibular,
            ],
            'skinfolds' => [
                'triceps' => $this->dobra_tricipital,
                'subscapular' => $this->dobra_subescapular,
                'suprailiac' => $this->dobra_suprailica,
                'abdominal' => $this->dobra_abdominal,
                'pectoral' => $this->dobra_peitoral,
                'medial_thigh' => $this->dobra_coxa,
                'midaxillary' => $this->dobra_axilar_media,
            ],
        ];
    }

    /**
     * @return array{systolic: int|null, diastolic: int|null}|null
     */
    private function bpReading(?int $systolic, ?int $diastolic): ?array
    {
        if ($systolic === null && $diastolic === null) {
            return null;
        }

        return [
            'systolic' => $systolic,
            'diastolic' => $diastolic,
        ];
    }
}
