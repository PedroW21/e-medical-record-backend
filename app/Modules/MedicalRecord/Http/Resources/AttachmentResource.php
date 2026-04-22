<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use App\Modules\MedicalRecord\Models\Anexo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @mixin Anexo
 */
class AttachmentResource extends JsonResource
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
            'attachment_type' => $this->tipo_anexo->value,
            'name' => $this->nome,
            'file_type' => $this->tipo_arquivo->value,
            'file_url' => URL::temporarySignedRoute(
                'attachments.download',
                now()->addMinutes(30),
                ['id' => $this->id]
            ),
            'file_size' => $this->tamanho_bytes,
            'processing_status' => $this->status_processamento?->value,
            'extracted_data' => $this->dados_extraidos,
            'processing_error' => $this->erro_processamento,
            'processed_at' => $this->processado_em?->toIso8601String(),
            'confirmed_at' => $this->confirmado_em?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
