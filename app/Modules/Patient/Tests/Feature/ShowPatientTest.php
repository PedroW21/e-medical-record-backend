<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Patient\Models\Paciente;

it('returns patient details with address and relations', function (): void {
    $user = User::factory()->doctor()->create();
    $patient = Paciente::factory()->withAddress()->create(['user_id' => $user->id]);
    $patient->alergias()->create(['nome' => 'Penicilina']);

    $response = $this->actingAs($user)->getJson("/api/patients/{$patient->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'cpf', 'address', 'allergies', 'chronic_conditions', 'medical_history'],
        ])
        ->assertJsonPath('data.id', $patient->id);
});

it('returns 404 for another doctor patient', function (): void {
    $user = User::factory()->doctor()->create();
    $otherUser = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->getJson("/api/patients/{$patient->id}");

    $response->assertNotFound();
});

it('returns 404 for non-existent patient', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->getJson('/api/patients/99999');

    $response->assertNotFound();
});
