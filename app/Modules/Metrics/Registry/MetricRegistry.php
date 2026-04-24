<?php

declare(strict_types=1);

namespace App\Modules\Metrics\Registry;

use App\Modules\Metrics\DTOs\MetricDefinition;

/**
 * Hard-coded registry of metrics exposed to the frontend evolution charts.
 *
 * Values mirror the `METRIC_CATEGORIES` entries in
 * `e-medical-record-frontend/src/modules/medical-records/config/metricsConfig.ts`.
 * A unit test verifies the shape against a golden JSON snapshot so drift
 * between the two codebases is caught early.
 */
final class MetricRegistry
{
    /**
     * @var array<string, MetricDefinition>|null
     */
    private static ?array $cache = null;

    /**
     * @return array<string, MetricDefinition>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $definitions = [
            new MetricDefinition('hemoglobin', 'hemogram', 'Hemoglobina', 'g/dL', 12.0, 17.5, '#DC2626', 'hemo-hemoglobina'),
            new MetricDefinition('hematocrit', 'hemogram', 'Hematocrito', '%', 36.0, 50.0, '#B91C1C', 'hemo-hematocrito'),
            new MetricDefinition('leukocytes', 'hemogram', 'Leucocitos', '/mm3', 4000.0, 11000.0, '#7C3AED', 'hemo-leucocitos'),
            new MetricDefinition('platelets', 'hemogram', 'Plaquetas', '/mm3', 150000.0, 400000.0, '#A855F7', 'hemo-plaquetas'),

            new MetricDefinition('glucose', 'biochemistry', 'Glicemia', 'mg/dL', 70.0, 99.0, '#059669', 'glicemia-jejum'),
            new MetricDefinition('urea', 'biochemistry', 'Ureia', 'mg/dL', 15.0, 40.0, '#0D9488', 'ureia'),
            new MetricDefinition('creatinine', 'biochemistry', 'Creatinina', 'mg/dL', 0.7, 1.3, '#14B8A6', 'creatinina'),
            new MetricDefinition('uric_acid', 'biochemistry', 'Acido Urico', 'mg/dL', 2.5, 7.0, '#2DD4BF', 'acido-urico'),

            new MetricDefinition('total_cholesterol', 'lipid_profile', 'Colesterol Total', 'mg/dL', null, 200.0, '#F59E0B', 'colesterol-total'),
            new MetricDefinition('hdl', 'lipid_profile', 'HDL', 'mg/dL', 40.0, null, '#10B981', 'hdl'),
            new MetricDefinition('ldl', 'lipid_profile', 'LDL', 'mg/dL', null, 130.0, '#EF4444', 'ldl'),
            new MetricDefinition('triglycerides', 'lipid_profile', 'Triglicerideos', 'mg/dL', null, 150.0, '#F97316', 'triglicerideos'),

            new MetricDefinition('tgo', 'liver_function', 'TGO (AST)', 'U/L', null, 40.0, '#84CC16', 'tgo'),
            new MetricDefinition('tgp', 'liver_function', 'TGP (ALT)', 'U/L', null, 41.0, '#22C55E', 'tgp'),
            new MetricDefinition('ggt', 'liver_function', 'GGT', 'U/L', null, 60.0, '#4ADE80', 'ggt'),
            new MetricDefinition('bilirubin', 'liver_function', 'Bilirrubina Total', 'mg/dL', null, 1.2, '#86EFAC', 'bilirrubina-total'),

            new MetricDefinition('tsh', 'thyroid', 'TSH', 'mUI/L', 0.4, 4.0, '#6366F1', 'tsh'),
            new MetricDefinition('t4_free', 'thyroid', 'T4 Livre', 'ng/dL', 0.8, 1.8, '#818CF8', 't4-livre'),
            new MetricDefinition('t3', 'thyroid', 'T3', 'ng/dL', 80.0, 200.0, '#A5B4FC', 't3-livre'),

            new MetricDefinition('microalbuminuria', 'renal_function', 'Microalbuminuria', 'mg/L', null, 30.0, '#38BDF8', 'microalbuminuria'),
        ];

        $map = [];
        foreach ($definitions as $definition) {
            $map[$definition->id] = $definition;
        }

        self::$cache = $map;

        return self::$cache;
    }

    public static function find(string $metricId): ?MetricDefinition
    {
        return self::all()[$metricId] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function catalogoExameIds(): array
    {
        return array_values(array_map(
            fn (MetricDefinition $d): string => $d->catalogoExameId,
            self::all(),
        ));
    }

    /**
     * @return array<string, string> map catalogo_exame_id => metric_id
     */
    public static function catalogoToMetricMap(): array
    {
        $map = [];
        foreach (self::all() as $definition) {
            $map[$definition->catalogoExameId] = $definition->id;
        }

        return $map;
    }
}
