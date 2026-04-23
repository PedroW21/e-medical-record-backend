<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Models\MagistralFormula;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MagistralFormula
 */
final class MagistralFormulaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->categoria_id,
            'name' => $this->nome,
            'components' => $this->componentes,
            'excipient' => $this->excipiente,
            'posology' => $this->posologia,
            'instructions' => $this->instrucoes,
            'notes' => $this->notas,
        ];
    }
}
