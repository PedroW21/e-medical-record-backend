<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Events;

use App\Modules\MedicalRecord\Models\Anexo;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AttachmentConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Anexo $attachment) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('medical-records.'.$this->attachment->prontuario_id)];
    }

    public function broadcastAs(): string
    {
        return 'attachment.confirmed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->attachment->id,
            'medical_record_id' => $this->attachment->prontuario_id,
            'attachment_type' => $this->attachment->tipo_anexo->value,
            'processing_status' => $this->attachment->status_processamento?->value,
            'confirmed_at' => $this->attachment->confirmado_em?->toIso8601String(),
            'extracted_data' => $this->attachment->dados_extraidos,
        ];
    }
}
