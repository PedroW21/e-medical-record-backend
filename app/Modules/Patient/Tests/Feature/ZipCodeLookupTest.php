<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;

it('returns address for valid zip code', function (): void {
    Http::fake([
        'viacep.com.br/*' => Http::response([
            'cep' => '04101-000',
            'logradouro' => 'Rua Vergueiro',
            'bairro' => 'Vila Mariana',
            'localidade' => 'São Paulo',
            'uf' => 'SP',
        ]),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/addresses/zip/04101000');

    $response->assertOk()
        ->assertJsonPath('data.zipCode', '04101-000')
        ->assertJsonPath('data.street', 'Rua Vergueiro')
        ->assertJsonPath('data.city', 'São Paulo')
        ->assertJsonPath('data.state', 'SP');
});

it('returns 404 for invalid zip code', function (): void {
    Http::fake([
        'viacep.com.br/*' => Http::response(['erro' => true]),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/addresses/zip/00000000');

    $response->assertNotFound();
});
