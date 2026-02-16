<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Delegation\Models\Delegacao;

it('allows a doctor to create a delegation for a secretary', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();

    $response = $this->actingAs($doctor)->postJson('/api/delegations', [
        'secretary_id' => $secretary->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.doctor.id', $doctor->id)
        ->assertJsonPath('data.secretary.id', $secretary->id);

    $this->assertDatabaseHas('delegacoes', [
        'medico_id' => $doctor->id,
        'secretaria_id' => $secretary->id,
    ]);
});

it('prevents a secretary from creating a delegation', function (): void {
    $secretary = User::factory()->secretary()->create();
    $otherSecretary = User::factory()->secretary()->create();

    $response = $this->actingAs($secretary)->postJson('/api/delegations', [
        'secretary_id' => $otherSecretary->id,
    ]);

    $response->assertForbidden();
});

it('prevents duplicate delegation', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($doctor)->postJson('/api/delegations', [
        'secretary_id' => $secretary->id,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('secretary_id');
});

it('rejects delegation to a non-secretary user', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->postJson('/api/delegations', [
        'secretary_id' => $otherDoctor->id,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('secretary_id');
});
