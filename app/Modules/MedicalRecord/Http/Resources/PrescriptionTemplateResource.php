<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\ModeloPrescricao
 */
final class PrescriptionTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nome,
            'subtype' => $this->subtipo?->value,
            'tags' => $this->tags,
            'items' => $this->itens,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
