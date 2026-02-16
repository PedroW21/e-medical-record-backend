<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Paciente\Models\Paciente;

it('cria um paciente com dados mínimos', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->postJson('/api/pacientes', [
        'name' => 'Maria da Silva',
        'cpf' => '123.456.789-00',
        'phone' => '(11) 99876-5432',
        'birth_date' => '1985-03-15',
        'gender' => 'female',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Maria da Silva')
        ->assertJsonPath('data.cpf', '123.456.789-00')
        ->assertJsonPath('data.gender', 'female')
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('pacientes', [
        'user_id' => $user->id,
        'nome' => 'Maria da Silva',
        'cpf' => '123.456.789-00',
    ]);
});

it('cria um paciente com endereço', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->postJson('/api/pacientes', [
        'name' => 'João Santos',
        'cpf' => '987.654.321-00',
        'phone' => '(11) 98765-4321',
        'birth_date' => '1978-07-22',
        'gender' => 'male',
        'address' => [
            'cep' => '04101-000',
            'street' => 'Rua das Flores',
            'number' => '123',
            'neighborhood' => 'Vila Mariana',
            'city' => 'São Paulo',
            'state' => 'SP',
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.address.street', 'Rua das Flores')
        ->assertJsonPath('data.address.city', 'São Paulo');
});

it('cria um paciente com alergias e condições crônicas', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->postJson('/api/pacientes', [
        'name' => 'Ana Ferreira',
        'cpf' => '456.789.123-00',
        'phone' => '(21) 99123-4567',
        'birth_date' => '1990-11-08',
        'gender' => 'female',
        'allergies' => ['Penicilina', 'Dipirona'],
        'chronic_conditions' => ['Hipertensão Arterial'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.allergies', ['Penicilina', 'Dipirona'])
        ->assertJsonPath('data.chronic_conditions', ['Hipertensão Arterial']);

    $this->assertDatabaseHas('alergias', ['nome' => 'Penicilina']);
    $this->assertDatabaseHas('alergias', ['nome' => 'Dipirona']);
});

it('rejeita criação sem campos obrigatórios', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->postJson('/api/pacientes', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'cpf', 'phone', 'birth_date', 'gender']);
});

it('rejeita CPF duplicado para o mesmo médico', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->create(['user_id' => $user->id, 'cpf' => '123.456.789-00']);

    $response = $this->actingAs($user)->postJson('/api/pacientes', [
        'name' => 'Outro Paciente',
        'cpf' => '123.456.789-00',
        'phone' => '(11) 99999-0000',
        'birth_date' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('cpf');
});

it('permite mesmo CPF para médicos diferentes', function (): void {
    $user1 = User::factory()->doctor()->create();
    $user2 = User::factory()->doctor()->create();
    Paciente::factory()->create(['user_id' => $user1->id, 'cpf' => '123.456.789-00']);

    $response = $this->actingAs($user2)->postJson('/api/pacientes', [
        'name' => 'Mesmo CPF',
        'cpf' => '123.456.789-00',
        'phone' => '(11) 99999-0000',
        'birth_date' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertCreated();
});
