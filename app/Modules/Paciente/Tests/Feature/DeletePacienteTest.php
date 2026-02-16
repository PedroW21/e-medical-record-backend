<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Paciente\Models\Paciente;

it('exclui (soft delete) um paciente', function (): void {
    $user = User::factory()->doctor()->create();
    $paciente = Paciente::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->deleteJson("/api/pacientes/{$paciente->id}");

    $response->assertOk()
        ->assertJsonPath('message', 'Paciente excluído com sucesso.');

    $this->assertSoftDeleted('pacientes', ['id' => $paciente->id]);
});

it('não permite excluir paciente de outro médico', function (): void {
    $user = User::factory()->doctor()->create();
    $otherUser = User::factory()->doctor()->create();
    $paciente = Paciente::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->deleteJson("/api/pacientes/{$paciente->id}");

    $response->assertNotFound();
});
