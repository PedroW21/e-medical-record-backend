<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Models\User;
use App\Modules\MedicalRecord\Models\ModeloRelatorioMedico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeloRelatorioMedico>
 */
final class MedicalReportTemplateFactory extends Factory
{
    protected $model = ModeloRelatorioMedico::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $templates = [
            "Atesto para os devidos fins que o(a) paciente {{NOME_PACIENTE}}, portador(a) do diagnóstico {{CID_10}}, encontra-se sob meus cuidados médicos.\n\n{{OBSERVACOES}}",
            "Declaro que o(a) paciente {{NOME_PACIENTE}}, CID-10: {{CID_10}}, necessita de repouso por {{PERIODO}} dias a partir da presente data.\n\n{{OBSERVACOES}}",
            "Relatório Médico\n\nPaciente: {{NOME_PACIENTE}}\nDiagnóstico (CID-10): {{CID_10}}\n\nEvolução clínica:\n{{OBSERVACOES}}",
        ];

        return [
            'user_id' => User::factory()->doctor(),
            'nome' => fake()->sentence(3),
            'corpo_template' => fake()->randomElement($templates),
        ];
    }
}
