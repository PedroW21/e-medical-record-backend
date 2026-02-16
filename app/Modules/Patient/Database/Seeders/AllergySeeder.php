<?php

declare(strict_types=1);

namespace App\Modules\Patient\Database\Seeders;

use App\Modules\Patient\Models\Alergia;
use Illuminate\Database\Seeder;

final class AllergySeeder extends Seeder
{
    public function run(): void
    {
        $allergies = [
            'Penicilina', 'Dipirona', 'AAS', 'Sulfa', 'Latex',
            'Ibuprofeno', 'Contraste Iodado', 'Frutos do Mar', 'Amendoim',
            'Gluten', 'Nimesulida', 'Amoxicilina', 'Cefalosporina',
            'Paracetamol', 'Diclofenaco',
        ];

        foreach ($allergies as $nome) {
            Alergia::query()->firstOrCreate(['nome' => $nome]);
        }
    }
}
