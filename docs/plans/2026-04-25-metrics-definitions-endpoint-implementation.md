# Metrics Definitions Endpoint Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Expor `GET /api/metrics/definitions` retornando 20 metric definitions (Phase 7 MVP) com `EtagMiddleware` aplicado, eliminando a duplicação entre frontend `metricsConfig.ts` e backend `MetricRegistry.php`.

**Architecture:** Single-action `MetricDefinitionsController` consome `MetricRegistry::all()` e devolve `MetricDefinitionResource::collection(...)` (omite `catalogoExameId` interno). Rota nova num group aninhado de `routes.php` herda `auth:sanctum` e adiciona `EtagMiddleware`. Sem mudança de DB, sem mexer em endpoints existentes.

**Tech Stack:** Laravel 12, Pest 4, PHP 8.5, Sanctum, Scribe, Pint.

**Design reference:** `docs/plans/2026-04-25-metrics-definitions-endpoint-design.md`

---

## Task 1: MetricDefinitionResource

**Files:**
- Create: `app/Modules/Metrics/Http/Resources/MetricDefinitionResource.php`

**Step 1.1: Criar Resource**

Conteúdo exato:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Http\Resources;

use App\Modules\Metrics\DTOs\MetricDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MetricDefinition
 */
final class MetricDefinitionResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     category: string,
     *     name: string,
     *     unit: string,
     *     ref_min: float|null,
     *     ref_max: float|null,
     *     color: string
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'name' => $this->name,
            'unit' => $this->unit,
            'ref_min' => $this->refMin,
            'ref_max' => $this->refMax,
            'color' => $this->color,
        ];
    }
}
```

**Step 1.2: Smoke check sintaxe**

Run: `php -l app/Modules/Metrics/Http/Resources/MetricDefinitionResource.php`
Expected: `No syntax errors detected`

**Step 1.3: Commit**

```bash
git add app/Modules/Metrics/Http/Resources/MetricDefinitionResource.php
git commit -m "feat(metrics): adicionar MetricDefinitionResource omitindo catalogoExameId"
```

---

## Task 2: Failing test — endpoint não existe

**Files:**
- Create: `app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php`

**Step 2.1: Criar arquivo de teste com primeiro caso (vai falhar com 404)**

Conteúdo:

```php
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
```

**Step 2.2: Rodar teste — deve falhar com 404**

Run: `php artisan test --compact app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php`
Expected: 1 failed — `Response status code [404] is not a successful status code.`

---

## Task 3: MetricDefinitionsController + rota

**Files:**
- Create: `app/Modules/Metrics/Http/Controllers/MetricDefinitionsController.php`
- Modify: `app/Modules/Metrics/routes.php`

**Step 3.1: Criar Controller**

Conteúdo exato:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Metrics\Http\Resources\MetricDefinitionResource;
use App\Modules\Metrics\Registry\MetricRegistry;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class MetricDefinitionsController extends Controller
{
    /**
     * List all metric definitions exposed to the frontend evolution charts.
     *
     * Returns the full set of MVP metrics (Phase 7) grouped by category in
     * stable display order. The response is cached via ETag — clients can
     * revalidate with `If-None-Match` and receive a 304 when nothing changed.
     *
     * @group Metrics
     * @authenticated
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {"id":"hemoglobin","category":"hemogram","name":"Hemoglobina","unit":"g/dL","ref_min":12.0,"ref_max":17.5,"color":"#DC2626"},
     *     {"id":"glucose","category":"biochemistry","name":"Glicemia","unit":"mg/dL","ref_min":70.0,"ref_max":99.0,"color":"#059669"},
     *     {"id":"tsh","category":"thyroid","name":"TSH","unit":"mUI/L","ref_min":0.4,"ref_max":4.0,"color":"#6366F1"}
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     */
    public function __invoke(): AnonymousResourceCollection
    {
        return MetricDefinitionResource::collection(
            array_values(MetricRegistry::all()),
        );
    }
}
```

**Step 3.2: Editar `app/Modules/Metrics/routes.php`**

Substituir conteúdo inteiro por:

```php
<?php

declare(strict_types=1);

use App\Http\Middleware\EtagMiddleware;
use App\Modules\Metrics\Http\Controllers\MetricDefinitionsController;
use App\Modules\Metrics\Http\Controllers\PatientMetricsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/patients/{id}/metrics', [PatientMetricsController::class, 'index']);
    Route::get('/patients/{id}/metrics/{metricId}/history', [PatientMetricsController::class, 'history']);

    Route::middleware(EtagMiddleware::class)->group(function (): void {
        Route::get('/metrics/definitions', MetricDefinitionsController::class);
    });
});
```

**Step 3.3: Rodar teste — deve passar**

Run: `php artisan test --compact app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php`
Expected: `Tests:    1 passed`

**Step 3.4: Commit**

```bash
git add app/Modules/Metrics/Http/Controllers/MetricDefinitionsController.php app/Modules/Metrics/routes.php app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php
git commit -m "feat(metrics): expor endpoint /api/metrics/definitions"
```

---

## Task 4: Teste — `catalogoExameId` não vaza

**Files:**
- Modify: `app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php`

**Step 4.1: Anexar caso ao final do arquivo**

```php
it('omits internal catalogoExameId from payload', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->getJson('/api/metrics/definitions');

    $payload = $response->json('data');

    foreach ($payload as $metric) {
        expect($metric)->not->toHaveKey('catalogoExameId')
            ->and($metric)->not->toHaveKey('catalogo_exame_id');
    }
});
```

**Step 4.2: Rodar testes**

Run: `php artisan test --compact app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php`
Expected: `Tests:    2 passed`

**Step 4.3: Commit**

```bash
git add app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php
git commit -m "test(metrics): garantir que catalogoExameId não vaza no payload"
```

---

## Task 5: Teste — order de categorias

**Files:**
- Modify: `app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php`

**Step 5.1: Anexar caso**

```php
it('groups categories in the documented display order', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->getJson('/api/metrics/definitions');

    $categoriesInOrder = array_values(array_unique(
        array_column($response->json('data'), 'category')
    ));

    expect($categoriesInOrder)->toBe([
        'hemogram',
        'biochemistry',
        'lipid_profile',
        'liver_function',
        'thyroid',
        'renal_function',
    ]);
});
```

**Step 5.2: Rodar testes**

Run: `php artisan test --compact app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php`
Expected: `Tests:    3 passed`

**Step 5.3: Commit**

```bash
git add app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php
git commit -m "test(metrics): travar ordem das categorias no payload"
```

---

## Task 6: Testes — auth + ETag headers

**Files:**
- Modify: `app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php`

**Step 6.1: Anexar 4 casos restantes**

```php
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

it('returns 304 when If-None-Match matches', function (): void {
    $user = User::factory()->doctor()->create();

    $first = $this->actingAs($user)->getJson('/api/metrics/definitions');
    $etag = (string) $first->headers->get('ETag');

    $second = $this->actingAs($user)
        ->withHeader('If-None-Match', $etag)
        ->getJson('/api/metrics/definitions');

    $second->assertStatus(304);
    expect($second->getContent())->toBe('');
});

it('returns 200 with new ETag when If-None-Match does not match', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)
        ->withHeader('If-None-Match', 'W/"deadbeef"')
        ->getJson('/api/metrics/definitions');

    $response->assertOk();
    expect($response->headers->get('ETag'))->not->toBe('W/"deadbeef"');
});
```

**Step 6.2: Rodar testes**

Run: `php artisan test --compact app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php`
Expected: `Tests:    8 passed`

**Step 6.3: Commit**

```bash
git add app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php
git commit -m "test(metrics): cobrir auth e cabeçalhos ETag/Cache-Control"
```

---

## Task 7: Suite Metrics + Pint

**Step 7.1: Rodar suite completa do módulo**

Run: `php artisan test --compact app/Modules/Metrics/Tests/`
Expected: `Tests:    27 passed` (19 baseline + 8 novos)

**Step 7.2: Pint**

Run: `vendor/bin/pint --dirty`
Expected: zero errors. Aplicar mudanças se houver.

**Step 7.3: Se Pint mudou algo, commit**

```bash
git add -u
git commit -m "style(metrics): aplicar Pint" || echo "Nada para commitar"
```

---

## Task 8: Scribe regenerar

**Step 8.1: Regenerar docs**

Run: `php artisan scribe:generate`
Expected: termina sem erro. Gera `.scribe/endpoints/...` + `public/docs`.

**Step 8.2: Verificar que endpoint apareceu**

Run: `grep -r "metrics/definitions" .scribe/ public/docs/ 2>/dev/null | head -5`
Expected: pelo menos uma linha referenciando a rota.

**Step 8.3: Commit das docs regeneradas**

```bash
git add .scribe/ public/docs/
git commit -m "docs(scribe): regenerar docs após adicionar endpoint de metric definitions"
```

---

## Task 9: Suite global

**Step 9.1: Rodar TODA a suite — confirmar zero regressão**

Run: `php artisan test --compact`
Expected: zero failures.

**Step 9.2: Se tudo verde, commit final (caso reste algo)**

```bash
git status
# Se houver arquivos não commitados de Pint/Scribe, fazer commit apropriado.
```

---

## Acceptance final

- [ ] `GET /api/metrics/definitions` 200 com 20 entries.
- [ ] Cada item tem `id, category, name, unit, ref_min, ref_max, color`. Sem `catalogoExameId`.
- [ ] Order: hemogram → biochemistry → lipid_profile → liver_function → thyroid → renal_function.
- [ ] `ETag: W/"..."` + `Cache-Control: private, must-revalidate`.
- [ ] 304 com `If-None-Match` matching.
- [ ] 401 sem auth.
- [ ] `MetricRegistry.php` intacto.
- [ ] `PatientMetricsController` intacto.
- [ ] Suite global verde.
- [ ] Pint clean.
- [ ] Scribe regenerado.

## Hand-off ao frontend

Após merge, frontend pode:
- Deletar `src/modules/metrics/config/metricsConfig.ts`.
- Regerar `types/metrics.ts` a partir do contrato API.
- Manter mapping local `categoryId → { display name PT-BR, icon PrimeIcons }` (não exposto pelo backend por design).
- Deletar golden JSON drift test.
