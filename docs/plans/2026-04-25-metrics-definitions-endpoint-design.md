# Design — Endpoint de Metric Definitions com ETag

**Data:** 2026-04-25
**Módulo:** `Metrics`
**Status:** design aprovado, pronto para implementação

## Contexto

Hoje as definitions de métricas (hemoglobina, glicemia, etc.) vivem duplicadas em dois lugares:

- Frontend: `e-medical-record-frontend/src/modules/metrics/config/metricsConfig.ts`
- Backend: `app/Modules/Metrics/Registry/MetricRegistry.php`

Um teste unitário (`MetricRegistryTest::matches the frontend metricsConfig shape`) usa um snapshot golden para evitar drift, mas a duplicação obriga edição em duas árvores a cada mudança.

O frontend irá deletar `metricsConfig.ts` e os tipos derivados após o backend expor as definitions via API. Backend passa a ser fonte única de verdade do **domínio** (id, name, unit, refs, color, category). Frontend mantém a fonte de verdade do **display** (icone PrimeIcons, display name PT-BR de categoria) — backend não acopla com framework de UI.

## Goal

Expor `GET /api/metrics/definitions` retornando todas as metric definitions Phase 7 MVP (20 entries em 6 categorias), com cache HTTP via `EtagMiddleware`. Frontend consome via SWR + ETag, mesmo padrão do módulo Catalog.

## Não-Goal

- Phase 7.5 (vital_signs, gfr, peso, altura) — fica para issue futura.
- Modificar `GET /api/patients/{id}/metrics` — continua igual.
- Deletar `MetricRegistry.php` — segue como fonte interna.
- Aplicar `EtagMiddleware` em endpoints per-patient (cache benefit baixo).

## Decisões

### Endpoint separado, não enriquecimento do index

`GET /api/metrics/definitions`, auth Sanctum, com `EtagMiddleware`.

- Definitions são globais, não por paciente. Cache trivialmente compartilhável.
- ETag rende mais (definitions raramente mudam).
- `index` per-patient mantém single responsibility (wide-format de valores).

### Shape flat, sem display metadata da categoria

```json
{
  "data": [
    {
      "id": "hemoglobin",
      "category": "hemogram",
      "name": "Hemoglobina",
      "unit": "g/dL",
      "ref_min": 12.0,
      "ref_max": 17.5,
      "color": "#DC2626"
    }
  ]
}
```

`category` é apenas o id semântico (string). Frontend mantém mapping local `{ hemogram: { name: 'Hemograma', icon: 'pi pi-chart-bar' }, ... }`. Backend não expõe `category_name` nem `icon` — display PT-BR e classes de PrimeIcons não pertencem ao domínio.

### `catalogoExameId` permanece interno

DTO `MetricDefinition` continua com `catalogoExameId` (mapping interno backend → `catalogo_exames_laboratoriais`). `MetricDefinitionResource::toArray` omite o campo do payload.

### Order

`MetricRegistry::all()` retorna keyed map em insertion order:
hemogram (4) → biochemistry (4) → lipid_profile (4) → liver_function (4) → thyroid (3) → renal_function (1).
`array_values(MetricRegistry::all())` preserva ordem.

### Phase 7 sem `vital_signs`

Frontend perde a Accordion section "Sinais Vitais" temporariamente até Phase 7.5 shipar (vital_signs, gfr, peso, altura). Greenfield, sem prod, tradeoff aceito.

### Controller single-action

`MetricDefinitionsController` com `__invoke`. Concern global, separado de `PatientMetricsController` (per-patient). Espelha pattern dos `*CatalogController`.

### Reuso de `EtagMiddleware`

`App\Http\Middleware\EtagMiddleware` (global, já usado pelo Catalog). Aplicado via group aninhado em `routes.php`.

## Arquivos

### Novos

- `app/Modules/Metrics/Http/Controllers/MetricDefinitionsController.php`
- `app/Modules/Metrics/Http/Resources/MetricDefinitionResource.php`
- `app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php`

### Modificados

- `app/Modules/Metrics/routes.php` — adiciona rota com `EtagMiddleware`

### Sem mudança

- `app/Modules/Metrics/Registry/MetricRegistry.php`
- `app/Modules/Metrics/DTOs/MetricDefinition.php`
- `app/Modules/Metrics/Http/Controllers/PatientMetricsController.php`

## Implementação

### `MetricDefinitionResource`

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

### `MetricDefinitionsController`

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

### `routes.php`

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

## Tests

`app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php` — 8 testes Pest:

1. `lists all metric definitions for an authenticated user` — 200, 20 entries, shape correto.
2. `omits internal catalogoExameId from payload` — campo não vaza.
3. `groups categories in the documented display order` — order travado.
4. `rejects unauthenticated requests` — 401.
5. `returns a weak ETag and Cache-Control headers` — header presente, `Cache-Control: private, must-revalidate`.
6. `returns the same ETag on idempotent GET` — determinístico.
7. `returns 304 when If-None-Match matches` — cache hit.
8. `returns 200 with new ETag when If-None-Match does not match` — cache miss.

Sem teste de "new ETag on registry change" — `MetricRegistry::$cache` é static private sem hook de reset; o golden test em `MetricRegistryTest::matches the frontend metricsConfig shape` já trava as 20 entries, qualquer drift quebra build.

Sem teste de "different ETags for query params" — endpoint sem query params.

## Acceptance

- [ ] `GET /api/metrics/definitions` 200 com 20 definitions Phase 7 MVP.
- [ ] Response wrapped `{ "data": [...] }`.
- [ ] Cada item: `id, category, name, unit, ref_min, ref_max, color`. Sem `catalogoExameId`.
- [ ] Order: hemogram → biochemistry → lipid_profile → liver_function → thyroid → renal_function.
- [ ] Headers `ETag: W/"..."` + `Cache-Control: private, must-revalidate`.
- [ ] 304 com `If-None-Match` matching.
- [ ] 401 sem auth.
- [ ] `MetricRegistry.php` intacto.
- [ ] `PatientMetricsController` intacto.
- [ ] `php artisan scribe:generate` regenera docs.
- [ ] `vendor/bin/pint --dirty` clean.

## Ordem de execução

1. Criar `MetricDefinitionResource`.
2. Criar `MetricDefinitionsController` com Scribe docs.
3. Editar `routes.php` (group aninhado com `EtagMiddleware`).
4. Criar `ListMetricDefinitionsTest`.
5. `php artisan test --compact app/Modules/Metrics/Tests/Feature/ListMetricDefinitionsTest.php`.
6. `php artisan test --compact app/Modules/Metrics/Tests/`.
7. `vendor/bin/pint --dirty`.
8. `php artisan scribe:generate`.
9. Commit `feat(metrics): expor endpoint /api/metrics/definitions com ETag`.
10. PR PT-BR contra `main`.
