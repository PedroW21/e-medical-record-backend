<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prescricao;
use App\Modules\MedicalRecord\Models\Prontuario;

it('deletes a prescription from draft record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prescription = Prescricao::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/prescriptions/{$prescription->id}"
    );

    $response->assertOk();
    $this->assertDatabaseMissing('prescricoes', ['id' => $prescription->id]);
});

it('rejects deletion on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);
    $prescription = Prescricao::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/prescriptions/{$prescription->id}"
    );

    $response->assertStatus(409);
});

it('rejects deletion by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $prescription = Prescricao::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->actingAs($doctorB)->deleteJson(
        "/api/medical-records/{$prontuario->id}/prescriptions/{$prescription->id}"
    );

    $response->assertForbidden();
});
