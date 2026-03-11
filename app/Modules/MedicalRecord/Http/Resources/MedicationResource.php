<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\Medicamento
 */
final class MedicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nome,
            'active_ingredient' => $this->principio_ativo,
            'presentation' => $this->apresentacao,
            'manufacturer' => $this->fabricante,
            'anvisa_code' => $this->codigo_anvisa,
            'anvisa_list' => $this->lista_anvisa,
            'is_controlled' => $this->controlado,
        ];
    }
}
