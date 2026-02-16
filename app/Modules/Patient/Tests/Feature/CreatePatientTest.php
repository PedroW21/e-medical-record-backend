<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Patient\Models\Paciente;

it('creates a patient with minimal data', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->postJson('/api/patients', [
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

it('creates a patient with address', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->postJson('/api/patients', [
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

it('creates a patient with allergies and chronic conditions', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->postJson('/api/patients', [
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

it('rejects creation without required fields', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->postJson('/api/patients', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'cpf', 'phone', 'birth_date', 'gender']);
});

it('rejects duplicate CPF for the same doctor', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->create(['user_id' => $user->id, 'cpf' => '123.456.789-00']);

    $response = $this->actingAs($user)->postJson('/api/patients', [
        'name' => 'Outro Paciente',
        'cpf' => '123.456.789-00',
        'phone' => '(11) 99999-0000',
        'birth_date' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('cpf');
});

it('allows same CPF for different doctors', function (): void {
    $user1 = User::factory()->doctor()->create();
    $user2 = User::factory()->doctor()->create();
    Paciente::factory()->create(['user_id' => $user1->id, 'cpf' => '123.456.789-00']);

    $response = $this->actingAs($user2)->postJson('/api/patients', [
        'name' => 'Mesmo CPF',
        'cpf' => '123.456.789-00',
        'phone' => '(11) 99999-0000',
        'birth_date' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertCreated();
});
