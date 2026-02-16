<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Database\Seeders;

use App\Models\User;
use App\Modules\Auth\Enums\UserRole;
use App\Modules\Delegation\Models\Delegacao;
use Illuminate\Database\Seeder;

final class DelegationSeeder extends Seeder
{
    public function run(): void
    {
        // Create a secretary if none exists
        $secretary = User::query()->where('role', UserRole::Secretary)->first();

        if (! $secretary) {
            $secretary = User::factory()->secretary()->create([
                'name' => 'Ana Secretária',
                'email' => 'ana.secretaria@verner.goulart',
                'slug' => 'ana-secretaria',
            ]);
        }

        // Delegate all existing doctors to this secretary
        $doctors = User::query()->where('role', UserRole::Doctor)->get();

        foreach ($doctors as $doctor) {
            Delegacao::query()->firstOrCreate([
                'medico_id' => $doctor->id,
                'secretaria_id' => $secretary->id,
            ]);
        }
    }
}
