<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Farmacia;

it('requires authentication', function (): void {
    $this->getJson('/api/catalog/pharmacies')->assertUnauthorized();
});

it('lists pharmacies ordered by name', function (): void {
    $user = User::factory()->doctor()->create();

    Farmacia::factory()->create(['id' => 'victa', 'nome' => 'Victa', 'cor' => '#3B82F6']);
    Farmacia::factory()->create(['id' => 'alpha', 'nome' => 'Alpha', 'cor' => '#000000']);

    $response = $this->actingAs($user)->getJson('/api/catalog/pharmacies');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', 'alpha')
        ->assertJsonPath('data.1.id', 'victa')
        ->assertJsonStructure(['data' => [['id', 'name', 'color']]]);
});
