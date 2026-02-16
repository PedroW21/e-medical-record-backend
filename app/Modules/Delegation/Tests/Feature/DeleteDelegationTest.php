<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Delegation\Models\Delegacao;

it('allows a doctor to delete their delegation', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    $delegation = Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($doctor)->deleteJson("/api/delegations/{$delegation->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('delegacoes', ['id' => $delegation->id]);
});

it('prevents a secretary from deleting a delegation', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    $delegation = Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($secretary)->deleteJson("/api/delegations/{$delegation->id}");

    $response->assertForbidden();
});

it('prevents a doctor from deleting another doctor delegation', function (): void {
    $doctor1 = User::factory()->doctor()->create();
    $doctor2 = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    $delegation = Delegacao::factory()->create(['medico_id' => $doctor1->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($doctor2)->deleteJson("/api/delegations/{$delegation->id}");

    $response->assertForbidden();
});
