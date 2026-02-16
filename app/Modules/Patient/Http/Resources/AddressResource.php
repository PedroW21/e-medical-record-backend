<?php

declare(strict_types=1);

namespace App\Modules\Patient\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Patient\Models\Endereco
 */
final class AddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'cep' => $this->cep,
            'street' => $this->logradouro,
            'number' => $this->numero,
            'complement' => $this->complemento,
            'neighborhood' => $this->bairro,
            'city' => $this->cidade,
            'state' => $this->estado,
        ];
    }
}
