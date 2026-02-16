<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Appointment\Models\Consulta
 */
final class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->user_id,
            'patient_id' => $this->paciente_id,
            'patient_name' => $this->paciente?->nome ?? $this->nome_solicitante,
            'date' => $this->data,
            'time' => $this->horario,
            'type' => $this->tipo->value,
            'type_label' => $this->tipo->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'origin' => $this->origem->value,
            'notes' => $this->observacoes,
            'requester_name' => $this->nome_solicitante,
            'requester_phone' => $this->telefone_solicitante,
            'requester_email' => $this->email_solicitante,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
