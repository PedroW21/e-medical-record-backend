<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Casts;

use App\Modules\MedicalRecord\DTOs\RiskScoresData;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<RiskScoresData, RiskScoresData|array<string, mixed>>
 */
final class RiskScoresCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?RiskScoresData
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);

        return RiskScoresData::fromArray($data);
    }

    /**
     * @param  RiskScoresData|array<string, mixed>|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof RiskScoresData) {
            return json_encode($value->toArray());
        }

        return json_encode($value);
    }
}
