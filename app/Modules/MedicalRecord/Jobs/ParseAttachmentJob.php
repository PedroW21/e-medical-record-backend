<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Jobs;

use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Enums\ProcessingStatus;
use App\Modules\MedicalRecord\Events\AttachmentParseCompleted;
use App\Modules\MedicalRecord\Events\AttachmentParseFailed;
use App\Modules\MedicalRecord\Models\Anexo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ParseAttachmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $attachmentId) {}

    public function handle(): void
    {
        $attachment = Anexo::query()->find($this->attachmentId);

        if ($attachment === null) {
            return;
        }

        if (! $attachment->isParseable()) {
            return;
        }

        $attachment->update([
            'status_processamento' => ProcessingStatus::Processing,
        ]);

        try {
            $mock = $this->mockExtractionFor($attachment->tipo_anexo);

            $attachment->update([
                'status_processamento' => ProcessingStatus::Completed,
                'dados_extraidos' => $mock,
                'erro_processamento' => null,
                'processado_em' => now(),
            ]);

            AttachmentParseCompleted::dispatch($attachment->fresh());
        } catch (Throwable $e) {
            $attachment->update([
                'status_processamento' => ProcessingStatus::Failed,
                'erro_processamento' => $e->getMessage(),
                'processado_em' => now(),
            ]);

            AttachmentParseFailed::dispatch($attachment->fresh());
        }
    }

    /**
     * Produce a mocked `dados_extraidos` payload shaped to the attachment type.
     * Replace with real AI integration in a later PR.
     *
     * @return array<string, mixed>
     */
    private function mockExtractionFor(AttachmentType $tipo): array
    {
        $today = now()->toDateString();

        return match ($tipo) {
            AttachmentType::Ecg => ['date' => $today, 'pattern' => 'normal'],
            AttachmentType::Rx => ['date' => $today, 'pattern' => 'normal'],
            AttachmentType::Eco => ['date' => $today, 'type' => 'transthoracic', 'ef' => 60],
            AttachmentType::Dexa => ['date' => $today, 'bmd' => 1.1, 't_score' => -0.5],
            AttachmentType::Lab => ['date' => $today, 'panels' => [], 'loose' => []],
            AttachmentType::Mapa => ['date' => $today, 'systolic_awake' => 128, 'diastolic_awake' => 82],
            AttachmentType::Mrpa => ['date' => $today, 'days_monitored' => 7, 'limb' => 'right_arm', 'measurements' => []],
            AttachmentType::Holter, AttachmentType::Polissonografia => ['date' => $today, 'text' => 'Stub extraction — replace with AI output.'],
            default => ['date' => $today, 'raw' => 'Stub extraction — replace with AI output.'],
        };
    }
}
