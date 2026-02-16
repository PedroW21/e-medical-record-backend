<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;

it('retorna endereço para CEP válido', function (): void {
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

    $response = $this->actingAs($user)->getJson('/api/enderecos/cep/04101000');

    $response->assertOk()
        ->assertJsonPath('data.cep', '04101-000')
        ->assertJsonPath('data.logradouro', 'Rua Vergueiro')
        ->assertJsonPath('data.cidade', 'São Paulo')
        ->assertJsonPath('data.estado', 'SP');
});

it('retorna 404 para CEP inválido', function (): void {
    Http::fake([
        'viacep.com.br/*' => Http::response(['erro' => true]),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/enderecos/cep/00000000');

    $response->assertNotFound();
});
