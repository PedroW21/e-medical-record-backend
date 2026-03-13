<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait MedicalRecordValidationRules
{
    /**
     * Validation rules for the anthropometry JSONB field.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function anthropometryRules(): array
    {
        return [
            'anthropometry' => ['nullable', 'array'],
            'anthropometry.blood_pressure' => ['nullable', 'array'],
            'anthropometry.blood_pressure.right_arm' => ['nullable', 'array'],
            'anthropometry.blood_pressure.right_arm.standing' => ['nullable', 'array'],
            'anthropometry.blood_pressure.right_arm.standing.systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'anthropometry.blood_pressure.right_arm.standing.diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'anthropometry.blood_pressure.right_arm.sitting' => ['nullable', 'array'],
            'anthropometry.blood_pressure.right_arm.sitting.systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'anthropometry.blood_pressure.right_arm.sitting.diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'anthropometry.blood_pressure.right_arm.supine' => ['nullable', 'array'],
            'anthropometry.blood_pressure.right_arm.supine.systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'anthropometry.blood_pressure.right_arm.supine.diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'anthropometry.blood_pressure.left_arm' => ['nullable', 'array'],
            'anthropometry.blood_pressure.left_arm.standing' => ['nullable', 'array'],
            'anthropometry.blood_pressure.left_arm.standing.systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'anthropometry.blood_pressure.left_arm.standing.diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'anthropometry.blood_pressure.left_arm.sitting' => ['nullable', 'array'],
            'anthropometry.blood_pressure.left_arm.sitting.systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'anthropometry.blood_pressure.left_arm.sitting.diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'anthropometry.blood_pressure.left_arm.supine' => ['nullable', 'array'],
            'anthropometry.blood_pressure.left_arm.supine.systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'anthropometry.blood_pressure.left_arm.supine.diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'anthropometry.blood_pressure.heart_rate' => ['nullable', 'integer', 'min:20', 'max:250'],
            'anthropometry.blood_pressure.oxygen_sat' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'anthropometry.blood_pressure.temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'anthropometry.measures' => ['nullable', 'array'],
            'anthropometry.measures.weight' => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'anthropometry.measures.height' => ['nullable', 'numeric', 'min:30', 'max:280'],
            'anthropometry.measures.bmi' => ['nullable', 'numeric', 'min:5', 'max:100'],
            'anthropometry.measures.bmi_classification' => ['nullable', 'string', Rule::in(['underweight', 'normal', 'overweight', 'obesity_1', 'obesity_2', 'obesity_3'])],
            'anthropometry.measures.abdominal_circumference' => ['nullable', 'numeric', 'min:30', 'max:200'],
            'anthropometry.measures.hip_circumference' => ['nullable', 'numeric', 'min:30', 'max:200'],
            'anthropometry.measures.waist_hip_ratio' => ['nullable', 'numeric', 'min:0', 'max:3'],
            'anthropometry.measures.waist_height_ratio' => ['nullable', 'numeric', 'min:0', 'max:3'],
            'anthropometry.measures.cervical_circumference' => ['nullable', 'numeric', 'min:20', 'max:70'],
            'anthropometry.measures.calf_measurement_left' => ['nullable', 'numeric', 'min:15', 'max:70'],
            'anthropometry.measures.calf_measurement_right' => ['nullable', 'numeric', 'min:15', 'max:70'],
            'anthropometry.measures.mouth_opening' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'anthropometry.measures.thyromental_distance' => ['nullable', 'numeric', 'min:0', 'max:15'],
            'anthropometry.measures.mentosternal_distance' => ['nullable', 'numeric', 'min:0', 'max:25'],
            'anthropometry.measures.mandible_displacement' => ['nullable', 'string', Rule::in(['good', 'reduced'])],
            'anthropometry.skinfolds' => ['nullable', 'array'],
            'anthropometry.skinfolds.triceps' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'anthropometry.skinfolds.subscapular' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'anthropometry.skinfolds.suprailiac' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'anthropometry.skinfolds.abdominal' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'anthropometry.skinfolds.pectoral' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'anthropometry.skinfolds.medial_thigh' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'anthropometry.skinfolds.midaxillary' => ['nullable', 'numeric', 'min:1', 'max:80'],
        ];
    }

    /**
     * Validation rules for the physical_exam JSONB field.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function physicalExamRules(): array
    {
        return [
            'physical_exam' => ['nullable', 'array'],
            'physical_exam.cardiac' => ['required_with:physical_exam', 'array'],
            'physical_exam.cardiac.is_normal' => ['required_with:physical_exam.cardiac', 'boolean'],
            'physical_exam.cardiac.rhythm' => ['nullable', 'string', 'max:500'],
            'physical_exam.cardiac.heart_sounds' => ['nullable', 'string', 'max:500'],
            'physical_exam.cardiac.murmur' => ['nullable', 'string', 'max:500'],
            'physical_exam.cardiac.observations' => ['nullable', 'string', 'max:2000'],
            'physical_exam.respiratory' => ['required_with:physical_exam', 'array'],
            'physical_exam.respiratory.is_normal' => ['required_with:physical_exam.respiratory', 'boolean'],
            'physical_exam.respiratory.vesicular_murmur' => ['nullable', 'string', 'max:500'],
            'physical_exam.respiratory.adventitious_sounds' => ['nullable', 'string', 'max:500'],
            'physical_exam.respiratory.observations' => ['nullable', 'string', 'max:2000'],
            'physical_exam.lower_limbs' => ['required_with:physical_exam', 'array'],
            'physical_exam.lower_limbs.varicose_veins' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.edema' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.lymphedema' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.ulcer' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.asymmetry' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.sensitivity_alteration' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.motricity_alteration' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.observations' => ['nullable', 'string', 'max:2000'],
            'physical_exam.ceap' => ['nullable', 'integer', 'min:0', 'max:6'],
            'physical_exam.dentition' => ['required_with:physical_exam', 'array'],
            'physical_exam.dentition.status' => ['required_with:physical_exam.dentition', 'string', Rule::in(['regular', 'prosthesis', 'altered'])],
            'physical_exam.dentition.prosthesis_location' => ['nullable', 'array'],
            'physical_exam.dentition.prosthesis_location.*' => ['string', Rule::in(['superior', 'inferior'])],
            'physical_exam.dentition.diseases' => ['nullable', 'array'],
            'physical_exam.dentition.diseases.*' => ['string', Rule::in(['gingivitis', 'periodontitis', 'tartar'])],
            'physical_exam.dentition.observations' => ['nullable', 'string', 'max:2000'],
            'physical_exam.gums' => ['required_with:physical_exam', 'array'],
            'physical_exam.gums.status' => ['required_with:physical_exam.gums', 'string', Rule::in(['regular', 'altered'])],
            'physical_exam.gums.observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Validation rules for the problem_list JSONB field.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function problemListRules(): array
    {
        return [
            'problem_list' => ['nullable', 'array'],
            'problem_list.selected_problems' => ['nullable', 'array'],
            'problem_list.selected_problems.*.problem_id' => ['required', 'string', 'max:100'],
            'problem_list.selected_problems.*.label' => ['required', 'string', 'max:255'],
            'problem_list.selected_problems.*.category' => ['required', 'string', Rule::in(['inflammatory', 'hematologic', 'metabolic', 'gastrointestinal', 'endocrine', 'renal', 'musculoskeletal'])],
            'problem_list.selected_problems.*.is_custom' => ['required', 'boolean'],
            'problem_list.selected_problems.*.selected_variation' => ['nullable', 'string', 'max:100'],
            'problem_list.custom_problems' => ['nullable', 'array'],
            'problem_list.custom_problems.*' => ['string', 'max:500'],
        ];
    }

    /**
     * Validation rules for the risk_scores JSONB field.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function riskScoresRules(): array
    {
        return [
            'risk_scores' => ['nullable', 'array', Rule::requiredIf(fn () => $this->input('type') === 'pre_anesthetic')],
            'risk_scores.primary_disease' => ['nullable', 'string', 'max:500'],
            'risk_scores.planned_surgery' => ['nullable', 'string', 'max:500'],
            'risk_scores.cardiovascular' => ['required_with:risk_scores', 'array'],
            'risk_scores.pulmonary' => ['required_with:risk_scores', 'array'],
            'risk_scores.renal' => ['required_with:risk_scores', 'array'],
        ];
    }

    /**
     * Validation rules for the conduct JSONB field.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function conductRules(): array
    {
        return [
            'conduct' => ['nullable', 'array'],
            'conduct.sleep_hygiene' => ['required_with:conduct', 'boolean'],
            'conduct.sleep_default_text' => ['required_with:conduct', 'string', 'max:2000'],
            'conduct.sleep_observations' => ['nullable', 'string', 'max:2000'],
            'conduct.diets' => ['nullable', 'array'],
            'conduct.diets.*.type' => ['required', 'string', Rule::in(['dash', 'mediterranean', 'low_carb', 'high_fat', 'intermittent_fasting', 'carnivore', 'paleolithic', 'antihistamine', 'other'])],
            'conduct.diets.*.label' => ['required', 'string', 'max:255'],
            'conduct.diets.*.default_text' => ['required', 'string', 'max:2000'],
            'conduct.diets.*.custom_text' => ['nullable', 'string', 'max:2000'],
            'conduct.physical_activity' => ['required_with:conduct', 'array'],
            'conduct.physical_activity.default_text' => ['required', 'string', 'max:2000'],
            'conduct.physical_activity.custom_text' => ['nullable', 'string', 'max:2000'],
            'conduct.xenobiotics_restriction' => ['required_with:conduct', 'boolean'],
            'conduct.xenobiotics_default_text' => ['required_with:conduct', 'string', 'max:2000'],
            'conduct.xenobiotics_observations' => ['nullable', 'string', 'max:2000'],
            'conduct.medication_compliance' => ['required_with:conduct', 'boolean'],
            'conduct.medication_default_text' => ['required_with:conduct', 'string', 'max:2000'],
            'conduct.medication_observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Shared custom validation messages in Portuguese for JSONB fields.
     *
     * @return array<string, string>
     */
    protected function sharedMessages(): array
    {
        return [
            'anthropometry.blood_pressure.right_arm.sitting.systolic.min' => 'A pressão sistólica deve ser no mínimo 40 mmHg.',
            'anthropometry.blood_pressure.right_arm.sitting.systolic.max' => 'A pressão sistólica deve ser no máximo 300 mmHg.',
            'anthropometry.measures.weight.min' => 'O peso deve ser no mínimo 0,5 kg.',
            'anthropometry.measures.height.min' => 'A altura deve ser no mínimo 30 cm.',
            'physical_exam.cardiac.is_normal.required_with' => 'O campo de ausculta cardíaca normal é obrigatório.',
            'physical_exam.respiratory.is_normal.required_with' => 'O campo de ausculta respiratória normal é obrigatório.',
            'physical_exam.dentition.status.required_with' => 'O status da dentição é obrigatório.',
            'physical_exam.gums.status.required_with' => 'O status da gengiva é obrigatório.',
            'conduct.sleep_hygiene.required_with' => 'O campo de higiene do sono é obrigatório na conduta.',
            'conduct.medication_compliance.required_with' => 'O campo de adesão medicamentosa é obrigatório na conduta.',
        ];
    }
}
