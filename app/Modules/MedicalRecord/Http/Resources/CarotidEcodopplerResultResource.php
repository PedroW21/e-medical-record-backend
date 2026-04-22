<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\ResultadoEcodopplerCarotidas
 */
final class CarotidEcodopplerResultResource extends JsonResource
{
    /**
     * Builds a nullable artery measurement object from flat DB columns.
     *
     * @return array{intimal_thickness: float|null, stenosis_degree: float|null}|null
     */
    private function arteryMeasurement(float|string|null $intimalThickness, float|string|null $stenosisDegree): ?array
    {
        if ($intimalThickness === null && $stenosisDegree === null) {
            return null;
        }

        return [
            'intimal_thickness' => $intimalThickness !== null ? (float) $intimalThickness : null,
            'stenosis_degree' => $stenosisDegree !== null ? (float) $stenosisDegree : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medical_record_id' => $this->prontuario_id,
            'patient_id' => $this->paciente_id,
            'date' => $this->data->format('Y-m-d'),
            'common_carotid_left' => $this->arteryMeasurement(
                $this->espessura_intimal_carotida_comum_e,
                $this->grau_estenose_carotida_comum_e,
            ),
            'common_carotid_right' => $this->arteryMeasurement(
                $this->espessura_intimal_carotida_comum_d,
                $this->grau_estenose_carotida_comum_d,
            ),
            'external_carotid_left' => $this->arteryMeasurement(
                $this->espessura_intimal_carotida_externa_e,
                $this->grau_estenose_carotida_externa_e,
            ),
            'external_carotid_right' => $this->arteryMeasurement(
                $this->espessura_intimal_carotida_externa_d,
                $this->grau_estenose_carotida_externa_d,
            ),
            'bulb_internal_left' => $this->arteryMeasurement(
                $this->espessura_intimal_bulbo_interna_e,
                $this->grau_estenose_bulbo_interna_e,
            ),
            'bulb_internal_right' => $this->arteryMeasurement(
                $this->espessura_intimal_bulbo_interna_d,
                $this->grau_estenose_bulbo_interna_d,
            ),
            'vertebral_left' => $this->arteryMeasurement(
                $this->espessura_intimal_vertebral_e,
                $this->grau_estenose_vertebral_e,
            ),
            'vertebral_right' => $this->arteryMeasurement(
                $this->espessura_intimal_vertebral_d,
                $this->grau_estenose_vertebral_d,
            ),
            'observations' => $this->observacoes,
            'anexo_id' => $this->anexo_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
