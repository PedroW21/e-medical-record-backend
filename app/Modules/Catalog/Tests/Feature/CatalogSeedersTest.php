<?php

declare(strict_types=1);

use App\Modules\Catalog\Database\Seeders\InjectableCatalogSeeder;
use App\Modules\Catalog\Database\Seeders\InjectableProtocolSeeder;
use App\Modules\Catalog\Database\Seeders\MagistralCatalogSeeder;
use App\Modules\Catalog\Database\Seeders\PharmacySeeder;
use App\Modules\Catalog\Database\Seeders\ProblemListSeeder;
use App\Modules\Catalog\Database\Seeders\TherapeuticCategorySeeder;
use App\Modules\Catalog\Models\CategoriaTerapeutica;
use App\Modules\Catalog\Models\Farmacia;
use App\Modules\Catalog\Models\Injetavel;
use App\Modules\Catalog\Models\InjetavelProtocolo;
use App\Modules\Catalog\Models\InjetavelProtocoloComponente;
use App\Modules\Catalog\Models\ListaProblema;
use App\Modules\Catalog\Models\MagistralCategoria;
use App\Modules\Catalog\Models\MagistralFormula;

it('seeds pharmacies idempotently', function (): void {
    (new PharmacySeeder)->run();
    (new PharmacySeeder)->run();

    expect(Farmacia::query()->count())->toBe(3)
        ->and(Farmacia::query()->pluck('id')->sort()->values()->all())
        ->toEqual(['healthtech', 'pineda', 'victa']);
});

it('seeds therapeutic categories', function (): void {
    (new TherapeuticCategorySeeder)->run();

    expect(CategoriaTerapeutica::query()->count())->toBeGreaterThan(20);
});

it('seeds magistral categories and formulas', function (): void {
    (new MagistralCatalogSeeder)->run();

    expect(MagistralCategoria::query()->count())->toBeGreaterThan(50)
        ->and(MagistralFormula::query()->count())->toBeGreaterThan(100);
});

it('seeds injectables referencing seeded pharmacies', function (): void {
    (new PharmacySeeder)->run();
    (new InjectableCatalogSeeder)->run();

    expect(Injetavel::query()->count())->toBeGreaterThan(100)
        ->and(Injetavel::query()->whereNotNull('farmacia_id')->count())
        ->toBe(Injetavel::query()->count());
});

it('seeds protocols with ordered components', function (): void {
    (new PharmacySeeder)->run();
    (new TherapeuticCategorySeeder)->run();
    (new InjectableProtocolSeeder)->run();

    expect(InjetavelProtocolo::query()->count())->toBeGreaterThan(100)
        ->and(InjetavelProtocoloComponente::query()->count())->toBeGreaterThan(100);
});

it('seeds problem list', function (): void {
    (new ProblemListSeeder)->run();

    expect(ListaProblema::query()->count())->toBeGreaterThan(30);
});
