<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Appointment\Models\HorarioAtendimento
 */
final class ScheduleSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->dia_semana->value,
            'day_label' => $this->dia_semana->label(),
            'start_time' => $this->hora_inicio,
            'end_time' => $this->hora_fim,
        ];
    }
}
