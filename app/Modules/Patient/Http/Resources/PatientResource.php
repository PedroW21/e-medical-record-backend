<?php

declare(strict_types=1);

namespace App\Modules\Patient\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Patient\Models\Paciente
 */
final class PatientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nome,
            'cpf' => $this->cpf,
            'phone' => $this->telefone,
            'email' => $this->email,
            'birth_date' => $this->data_nascimento->format('Y-m-d'),
            'gender' => $this->sexo->toFrontend(),
            'blood_type' => $this->tipo_sanguineo?->value,
            'allergies' => $this->alergias->pluck('nome')->toArray(),
            'chronic_conditions' => $this->condicoesCronicas->pluck('nome')->toArray(),
            'medical_history' => [
                'smoking' => $this->historico_tabagismo?->value ?? 'none',
                'alcohol' => $this->historico_alcool?->value ?? 'none',
            ],
            'last_visit' => $this->ultima_consulta?->toISOString(),
            'address' => $this->whenLoaded('endereco', fn () => $this->endereco ? new AddressResource($this->endereco) : null),
            'status' => $this->status->value,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
