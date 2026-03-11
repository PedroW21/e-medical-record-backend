<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prescricao;
use App\Modules\MedicalRecord\Models\Prontuario;

it('lists prescriptions for a medical record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    Prescricao::factory()->count(3)->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctor)->getJson("/api/medical-records/{$prontuario->id}/prescriptions");

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('returns empty for record with no prescriptions', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->getJson("/api/medical-records/{$prontuario->id}/prescriptions");

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('rejects listing by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $response = $this->actingAs($doctorB)->getJson("/api/medical-records/{$prontuario->id}/prescriptions");

    $response->assertForbidden();
});
