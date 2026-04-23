<?php

declare(strict_types=1);

namespace App\Modules\Metrics\DTOs;

/**
 * Single datapoint in a metric evolution series.
 */
final readonly class MetricPoint
{
    public function __construct(
        public string $date,
        public float $value,
    ) {}
}
