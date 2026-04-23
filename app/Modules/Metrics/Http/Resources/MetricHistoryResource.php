<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Http\Resources;

use App\Modules\Metrics\DTOs\MetricDefinition;
use App\Modules\Metrics\DTOs\MetricPoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MetricHistoryResource extends JsonResource
{
    /**
     * @param  array{definition: MetricDefinition, history: array<int, MetricPoint>}  $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var MetricDefinition $definition */
        $definition = $this->resource['definition'];
        /** @var array<int, MetricPoint> $history */
        $history = $this->resource['history'];

        return [
            'metric_id' => $definition->id,
            'metric_name' => $definition->name,
            'unit' => $definition->unit,
            'ref_min' => $definition->refMin,
            'ref_max' => $definition->refMax,
            'color' => $definition->color,
            'history' => array_map(
                fn (MetricPoint $point): array => [
                    'date' => $point->date,
                    'value' => $point->value,
                ],
                $history,
            ),
        ];
    }
}
