<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use App\Modules\MedicalRecord\Enums\ExamType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\ResultadoTextoLivre
 */
final class FreeTextResultResource extends JsonResource
{
    use ExamResultFieldMap;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = [
            'id' => $this->id,
            'medical_record_id' => $this->prontuario_id,
            'patient_id' => $this->paciente_id,
            'date' => $this->data->format('Y-m-d'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        $map = self::dbToApiMap(ExamType::FreeText);
        foreach ($map as $dbCol => $apiField) {
            if ($apiField === 'date') {
                continue;
            }
            $base[$apiField] = $this->{$dbCol};
        }

        return $base;
    }
}
