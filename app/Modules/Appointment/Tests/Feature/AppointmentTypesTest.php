<?php

declare(strict_types=1);

use App\Models\User;

it('returns all appointment types', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->getJson('/api/appointments/types');

    $response->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('data.0.value', 'consultation')
        ->assertJsonPath('data.0.label', 'Consulta');
});
