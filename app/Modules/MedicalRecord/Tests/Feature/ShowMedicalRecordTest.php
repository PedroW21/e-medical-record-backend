<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\MedidaAntropometrica;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\Patient\Models\Paciente;

it('shows a medical record with all sections', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->withPhysicalExam()->withProblemList()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/medical-records/{$record->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $record->id)
        ->assertJsonPath('data.patient_id', $patient->id)
        ->assertJsonPath('data.doctor_id', $doctor->id)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonStructure(['data' => [
            'id',
            'patient_id',
            'doctor_id',
            'type',
            'status',
            'based_on_record_id',
            'physical_exam',
            'problem_list',
            'risk_scores',
            'conduct',
            'finalized_at',
            'created_at',
            'updated_at',
        ]]);
});

it('returns anthropometry in nested format', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    MedidaAntropometrica::factory()->create([
        'prontuario_id' => $record->id,
        'paciente_id' => $patient->id,
        'peso' => 75.50,
        'altura' => 175.00,
        'imc' => 24.65,
        'classificacao_imc' => 'normal',
        'fc' => 72,
        'spo2' => 98.50,
        'temperatura' => 36.50,
        'pa_sentado_d_pas' => 120,
        'pa_sentado_d_pad' => 80,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/medical-records/{$record->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $record->id)
        ->assertJsonStructure(['data' => [
            'anthropometry' => [
                'blood_pressure' => [
                    'right_arm',
                    'left_arm',
                    'heart_rate',
                    'oxygen_sat',
                    'temperature',
                ],
                'measures' => [
                    'weight',
                    'height',
                    'bmi',
                    'bmi_classification',
                ],
                'skinfolds',
            ],
        ]]);
});

it('returns 404 for another doctor record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($otherDoctor)->getJson("/api/medical-records/{$record->id}");

    $response->assertNotFound();
});

it('returns 401 for unauthenticated user', function (): void {
    $record = Prontuario::factory()->create();

    $response = $this->getJson("/api/medical-records/{$record->id}");

    $response->assertUnauthorized();
});
