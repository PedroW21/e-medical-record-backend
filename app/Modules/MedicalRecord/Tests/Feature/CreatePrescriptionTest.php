<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\AnvisaList;
use App\Modules\MedicalRecord\Models\Medicamento;
use App\Modules\MedicalRecord\Models\Prontuario;

it('creates an allopathic prescription', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/prescriptions", [
        'subtype' => 'allopathic',
        'items' => [
            [
                'medication_name' => 'Amoxicilina 500mg',
                'dosage' => '1 comprimido',
                'frequency' => '8/8h',
                'duration' => '7 dias',
            ],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.medical_record_id', $prontuario->id)
        ->assertJsonPath('data.subtype', 'allopathic')
        ->assertJsonPath('data.recipe_type', 'normal')
        ->assertJsonPath('data.recipe_type_override', false);
});

it('creates a magistral prescription', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/prescriptions", [
        'subtype' => 'magistral',
        'items' => [
            [
                'name' => 'Fórmula vitamina D',
                'components' => [['name' => 'Vitamina D3', 'dose' => '50.000 UI']],
                'posology' => '1 cápsula por semana',
            ],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.subtype', 'magistral')
        ->assertJsonPath('data.recipe_type', 'normal');
});

it('auto-guesses recipe type from ANVISA list', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $medication = Medicamento::factory()->controlled(AnvisaList::B1)->create();

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/prescriptions", [
        'subtype' => 'allopathic',
        'items' => [
            [
                'medication_id' => $medication->id,
                'medication_name' => $medication->nome,
                'dosage' => '1 comprimido',
                'frequency' => 'à noite',
                'duration' => '30 dias',
            ],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.recipe_type', 'blue_b');
});

it('respects recipe type override', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/prescriptions", [
        'subtype' => 'allopathic',
        'items' => [
            [
                'medication_name' => 'Paracetamol 500mg',
                'dosage' => '1 comprimido',
                'frequency' => '8/8h',
                'duration' => '5 dias',
            ],
        ],
        'recipe_type_override' => true,
        'recipe_type' => 'yellow_a',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.recipe_type', 'yellow_a')
        ->assertJsonPath('data.recipe_type_override', true);
});

it('rejects creation on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/prescriptions", [
        'subtype' => 'allopathic',
        'items' => [
            [
                'medication_name' => 'Paracetamol 500mg',
                'dosage' => '1 comprimido',
                'frequency' => '8/8h',
                'duration' => '5 dias',
            ],
        ],
    ]);

    $response->assertStatus(409);
});

it('rejects creation by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $response = $this->actingAs($doctorB)->postJson("/api/medical-records/{$prontuario->id}/prescriptions", [
        'subtype' => 'allopathic',
        'items' => [
            [
                'medication_name' => 'Paracetamol 500mg',
                'dosage' => '1 comprimido',
                'frequency' => '8/8h',
                'duration' => '5 dias',
            ],
        ],
    ]);

    $response->assertForbidden();
});

it('rejects creation with empty items', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/prescriptions", [
        'subtype' => 'allopathic',
        'items' => [],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['items']);
});

it('creates an injectable_im prescription', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/prescriptions", [
        'subtype' => 'injectable_im',
        'items' => [
            [
                'medication_name' => 'Diclofenaco Sódico 75mg/3mL',
                'dosage' => '3mL',
            ],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.medical_record_id', $prontuario->id)
        ->assertJsonPath('data.subtype', 'injectable_im')
        ->assertJsonPath('data.recipe_type', 'normal');
});

it('rejects unauthenticated access', function (): void {
    $prontuario = Prontuario::factory()->create();

    $response = $this->postJson("/api/medical-records/{$prontuario->id}/prescriptions", [
        'subtype' => 'allopathic',
        'items' => [
            [
                'medication_name' => 'Paracetamol 500mg',
                'dosage' => '1 comprimido',
                'frequency' => '8/8h',
                'duration' => '5 dias',
            ],
        ],
    ]);

    $response->assertUnauthorized();
});
