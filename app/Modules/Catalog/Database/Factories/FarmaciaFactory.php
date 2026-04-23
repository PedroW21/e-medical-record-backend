<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\Farmacia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Farmacia>
 */
final class FarmaciaFactory extends Factory
{
    protected $model = Farmacia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->slug(2),
            'nome' => fake()->company(),
            'cor' => '#'.fake()->hexColor(),
        ];
    }
}
