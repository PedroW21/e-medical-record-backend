<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Factories;

use App\Modules\Paciente\Models\CondicaoCronica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CondicaoCronica>
 */
final class CondicaoCronicaFactory extends Factory
{
    protected $model = CondicaoCronica::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->randomElement([
                'Hipertensão Arterial', 'Diabetes Tipo 2', 'Diabetes Tipo 1',
                'Asma', 'DPOC', 'Insuficiência Cardíaca', 'Hipotireoidismo',
                'Hipertireoidismo', 'Artrite Reumatoide', 'Lúpus Eritematoso Sistêmico',
                'Fibromialgia', 'Doença Celíaca', 'Epilepsia', 'Depressão',
                'Ansiedade Generalizada',
            ]),
        ];
    }
}
