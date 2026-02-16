<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Paciente\Models\Paciente;

it('retorna detalhes do paciente com endereço e relações', function (): void {
    $user = User::factory()->doctor()->create();
    $paciente = Paciente::factory()->withEndereco()->create(['user_id' => $user->id]);
    $paciente->alergias()->create(['nome' => 'Penicilina']);

    $response = $this->actingAs($user)->getJson("/api/pacientes/{$paciente->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'cpf', 'address', 'allergies', 'chronic_conditions', 'medical_history'],
        ])
        ->assertJsonPath('data.id', $paciente->id);
});

it('retorna 404 para paciente de outro médico', function (): void {
    $user = User::factory()->doctor()->create();
    $otherUser = User::factory()->doctor()->create();
    $paciente = Paciente::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->getJson("/api/pacientes/{$paciente->id}");

    $response->assertNotFound();
});

it('retorna 404 para paciente inexistente', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->getJson('/api/pacientes/99999');

    $response->assertNotFound();
});
