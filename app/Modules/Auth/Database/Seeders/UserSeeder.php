<?php

declare(strict_types=1);

namespace App\Modules\Auth\Database\Seeders;

use App\Models\User;
use App\Modules\Auth\Enums\UserRole;
use Illuminate\Database\Seeder;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'glayson.verner@verner.goulart'],
            [
                'name' => 'Glayson Verner',
                'password' => bcrypt('123456'),
                'role' => UserRole::Doctor,
                'crm' => 'CRM/SP 654321',
                'specialty' => 'Clínico Geral',
            ],
        );

        User::updateOrCreate(
            ['email' => 'pedro.verner@verner.goulart'],
            [
                'name' => 'Pedro Verner',
                'password' => bcrypt('123456'),
                'role' => UserRole::Doctor,
                'crm' => 'CRM/SP 123456',
                'specialty' => 'Clínico Geral',
            ],
        );
    }
}
