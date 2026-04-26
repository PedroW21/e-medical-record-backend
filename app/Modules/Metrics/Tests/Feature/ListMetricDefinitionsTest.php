<?php

declare(strict_types=1);

use App\Models\User;

it('lists all metric definitions for an authenticated user', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->getJson('/api/metrics/definitions');

    $response->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'category', 'name', 'unit', 'ref_min', 'ref_max', 'color'],
            ],
        ]);
});

it('omits internal catalogoExameId from payload', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->getJson('/api/metrics/definitions');

    $payload = $response->json('data');

    foreach ($payload as $metric) {
        expect($metric)->not->toHaveKey('catalogoExameId')
            ->and($metric)->not->toHaveKey('catalogo_exame_id');
    }
});

it('groups categories contiguously in the documented display order', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->getJson('/api/metrics/definitions');

    expect(array_column($response->json('data'), 'category'))->toBe([
        'hemogram', 'hemogram', 'hemogram', 'hemogram',
        'biochemistry', 'biochemistry', 'biochemistry', 'biochemistry',
        'lipid_profile', 'lipid_profile', 'lipid_profile', 'lipid_profile',
        'liver_function', 'liver_function', 'liver_function', 'liver_function',
        'thyroid', 'thyroid', 'thyroid',
        'renal_function',
    ]);
});

it('rejects unauthenticated requests', function (): void {
    $this->getJson('/api/metrics/definitions')->assertUnauthorized();
});

it('returns a weak ETag and Cache-Control headers', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->getJson('/api/metrics/definitions');

    $response->assertOk();

    $etag = (string) $response->headers->get('ETag');
    expect($etag)->toStartWith('W/"')->toEndWith('"');

    $cacheControl = (string) $response->headers->get('Cache-Control');
    expect($cacheControl)->toContain('private')->toContain('must-revalidate');
});

it('returns the same ETag on idempotent GET', function (): void {
    $user = User::factory()->doctor()->create();

    $first = $this->actingAs($user)->getJson('/api/metrics/definitions');
    $second = $this->actingAs($user)->getJson('/api/metrics/definitions');

    expect($first->headers->get('ETag'))->toBe($second->headers->get('ETag'));
});

it('returns 304 with revalidated headers when If-None-Match matches', function (): void {
    $user = User::factory()->doctor()->create();

    $first = $this->actingAs($user)->getJson('/api/metrics/definitions');
    $etag = (string) $first->headers->get('ETag');

    $second = $this->actingAs($user)
        ->withHeader('If-None-Match', $etag)
        ->getJson('/api/metrics/definitions');

    $second->assertStatus(304);
    expect($second->getContent())->toBe('')
        ->and($second->headers->get('ETag'))->toBe($etag);

    $cacheControl = (string) $second->headers->get('Cache-Control');
    expect($cacheControl)->toContain('private')->toContain('must-revalidate');
});

it('returns 200 with new ETag when If-None-Match does not match', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)
        ->withHeader('If-None-Match', 'W/"deadbeef"')
        ->getJson('/api/metrics/definitions');

    $response->assertOk();
    expect($response->headers->get('ETag'))->not->toBe('W/"deadbeef"');
});
