<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use App\Modules\MedicalRecord\Enums\ExamType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\ResultadoEcocardiograma
 */
final class EchoResultResource extends JsonResource
{
    use ExamResultFieldMap;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $map = self::dbToApiMap(ExamType::Echo);

        $valveKeys = ['valve_aortic', 'valve_mitral', 'valve_tricuspid'];

        $base = [
            'id' => $this->id,
            'medical_record_id' => $this->prontuario_id,
            'patient_id' => $this->paciente_id,
            'date' => $this->data->format('Y-m-d'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        foreach ($map as $dbCol => $apiField) {
            if ($apiField === 'date' || in_array($apiField, $valveKeys, true)) {
                continue;
            }
            $base[$apiField] = $this->{$dbCol};
        }

        $base['valve_aortic'] = $this->valva_aortica;
        $base['valve_mitral'] = $this->valva_mitral;
        $base['valve_tricuspid'] = $this->valva_tricuspide;

        return $base;
    }
}
