<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\Patient\Models\Paciente;

it('creates a medical record with anthropometry data', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson('/api/medical-records', [
        'patient_id' => $patient->id,
        'type' => 'first_visit',
        'anthropometry' => [
            'blood_pressure' => [
                'right_arm' => [
                    'sitting' => ['systolic' => 120, 'diastolic' => 80],
                ],
                'heart_rate' => 72,
                'oxygen_sat' => 98.5,
                'temperature' => 36.5,
            ],
            'measures' => [
                'weight' => 75.5,
                'height' => 175.0,
                'bmi' => 24.65,
                'bmi_classification' => 'normal',
            ],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'first_visit')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.patient_id', $patient->id);

    $this->assertDatabaseHas('prontuarios', [
        'paciente_id' => $patient->id,
        'user_id' => $doctor->id,
    ]);

    $this->assertDatabaseHas('medidas_antropometricas', [
        'paciente_id' => $patient->id,
        'peso' => 75.50,
        'altura' => 175.00,
        'fc' => 72,
        'pa_sentado_d_pas' => 120,
        'pa_sentado_d_pad' => 80,
    ]);
});

it('creates a medical record with physical exam JSONB', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson('/api/medical-records', [
        'patient_id' => $patient->id,
        'type' => 'first_visit',
        'physical_exam' => [
            'cardiac' => ['is_normal' => true],
            'respiratory' => ['is_normal' => true],
            'lower_limbs' => [
                'varicose_veins' => false,
                'edema' => false,
                'lymphedema' => false,
                'ulcer' => false,
                'asymmetry' => false,
                'sensitivity_alteration' => false,
                'motricity_alteration' => false,
            ],
            'dentition' => ['status' => 'regular'],
            'gums' => ['status' => 'regular'],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'first_visit')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonStructure(['data' => ['id', 'patient_id', 'doctor_id', 'type', 'status', 'physical_exam']]);

    $this->assertDatabaseHas('prontuarios', [
        'paciente_id' => $patient->id,
        'user_id' => $doctor->id,
    ]);
});

it('creates a medical record with problem list JSONB', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson('/api/medical-records', [
        'patient_id' => $patient->id,
        'type' => 'first_visit',
        'problem_list' => [
            'selected_problems' => [
                [
                    'problem_id' => 'has',
                    'label' => 'Hipertensão Arterial Sistêmica',
                    'category' => 'metabolic',
                    'is_custom' => false,
                ],
            ],
            'custom_problems' => [],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'first_visit')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonStructure(['data' => ['id', 'patient_id', 'problem_list']]);
});

it('creates a medical record with conduct JSONB', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson('/api/medical-records', [
        'patient_id' => $patient->id,
        'type' => 'first_visit',
        'conduct' => [
            'sleep_hygiene' => true,
            'sleep_default_text' => 'Manter higiene do sono adequada.',
            'sleep_observations' => null,
            'diets' => [],
            'physical_activity' => ['default_text' => 'Atividade física regular.'],
            'xenobiotics_restriction' => false,
            'xenobiotics_default_text' => 'Evitar tabagismo e etilismo.',
            'xenobiotics_observations' => null,
            'medication_compliance' => true,
            'medication_default_text' => 'Manter adesão medicamentosa.',
            'medication_observations' => null,
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'first_visit')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonStructure(['data' => ['id', 'patient_id', 'conduct']]);
});

it('creates a pre-anesthetic record with risk scores', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson('/api/medical-records', [
        'patient_id' => $patient->id,
        'type' => 'pre_anesthetic',
        'risk_scores' => [
            'primary_disease' => 'Estenose aórtica',
            'planned_surgery' => 'Troca valvar aórtica',
            'cardiovascular' => [
                'rcri' => ['final_value' => 'low', 'score_points' => 0],
                'acp_detsky' => ['final_value' => 'low', 'score_points' => 0],
                'aub_has2' => ['final_value' => 'low', 'score_points' => 0],
                'asa' => 'II',
                'nyha' => ['final_value' => 'I'],
                'met' => ['final_value' => 'above_4'],
            ],
            'pulmonary' => [
                'respiratory_failure_risk' => ['final_value' => 'low'],
                'pneumonia_risk' => ['final_value' => 'low'],
                'ariscat' => ['final_value' => 'low', 'score_points' => 15],
                'stop_bang' => ['final_value' => 'low', 'score_points' => 2],
            ],
            'renal' => [
                'ckd_epi' => ['method' => 'creatinine', 'creatinine' => 0.9, 'gfr' => 95.0, 'gfr_stage' => 'G1'],
            ],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'pre_anesthetic')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonStructure(['data' => ['id', 'patient_id', 'risk_scores']]);

    $this->assertDatabaseHas('prontuarios', [
        'paciente_id' => $patient->id,
        'user_id' => $doctor->id,
        'tipo' => 'pre_anesthetic',
    ]);
});

it('requires risk_scores when type is pre_anesthetic', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson('/api/medical-records', [
        'patient_id' => $patient->id,
        'type' => 'pre_anesthetic',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['risk_scores']);
});

it('creates a follow-up record based on a previous record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $previousRecord = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($doctor)->postJson('/api/medical-records', [
        'patient_id' => $patient->id,
        'type' => 'follow_up',
        'based_on_record_id' => $previousRecord->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'follow_up')
        ->assertJsonPath('data.based_on_record_id', $previousRecord->id)
        ->assertJsonPath('data.status', 'draft');

    $this->assertDatabaseHas('prontuarios', [
        'paciente_id' => $patient->id,
        'user_id' => $doctor->id,
        'baseado_em_prontuario_id' => $previousRecord->id,
    ]);
});

it('rejects creation for a patient that belongs to another doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $otherDoctor->id]);

    $response = $this->actingAs($doctor)->postJson('/api/medical-records', [
        'patient_id' => $patient->id,
        'type' => 'first_visit',
    ]);

    $response->assertNotFound();
});

it('rejects creation with invalid type', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson('/api/medical-records', [
        'patient_id' => $patient->id,
        'type' => 'invalid_type',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('returns 401 for unauthenticated user', function (): void {
    $response = $this->postJson('/api/medical-records', [
        'patient_id' => 1,
        'type' => 'first_visit',
    ]);

    $response->assertUnauthorized();
});
