<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Delegation\Models\Delegacao;

it('lists delegations for a doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($doctor)->getJson('/api/delegations');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('lists delegations for a secretary', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($secretary)->getJson('/api/delegations');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});
