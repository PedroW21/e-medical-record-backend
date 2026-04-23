<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Models\InjetavelProtocolo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InjetavelProtocolo
 */
final class InjectableProtocolResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pharmacy_id' => $this->farmacia_id,
            'therapeutic_category_id' => $this->categoria_terapeutica_id,
            'name' => $this->nome,
            'route' => $this->via->value,
            'application_notes' => $this->notas_aplicacao,
            'components' => $this->whenLoaded(
                'componentes',
                fn () => $this->componentes->map(fn ($component): array => [
                    'order' => $component->ordem,
                    'farmaco_name' => $component->nome_farmaco,
                    'dosage' => $component->dosagem,
                    'ampoule_count' => $component->quantidade_ampolas,
                    'route' => $component->via,
                ])->all(),
            ),
        ];
    }
}
