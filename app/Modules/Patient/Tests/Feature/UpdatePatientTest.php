<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Patient\Models\Paciente;

it('updates an existing patient', function (): void {
    $user = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $user->id, 'nome' => 'Nome Antigo']);

    $response = $this->actingAs($user)->putJson("/api/patients/{$patient->id}", [
        'name' => 'Nome Novo',
        'cpf' => $patient->cpf,
        'phone' => $patient->telefone,
        'birth_date' => $patient->data_nascimento->format('Y-m-d'),
        'gender' => $patient->sexo->toFrontend(),
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Nome Novo');

    $this->assertDatabaseHas('pacientes', ['id' => $patient->id, 'nome' => 'Nome Novo']);
});

it('does not allow updating another doctor patient', function (): void {
    $user = User::factory()->doctor()->create();
    $otherUser = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->putJson("/api/patients/{$patient->id}", [
        'name' => 'Hackeado',
        'cpf' => '111.111.111-11',
        'phone' => '(11) 99999-0000',
        'birth_date' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertNotFound();
});
