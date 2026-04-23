<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\Farmacia;
use App\Modules\Catalog\Models\Injetavel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Injetavel>
 */
final class InjetavelFactory extends Factory
{
    protected $model = Injetavel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->slug(3),
            'farmacia_id' => Farmacia::factory(),
            'nome' => fake()->unique()->words(2, true),
            'dosagem' => fake()->randomNumber(3).'mg',
            'volume' => fake()->numberBetween(1, 30).'mL',
            'via_exclusiva' => null,
            'composicao' => null,
            'is_blend' => false,
            'vias_permitidas' => ['im', 'ev', 'sc'],
        ];
    }
}
