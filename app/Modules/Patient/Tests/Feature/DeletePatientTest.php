<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Patient\Models\Paciente;

it('soft deletes a patient', function (): void {
    $user = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->deleteJson("/api/patients/{$patient->id}");

    $response->assertOk()
        ->assertJsonPath('message', 'Paciente excluído com sucesso.');

    $this->assertSoftDeleted('pacientes', ['id' => $patient->id]);
});

it('does not allow deleting another doctor patient', function (): void {
    $user = User::factory()->doctor()->create();
    $otherUser = User::factory()->doctor()->create();
    $patient = Paciente::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->deleteJson("/api/patients/{$patient->id}");

    $response->assertNotFound();
});
