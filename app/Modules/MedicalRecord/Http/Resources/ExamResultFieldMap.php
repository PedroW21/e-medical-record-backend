<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use App\Modules\MedicalRecord\Enums\ExamType;

trait ExamResultFieldMap
{
    /**
     * Returns the API field → DB column mapping for a given exam type.
     *
     * Keys that use dot-notation (e.g. "common_carotid_left.intimal_thickness") indicate
     * that the API sends a nested object whose leaf value maps to a flat DB column.
     *
     * @return array<string, string>
     */
    public static function apiToDbMap(ExamType $examType): array
    {
        $common = [
            'date' => 'data',
            'anexo_id' => 'anexo_id',
        ];

        $specific = match ($examType) {
            ExamType::Ecg => [
                'pattern' => 'padrao',
                'custom_text' => 'texto_personalizado',
            ],
            ExamType::Xray => [
                'pattern' => 'padrao',
                'custom_text' => 'texto_personalizado',
            ],
            ExamType::FreeText => [
                'type' => 'tipo',
                'text' => 'texto',
            ],
            ExamType::Temperature => [
                'time' => 'hora',
                'value' => 'valor',
            ],
            ExamType::HepaticElastography => [
                'fat_fraction' => 'fracao_gordura',
                'tsi' => 'tsi',
                'kpa' => 'kpa',
                'observations' => 'observacoes',
            ],
            ExamType::Mapa => [
                'systolic_awake' => 'pas_vigilia',
                'diastolic_awake' => 'pad_vigilia',
                'systolic_sleep' => 'pas_sono',
                'diastolic_sleep' => 'pad_sono',
                'systolic_24h' => 'pas_24h',
                'diastolic_24h' => 'pad_24h',
                'systolic_24h_override' => 'pas_24h_override',
                'diastolic_24h_override' => 'pad_24h_override',
                'nocturnal_dipping_systolic' => 'descenso_noturno_pas',
                'nocturnal_dipping_systolic_override' => 'descenso_noturno_pas_override',
                'nocturnal_dipping_diastolic' => 'descenso_noturno_pad',
                'nocturnal_dipping_diastolic_override' => 'descenso_noturno_pad_override',
                'notes' => 'observacoes',
            ],
            ExamType::Dexa => [
                'total_weight' => 'peso_total',
                'bmd' => 'dmo',
                't_score' => 't_score',
                'body_fat_pct' => 'gordura_corporal_pct',
                'total_fat' => 'gordura_total',
                'bmi' => 'imc',
                'visceral_fat' => 'gordura_visceral',
                'visceral_fat_pct' => 'gordura_visceral_pct',
                'lean_mass' => 'massa_magra',
                'lean_mass_pct' => 'massa_magra_pct',
                'fmi' => 'fmi',
                'ffmi' => 'ffmi',
                'rsmi' => 'rsmi',
                'rmr' => 'tmr',
            ],
            ExamType::ErgometricTest => [
                'protocol' => 'protocolo',
                'hr_max_predicted_pct' => 'pct_fc_max_prevista',
                'hr_max' => 'fc_max',
                'bp_systolic_max' => 'pas_max',
                'bp_systolic_pre' => 'pas_pre',
                'vo2_max' => 'vo2_max',
                'mvo2_max' => 'mvo2_max',
                'chronotropic_deficit' => 'deficit_cronotropico',
                'lv_functional_deficit' => 'deficit_funcional_ve',
                'cardiac_output' => 'debito_cardiaco',
                'stroke_volume' => 'volume_sistolico',
                'dp_max' => 'dp_max',
                'met_max' => 'met_max',
                'cardio_respiratory_fitness' => 'aptidao_cardiorrespiratoria',
                'observations' => 'observacoes',
            ],
            ExamType::CarotidEcodoppler => [
                'common_carotid_left.intimal_thickness' => 'espessura_intimal_carotida_comum_e',
                'common_carotid_left.stenosis_degree' => 'grau_estenose_carotida_comum_e',
                'common_carotid_right.intimal_thickness' => 'espessura_intimal_carotida_comum_d',
                'common_carotid_right.stenosis_degree' => 'grau_estenose_carotida_comum_d',
                'bulb_internal_left.intimal_thickness' => 'espessura_intimal_bulbo_interna_e',
                'bulb_internal_left.stenosis_degree' => 'grau_estenose_bulbo_interna_e',
                'bulb_internal_right.intimal_thickness' => 'espessura_intimal_bulbo_interna_d',
                'bulb_internal_right.stenosis_degree' => 'grau_estenose_bulbo_interna_d',
                'external_carotid_left.intimal_thickness' => 'espessura_intimal_carotida_externa_e',
                'external_carotid_left.stenosis_degree' => 'grau_estenose_carotida_externa_e',
                'external_carotid_right.intimal_thickness' => 'espessura_intimal_carotida_externa_d',
                'external_carotid_right.stenosis_degree' => 'grau_estenose_carotida_externa_d',
                'vertebral_left.intimal_thickness' => 'espessura_intimal_vertebral_e',
                'vertebral_left.stenosis_degree' => 'grau_estenose_vertebral_e',
                'vertebral_right.intimal_thickness' => 'espessura_intimal_vertebral_d',
                'vertebral_right.stenosis_degree' => 'grau_estenose_vertebral_d',
                'observations' => 'observacoes',
            ],
            ExamType::Echo => [
                'type' => 'tipo',
                'aorta_root' => 'raiz_aorta',
                'aorta_ascending' => 'aorta_ascendente',
                'aortic_arch' => 'arco_aortico',
                'la_mm' => 'ae_mm',
                'la_ml' => 'ae_ml',
                'la_indexed' => 'ae_indexado',
                'septum' => 'septo',
                'rvd' => 'dvd',
                'lvedd' => 'ddve',
                'lvesd' => 'dsve',
                'pw' => 'pp',
                'rwt' => 'erp',
                'lv_mass_index' => 'indice_massa_ve',
                'ef' => 'fe',
                'pasp' => 'psap',
                'tapse' => 'tapse',
                'e_mitral' => 'onda_e_mitral',
                'a_wave' => 'onda_a',
                'e_a_ratio' => 'relacao_e_a',
                'e_a_ratio_override' => 'relacao_e_a_override',
                'e_septal' => 'e_septal',
                'e_lateral' => 'e_lateral',
                'e_e_ratio' => 'relacao_e_e',
                's_tricuspid' => 's_tricuspide',
                'valve_aortic' => 'valva_aortica',
                'valve_mitral' => 'valva_mitral',
                'valve_tricuspid' => 'valva_tricuspide',
                'qualitative_analysis' => 'analise_qualitativa',
            ],
            ExamType::Mrpa => [
                'days_monitored' => 'dias_monitorados',
                'limb' => 'membro',
                'observations' => 'observacoes',
            ],
            ExamType::Cat => [
                'cd' => 'cd',
                'ce' => 'ce',
                'da' => 'da',
                'cx' => 'cx',
                'd1' => 'd1',
                'd2' => 'd2',
                'mge' => 'mge',
                'mgd' => 'mgd',
                'dp' => 'dp',
                'stents' => 'stents',
                'observations' => 'observacoes',
            ],
            ExamType::Scintigraphy => [
                'protocol' => 'protocolo',
                'stress_modality' => 'modalidade_estresse',
                'hr_max' => 'fc_max',
                'hr_max_predicted_pct' => 'pct_fc_max_prevista',
                'bp_max' => 'pa_max',
                'stress_symptoms' => 'sintomas_estresse',
                'stress_ecg_changes' => 'alteracoes_ecg_estresse',
                'perfusion_da.stress' => 'perfusao_da_estresse',
                'perfusion_da.rest' => 'perfusao_da_repouso',
                'perfusion_da.reversibility' => 'perfusao_da_reversibilidade',
                'perfusion_cx.stress' => 'perfusao_cx_estresse',
                'perfusion_cx.rest' => 'perfusao_cx_repouso',
                'perfusion_cx.reversibility' => 'perfusao_cx_reversibilidade',
                'perfusion_cd.stress' => 'perfusao_cd_estresse',
                'perfusion_cd.rest' => 'perfusao_cd_repouso',
                'perfusion_cd.reversibility' => 'perfusao_cd_reversibilidade',
                'sss' => 'sss',
                'srs' => 'srs',
                'sds' => 'sds',
                'sds_override' => 'sds_override',
                'sds_classification' => 'classificacao_sds',
                'sds_classification_override' => 'classificacao_sds_override',
                'ef_rest' => 'fe_repouso',
                'edv_rest' => 'vdf_repouso',
                'esv_rest' => 'vsf_repouso',
                'ef_stress' => 'fe_estresse',
                'edv_stress' => 'vdf_estresse',
                'esv_stress' => 'vsf_estresse',
                'tid_present' => 'tid_presente',
                'tid_ratio' => 'razao_tid',
                'tid_override' => 'tid_override',
                'segments' => 'segmentos',
                'increased_lung_uptake' => 'captacao_pulmonar_aumentada',
                'rv_dilation' => 'dilatacao_vd',
                'extracardiac_uptake' => 'captacao_extracardiaca',
                'global_result' => 'resultado_global',
                'defect_extent' => 'extensao_defeito',
                'observations' => 'observacoes',
            ],
            ExamType::DiabeticFoot => [
                'anamnesis' => 'anamnese',
                'neuropathic_symptoms' => 'sintomas_neuropaticos',
                'visual_inspection' => 'inspecao_visual',
                'deformities' => 'deformidades',
                'neurological' => 'neurologico',
                'vascular' => 'vascular',
                'thermometry' => 'termometria',
                'nss_score' => 'nss_score',
                'nss_override' => 'nss_override',
                'nds_score' => 'nds_score',
                'nds_override' => 'nds_override',
                'itb_right' => 'itb_direito',
                'itb_left' => 'itb_esquerdo',
                'itb_right_override' => 'itb_direito_override',
                'itb_left_override' => 'itb_esquerdo_override',
                'tbi_right' => 'tbi_direito',
                'tbi_left' => 'tbi_esquerdo',
                'tbi_right_override' => 'tbi_direito_override',
                'tbi_left_override' => 'tbi_esquerdo_override',
                'iwgdf_category' => 'categoria_iwgdf',
                'iwgdf_override' => 'categoria_iwgdf_override',
                'observations' => 'observacoes',
            ],
        };

        return array_merge($common, $specific);
    }

    /**
     * Returns the DB column → API field mapping for a given exam type (inverse of apiToDbMap).
     *
     * @return array<string, string>
     */
    public static function dbToApiMap(ExamType $examType): array
    {
        return array_flip(self::apiToDbMap($examType));
    }
}
