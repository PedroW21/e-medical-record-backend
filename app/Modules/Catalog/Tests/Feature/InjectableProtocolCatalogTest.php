<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Enums\InjectableProtocolRoute;
use App\Modules\Catalog\Models\CategoriaTerapeutica;
use App\Modules\Catalog\Models\Farmacia;
use App\Modules\Catalog\Models\InjetavelProtocolo;
use App\Modules\Catalog\Models\InjetavelProtocoloComponente;

it('requires authentication on protocol endpoints', function (): void {
    $this->getJson('/api/catalog/injectable-protocols')->assertUnauthorized();
    $this->getJson('/api/catalog/injectable-protocols/whatever')->assertUnauthorized();
});

it('filters protocols by route and therapeutic category', function (): void {
    $user = User::factory()->doctor()->create();

    $victa = Farmacia::factory()->create(['id' => 'victa']);
    $cardio = CategoriaTerapeutica::factory()->create(['id' => 'cardiologia']);
    $hepatic = CategoriaTerapeutica::factory()->create(['id' => 'saude_hepatica']);

    InjetavelProtocolo::factory()->create([
        'id' => 'proto-ev-1',
        'farmacia_id' => $victa->id,
        'categoria_terapeutica_id' => $cardio->id,
        'via' => InjectableProtocolRoute::Ev,
    ]);
    InjetavelProtocolo::factory()->create([
        'id' => 'proto-im-1',
        'farmacia_id' => $victa->id,
        'categoria_terapeutica_id' => $hepatic->id,
        'via' => InjectableProtocolRoute::Im,
    ]);

    $response = $this->actingAs($user)->getJson('/api/catalog/injectable-protocols?route=ev&therapeutic_category_id=cardiologia');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'proto-ev-1');
});

it('returns protocol with ordered components on show', function (): void {
    $user = User::factory()->doctor()->create();

    $victa = Farmacia::factory()->create(['id' => 'victa']);
    $cat = CategoriaTerapeutica::factory()->create(['id' => 'envelhecimento']);
    $protocol = InjetavelProtocolo::factory()->create([
        'id' => 'antioxidante-1',
        'farmacia_id' => $victa->id,
        'categoria_terapeutica_id' => $cat->id,
        'via' => InjectableProtocolRoute::Ev,
    ]);

    InjetavelProtocoloComponente::factory()->create([
        'protocolo_id' => $protocol->id,
        'ordem' => 2,
        'nome_farmaco' => 'Vitamina C',
        'dosagem' => '444mg/2mL',
        'quantidade_ampolas' => 1,
    ]);
    InjetavelProtocoloComponente::factory()->create([
        'protocolo_id' => $protocol->id,
        'ordem' => 1,
        'nome_farmaco' => 'N-Acetil-Cisteína',
        'dosagem' => '300mg/2mL',
        'quantidade_ampolas' => 1,
    ]);

    $response = $this->actingAs($user)->getJson('/api/catalog/injectable-protocols/antioxidante-1');

    $response->assertOk()
        ->assertJsonPath('data.id', 'antioxidante-1')
        ->assertJsonCount(2, 'data.components')
        ->assertJsonPath('data.components.0.farmaco_name', 'N-Acetil-Cisteína')
        ->assertJsonPath('data.components.1.farmaco_name', 'Vitamina C');
});

it('returns 404 for unknown protocol', function (): void {
    $user = User::factory()->doctor()->create();

    $this->actingAs($user)
        ->getJson('/api/catalog/injectable-protocols/unknown')
        ->assertNotFound();
});
