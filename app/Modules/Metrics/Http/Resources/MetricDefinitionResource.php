<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Http\Resources;

use App\Modules\Metrics\DTOs\MetricDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MetricDefinition
 */
final class MetricDefinitionResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     category: string,
     *     name: string,
     *     unit: string,
     *     ref_min: float|null,
     *     ref_max: float|null,
     *     color: string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'name' => $this->name,
            'unit' => $this->unit,
            'ref_min' => $this->refMin,
            'ref_max' => $this->refMax,
            'color' => $this->color,
        ];
    }
}
