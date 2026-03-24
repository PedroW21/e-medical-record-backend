<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\SolicitacaoExame;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitacaoExame>
 */
final class ExamRequestFactory extends Factory
{
    protected $model = SolicitacaoExame::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $exams = [
            ['id' => 'hemograma', 'name' => 'Hemograma completo', 'tuss_code' => '40302566', 'selected' => true],
            ['id' => 'glicemia', 'name' => 'Glicemia em jejum', 'tuss_code' => '40302213', 'selected' => true],
            ['id' => 'colesterol', 'name' => 'Colesterol total e frações', 'tuss_code' => '40302140', 'selected' => true],
            ['id' => 'tsh', 'name' => 'TSH ultrassensível', 'tuss_code' => '40302787', 'selected' => false],
            ['id' => 'creatinina', 'name' => 'Creatinina sérica', 'tuss_code' => '40302124', 'selected' => true],
            ['id' => 'tgo', 'name' => 'TGO (AST)', 'tuss_code' => '40302590', 'selected' => false],
            ['id' => 'tgp', 'name' => 'TGP (ALT)', 'tuss_code' => '40302604', 'selected' => true],
        ];

        $cids = ['E11.9', 'I10', 'J45.0', 'K29.5', 'M54.5', 'Z00.0', 'E78.5', 'I25.1'];
        $indications = [
            'Rastreamento de rotina anual.',
            'Acompanhamento de diabetes mellitus tipo 2.',
            'Controle de hipertensão arterial sistêmica.',
            'Investigação de dislipidemia.',
            'Pré-operatório eletivo.',
        ];

        $count = fake()->numberBetween(3, 5);
        $items = fake()->randomElements($exams, $count);

        return [
            'prontuario_id' => Prontuario::factory(),
            'modelo_id' => fake()->optional(0.4)->uuid(),
            'cid_10' => fake()->randomElement($cids),
            'indicacao_clinica' => fake()->randomElement($indications),
            'itens' => $items,
            'relatorio_medico' => null,
            'impresso_em' => fake()->optional(0.3)->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
