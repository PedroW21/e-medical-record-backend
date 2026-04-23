<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\MagistralType;
use App\Modules\Catalog\Models\MagistralCategoria;
use App\Modules\Catalog\Models\MagistralFormula;

it('requires authentication on categories endpoint', function (): void {
    $this->getJson('/api/catalog/magistral/categories')->assertUnauthorized();
});

it('lists magistral categories filtered by type', function (): void {
    $user = User::factory()->doctor()->create();

    MagistralCategoria::factory()->create(['id' => 'farmaco_melatonina', 'tipo' => MagistralType::Farmaco, 'rotulo' => 'Melatonina']);
    MagistralCategoria::factory()->create(['id' => 'alvo_intestino', 'tipo' => MagistralType::Alvo, 'rotulo' => 'Intestino']);

    $response = $this->actingAs($user)->getJson('/api/catalog/magistral/categories?type=farmaco');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'farmaco_melatonina')
        ->assertJsonPath('data.0.type', 'farmaco');
});

it('rejects invalid magistral type filter', function (): void {
    $user = User::factory()->doctor()->create();

    $this->actingAs($user)
        ->getJson('/api/catalog/magistral/categories?type=unknown')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

it('lists magistral formulas paginated', function (): void {
    $user = User::factory()->doctor()->create();

    $category = MagistralCategoria::factory()->create(['id' => 'farmaco_melatonina']);

    MagistralFormula::factory()->count(3)->create(['categoria_id' => $category->id]);

    $response = $this->actingAs($user)->getJson('/api/catalog/magistral/formulas?per_page=10');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'category_id', 'name', 'components', 'excipient', 'posology', 'instructions', 'notes']],
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
});

it('filters magistral formulas by category and search', function (): void {
    $user = User::factory()->doctor()->create();

    $categoryA = MagistralCategoria::factory()->create(['id' => 'farmaco_vitamina_c']);
    $categoryB = MagistralCategoria::factory()->create(['id' => 'farmaco_vitamina_e']);

    MagistralFormula::factory()->create(['id' => 'vc-1', 'categoria_id' => $categoryA->id, 'nome' => 'Vitamina C Lipossomal']);
    MagistralFormula::factory()->create(['id' => 'vc-2', 'categoria_id' => $categoryA->id, 'nome' => 'Vitamina C Pura']);
    MagistralFormula::factory()->create(['id' => 've-1', 'categoria_id' => $categoryB->id, 'nome' => 'Vitamina E']);

    $response = $this->actingAs($user)->getJson('/api/catalog/magistral/formulas?category_id=farmaco_vitamina_c&search=pura');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'vc-2');
});
