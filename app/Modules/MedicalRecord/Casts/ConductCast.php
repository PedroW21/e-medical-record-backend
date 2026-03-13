<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Casts;

use App\Modules\MedicalRecord\DTOs\ConductData;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<ConductData, ConductData|array<string, mixed>>
 */
final class ConductCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?ConductData
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);

        return ConductData::fromArray($data);
    }

    /**
     * @param  ConductData|array<string, mixed>|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ConductData) {
            return json_encode($value->toArray());
        }

        return json_encode($value);
    }
}
