<?php

declare(strict_types=1);

use App\Models\User;

it('logs out an authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/logout');

    $response->assertOk()
        ->assertJsonPath('message', 'Sessão encerrada com sucesso.');
});

it('returns 401 when logging out unauthenticated', function (): void {
    $response = $this->postJson('/api/logout');

    $response->assertUnauthorized();
});
