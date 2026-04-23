<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\ValorLaboratorial
 */
final class LabResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medical_record_id' => $this->prontuario_id,
            'patient_id' => $this->paciente_id,
            'catalog_exam_id' => $this->catalogo_exame_id,
            'name' => $this->nome_avulso ?? $this->whenLoaded('catalogoExame', fn () => $this->catalogoExame?->nome),
            'collection_date' => $this->data_coleta->format('Y-m-d'),
            'value' => $this->valor,
            'numeric_value' => $this->valor_numerico,
            'unit' => $this->unidade,
            'reference_range' => $this->faixa_referencia,
            'panel_id' => $this->painel_id,
            'anexo_id' => $this->anexo_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
