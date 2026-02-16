<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Paciente\Models\Paciente;

it('atualiza um paciente existente', function (): void {
    $user = User::factory()->doctor()->create();
    $paciente = Paciente::factory()->create(['user_id' => $user->id, 'nome' => 'Nome Antigo']);

    $response = $this->actingAs($user)->putJson("/api/pacientes/{$paciente->id}", [
        'name' => 'Nome Novo',
        'cpf' => $paciente->cpf,
        'phone' => $paciente->telefone,
        'birth_date' => $paciente->data_nascimento->format('Y-m-d'),
        'gender' => $paciente->sexo->toFrontend(),
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Nome Novo');

    $this->assertDatabaseHas('pacientes', ['id' => $paciente->id, 'nome' => 'Nome Novo']);
});

it('não permite atualizar paciente de outro médico', function (): void {
    $user = User::factory()->doctor()->create();
    $otherUser = User::factory()->doctor()->create();
    $paciente = Paciente::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->putJson("/api/pacientes/{$paciente->id}", [
        'name' => 'Hackeado',
        'cpf' => '111.111.111-11',
        'phone' => '(11) 99999-0000',
        'birth_date' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertNotFound();
});
