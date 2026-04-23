<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\ProblemCategory;
use App\Modules\Catalog\Models\ListaProblema;

it('requires authentication', function (): void {
    $this->getJson('/api/catalog/problem-list')->assertUnauthorized();
});

it('lists problems and filters by category', function (): void {
    $user = User::factory()->doctor()->create();

    ListaProblema::factory()->create(['id' => 'anemia', 'categoria' => ProblemCategory::Hematologic, 'rotulo' => 'Anemia']);
    ListaProblema::factory()->create(['id' => 'dm2', 'categoria' => ProblemCategory::Metabolic, 'rotulo' => 'DM2']);

    $response = $this->actingAs($user)->getJson('/api/catalog/problem-list?category=metabolic');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'dm2')
        ->assertJsonPath('data.0.category', 'metabolic');
});

it('rejects unknown category', function (): void {
    $user = User::factory()->doctor()->create();

    $this->actingAs($user)
        ->getJson('/api/catalog/problem-list?category=unknown')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category']);
});
