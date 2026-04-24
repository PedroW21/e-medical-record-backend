<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Farmacia;
use App\Modules\Catalog\Models\Injetavel;

function seedInjectable(string $id, string $name, string $pharmacyId = 'victa'): void
{
    Farmacia::query()->firstOrCreate(
        ['id' => $pharmacyId],
        ['nome' => ucfirst($pharmacyId), 'cor' => '#3B82F6']
    );

    Injetavel::factory()->create([
        'id' => $id,
        'farmacia_id' => $pharmacyId,
        'nome' => $name,
    ]);
}

function assertCacheControl(\Illuminate\Testing\TestResponse $response): void
{
    $cacheControl = (string) $response->headers->get('Cache-Control');

    expect($cacheControl)->toContain('private');
    expect($cacheControl)->toContain('must-revalidate');
}

it('returns a weak ETag header on successful GET', function (): void {
    $user = User::factory()->doctor()->create();

    Farmacia::factory()->create(['id' => 'victa', 'nome' => 'Victa', 'cor' => '#3B82F6']);

    $response = $this->actingAs($user)->getJson('/api/catalog/pharmacies');

    $response->assertOk();

    $etag = $response->headers->get('ETag');

    expect($etag)->not->toBeNull();
    expect($etag)->toStartWith('W/"');
    expect($etag)->toEndWith('"');

    assertCacheControl($response);
});

it('returns the same ETag for an idempotent GET', function (): void {
    $user = User::factory()->doctor()->create();

    Farmacia::factory()->create(['id' => 'victa', 'nome' => 'Victa', 'cor' => '#3B82F6']);

    $first = $this->actingAs($user)->getJson('/api/catalog/pharmacies');
    $second = $this->actingAs($user)->getJson('/api/catalog/pharmacies');

    expect($first->headers->get('ETag'))->toBe($second->headers->get('ETag'));
});

it('returns 304 Not Modified when If-None-Match matches', function (): void {
    $user = User::factory()->doctor()->create();

    Farmacia::factory()->create(['id' => 'victa', 'nome' => 'Victa', 'cor' => '#3B82F6']);

    $first = $this->actingAs($user)->getJson('/api/catalog/pharmacies');
    $etag = (string) $first->headers->get('ETag');

    $second = $this->actingAs($user)
        ->withHeader('If-None-Match', $etag)
        ->getJson('/api/catalog/pharmacies');

    $second->assertStatus(304);
    expect($second->getContent())->toBe('');
    $second->assertHeader('ETag', $etag);
    assertCacheControl($second);
});

it('returns 200 with a new ETag when If-None-Match does not match', function (): void {
    $user = User::factory()->doctor()->create();

    Farmacia::factory()->create(['id' => 'victa', 'nome' => 'Victa', 'cor' => '#3B82F6']);

    $response = $this->actingAs($user)
        ->withHeader('If-None-Match', 'W/"deadbeef"')
        ->getJson('/api/catalog/pharmacies');

    $response->assertOk();

    $etag = $response->headers->get('ETag');

    expect($etag)->not->toBeNull();
    expect($etag)->not->toBe('W/"deadbeef"');
});

it('produces different ETags for different query params', function (): void {
    $user = User::factory()->doctor()->create();

    seedInjectable('victa-magnesio', 'Magnésio');
    seedInjectable('victa-vitamina-c', 'Vitamina C');

    $first = $this->actingAs($user)->getJson('/api/catalog/injectables?search=magnesio');
    $second = $this->actingAs($user)->getJson('/api/catalog/injectables?search=vitamina');

    $first->assertOk();
    $second->assertOk();

    expect($first->headers->get('ETag'))->not->toBe($second->headers->get('ETag'));
});
