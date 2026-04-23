<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Models\Injetavel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Injetavel
 */
final class InjectableResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pharmacy_id' => $this->farmacia_id,
            'name' => $this->nome,
            'dosage' => $this->dosagem,
            'volume' => $this->volume,
            'exclusive_route' => $this->via_exclusiva,
            'composition' => $this->composicao,
            'is_blend' => $this->is_blend,
            'allowed_routes' => $this->vias_permitidas,
        ];
    }
}
