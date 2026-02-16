<?php

declare(strict_types=1);

namespace App\Modules\Patient\Database\Seeders;

use App\Modules\Patient\Models\CondicaoCronica;
use Illuminate\Database\Seeder;

final class ChronicConditionSeeder extends Seeder
{
    public function run(): void
    {
        $conditions = [
            'Hipertensão Arterial', 'Diabetes Tipo 2', 'Diabetes Tipo 1',
            'Asma', 'DPOC', 'Insuficiência Cardíaca', 'Hipotireoidismo',
            'Hipertireoidismo', 'Artrite Reumatoide', 'Lúpus Eritematoso Sistêmico',
            'Fibromialgia', 'Doença Celíaca', 'Epilepsia', 'Depressão',
            'Ansiedade Generalizada',
        ];

        foreach ($conditions as $nome) {
            CondicaoCronica::query()->firstOrCreate(['nome' => $nome]);
        }
    }
}
