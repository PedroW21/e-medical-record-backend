<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Seeders;

use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;
use Illuminate\Database\Seeder;

final class LabCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Hematologia — Série vermelha
            ['id' => 'hemo-hemacias', 'nome' => 'Hemácias', 'categoria' => 'hematologia', 'unidade' => 'milhões/mm³', 'faixa_referencia' => '4,5-6,1 (H) / 4,0-5,4 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'hemo-hemoglobina', 'nome' => 'Hemoglobina', 'categoria' => 'hematologia', 'unidade' => 'g/dL', 'faixa_referencia' => '13,5-18,0 (H) / 12,0-16,0 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'hemo-hematocrito', 'nome' => 'Hematócrito', 'categoria' => 'hematologia', 'unidade' => '%', 'faixa_referencia' => '40-54 (H) / 36-48 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'hemo-vcm', 'nome' => 'VCM', 'categoria' => 'hematologia', 'unidade' => 'fL', 'faixa_referencia' => '80-100', 'tipo_resultado' => 'numeric'],
            ['id' => 'hemo-hcm', 'nome' => 'HCM', 'categoria' => 'hematologia', 'unidade' => 'pg', 'faixa_referencia' => '27-33', 'tipo_resultado' => 'numeric'],
            ['id' => 'hemo-chcm', 'nome' => 'CHCM', 'categoria' => 'hematologia', 'unidade' => 'g/dL', 'faixa_referencia' => '32-36', 'tipo_resultado' => 'numeric'],
            ['id' => 'hemo-rdw', 'nome' => 'RDW', 'categoria' => 'hematologia', 'unidade' => '%', 'faixa_referencia' => '11,5-14,5', 'tipo_resultado' => 'numeric'],

            // Hematologia — Série branca
            ['id' => 'hemo-leucocitos', 'nome' => 'Leucócitos totais', 'categoria' => 'hematologia', 'unidade' => '/mm³', 'faixa_referencia' => '4.000-11.000', 'tipo_resultado' => 'numeric'],
            ['id' => 'hemo-neutrofilos', 'nome' => 'Neutrófilos', 'categoria' => 'hematologia', 'unidade' => '%', 'faixa_referencia' => '40-70', 'tipo_resultado' => 'numeric'],
            ['id' => 'hemo-linfocitos', 'nome' => 'Linfócitos', 'categoria' => 'hematologia', 'unidade' => '%', 'faixa_referencia' => '20-40', 'tipo_resultado' => 'numeric'],
            ['id' => 'hemo-monocitos', 'nome' => 'Monócitos', 'categoria' => 'hematologia', 'unidade' => '%', 'faixa_referencia' => '2-8', 'tipo_resultado' => 'numeric'],
            ['id' => 'hemo-eosinofilos', 'nome' => 'Eosinófilos', 'categoria' => 'hematologia', 'unidade' => '%', 'faixa_referencia' => '1-5', 'tipo_resultado' => 'numeric'],
            ['id' => 'hemo-basofilos', 'nome' => 'Basófilos', 'categoria' => 'hematologia', 'unidade' => '%', 'faixa_referencia' => '0-1', 'tipo_resultado' => 'numeric'],

            // Hematologia — Plaquetas
            ['id' => 'hemo-plaquetas', 'nome' => 'Plaquetas', 'categoria' => 'hematologia', 'unidade' => 'mil/mm³', 'faixa_referencia' => '150-400', 'tipo_resultado' => 'numeric'],
            ['id' => 'hemo-vpm', 'nome' => 'VPM (Volume plaquetário médio)', 'categoria' => 'hematologia', 'unidade' => 'fL', 'faixa_referencia' => '7,5-11,5', 'tipo_resultado' => 'numeric'],

            // Reticulócitos
            ['id' => 'reticulocitos-pct', 'nome' => 'Reticulócitos %', 'categoria' => 'hematologia', 'unidade' => '%', 'faixa_referencia' => '0,5-2,5', 'tipo_resultado' => 'numeric'],
            ['id' => 'reticulocitos-abs', 'nome' => 'Reticulócitos Absoluto', 'categoria' => 'hematologia', 'unidade' => '/mm³', 'faixa_referencia' => '25.000-120.000', 'tipo_resultado' => 'numeric'],
            ['id' => 'irf', 'nome' => 'IRF (Fração de Reticulócitos Imaturos)', 'categoria' => 'hematologia', 'unidade' => '%', 'faixa_referencia' => '2-14', 'tipo_resultado' => 'numeric'],
            ['id' => 'ret-he', 'nome' => 'RET-HE', 'categoria' => 'hematologia', 'unidade' => 'pg', 'faixa_referencia' => '28-35', 'tipo_resultado' => 'numeric'],

            // Coagulação e outros
            ['id' => 'vhs', 'nome' => 'VHS', 'categoria' => 'hematologia', 'unidade' => 'mm/h', 'faixa_referencia' => '< 20 (H) / < 30 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'fibrinogenio', 'nome' => 'Fibrinogênio', 'categoria' => 'hematologia', 'unidade' => 'mg/dL', 'faixa_referencia' => '200-400', 'tipo_resultado' => 'numeric'],
            ['id' => 'd-dimero', 'nome' => 'D-Dímero', 'categoria' => 'hematologia', 'unidade' => 'ng/mL', 'faixa_referencia' => '< 500', 'tipo_resultado' => 'numeric'],
            ['id' => 'g6pd', 'nome' => 'G6PD (Glicose-6-fosfato desidrogenase)', 'categoria' => 'hematologia', 'unidade' => 'U/g Hb', 'faixa_referencia' => '4,6-13,5', 'tipo_resultado' => 'numeric'],

            // Eletroforese de proteínas — frações individuais
            ['id' => 'eletro-albumina', 'nome' => 'Eletroforese - Albumina', 'categoria' => 'hematologia', 'unidade' => 'g/dL', 'faixa_referencia' => '3,5-5,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'eletro-alfa1', 'nome' => 'Eletroforese - Alfa-1 globulina', 'categoria' => 'hematologia', 'unidade' => 'g/dL', 'faixa_referencia' => '0,1-0,3', 'tipo_resultado' => 'numeric'],
            ['id' => 'eletro-alfa2', 'nome' => 'Eletroforese - Alfa-2 globulina', 'categoria' => 'hematologia', 'unidade' => 'g/dL', 'faixa_referencia' => '0,6-1,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'eletro-beta1', 'nome' => 'Eletroforese - Beta-1 globulina', 'categoria' => 'hematologia', 'unidade' => 'g/dL', 'faixa_referencia' => '0,3-0,6', 'tipo_resultado' => 'numeric'],
            ['id' => 'eletro-beta2', 'nome' => 'Eletroforese - Beta-2 globulina', 'categoria' => 'hematologia', 'unidade' => 'g/dL', 'faixa_referencia' => '0,2-0,5', 'tipo_resultado' => 'numeric'],
            ['id' => 'eletro-gama', 'nome' => 'Eletroforese - Gama globulina', 'categoria' => 'hematologia', 'unidade' => 'g/dL', 'faixa_referencia' => '0,7-1,6', 'tipo_resultado' => 'numeric'],

            // Bioquímica
            ['id' => 'glicemia-jejum', 'nome' => 'Glicemia de jejum', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '70-99', 'tipo_resultado' => 'numeric'],
            ['id' => 'hba1c', 'nome' => 'Hemoglobina glicada (HbA1c)', 'categoria' => 'bioquimica', 'unidade' => '%', 'faixa_referencia' => '< 5,7', 'tipo_resultado' => 'numeric'],
            ['id' => 'homa-ir', 'nome' => 'HOMA-IR', 'categoria' => 'bioquimica', 'unidade' => '-', 'faixa_referencia' => '< 2,5', 'tipo_resultado' => 'numeric'],
            ['id' => 'homa-beta', 'nome' => 'HOMA-Beta (Quicke)', 'categoria' => 'bioquimica', 'unidade' => '-', 'faixa_referencia' => '-', 'tipo_resultado' => 'numeric'],
            ['id' => 'colesterol-total', 'nome' => 'Colesterol total', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '< 190', 'tipo_resultado' => 'numeric'],
            ['id' => 'hdl', 'nome' => 'HDL colesterol', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '> 40 (H) / > 50 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'ldl', 'nome' => 'LDL colesterol', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '< 130', 'tipo_resultado' => 'numeric'],
            ['id' => 'vldl', 'nome' => 'VLDL colesterol', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '< 30', 'tipo_resultado' => 'numeric'],
            ['id' => 'triglicerideos', 'nome' => 'Triglicerídeos', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '< 150', 'tipo_resultado' => 'numeric'],
            ['id' => 'colesterol-nao-hdl', 'nome' => 'Colesterol não-HDL', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '< 160', 'tipo_resultado' => 'numeric'],
            ['id' => 'apo-a1', 'nome' => 'Apolipoproteína A1', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '120-175', 'tipo_resultado' => 'numeric'],
            ['id' => 'apo-b', 'nome' => 'Apolipoproteína B', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '< 130', 'tipo_resultado' => 'numeric'],
            ['id' => 'relacao-apo-a-b', 'nome' => 'Relação Apo A/Apo B', 'categoria' => 'bioquimica', 'unidade' => '-', 'faixa_referencia' => '> 1,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'lipoproteina-a', 'nome' => 'Lipoproteína(a)', 'categoria' => 'bioquimica', 'unidade' => 'nmol/L', 'faixa_referencia' => '< 75', 'tipo_resultado' => 'numeric'],
            ['id' => 'ureia', 'nome' => 'Ureia', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '15-40', 'tipo_resultado' => 'numeric'],
            ['id' => 'creatinina', 'nome' => 'Creatinina', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '0,7-1,3 (H) / 0,6-1,1 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'cistatina-c', 'nome' => 'Cistatina C', 'categoria' => 'bioquimica', 'unidade' => 'mg/L', 'faixa_referencia' => '0,51-0,98', 'tipo_resultado' => 'numeric'],
            ['id' => 'acido-urico', 'nome' => 'Ácido úrico', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '3,5-7,2 (H) / 2,6-6,0 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'tgo', 'nome' => 'TGO (AST)', 'categoria' => 'bioquimica', 'unidade' => 'U/L', 'faixa_referencia' => '< 40', 'tipo_resultado' => 'numeric'],
            ['id' => 'tgp', 'nome' => 'TGP (ALT)', 'categoria' => 'bioquimica', 'unidade' => 'U/L', 'faixa_referencia' => '< 41', 'tipo_resultado' => 'numeric'],
            ['id' => 'ggt', 'nome' => 'GGT (Gama-glutamiltransferase)', 'categoria' => 'bioquimica', 'unidade' => 'U/L', 'faixa_referencia' => '8-61 (H) / 5-36 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'fosfatase-alcalina', 'nome' => 'Fosfatase alcalina', 'categoria' => 'bioquimica', 'unidade' => 'U/L', 'faixa_referencia' => '40-130', 'tipo_resultado' => 'numeric'],

            // Bilirrubinas
            ['id' => 'bilirrubina-total', 'nome' => 'Bilirrubina total', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '0,3-1,2', 'tipo_resultado' => 'numeric'],
            ['id' => 'bilirrubina-direta', 'nome' => 'Bilirrubina direta', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '0,0-0,3', 'tipo_resultado' => 'numeric'],
            ['id' => 'bilirrubina-indireta', 'nome' => 'Bilirrubina indireta', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '0,1-0,9', 'tipo_resultado' => 'numeric'],

            // Fibrose hepática — componentes ELF
            ['id' => 'acido-hialuronico', 'nome' => 'Ácido hialurônico (HA)', 'categoria' => 'bioquimica', 'unidade' => 'ng/mL', 'faixa_referencia' => '< 75', 'tipo_resultado' => 'numeric'],
            ['id' => 'piiinp', 'nome' => 'PIIINP (Propeptídeo aminoterminal do procolágeno tipo III)', 'categoria' => 'bioquimica', 'unidade' => 'ng/mL', 'faixa_referencia' => '3,0-7,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'timp-1', 'nome' => 'TIMP-1 (Inibidor tecidual de metaloproteinase-1)', 'categoria' => 'bioquimica', 'unidade' => 'ng/mL', 'faixa_referencia' => '< 250', 'tipo_resultado' => 'numeric'],

            // Proteínas totais — frações individuais
            ['id' => 'proteinas-totais', 'nome' => 'Proteínas totais', 'categoria' => 'bioquimica', 'unidade' => 'g/dL', 'faixa_referencia' => '6,0-8,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'proteinas-albumina', 'nome' => 'Albumina sérica', 'categoria' => 'bioquimica', 'unidade' => 'g/dL', 'faixa_referencia' => '3,5-5,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'proteinas-globulinas', 'nome' => 'Globulinas', 'categoria' => 'bioquimica', 'unidade' => 'g/dL', 'faixa_referencia' => '2,0-3,5', 'tipo_resultado' => 'numeric'],
            ['id' => 'proteinas-relacao-ag', 'nome' => 'Relação A/G', 'categoria' => 'bioquimica', 'unidade' => '-', 'faixa_referencia' => '1,0-2,2', 'tipo_resultado' => 'numeric'],
            ['id' => 'ferro-serico', 'nome' => 'Ferro sérico (Fe²⁺)', 'categoria' => 'bioquimica', 'unidade' => 'µg/dL', 'faixa_referencia' => '65-175', 'tipo_resultado' => 'numeric'],
            ['id' => 'transferrina', 'nome' => 'Transferrina', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '200-360', 'tipo_resultado' => 'numeric'],
            ['id' => 'ist', 'nome' => 'Índice de Saturação de Transferrina (IST)', 'categoria' => 'bioquimica', 'unidade' => '%', 'faixa_referencia' => '20-50', 'tipo_resultado' => 'numeric'],
            ['id' => 'ferritina', 'nome' => 'Ferritina', 'categoria' => 'bioquimica', 'unidade' => 'ng/mL', 'faixa_referencia' => '30-400 (H) / 13-150 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'homocisteina', 'nome' => 'Homocisteína', 'categoria' => 'bioquimica', 'unidade' => 'µmol/L', 'faixa_referencia' => '5-15', 'tipo_resultado' => 'numeric'],
            ['id' => 'acido-metilmalonico', 'nome' => 'Ácido metilmalônico', 'categoria' => 'bioquimica', 'unidade' => 'nmol/L', 'faixa_referencia' => '73-271', 'tipo_resultado' => 'numeric'],
            ['id' => 'amilase', 'nome' => 'Amilase', 'categoria' => 'bioquimica', 'unidade' => 'U/L', 'faixa_referencia' => '28-100', 'tipo_resultado' => 'numeric'],
            ['id' => 'lipase', 'nome' => 'Lipase', 'categoria' => 'bioquimica', 'unidade' => 'U/L', 'faixa_referencia' => '13-60', 'tipo_resultado' => 'numeric'],
            ['id' => 'leptina', 'nome' => 'Leptina', 'categoria' => 'bioquimica', 'unidade' => 'ng/mL', 'faixa_referencia' => '3,7-11,1 (H) / 7,4-52,2 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'cpk-total', 'nome' => 'CPK total', 'categoria' => 'bioquimica', 'unidade' => 'U/L', 'faixa_referencia' => '32-294 (H) / 33-211 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'cpk-mb', 'nome' => 'CPK-MB', 'categoria' => 'bioquimica', 'unidade' => 'U/L', 'faixa_referencia' => '< 25', 'tipo_resultado' => 'numeric'],
            ['id' => 'serotonina', 'nome' => 'Serotonina', 'categoria' => 'bioquimica', 'unidade' => 'ng/mL', 'faixa_referencia' => '50-220', 'tipo_resultado' => 'numeric'],
            ['id' => 'sodio', 'nome' => 'Sódio', 'categoria' => 'bioquimica', 'unidade' => 'mEq/L', 'faixa_referencia' => '136-145', 'tipo_resultado' => 'numeric'],
            ['id' => 'potassio', 'nome' => 'Potássio', 'categoria' => 'bioquimica', 'unidade' => 'mEq/L', 'faixa_referencia' => '3,5-5,1', 'tipo_resultado' => 'numeric'],
            ['id' => 'magnesio', 'nome' => 'Magnésio', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '1,7-2,2', 'tipo_resultado' => 'numeric'],
            ['id' => 'calcio-total', 'nome' => 'Cálcio total', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '8,6-10,2', 'tipo_resultado' => 'numeric'],
            ['id' => 'calcio-ionico', 'nome' => 'Cálcio iônico', 'categoria' => 'bioquimica', 'unidade' => 'mmol/L', 'faixa_referencia' => '1,15-1,35', 'tipo_resultado' => 'numeric'],
            ['id' => 'fosforo', 'nome' => 'Fósforo', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '2,5-4,5', 'tipo_resultado' => 'numeric'],
            ['id' => 'zinco', 'nome' => 'Zinco', 'categoria' => 'bioquimica', 'unidade' => 'µg/dL', 'faixa_referencia' => '70-120', 'tipo_resultado' => 'numeric'],
            ['id' => 'selenio', 'nome' => 'Selênio', 'categoria' => 'bioquimica', 'unidade' => 'µg/L', 'faixa_referencia' => '46-143', 'tipo_resultado' => 'numeric'],
            ['id' => 'manganes', 'nome' => 'Manganês', 'categoria' => 'bioquimica', 'unidade' => 'µg/L', 'faixa_referencia' => '4,7-18,3', 'tipo_resultado' => 'numeric'],

            // Vitaminas
            ['id' => 'vitamina-b3', 'nome' => 'Vitamina B3 (Niacina)', 'categoria' => 'bioquimica', 'unidade' => 'µg/mL', 'faixa_referencia' => '0,5-8,5', 'tipo_resultado' => 'numeric'],
            ['id' => 'vitamina-b6', 'nome' => 'Vitamina B6 (Piridoxina)', 'categoria' => 'bioquimica', 'unidade' => 'ng/mL', 'faixa_referencia' => '5-50', 'tipo_resultado' => 'numeric'],
            ['id' => 'vitamina-b9', 'nome' => 'Ácido fólico (B9)', 'categoria' => 'bioquimica', 'unidade' => 'ng/mL', 'faixa_referencia' => '> 3,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'vitamina-b12', 'nome' => 'Vitamina B12', 'categoria' => 'bioquimica', 'unidade' => 'pg/mL', 'faixa_referencia' => '200-900', 'tipo_resultado' => 'numeric'],
            ['id' => 'vitamina-c', 'nome' => 'Vitamina C', 'categoria' => 'bioquimica', 'unidade' => 'mg/dL', 'faixa_referencia' => '0,4-2,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'vitamina-d-25oh', 'nome' => '25-OH-Vitamina D3', 'categoria' => 'bioquimica', 'unidade' => 'ng/mL', 'faixa_referencia' => '30-60', 'tipo_resultado' => 'numeric'],
            ['id' => 'vitamina-d-125', 'nome' => '1,25-Vitamina D (Calcitriol)', 'categoria' => 'bioquimica', 'unidade' => 'pg/mL', 'faixa_referencia' => '19,9-79,3', 'tipo_resultado' => 'numeric'],
            ['id' => 'vitamina-e', 'nome' => 'Vitamina E (Alfa-tocoferol)', 'categoria' => 'bioquimica', 'unidade' => 'mg/L', 'faixa_referencia' => '5,5-17,0', 'tipo_resultado' => 'numeric'],

            // Endocrinologia
            ['id' => 'insulina-basal', 'nome' => 'Insulina basal', 'categoria' => 'endocrinologia', 'unidade' => 'µUI/mL', 'faixa_referencia' => '2,6-24,9', 'tipo_resultado' => 'numeric'],
            ['id' => 'insulina-pos-prandial', 'nome' => 'Insulina pós-prandial', 'categoria' => 'endocrinologia', 'unidade' => 'µUI/mL', 'faixa_referencia' => '< 60', 'tipo_resultado' => 'numeric'],
            ['id' => 'peptideo-c', 'nome' => 'Peptídeo C', 'categoria' => 'endocrinologia', 'unidade' => 'ng/mL', 'faixa_referencia' => '1,1-4,4', 'tipo_resultado' => 'numeric'],
            ['id' => 'tsh', 'nome' => 'TSH', 'categoria' => 'endocrinologia', 'unidade' => 'mUI/L', 'faixa_referencia' => '0,4-4,0', 'tipo_resultado' => 'numeric'],
            ['id' => 't4-livre', 'nome' => 'T4 livre', 'categoria' => 'endocrinologia', 'unidade' => 'ng/dL', 'faixa_referencia' => '0,9-1,8', 'tipo_resultado' => 'numeric'],
            ['id' => 't3-livre', 'nome' => 'T3 livre', 'categoria' => 'endocrinologia', 'unidade' => 'pg/mL', 'faixa_referencia' => '2,0-4,4', 'tipo_resultado' => 'numeric'],
            ['id' => 't3-reverso', 'nome' => 'T3 reverso', 'categoria' => 'endocrinologia', 'unidade' => 'ng/dL', 'faixa_referencia' => '10-24', 'tipo_resultado' => 'numeric'],
            ['id' => 'tireoglobulina', 'nome' => 'Tireoglobulina (TGB)', 'categoria' => 'endocrinologia', 'unidade' => 'ng/mL', 'faixa_referencia' => '1,4-78,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'calcitonina', 'nome' => 'Calcitonina', 'categoria' => 'endocrinologia', 'unidade' => 'pg/mL', 'faixa_referencia' => '< 10 (H) / < 5 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'pth', 'nome' => 'PTH intacto (Paratormônio)', 'categoria' => 'endocrinologia', 'unidade' => 'pg/mL', 'faixa_referencia' => '15-65', 'tipo_resultado' => 'numeric'],
            ['id' => 'cortisol-salivar-8h', 'nome' => 'Cortisol salivar 8h', 'categoria' => 'endocrinologia', 'unidade' => 'µg/dL', 'faixa_referencia' => '0,10-0,74', 'tipo_resultado' => 'numeric'],
            ['id' => 'cortisol-salivar-16h', 'nome' => 'Cortisol salivar 16h', 'categoria' => 'endocrinologia', 'unidade' => 'µg/dL', 'faixa_referencia' => '< 0,21', 'tipo_resultado' => 'numeric'],
            ['id' => 'cortisol-salivar-23h', 'nome' => 'Cortisol salivar 23h', 'categoria' => 'endocrinologia', 'unidade' => 'µg/dL', 'faixa_referencia' => '< 0,09', 'tipo_resultado' => 'numeric'],
            ['id' => 'cortisol-serico-8h', 'nome' => 'Cortisol sérico 8h', 'categoria' => 'endocrinologia', 'unidade' => 'µg/dL', 'faixa_referencia' => '6,2-19,4', 'tipo_resultado' => 'numeric'],
            ['id' => 'cortisol-serico-16h', 'nome' => 'Cortisol sérico 16h', 'categoria' => 'endocrinologia', 'unidade' => 'µg/dL', 'faixa_referencia' => '2,3-11,9', 'tipo_resultado' => 'numeric'],
            ['id' => 'cortisol-serico-23h', 'nome' => 'Cortisol sérico 23h', 'categoria' => 'endocrinologia', 'unidade' => 'µg/dL', 'faixa_referencia' => '< 1,8', 'tipo_resultado' => 'numeric'],
            ['id' => 'transcortina', 'nome' => 'Transcortina (CBG)', 'categoria' => 'endocrinologia', 'unidade' => 'mg/L', 'faixa_referencia' => '22-55', 'tipo_resultado' => 'numeric'],
            ['id' => 'acth', 'nome' => 'ACTH', 'categoria' => 'endocrinologia', 'unidade' => 'pg/mL', 'faixa_referencia' => '7,2-63,3 (8h)', 'tipo_resultado' => 'numeric'],
            ['id' => 'igf1', 'nome' => 'IGF-1 (Somatomedina C)', 'categoria' => 'endocrinologia', 'unidade' => 'ng/mL', 'faixa_referencia' => 'Variável por idade', 'tipo_resultado' => 'numeric'],
            ['id' => 'igfbp3', 'nome' => 'IGFBP-3', 'categoria' => 'endocrinologia', 'unidade' => 'mg/L', 'faixa_referencia' => 'Variável por idade', 'tipo_resultado' => 'numeric'],

            // Hormonal
            ['id' => 'fsh', 'nome' => 'FSH', 'categoria' => 'hormonal', 'unidade' => 'mUI/mL', 'faixa_referencia' => 'Variável por fase', 'tipo_resultado' => 'numeric'],
            ['id' => 'lh', 'nome' => 'LH', 'categoria' => 'hormonal', 'unidade' => 'mUI/mL', 'faixa_referencia' => 'Variável por fase', 'tipo_resultado' => 'numeric'],
            ['id' => 'gh', 'nome' => 'GH (Hormônio do crescimento)', 'categoria' => 'hormonal', 'unidade' => 'ng/mL', 'faixa_referencia' => '< 3,0 (H) / < 8,0 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'testosterona-total', 'nome' => 'Testosterona total', 'categoria' => 'hormonal', 'unidade' => 'ng/dL', 'faixa_referencia' => '280-800 (H) / 15-70 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'testosterona-livre', 'nome' => 'Testosterona livre', 'categoria' => 'hormonal', 'unidade' => 'pg/mL', 'faixa_referencia' => '8,7-25,1 (H) / 0,3-1,9 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'testosterona-biodisponivel', 'nome' => 'Testosterona biodisponível', 'categoria' => 'hormonal', 'unidade' => 'ng/dL', 'faixa_referencia' => 'Variável por idade', 'tipo_resultado' => 'numeric'],
            ['id' => 'estradiol', 'nome' => 'Estradiol (E2)', 'categoria' => 'hormonal', 'unidade' => 'pg/mL', 'faixa_referencia' => 'Variável por fase', 'tipo_resultado' => 'numeric'],
            ['id' => 'estriol', 'nome' => 'Estriol (E3)', 'categoria' => 'hormonal', 'unidade' => 'ng/mL', 'faixa_referencia' => 'Variável por fase', 'tipo_resultado' => 'numeric'],
            ['id' => 'estrona', 'nome' => 'Estrona (E1)', 'categoria' => 'hormonal', 'unidade' => 'pg/mL', 'faixa_referencia' => 'Variável por fase', 'tipo_resultado' => 'numeric'],
            ['id' => 'progesterona', 'nome' => 'Progesterona', 'categoria' => 'hormonal', 'unidade' => 'ng/mL', 'faixa_referencia' => 'Variável por fase', 'tipo_resultado' => 'numeric'],
            ['id' => 'prolactina', 'nome' => 'Prolactina', 'categoria' => 'hormonal', 'unidade' => 'ng/mL', 'faixa_referencia' => '4,0-15,2 (H) / 4,8-23,3 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'shbg', 'nome' => 'SHBG', 'categoria' => 'hormonal', 'unidade' => 'nmol/L', 'faixa_referencia' => '18-54 (H) / 26-110 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'dhea-s', 'nome' => 'DHEA-S (S-DHEA)', 'categoria' => 'hormonal', 'unidade' => 'µg/dL', 'faixa_referencia' => 'Variável por idade', 'tipo_resultado' => 'numeric'],
            ['id' => 'dhea', 'nome' => 'DHEA', 'categoria' => 'hormonal', 'unidade' => 'ng/dL', 'faixa_referencia' => 'Variável por idade', 'tipo_resultado' => 'numeric'],
            ['id' => 'dht', 'nome' => 'DHT (Di-hidrotestosterona)', 'categoria' => 'hormonal', 'unidade' => 'pg/mL', 'faixa_referencia' => '250-990 (H) / 24-368 (M)', 'tipo_resultado' => 'numeric'],

            // Imunologia / Autoimunidade
            ['id' => 'pcr-us', 'nome' => 'PCR ultrassensível', 'categoria' => 'imunologia', 'unidade' => 'mg/L', 'faixa_referencia' => '< 1,0 (baixo risco)', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-tpo', 'nome' => 'Anti-TPO', 'categoria' => 'imunologia', 'unidade' => 'UI/mL', 'faixa_referencia' => '< 34', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-tg', 'nome' => 'Anti-Tireoglobulina', 'categoria' => 'imunologia', 'unidade' => 'UI/mL', 'faixa_referencia' => '< 115', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-tsh', 'nome' => 'Anti-TSH (TRAb)', 'categoria' => 'imunologia', 'unidade' => 'UI/L', 'faixa_referencia' => '< 1,75', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-gad', 'nome' => 'Anti-GAD65', 'categoria' => 'imunologia', 'unidade' => 'UI/mL', 'faixa_referencia' => '< 5,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-znt8', 'nome' => 'Anti-ZnT8', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 15', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-ia2', 'nome' => 'Anti-IA2 (Antígeno 2 do insulinoma)', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 7,5', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-insulina', 'nome' => 'Anti-insulina (IAA)', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 10', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-ilhotas', 'nome' => 'Anti-ilhotas (ICA)', 'categoria' => 'imunologia', 'unidade' => '-', 'faixa_referencia' => 'Não reagente', 'tipo_resultado' => 'numeric'],
            ['id' => 'ige-total', 'nome' => 'IgE total', 'categoria' => 'imunologia', 'unidade' => 'UI/mL', 'faixa_referencia' => '< 100', 'tipo_resultado' => 'numeric'],
            ['id' => 'fator-reumatoide', 'nome' => 'Fator reumatoide (FR)', 'categoria' => 'imunologia', 'unidade' => 'UI/mL', 'faixa_referencia' => '< 14', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-ccp', 'nome' => 'Anti-CCP', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 20', 'tipo_resultado' => 'numeric'],
            ['id' => 'fan', 'nome' => 'FAN (fator antinúcleo)', 'categoria' => 'imunologia', 'unidade' => '-', 'faixa_referencia' => 'Não reagente', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-dsdna', 'nome' => 'Anti-DNA nativo (anti-dsDNA)', 'categoria' => 'imunologia', 'unidade' => 'UI/mL', 'faixa_referencia' => '< 200', 'tipo_resultado' => 'numeric'],
            ['id' => 'complemento-c3', 'nome' => 'Complemento C3', 'categoria' => 'imunologia', 'unidade' => 'mg/dL', 'faixa_referencia' => '90-180', 'tipo_resultado' => 'numeric'],
            ['id' => 'complemento-c4', 'nome' => 'Complemento C4', 'categoria' => 'imunologia', 'unidade' => 'mg/dL', 'faixa_referencia' => '10-40', 'tipo_resultado' => 'numeric'],

            // Painel Reumatológico — anticorpos específicos
            ['id' => 'anti-ssa-ro60', 'nome' => 'Anti-SS-A (Ro60)', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 20', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-ssb-la', 'nome' => 'Anti-SS-B (La)', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 20', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-smith', 'nome' => 'Anti-Smith (Sm)', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 20', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-rnp', 'nome' => 'Anti-RNP', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 20', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-jo1', 'nome' => 'Anti-Jo-1', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 20', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-scl70', 'nome' => 'Anti-SCL-70', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 20', 'tipo_resultado' => 'numeric'],
            ['id' => 'histamina', 'nome' => 'Histamina plasmática', 'categoria' => 'imunologia', 'unidade' => 'ng/mL', 'faixa_referencia' => '< 1,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'dao', 'nome' => 'Diamino oxidase (DAO)', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '> 10', 'tipo_resultado' => 'numeric'],
            ['id' => 'triptase', 'nome' => 'Triptase sérica', 'categoria' => 'imunologia', 'unidade' => 'µg/L', 'faixa_referencia' => '< 11,4', 'tipo_resultado' => 'numeric'],
            ['id' => 'zonulina-serica', 'nome' => 'Zonulina sérica', 'categoria' => 'imunologia', 'unidade' => 'ng/mL', 'faixa_referencia' => '< 48', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-gliadina-iga', 'nome' => 'Anti-gliadina IgA', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 20', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-gliadina-igg', 'nome' => 'Anti-gliadina IgG', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 20', 'tipo_resultado' => 'numeric'],
            ['id' => 'anti-transglutaminase', 'nome' => 'Anti-transglutaminase IgA', 'categoria' => 'imunologia', 'unidade' => 'U/mL', 'faixa_referencia' => '< 10', 'tipo_resultado' => 'numeric'],

            // IgE específicas
            ['id' => 'ige-leite', 'nome' => 'IgE específica - leite de vaca', 'categoria' => 'imunologia', 'unidade' => 'kU/L', 'faixa_referencia' => '< 0,35', 'tipo_resultado' => 'numeric'],
            ['id' => 'ige-ovo', 'nome' => 'IgE específica - ovo (clara)', 'categoria' => 'imunologia', 'unidade' => 'kU/L', 'faixa_referencia' => '< 0,35', 'tipo_resultado' => 'numeric'],
            ['id' => 'ige-trigo', 'nome' => 'IgE específica - trigo', 'categoria' => 'imunologia', 'unidade' => 'kU/L', 'faixa_referencia' => '< 0,35', 'tipo_resultado' => 'numeric'],

            // Marcadores Tumorais
            ['id' => 'cea', 'nome' => 'CEA (Antígeno carcinoembrionário)', 'categoria' => 'marcadores_tumorais', 'unidade' => 'ng/mL', 'faixa_referencia' => '< 3,0 (não fumante) / < 5,0 (fumante)', 'tipo_resultado' => 'numeric'],
            ['id' => 'ca-199', 'nome' => 'CA 19.9', 'categoria' => 'marcadores_tumorais', 'unidade' => 'U/mL', 'faixa_referencia' => '< 37', 'tipo_resultado' => 'numeric'],
            ['id' => 'ca-125', 'nome' => 'CA 125', 'categoria' => 'marcadores_tumorais', 'unidade' => 'U/mL', 'faixa_referencia' => '< 35', 'tipo_resultado' => 'numeric'],
            ['id' => 'alfa-fetoproteina', 'nome' => 'Alfa-fetoproteína (AFP)', 'categoria' => 'marcadores_tumorais', 'unidade' => 'ng/mL', 'faixa_referencia' => '< 7,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'beta2-microglobulina', 'nome' => 'Beta-2 microglobulina', 'categoria' => 'marcadores_tumorais', 'unidade' => 'mg/L', 'faixa_referencia' => '0,8-2,2', 'tipo_resultado' => 'numeric'],

            // Coprologia
            ['id' => 'calprotectina-fecal', 'nome' => 'Calprotectina fecal', 'categoria' => 'coprologia', 'unidade' => 'µg/g', 'faixa_referencia' => '< 50', 'tipo_resultado' => 'numeric'],
            ['id' => 'zonulina-fecal', 'nome' => 'Zonulina fecal', 'categoria' => 'coprologia', 'unidade' => 'ng/mL', 'faixa_referencia' => '< 107', 'tipo_resultado' => 'numeric'],
            ['id' => 'elastase-pancreatica', 'nome' => 'Elastase pancreática fecal', 'categoria' => 'coprologia', 'unidade' => 'µg/g', 'faixa_referencia' => '> 200', 'tipo_resultado' => 'numeric'],

            // EAS (urina tipo I) — parâmetros individuais
            ['id' => 'eas-ph', 'nome' => 'EAS - pH urinário', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => '5,0-7,0', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-densidade', 'nome' => 'EAS - Densidade', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => '1.005-1.030', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-proteinas', 'nome' => 'EAS - Proteínas', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => 'Negativo', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-glicose', 'nome' => 'EAS - Glicose', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => 'Negativo', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-hemoglobina', 'nome' => 'EAS - Hemoglobina', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => 'Negativo', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-leucocitos', 'nome' => 'EAS - Leucócitos', 'categoria' => 'liquidos', 'unidade' => '/campo', 'faixa_referencia' => '< 5', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-hemacias', 'nome' => 'EAS - Hemácias', 'categoria' => 'liquidos', 'unidade' => '/campo', 'faixa_referencia' => '< 3', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-nitrito', 'nome' => 'EAS - Nitrito', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => 'Negativo', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-cetonas', 'nome' => 'EAS - Cetonas', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => 'Negativo', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-bilirrubina', 'nome' => 'EAS - Bilirrubina', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => 'Negativo', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-urobilinogenio', 'nome' => 'EAS - Urobilinogênio', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => 'Normal', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-celulas-epiteliais', 'nome' => 'EAS - Células epiteliais', 'categoria' => 'liquidos', 'unidade' => '/campo', 'faixa_referencia' => 'Raras', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-cilindros', 'nome' => 'EAS - Cilindros', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => 'Ausentes', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-cristais', 'nome' => 'EAS - Cristais', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => 'Ausentes', 'tipo_resultado' => 'numeric'],
            ['id' => 'eas-bacterias', 'nome' => 'EAS - Bactérias', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => 'Ausentes', 'tipo_resultado' => 'numeric'],
            ['id' => 'urocultura', 'nome' => 'Urocultura', 'categoria' => 'microbiologia', 'unidade' => '-', 'faixa_referencia' => 'Negativa', 'tipo_resultado' => 'numeric'],
            ['id' => 'microalbuminuria', 'nome' => 'Microalbuminúria', 'categoria' => 'liquidos', 'unidade' => 'mg/L', 'faixa_referencia' => '< 20', 'tipo_resultado' => 'numeric'],
            ['id' => 'relacao-albumina-creatinina', 'nome' => 'Relação albumina/creatinina urinária', 'categoria' => 'liquidos', 'unidade' => 'mg/g', 'faixa_referencia' => '< 30', 'tipo_resultado' => 'numeric'],
            ['id' => 'calcio-urinario', 'nome' => 'Cálcio urinário 24h', 'categoria' => 'liquidos', 'unidade' => 'mg/24h', 'faixa_referencia' => '100-300 (H) / 100-250 (M)', 'tipo_resultado' => 'numeric'],
            ['id' => 'magnesio-urinario', 'nome' => 'Magnésio urinário 24h', 'categoria' => 'liquidos', 'unidade' => 'mg/24h', 'faixa_referencia' => '73-122', 'tipo_resultado' => 'numeric'],
            ['id' => 'creatinina-urinaria', 'nome' => 'Creatinina urinária', 'categoria' => 'liquidos', 'unidade' => 'mg/dL', 'faixa_referencia' => '30-300', 'tipo_resultado' => 'numeric'],
            ['id' => 'relacao-ca-cr', 'nome' => 'Relação Ca²⁺/Cr', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => '< 0,20', 'tipo_resultado' => 'numeric'],
            ['id' => 'indican-urinario', 'nome' => 'Indicam urinário', 'categoria' => 'liquidos', 'unidade' => '-', 'faixa_referencia' => 'Negativo', 'tipo_resultado' => 'numeric'],

            // Cardiológico (complementar)
            ['id' => 'bnp', 'nome' => 'BNP ou NT-proBNP', 'categoria' => 'bioquimica', 'unidade' => 'pg/mL', 'faixa_referencia' => '< 100 (BNP) / < 300 (NT-proBNP)', 'tipo_resultado' => 'numeric'],
            ['id' => 'troponina', 'nome' => 'Troponina ultrassensível', 'categoria' => 'bioquimica', 'unidade' => 'ng/L', 'faixa_referencia' => '< 14 (H) / < 10 (M)', 'tipo_resultado' => 'numeric'],
        ];

        CatalogoExameLaboratorial::query()->upsert(
            $items,
            uniqueBy: ['id'],
            update: ['nome', 'categoria', 'unidade', 'faixa_referencia', 'tipo_resultado'],
        );
    }
}
