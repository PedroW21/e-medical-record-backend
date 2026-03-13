<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Casts;

use App\Modules\MedicalRecord\DTOs\ProblemListData;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<ProblemListData, ProblemListData|array<string, mixed>>
 */
final class ProblemListCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?ProblemListData
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);

        return ProblemListData::fromArray($data);
    }

    /**
     * @param  ProblemListData|array<string, mixed>|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ProblemListData) {
            return json_encode($value->toArray());
        }

        return json_encode($value);
    }
}
