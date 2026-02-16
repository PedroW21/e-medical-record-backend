<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Delegation\Models\Delegacao
 */
final class DelegationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor' => [
                'id' => $this->medico->id,
                'name' => $this->medico->name,
                'specialty' => $this->medico->specialty,
            ],
            'secretary' => [
                'id' => $this->secretaria->id,
                'name' => $this->secretaria->name,
            ],
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
