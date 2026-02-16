<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Patient\Models\Paciente;

it('lists patients for the authenticated doctor', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/patients');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'name', 'cpf', 'phone', 'birth_date', 'gender', 'status']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

it('does not list patients from another doctor', function (): void {
    $user = User::factory()->doctor()->create();
    $otherUser = User::factory()->doctor()->create();
    Paciente::factory()->count(2)->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->getJson('/api/patients');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('filters patients by name search', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->create(['user_id' => $user->id, 'nome' => 'Maria da Silva']);
    Paciente::factory()->create(['user_id' => $user->id, 'nome' => 'João Santos']);

    $response = $this->actingAs($user)->getJson('/api/patients?search=Maria');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Maria da Silva');
});

it('filters patients by status', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    Paciente::factory()->create(['user_id' => $user->id, 'status' => 'inactive']);

    $response = $this->actingAs($user)->getJson('/api/patients?status=active');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('paginates results', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->count(20)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/patients?per_page=5&page=1');

    $response->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.total', 20);
});

it('requires authentication to list patients', function (): void {
    $response = $this->getJson('/api/patients');

    $response->assertUnauthorized();
});
