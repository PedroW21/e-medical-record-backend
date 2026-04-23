<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Farmacia;
use App\Modules\Catalog\Models\Injetavel;

it('requires authentication on injectable endpoints', function (): void {
    $this->getJson('/api/catalog/injectables')->assertUnauthorized();
    $this->getJson('/api/catalog/injectables/whatever')->assertUnauthorized();
});

it('lists injectables filtered by pharmacy and search', function (): void {
    $user = User::factory()->doctor()->create();

    $victa = Farmacia::factory()->create(['id' => 'victa', 'nome' => 'Victa']);
    $pineda = Farmacia::factory()->create(['id' => 'pineda', 'nome' => 'Pineda']);

    Injetavel::factory()->create(['id' => 'victa-magnesio', 'farmacia_id' => $victa->id, 'nome' => 'Magnesio']);
    Injetavel::factory()->create(['id' => 'victa-vitc', 'farmacia_id' => $victa->id, 'nome' => 'Vitamina C']);
    Injetavel::factory()->create(['id' => 'pineda-magnesio', 'farmacia_id' => $pineda->id, 'nome' => 'Magnesio']);

    $response = $this->actingAs($user)->getJson('/api/catalog/injectables?pharmacy_id=victa&search=magnesio');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'victa-magnesio');
});

it('returns 404 when injectable does not exist', function (): void {
    $user = User::factory()->doctor()->create();

    $this->actingAs($user)
        ->getJson('/api/catalog/injectables/unknown-id')
        ->assertNotFound();
});

it('retrieves a single injectable by id', function (): void {
    $user = User::factory()->doctor()->create();
    $pharmacy = Farmacia::factory()->create(['id' => 'victa']);
    $injectable = Injetavel::factory()->create([
        'id' => 'victa-taurina',
        'farmacia_id' => $pharmacy->id,
        'nome' => 'Taurina',
        'vias_permitidas' => ['im', 'ev'],
    ]);

    $response = $this->actingAs($user)->getJson('/api/catalog/injectables/'.$injectable->id);

    $response->assertOk()
        ->assertJsonPath('data.id', 'victa-taurina')
        ->assertJsonPath('data.allowed_routes', ['im', 'ev']);
});
