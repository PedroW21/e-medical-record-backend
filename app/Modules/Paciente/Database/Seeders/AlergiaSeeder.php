<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Seeders;

use App\Modules\Paciente\Models\Alergia;
use Illuminate\Database\Seeder;

final class AlergiaSeeder extends Seeder
{
    public function run(): void
    {
        $alergias = [
            'Penicilina', 'Dipirona', 'AAS', 'Sulfa', 'Latex',
            'Ibuprofeno', 'Contraste Iodado', 'Frutos do Mar', 'Amendoim',
            'Gluten', 'Nimesulida', 'Amoxicilina', 'Cefalosporina',
            'Paracetamol', 'Diclofenaco',
        ];

        foreach ($alergias as $nome) {
            Alergia::query()->firstOrCreate(['nome' => $nome]);
        }
    }
}
