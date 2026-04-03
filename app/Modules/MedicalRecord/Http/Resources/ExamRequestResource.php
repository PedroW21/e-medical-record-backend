<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\SolicitacaoExame
 */
final class ExamRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medical_record_id' => $this->prontuario_id,
            'model_id' => $this->modelo_id,
            'cid_10' => $this->cid_10,
            'clinical_indication' => $this->indicacao_clinica,
            'items' => $this->itens,
            'medical_report' => $this->relatorio_medico,
            'printed_at' => $this->impresso_em,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
