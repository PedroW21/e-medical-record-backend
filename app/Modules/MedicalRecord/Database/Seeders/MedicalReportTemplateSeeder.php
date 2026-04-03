<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Seeders;

use App\Modules\MedicalRecord\Models\ModeloRelatorioMedico;
use Illuminate\Database\Seeder;

final class MedicalReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'nome' => 'Relatório Padrão',
                'corpo_template' => "Paciente portador dos quadros identificados nos CID-10 {{CID_10}}, com oscilações em sinais e sintomas clínicos, sem seguimento laboratorial/radiológico adequado, necessitando avaliação complementar laboratorial para fins de seguimento/otimização de terapêuticas.\n\nCID-10: {{CID_10}}",
            ],
            [
                'nome' => 'Relatório Pós-Cirúrgico',
                'corpo_template' => "Paciente portador dos quadros identificados nos CID-10 {{CID_10}}, com oscilações em sinais e sintomas clínicos, sem seguimento laboratorial/radiológico adequado, submetido a intervenção cirúrgica recente, com piora do estado clínico, necessitando avaliação complementar laboratorial para fins de seguimento/otimização de terapêuticas.\n\nCID-10: {{CID_10}}",
            ],
        ];

        foreach ($templates as $template) {
            ModeloRelatorioMedico::updateOrCreate(
                ['nome' => $template['nome'], 'user_id' => null],
                ['corpo_template' => $template['corpo_template']],
            );
        }
    }
}
