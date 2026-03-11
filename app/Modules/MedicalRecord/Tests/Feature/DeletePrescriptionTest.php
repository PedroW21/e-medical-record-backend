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

it('rejects unauthenticated access', function (): void {
    $prontuario = Prontuario::factory()->create();
    $prescription = Prescricao::factory()->create(['prontuario_id' => $prontuario->id]);

    $response = $this->deleteJson(
        "/api/medical-records/{$prontuario->id}/prescriptions/{$prescription->id}"
    );

    $response->assertUnauthorized();
});

it('returns 404 for nonexistent prescription', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/prescriptions/99999"
    );

    $response->assertNotFound();
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

it('rejects deletion when prescription belongs to a different medical record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prescription = Prescricao::factory()->create(['prontuario_id' => $prontuarioA->id]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuarioB->id}/prescriptions/{$prescription->id}"
    );

    $response->assertNotFound();
    $this->assertDatabaseHas('prescricoes', ['id' => $prescription->id]);
});
