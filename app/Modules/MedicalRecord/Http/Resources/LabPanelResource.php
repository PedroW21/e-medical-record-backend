<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\PainelLaboratorial
 */
final class LabPanelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nome,
            'category' => $this->categoria?->value,
            'subsections' => $this->subsecoes,
        ];
    }
}
