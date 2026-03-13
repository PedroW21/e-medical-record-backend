<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Casts;

use App\Modules\MedicalRecord\DTOs\PhysicalExamData;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<PhysicalExamData, PhysicalExamData|array<string, mixed>>
 */
final class PhysicalExamCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PhysicalExamData
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);

        return PhysicalExamData::fromArray($data);
    }

    /**
     * @param  PhysicalExamData|array<string, mixed>|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PhysicalExamData) {
            return json_encode($value->toArray());
        }

        return json_encode($value);
    }
}
