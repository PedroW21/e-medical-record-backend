<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\CategoriaTerapeutica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaTerapeutica>
 */
final class CategoriaTerapeuticaFactory extends Factory
{
    protected $model = CategoriaTerapeutica::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->slug(2),
            'nome' => fake()->unique()->words(2, true),
        ];
    }
}
