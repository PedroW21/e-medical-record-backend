<?php

declare(strict_types=1);

use App\Modules\MedicalRecord\Database\Seeders\LabPanelSeeder;
use App\Modules\MedicalRecord\Models\PainelLaboratorial;

it('seeds lab panels from catalog resource', function (): void {
    (new LabPanelSeeder)->run();

    expect(PainelLaboratorial::query()->count())->toBeGreaterThan(30)
        ->and(PainelLaboratorial::query()->where('id', 'hemograma-completo')->exists())->toBeTrue();
});

it('is idempotent when invoked twice', function (): void {
    (new LabPanelSeeder)->run();
    $firstCount = PainelLaboratorial::query()->count();

    (new LabPanelSeeder)->run();
    $secondCount = PainelLaboratorial::query()->count();

    expect($secondCount)->toBe($firstCount);
});
