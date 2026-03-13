<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\Patient\Models\Paciente;

it('updates physical exam JSONB on a draft record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($doctor)->putJson("/api/medical-records/{$record->id}", [
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

    $response->assertOk()
        ->assertJsonPath('data.id', $record->id)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonStructure(['data' => ['id', 'physical_exam']]);
});

it('updates anthropometry data', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($doctor)->putJson("/api/medical-records/{$record->id}", [
        'anthropometry' => [
            'blood_pressure' => [
                'right_arm' => [
                    'sitting' => ['systolic' => 118, 'diastolic' => 78],
                ],
                'heart_rate' => 68,
                'oxygen_sat' => 99.0,
                'temperature' => 36.8,
            ],
            'measures' => [
                'weight' => 80.0,
                'height' => 175.0,
                'bmi' => 26.1,
                'bmi_classification' => 'overweight',
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $record->id)
        ->assertJsonStructure(['data' => ['id', 'anthropometry']]);

    $this->assertDatabaseHas('medidas_antropometricas', [
        'prontuario_id' => $record->id,
        'peso' => 80.00,
        'pa_sentado_d_pas' => 118,
        'pa_sentado_d_pad' => 78,
    ]);
});

it('rejects update on a finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->finalized()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($doctor)->putJson("/api/medical-records/{$record->id}", [
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

    $response->assertStatus(409);
});

it('rejects update by another doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $doctor->id]);
    $record = Prontuario::factory()->create([
        'user_id' => $doctor->id,
        'paciente_id' => $patient->id,
    ]);

    $response = $this->actingAs($otherDoctor)->putJson("/api/medical-records/{$record->id}", [
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

    $response->assertNotFound();
});

it('returns 401 for unauthenticated user', function (): void {
    $record = Prontuario::factory()->create();

    $response = $this->putJson("/api/medical-records/{$record->id}", [
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

    $response->assertUnauthorized();
});
