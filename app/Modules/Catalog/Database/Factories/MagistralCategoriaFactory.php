<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Enums\MagistralType;
use App\Modules\Catalog\Models\MagistralCategoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MagistralCategoria>
 */
final class MagistralCategoriaFactory extends Factory
{
    protected $model = MagistralCategoria::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->slug(2),
            'tipo' => fake()->randomElement(MagistralType::cases())->value,
            'rotulo' => fake()->unique()->words(2, true),
            'icone' => fake()->randomElement(['moon', 'sun', 'heart', 'shield-check']),
        ];
    }
}
