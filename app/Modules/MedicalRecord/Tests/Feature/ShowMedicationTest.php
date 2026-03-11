<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Medicamento;

it('shows a medication by id', function (): void {
    $user = User::factory()->doctor()->create();
    $med = Medicamento::factory()->create(['nome' => 'Paracetamol 500mg', 'principio_ativo' => 'Paracetamol']);

    $response = $this->actingAs($user)->getJson("/api/medications/{$med->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $med->id)
        ->assertJsonPath('data.name', 'Paracetamol 500mg')
        ->assertJsonPath('data.active_ingredient', 'Paracetamol');
});

it('returns 404 for inactive medication', function (): void {
    $user = User::factory()->doctor()->create();
    $med = Medicamento::factory()->inactive()->create();

    $response = $this->actingAs($user)->getJson("/api/medications/{$med->id}");

    $response->assertNotFound();
});

it('returns 404 for nonexistent id', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->getJson('/api/medications/99999');

    $response->assertNotFound();
});

it('rejects unauthenticated access', function (): void {
    $response = $this->getJson('/api/medications/1');

    $response->assertUnauthorized();
});
