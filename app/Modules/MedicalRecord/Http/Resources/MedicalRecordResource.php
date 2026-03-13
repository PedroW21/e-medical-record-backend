<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Prontuario
 */
final class MedicalRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->paciente_id,
            'doctor_id' => $this->user_id,
            'type' => $this->tipo->value,
            'status' => $this->status->value,
            'based_on_record_id' => $this->baseado_em_prontuario_id,
            'anthropometry' => $this->whenLoaded('medidaAntropometrica', fn () => $this->medidaAntropometrica
                ? new AnthropometryResource($this->medidaAntropometrica)
                : null
            ),
            'physical_exam' => $this->exame_fisico?->toArray(),
            'problem_list' => $this->lista_problemas?->toArray(),
            'risk_scores' => $this->escores_risco?->toArray(),
            'conduct' => $this->conduta?->toArray(),
            'finalized_at' => $this->finalizado_em?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
