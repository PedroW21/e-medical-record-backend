<?php

declare(strict_types=1);

use App\Modules\Metrics\DTOs\MetricDefinition;
use App\Modules\Metrics\Registry\MetricRegistry;

it('ships the full set of MVP metrics', function (): void {
    $all = MetricRegistry::all();

    expect($all)->toHaveCount(20);
});

it('groups metrics across the expected categories', function (): void {
    $categories = array_count_values(array_map(
        fn (MetricDefinition $definition): string => $definition->category,
        MetricRegistry::all(),
    ));

    expect($categories)->toEqual([
        'hemogram' => 4,
        'biochemistry' => 4,
        'lipid_profile' => 4,
        'liver_function' => 4,
        'thyroid' => 3,
        'renal_function' => 1,
    ]);
});

it('exposes catalog exam ids as a flat list', function (): void {
    $ids = MetricRegistry::catalogoExameIds();

    expect($ids)->toHaveCount(20)
        ->and($ids)->toContain('hemo-hemoglobina', 'glicemia-jejum', 'tsh', 'microalbuminuria');
});

it('produces a catalog-to-metric lookup map', function (): void {
    $map = MetricRegistry::catalogoToMetricMap();

    expect($map['hemo-hemoglobina'])->toBe('hemoglobin')
        ->and($map['glicemia-jejum'])->toBe('glucose')
        ->and($map['t4-livre'])->toBe('t4_free');
});

it('returns null for unknown metric lookups', function (): void {
    expect(MetricRegistry::find('unknown'))->toBeNull();
});

it('matches the frontend metricsConfig shape for every registered metric', function (): void {
    $expected = [
        'hemoglobin' => ['Hemoglobina', 'g/dL', 12.0, 17.5, '#DC2626'],
        'hematocrit' => ['Hematocrito', '%', 36.0, 50.0, '#B91C1C'],
        'leukocytes' => ['Leucocitos', '/mm3', 4000.0, 11000.0, '#7C3AED'],
        'platelets' => ['Plaquetas', '/mm3', 150000.0, 400000.0, '#A855F7'],
        'glucose' => ['Glicemia', 'mg/dL', 70.0, 99.0, '#059669'],
        'urea' => ['Ureia', 'mg/dL', 15.0, 40.0, '#0D9488'],
        'creatinine' => ['Creatinina', 'mg/dL', 0.7, 1.3, '#14B8A6'],
        'uric_acid' => ['Acido Urico', 'mg/dL', 2.5, 7.0, '#2DD4BF'],
        'total_cholesterol' => ['Colesterol Total', 'mg/dL', null, 200.0, '#F59E0B'],
        'hdl' => ['HDL', 'mg/dL', 40.0, null, '#10B981'],
        'ldl' => ['LDL', 'mg/dL', null, 130.0, '#EF4444'],
        'triglycerides' => ['Triglicerideos', 'mg/dL', null, 150.0, '#F97316'],
        'tgo' => ['TGO (AST)', 'U/L', null, 40.0, '#84CC16'],
        'tgp' => ['TGP (ALT)', 'U/L', null, 41.0, '#22C55E'],
        'ggt' => ['GGT', 'U/L', null, 60.0, '#4ADE80'],
        'bilirubin' => ['Bilirrubina Total', 'mg/dL', null, 1.2, '#86EFAC'],
        'tsh' => ['TSH', 'mUI/L', 0.4, 4.0, '#6366F1'],
        't4_free' => ['T4 Livre', 'ng/dL', 0.8, 1.8, '#818CF8'],
        't3' => ['T3', 'ng/dL', 80.0, 200.0, '#A5B4FC'],
        'microalbuminuria' => ['Microalbuminuria', 'mg/L', null, 30.0, '#38BDF8'],
    ];

    foreach ($expected as $metricId => [$name, $unit, $refMin, $refMax, $color]) {
        $definition = MetricRegistry::find($metricId);

        expect($definition)->not->toBeNull()
            ->and($definition->name)->toBe($name)
            ->and($definition->unit)->toBe($unit)
            ->and($definition->refMin)->toBe($refMin)
            ->and($definition->refMax)->toBe($refMax)
            ->and($definition->color)->toBe($color);
    }
});
