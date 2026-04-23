<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\CategoriaTerapeutica;

it('requires authentication', function (): void {
    $this->getJson('/api/catalog/therapeutic-categories')->assertUnauthorized();
});

it('lists therapeutic categories', function (): void {
    $user = User::factory()->doctor()->create();

    CategoriaTerapeutica::factory()->create(['id' => 'cardiologia', 'nome' => 'Cardiologia']);
    CategoriaTerapeutica::factory()->create(['id' => 'saude_hepatica', 'nome' => 'Saúde Hepática']);

    $response = $this->actingAs($user)->getJson('/api/catalog/therapeutic-categories');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', 'cardiologia')
        ->assertJsonStructure(['data' => [['id', 'label']]]);
});
