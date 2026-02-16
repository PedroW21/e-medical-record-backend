<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Appointment\Models\Consulta
 */
final class AvailabilityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->data,
            'time' => $this->horario,
        ];
    }
}
