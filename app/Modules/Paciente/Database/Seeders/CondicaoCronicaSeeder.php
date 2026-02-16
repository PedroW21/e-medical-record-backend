<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Seeders;

use App\Modules\Paciente\Models\CondicaoCronica;
use Illuminate\Database\Seeder;

final class CondicaoCronicaSeeder extends Seeder
{
    public function run(): void
    {
        $condicoes = [
            'Hipertensão Arterial', 'Diabetes Tipo 2', 'Diabetes Tipo 1',
            'Asma', 'DPOC', 'Insuficiência Cardíaca', 'Hipotireoidismo',
            'Hipertireoidismo', 'Artrite Reumatoide', 'Lúpus Eritematoso Sistêmico',
            'Fibromialgia', 'Doença Celíaca', 'Epilepsia', 'Depressão',
            'Ansiedade Generalizada',
        ];

        foreach ($condicoes as $nome) {
            CondicaoCronica::query()->firstOrCreate(['nome' => $nome]);
        }
    }
}
