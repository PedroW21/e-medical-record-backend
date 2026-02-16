<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Database\Factories;

use App\Models\User;
use App\Modules\Delegation\Models\Delegacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delegacao>
 */
final class DelegationFactory extends Factory
{
    protected $model = Delegacao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medico_id' => User::factory()->doctor(),
            'secretaria_id' => User::factory()->secretary(),
        ];
    }
}
