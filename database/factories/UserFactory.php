<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Auth\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Doctor,
            'crm' => fake()->numerify('CRM/SP ######'),
            'specialty' => fake()->randomElement(['Clínico Geral', 'Cardiologia', 'Dermatologia', 'Ortopedia']),
            'avatar_url' => null,
            'slug' => fake()->unique()->slug(2),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function doctor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Doctor,
            'crm' => fake()->numerify('CRM/SP ######'),
            'specialty' => 'Clínico Geral',
        ]);
    }

    public function secretary(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Secretary,
            'crm' => null,
            'specialty' => null,
        ]);
    }
}
