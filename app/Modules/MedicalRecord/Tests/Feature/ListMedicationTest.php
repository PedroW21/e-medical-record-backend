<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Medicamento;

it('lists active medications', function (): void {
    $user = User::factory()->doctor()->create();
    Medicamento::factory()->count(3)->create();
    Medicamento::factory()->inactive()->create();

    $response = $this->actingAs($user)->getJson('/api/medications');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('searches by name', function (): void {
    $user = User::factory()->doctor()->create();
    Medicamento::factory()->create(['nome' => 'Paracetamol 500mg']);
    Medicamento::factory()->create(['nome' => 'Ibuprofeno 600mg']);

    $response = $this->actingAs($user)->getJson('/api/medications?search=paracetamol');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Paracetamol 500mg');
});

it('searches by active ingredient', function (): void {
    $user = User::factory()->doctor()->create();
    Medicamento::factory()->create(['principio_ativo' => 'Dipirona Sódica']);
    Medicamento::factory()->create(['principio_ativo' => 'Paracetamol']);

    $response = $this->actingAs($user)->getJson('/api/medications?search=dipirona');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters controlled medications', function (): void {
    $user = User::factory()->doctor()->create();
    Medicamento::factory()->controlled()->create();
    Medicamento::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/medications?controlled=1');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters non-controlled medications', function (): void {
    $user = User::factory()->doctor()->create();
    Medicamento::factory()->controlled()->create();
    Medicamento::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/medications?controlled=0');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('paginates results', function (): void {
    $user = User::factory()->doctor()->create();
    Medicamento::factory()->count(5)->create();

    $response = $this->actingAs($user)->getJson('/api/medications?per_page=2');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 5);
});

it('rejects unauthenticated access', function (): void {
    $response = $this->getJson('/api/medications');

    $response->assertUnauthorized();
});
