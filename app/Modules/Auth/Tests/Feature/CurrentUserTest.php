<?php

declare(strict_types=1);

use App\Models\User;

it('returns current user when authenticated', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->getJson('/api/user');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'role', 'crm', 'specialty', 'avatar_url', 'created_at', 'updated_at'],
        ])
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.role', 'doctor');
});

it('returns 401 when not authenticated', function (): void {
    $response = $this->getJson('/api/user');

    $response->assertUnauthorized();
});

it('returns null crm and specialty for secretary', function (): void {
    $user = User::factory()->secretary()->create();

    $response = $this->actingAs($user)->getJson('/api/user');

    $response->assertOk()
        ->assertJsonPath('data.role', 'secretary')
        ->assertJsonPath('data.crm', null)
        ->assertJsonPath('data.specialty', null);
});
