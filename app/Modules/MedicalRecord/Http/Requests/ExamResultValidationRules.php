<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\ExamType;

trait ExamResultValidationRules
{
    /**
     * Returns validation rules for storing a given exam type.
     *
     * @return array<string, mixed>
     */
    public function storeRulesFor(ExamType $examType): array
    {
        $base = [
            'date' => ['required', 'date', 'before_or_equal:today'],
        ];

        $specific = match ($examType) {
            ExamType::Ecg => [
                'pattern' => ['required', 'string', 'in:normal,right_deviation,left_deviation,altered'],
                'custom_text' => ['nullable', 'string', 'max:5000'],
            ],

            ExamType::Xray => [
                'pattern' => ['required', 'string', 'in:normal,poor_quality,altered'],
                'custom_text' => ['nullable', 'string', 'max:5000'],
            ],

            ExamType::FreeText => [
                'type' => ['required', 'string', 'in:holter,polysomnography,other'],
                'text' => ['required', 'string', 'max:10000'],
            ],

            ExamType::Temperature => [
                'time' => ['required', 'date_format:H:i'],
                'value' => ['required', 'numeric', 'min:30', 'max:45'],
            ],

            ExamType::HepaticElastography => [
                'fat_fraction' => ['nullable', 'numeric', 'min:0'],
                'tsi' => ['nullable', 'numeric', 'min:0'],
                'kpa' => ['nullable', 'numeric', 'min:0'],
                'observations' => ['nullable', 'string', 'max:5000'],
            ],

            ExamType::Mapa => [
                'systolic_awake' => ['nullable', 'numeric', 'min:0', 'max:300'],
                'diastolic_awake' => ['nullable', 'numeric', 'min:0', 'max:200'],
                'systolic_sleep' => ['nullable', 'numeric', 'min:0', 'max:300'],
                'diastolic_sleep' => ['nullable', 'numeric', 'min:0', 'max:200'],
                'systolic_24h' => ['nullable', 'numeric', 'min:0', 'max:300'],
                'diastolic_24h' => ['nullable', 'numeric', 'min:0', 'max:200'],
                'systolic_24h_override' => ['nullable', 'boolean'],
                'diastolic_24h_override' => ['nullable', 'boolean'],
                'nocturnal_dipping_systolic' => ['nullable', 'numeric'],
                'nocturnal_dipping_systolic_override' => ['nullable', 'boolean'],
                'nocturnal_dipping_diastolic' => ['nullable', 'numeric'],
                'nocturnal_dipping_diastolic_override' => ['nullable', 'boolean'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ],

            ExamType::Dexa => [
                'total_weight' => ['nullable', 'numeric', 'min:0'],
                'bmd' => ['nullable', 'numeric', 'min:0'],
                't_score' => ['nullable', 'numeric'],
                'body_fat_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'total_fat' => ['nullable', 'numeric', 'min:0'],
                'bmi' => ['nullable', 'numeric', 'min:0'],
                'visceral_fat' => ['nullable', 'numeric', 'min:0'],
                'visceral_fat_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'lean_mass' => ['nullable', 'numeric', 'min:0'],
                'lean_mass_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'fmi' => ['nullable', 'numeric', 'min:0'],
                'ffmi' => ['nullable', 'numeric', 'min:0'],
                'rsmi' => ['nullable', 'numeric', 'min:0'],
                'rmr' => ['nullable', 'numeric', 'min:0'],
            ],

            ExamType::ErgometricTest => [
                'protocol' => ['nullable', 'string', 'in:bruce,bruce_modified,ellestad,naughton,balke,ramp'],
                'hr_max_predicted_pct' => ['nullable', 'numeric', 'min:0', 'max:200'],
                'hr_max' => ['nullable', 'integer', 'min:0', 'max:300'],
                'bp_systolic_max' => ['nullable', 'integer', 'min:0', 'max:400'],
                'bp_systolic_pre' => ['nullable', 'integer', 'min:0', 'max:400'],
                'vo2_max' => ['nullable', 'numeric'],
                'mvo2_max' => ['nullable', 'numeric'],
                'chronotropic_deficit' => ['nullable', 'numeric'],
                'lv_functional_deficit' => ['nullable', 'numeric'],
                'cardiac_output' => ['nullable', 'numeric'],
                'stroke_volume' => ['nullable', 'numeric'],
                'dp_max' => ['nullable', 'integer', 'min:0'],
                'met_max' => ['nullable', 'numeric'],
                'cardio_respiratory_fitness' => ['nullable', 'string', 'in:low,moderate,excellent'],
                'observations' => ['nullable', 'string', 'max:5000'],
            ],

            ExamType::CarotidEcodoppler => [
                'common_carotid_left' => ['nullable', 'array'],
                'common_carotid_left.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'common_carotid_left.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'common_carotid_right' => ['nullable', 'array'],
                'common_carotid_right.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'common_carotid_right.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'external_carotid_left' => ['nullable', 'array'],
                'external_carotid_left.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'external_carotid_left.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'external_carotid_right' => ['nullable', 'array'],
                'external_carotid_right.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'external_carotid_right.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'bulb_internal_left' => ['nullable', 'array'],
                'bulb_internal_left.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'bulb_internal_left.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'bulb_internal_right' => ['nullable', 'array'],
                'bulb_internal_right.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'bulb_internal_right.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'vertebral_left' => ['nullable', 'array'],
                'vertebral_left.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'vertebral_left.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'vertebral_right' => ['nullable', 'array'],
                'vertebral_right.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'vertebral_right.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'observations' => ['nullable', 'string', 'max:5000'],
            ],

            ExamType::Echo => [
                'type' => ['required', 'string', 'in:transthoracic,transesophageal'],
                'aorta_root' => ['nullable', 'numeric', 'min:0'],
                'aorta_ascending' => ['nullable', 'numeric', 'min:0'],
                'aortic_arch' => ['nullable', 'numeric', 'min:0'],
                'la_mm' => ['nullable', 'numeric', 'min:0'],
                'la_ml' => ['nullable', 'numeric', 'min:0'],
                'la_indexed' => ['nullable', 'numeric', 'min:0'],
                'septum' => ['nullable', 'numeric', 'min:0'],
                'rvd' => ['nullable', 'numeric', 'min:0'],
                'lvedd' => ['nullable', 'numeric', 'min:0'],
                'lvesd' => ['nullable', 'numeric', 'min:0'],
                'pw' => ['nullable', 'numeric', 'min:0'],
                'rwt' => ['nullable', 'numeric', 'min:0'],
                'lv_mass_index' => ['nullable', 'numeric', 'min:0'],
                'ef' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'pasp' => ['nullable', 'numeric', 'min:0'],
                'tapse' => ['nullable', 'numeric', 'min:0'],
                'e_mitral' => ['nullable', 'numeric', 'min:0'],
                'a_wave' => ['nullable', 'numeric', 'min:0'],
                'e_a_ratio' => ['nullable', 'numeric', 'min:0'],
                'e_a_ratio_override' => ['nullable', 'boolean'],
                'e_septal' => ['nullable', 'numeric', 'min:0'],
                'e_lateral' => ['nullable', 'numeric', 'min:0'],
                'e_e_ratio' => ['nullable', 'numeric', 'min:0'],
                's_tricuspid' => ['nullable', 'numeric', 'min:0'],
                'valve_aortic' => ['nullable', 'array'],
                'valve_aortic.status' => ['nullable', 'string', 'in:regular,alterada'],
                'valve_aortic.description' => ['nullable', 'string', 'max:1000'],
                'valve_mitral' => ['nullable', 'array'],
                'valve_mitral.status' => ['nullable', 'string', 'in:regular,alterada'],
                'valve_mitral.description' => ['nullable', 'string', 'max:1000'],
                'valve_tricuspid' => ['nullable', 'array'],
                'valve_tricuspid.status' => ['nullable', 'string', 'in:regular,alterada'],
                'valve_tricuspid.description' => ['nullable', 'string', 'max:1000'],
                'qualitative_analysis' => ['nullable', 'string', 'max:5000'],
            ],

            ExamType::Mrpa => [
                'days_monitored' => ['required', 'integer', 'min:1', 'max:30'],
                'limb' => ['required', 'string', 'in:right_arm,left_arm'],
                'observations' => ['nullable', 'string', 'max:5000'],
                'measurements' => ['required', 'array', 'min:1'],
                'measurements.*.date' => ['required', 'date'],
                'measurements.*.time' => ['required', 'date_format:H:i'],
                'measurements.*.period' => ['required', 'string', 'in:morning,evening'],
                'measurements.*.systolic' => ['required', 'integer', 'min:0', 'max:400'],
                'measurements.*.diastolic' => ['required', 'integer', 'min:0', 'max:300'],
            ],

            ExamType::Cat => [
                'cd' => ['nullable', 'array'],
                'ce' => ['nullable', 'array'],
                'da' => ['nullable', 'array'],
                'cx' => ['nullable', 'array'],
                'd1' => ['nullable', 'array'],
                'd2' => ['nullable', 'array'],
                'mge' => ['nullable', 'array'],
                'mgd' => ['nullable', 'array'],
                'dp' => ['nullable', 'array'],
                'stents' => ['nullable', 'array'],
                'stents.*.artery' => ['required', 'string', 'in:cd,ce,da,cx,d1,d2,mge,mgd,dp'],
                'stents.*.status' => ['nullable', 'string', 'in:pervio,obstruido'],
                'observations' => ['nullable', 'string', 'max:5000'],
            ],

            ExamType::Scintigraphy => [
                'protocol' => ['nullable', 'string', 'in:one_day_stress_rest,one_day_rest_stress,two_day'],
                'stress_modality' => ['nullable', 'string', 'in:physical,pharmacological,combined'],
                'hr_max' => ['nullable', 'integer', 'min:0'],
                'hr_max_predicted_pct' => ['nullable', 'numeric', 'min:0', 'max:200'],
                'bp_max' => ['nullable', 'integer', 'min:0'],
                'stress_symptoms' => ['nullable', 'array'],
                'stress_symptoms.*' => ['string', 'in:chest_pain,dyspnea,dizziness,none'],
                'stress_ecg_changes' => ['nullable', 'array'],
                'stress_ecg_changes.*' => ['string', 'in:st_depression,st_elevation,arrhythmia,none'],
                'perfusion_da' => ['nullable', 'array'],
                'perfusion_da.stress' => ['nullable', 'string', 'in:normal,mild_hypoperfusion,moderate_hypoperfusion,severe_hypoperfusion,absent'],
                'perfusion_da.rest' => ['nullable', 'string', 'in:normal,mild_hypoperfusion,moderate_hypoperfusion,severe_hypoperfusion,absent'],
                'perfusion_da.reversibility' => ['nullable', 'string', 'in:reversible,partially_reversible,fixed,reverse_redistribution'],
                'perfusion_cx' => ['nullable', 'array'],
                'perfusion_cx.stress' => ['nullable', 'string', 'in:normal,mild_hypoperfusion,moderate_hypoperfusion,severe_hypoperfusion,absent'],
                'perfusion_cx.rest' => ['nullable', 'string', 'in:normal,mild_hypoperfusion,moderate_hypoperfusion,severe_hypoperfusion,absent'],
                'perfusion_cx.reversibility' => ['nullable', 'string', 'in:reversible,partially_reversible,fixed,reverse_redistribution'],
                'perfusion_cd' => ['nullable', 'array'],
                'perfusion_cd.stress' => ['nullable', 'string', 'in:normal,mild_hypoperfusion,moderate_hypoperfusion,severe_hypoperfusion,absent'],
                'perfusion_cd.rest' => ['nullable', 'string', 'in:normal,mild_hypoperfusion,moderate_hypoperfusion,severe_hypoperfusion,absent'],
                'perfusion_cd.reversibility' => ['nullable', 'string', 'in:reversible,partially_reversible,fixed,reverse_redistribution'],
                'sss' => ['nullable', 'integer', 'min:0'],
                'srs' => ['nullable', 'integer', 'min:0'],
                'sds' => ['nullable', 'integer', 'min:0'],
                'sds_override' => ['nullable', 'boolean'],
                'sds_classification' => ['nullable', 'string', 'in:normal,mild_ischemia,moderate_ischemia,severe_ischemia'],
                'sds_classification_override' => ['nullable', 'boolean'],
                'ef_rest' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'edv_rest' => ['nullable', 'numeric', 'min:0'],
                'esv_rest' => ['nullable', 'numeric', 'min:0'],
                'ef_stress' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'edv_stress' => ['nullable', 'numeric', 'min:0'],
                'esv_stress' => ['nullable', 'numeric', 'min:0'],
                'tid_present' => ['nullable', 'boolean'],
                'tid_ratio' => ['nullable', 'numeric', 'min:0'],
                'tid_override' => ['nullable', 'boolean'],
                'segments' => ['nullable', 'array'],
                'increased_lung_uptake' => ['nullable', 'boolean'],
                'rv_dilation' => ['nullable', 'boolean'],
                'extracardiac_uptake' => ['nullable', 'string', 'max:5000'],
                'global_result' => ['nullable', 'string', 'in:normal,ischemia,fibrosis,mixed'],
                'defect_extent' => ['nullable', 'string', 'in:small,moderate,large'],
                'observations' => ['nullable', 'string', 'max:5000'],
            ],

            ExamType::DiabeticFoot => [
                'anamnesis' => ['nullable', 'array'],
                'neuropathic_symptoms' => ['nullable', 'array'],
                'visual_inspection' => ['nullable', 'array'],
                'deformities' => ['nullable', 'array'],
                'neurological' => ['nullable', 'array'],
                'vascular' => ['nullable', 'array'],
                'thermometry' => ['nullable', 'array'],
                'nss_score' => ['nullable', 'integer', 'min:0'],
                'nss_override' => ['nullable', 'boolean'],
                'nds_score' => ['nullable', 'integer', 'min:0'],
                'nds_override' => ['nullable', 'boolean'],
                'itb_right' => ['nullable', 'numeric', 'min:0'],
                'itb_left' => ['nullable', 'numeric', 'min:0'],
                'itb_right_override' => ['nullable', 'boolean'],
                'itb_left_override' => ['nullable', 'boolean'],
                'tbi_right' => ['nullable', 'numeric', 'min:0'],
                'tbi_left' => ['nullable', 'numeric', 'min:0'],
                'tbi_right_override' => ['nullable', 'boolean'],
                'tbi_left_override' => ['nullable', 'boolean'],
                'iwgdf_category' => ['nullable', 'integer', 'in:0,1,2,3'],
                'iwgdf_override' => ['nullable', 'boolean'],
                'observations' => ['nullable', 'string', 'max:5000'],
            ],
        };

        return array_merge($base, $specific);
    }

    /**
     * Returns Portuguese validation messages for exam result fields.
     *
     * @return array<string, string>
     */
    public function examMessages(): array
    {
        return [
            'date.required' => 'A data do exame é obrigatória.',
            'date.date' => 'A data do exame deve ser uma data válida.',
            'date.before_or_equal' => 'A data do exame não pode ser futura.',

            'pattern.required' => 'O padrão do exame é obrigatório.',
            'pattern.in' => 'O padrão informado não é válido.',

            'type.required' => 'O tipo do exame é obrigatório.',
            'type.in' => 'O tipo informado não é válido.',

            'text.required' => 'O texto do exame é obrigatório.',
            'text.max' => 'O texto do exame não pode ultrapassar 10.000 caracteres.',

            'custom_text.max' => 'O texto personalizado não pode ultrapassar 5.000 caracteres.',

            'time.required' => 'O horário é obrigatório.',
            'time.date_format' => 'O horário deve estar no formato HH:mm.',

            'value.required' => 'O valor da temperatura é obrigatório.',
            'value.numeric' => 'O valor da temperatura deve ser um número.',
            'value.min' => 'O valor da temperatura deve ser no mínimo 30°C.',
            'value.max' => 'O valor da temperatura deve ser no máximo 45°C.',

            'days_monitored.required' => 'O número de dias monitorados é obrigatório.',
            'days_monitored.integer' => 'O número de dias monitorados deve ser um número inteiro.',
            'days_monitored.min' => 'O número de dias monitorados deve ser no mínimo 1.',
            'days_monitored.max' => 'O número de dias monitorados deve ser no máximo 30.',

            'limb.required' => 'O membro utilizado é obrigatório.',
            'limb.in' => 'O membro informado não é válido. Use "right_arm" ou "left_arm".',

            'measurements.required' => 'É obrigatório informar ao menos uma medição.',
            'measurements.array' => 'As medições devem ser uma lista.',
            'measurements.min' => 'É obrigatório informar ao menos uma medição.',
            'measurements.*.date.required' => 'A data de cada medição é obrigatória.',
            'measurements.*.date.date' => 'A data da medição deve ser uma data válida.',
            'measurements.*.time.required' => 'O horário de cada medição é obrigatório.',
            'measurements.*.time.date_format' => 'O horário da medição deve estar no formato HH:mm.',
            'measurements.*.period.required' => 'O período de cada medição é obrigatório.',
            'measurements.*.period.in' => 'O período da medição deve ser "morning" ou "evening".',
            'measurements.*.systolic.required' => 'A pressão sistólica de cada medição é obrigatória.',
            'measurements.*.systolic.integer' => 'A pressão sistólica deve ser um número inteiro.',
            'measurements.*.diastolic.required' => 'A pressão diastólica de cada medição é obrigatória.',
            'measurements.*.diastolic.integer' => 'A pressão diastólica deve ser um número inteiro.',

            'stents.*.artery.required' => 'A artéria do stent é obrigatória.',
            'stents.*.artery.in' => 'A artéria do stent informada não é válida.',

            'observations.max' => 'As observações não podem ultrapassar 5.000 caracteres.',
            'notes.max' => 'As observações não podem ultrapassar 5.000 caracteres.',

            'protocol.in' => 'O protocolo informado não é válido.',
            'cardio_respiratory_fitness.in' => 'A aptidão cardiorrespiratória informada não é válida.',

            'ef.max' => 'A fração de ejeção não pode ultrapassar 100%.',

            'valve_aortic.status.in' => 'O status da valva aórtica não é válido.',
            'valve_mitral.status.in' => 'O status da valva mitral não é válido.',
            'valve_tricuspid.status.in' => 'O status da valva tricúspide não é válido.',

            'global_result.in' => 'O resultado global informado não é válido.',
            'defect_extent.in' => 'A extensão do defeito informada não é válida.',
            'sds_classification.in' => 'A classificação do SDS informada não é válida.',

            'stress_modality.in' => 'A modalidade de estresse informada não é válida.',
            'stress_symptoms.*.in' => 'Um ou mais sintomas de estresse informados não são válidos.',
            'stress_ecg_changes.*.in' => 'Uma ou mais alterações de ECG informadas não são válidas.',

            'perfusion_da.stress.in' => 'O grau de perfusão da DA no estresse não é válido.',
            'perfusion_da.rest.in' => 'O grau de perfusão da DA no repouso não é válido.',
            'perfusion_da.reversibility.in' => 'O tipo de reversibilidade da DA não é válido.',
            'perfusion_cx.stress.in' => 'O grau de perfusão da CX no estresse não é válido.',
            'perfusion_cx.rest.in' => 'O grau de perfusão da CX no repouso não é válido.',
            'perfusion_cx.reversibility.in' => 'O tipo de reversibilidade da CX não é válido.',
            'perfusion_cd.stress.in' => 'O grau de perfusão da CD no estresse não é válido.',
            'perfusion_cd.rest.in' => 'O grau de perfusão da CD no repouso não é válido.',
            'perfusion_cd.reversibility.in' => 'O tipo de reversibilidade da CD não é válido.',
        ];
    }
}
