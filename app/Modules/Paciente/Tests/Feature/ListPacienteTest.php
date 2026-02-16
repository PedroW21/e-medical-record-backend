<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Paciente\Models\Paciente;

it('lista pacientes do médico autenticado', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/pacientes');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'name', 'cpf', 'phone', 'birth_date', 'gender', 'status']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

it('não lista pacientes de outro médico', function (): void {
    $user = User::factory()->doctor()->create();
    $otherUser = User::factory()->doctor()->create();
    Paciente::factory()->count(2)->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->getJson('/api/pacientes');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('filtra pacientes por busca de nome', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->create(['user_id' => $user->id, 'nome' => 'Maria da Silva']);
    Paciente::factory()->create(['user_id' => $user->id, 'nome' => 'João Santos']);

    $response = $this->actingAs($user)->getJson('/api/pacientes?busca=Maria');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Maria da Silva');
});

it('filtra pacientes por status', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    Paciente::factory()->create(['user_id' => $user->id, 'status' => 'inactive']);

    $response = $this->actingAs($user)->getJson('/api/pacientes?status=active');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('pagina os resultados', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->count(20)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/pacientes?per_page=5&page=1');

    $response->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.total', 20);
});

it('requer autenticação para listar pacientes', function (): void {
    $response = $this->getJson('/api/pacientes');

    $response->assertUnauthorized();
});
