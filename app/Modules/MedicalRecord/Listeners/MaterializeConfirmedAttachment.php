<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Listeners;

use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Events\AttachmentConfirmed;
use App\Modules\MedicalRecord\Services\ExamResultService;
use App\Modules\MedicalRecord\Services\LabResultService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class MaterializeConfirmedAttachment implements ShouldQueue
{
    public int $tries = 1;

    public function __construct(
        private readonly ExamResultService $examResultService,
        private readonly LabResultService $labResultService,
    ) {}

    /**
     * Materialise the confirmed attachment's extracted data into structured
     * exam-result or lab-analyte rows.
     *
     * No-ops for attachment types that do not materialise (`documento`, `outro`)
     * or when the extracted payload is empty.
     */
    public function handle(AttachmentConfirmed $event): void
    {
        $attachment = $event->attachment;

        if ($attachment->tipo_anexo === AttachmentType::Documento
            || $attachment->tipo_anexo === AttachmentType::Outro) {
            return;
        }

        $payload = $attachment->dados_extraidos ?? [];

        if ($payload === []) {
            return;
        }

        if ($attachment->tipo_anexo->isLabType()) {
            $this->labResultService->materializeFromAttachment(
                medicalRecordId: $attachment->prontuario_id,
                anexoId: $attachment->id,
                payload: $payload,
            );

            return;
        }

        $examType = $attachment->tipo_anexo->toExamType();

        if ($examType === null) {
            return;
        }

        $this->examResultService->materializeFromAttachment(
            medicalRecordId: $attachment->prontuario_id,
            examType: $examType,
            anexoId: $attachment->id,
            payload: $payload,
        );
    }
}
