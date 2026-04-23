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
            'materialized' => $this->resolveMaterializedReference(),
            'lab_analytes_count' => $this->tipo_anexo->isLabType()
                ? (int) $this->valoresLaboratoriais()->count()
                : null,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Build a compact reference to the exam-result row materialised from this attachment (if any).
     *
     * @return array{id: int, exam_type: string}|null
     */
    private function resolveMaterializedReference(): ?array
    {
        $relation = $this->materializedResult();

        if ($relation === null) {
            return null;
        }

        $result = $relation->first();

        if ($result === null) {
            return null;
        }

        $examType = $this->tipo_anexo->toExamType();

        return [
            'id' => $result->id,
            'exam_type' => $examType?->value ?? '',
        ];
    }
}
