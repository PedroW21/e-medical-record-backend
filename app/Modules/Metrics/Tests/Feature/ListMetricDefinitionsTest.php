<?php

declare(strict_types=1);

use App\Models\User;

it('lists all metric definitions for an authenticated user', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->getJson('/api/metrics/definitions');

    $response->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'category', 'name', 'unit', 'ref_min', 'ref_max', 'color'],
            ],
        ]);
});
