<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\Prescricao
 */
final class PrescriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medical_record_id' => $this->prontuario_id,
            'subtype' => $this->subtipo?->value,
            'recipe_type' => $this->tipo_receita?->value,
            'recipe_type_override' => $this->tipo_receita_override,
            'items' => $this->itens,
            'notes' => $this->observacoes,
            'printed_at' => $this->impresso_em,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
