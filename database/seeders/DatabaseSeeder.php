<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Auth\Database\Seeders\UserSeeder;
use App\Modules\Paciente\Database\Seeders\AlergiaSeeder;
use App\Modules\Paciente\Database\Seeders\CondicaoCronicaSeeder;
use App\Modules\Paciente\Database\Seeders\PacienteSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AlergiaSeeder::class,
            CondicaoCronicaSeeder::class,
            PacienteSeeder::class,
        ]);
    }
}
