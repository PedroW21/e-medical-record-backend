<?php

declare(strict_types=1);

namespace App\Modules\Metrics\DTOs;

/**
 * Static definition of a metric exposed by the Metrics API.
 *
 * Mirrors the frontend `metricsConfig.ts` entries so the API response can
 * enrich history series with the same display metadata the frontend uses
 * (unit, reference range, color). `catalogoExameId` points at the
 * `catalogo_exames_laboratoriais` row that feeds values for this metric.
 */
final readonly class MetricDefinition
{
    public function __construct(
        public string $id,
        public string $category,
        public string $name,
        public string $unit,
        public ?float $refMin,
        public ?float $refMax,
        public string $color,
        public string $catalogoExameId,
    ) {}
}
