<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Enums\ProblemCategory;
use App\Modules\Catalog\Models\ListaProblema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListaProblema>
 */
final class ListaProblemaFactory extends Factory
{
    protected $model = ListaProblema::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->slug(2),
            'categoria' => fake()->randomElement(ProblemCategory::cases())->value,
            'rotulo' => fake()->unique()->words(2, true),
            'variacao' => null,
        ];
    }
}
