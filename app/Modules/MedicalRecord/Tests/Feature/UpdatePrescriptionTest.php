<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\AnvisaList;
use App\Modules\MedicalRecord\Models\Medicamento;
use App\Modules\MedicalRecord\Models\Prescricao;
use App\Modules\MedicalRecord\Models\Prontuario;

it('updates prescription items', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prescription = Prescricao::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/prescriptions/{$prescription->id}",
        [
            'items' => [
                [
                    'medication_name' => 'Dipirona 500mg',
                    'dosage' => '1 comprimido',
                    'frequency' => '6/6h',
                    'duration' => '5 dias',
                ],
            ],
        ]
    );

    $response->assertOk()
        ->assertJsonPath('data.id', $prescription->id)
        ->assertJsonPath('data.items.0.medication_name', 'Dipirona 500mg');
});

it('re-guesses recipe type on update', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prescription = Prescricao::factory()->create(['prontuario_id' => $prontuario->id]);
    $medication = Medicamento::factory()->controlled(AnvisaList::B1)->create();

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/prescriptions/{$prescription->id}",
        [
            'items' => [
                [
                    'medication_id' => $medication->id,
                    'medication_name' => $medication->nome,
                    'dosage' => '1 comprimido',
                    'frequency' => 'à noite',
                    'duration' => '30 dias',
                ],
            ],
        ]
    );

    $response->assertOk()
        ->assertJsonPath('data.recipe_type', 'blue_b');
});

it('rejects update on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);
    $prescription = Prescricao::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/prescriptions/{$prescription->id}",
        [
            'items' => [
                [
                    'medication_name' => 'Paracetamol 500mg',
                    'dosage' => '1 comprimido',
                    'frequency' => '8/8h',
                    'duration' => '5 dias',
                ],
            ],
        ]
    );

    $response->assertStatus(409);
});

it('rejects update by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $prescription = Prescricao::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctorB)->putJson(
        "/api/medical-records/{$prontuario->id}/prescriptions/{$prescription->id}",
        [
            'items' => [
                [
                    'medication_name' => 'Dipirona 500mg',
                    'dosage' => '1 comprimido',
                    'frequency' => '6/6h',
                    'duration' => '5 dias',
                ],
            ],
        ]
    );

    $response->assertForbidden();
});
