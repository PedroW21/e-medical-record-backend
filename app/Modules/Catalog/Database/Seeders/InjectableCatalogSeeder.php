<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Models\Injetavel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

final class InjectableCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $path = resource_path('catalog/data/injectable.json');

        if (! file_exists($path)) {
            return;
        }

        /** @var array{farmacos: array<int, array<string, mixed>>} $data */
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $now = CarbonImmutable::now();

        collect($data['farmacos'] ?? [])
            ->map(fn (array $row): array => [
                'id' => (string) $row['id'],
                'farmacia_id' => (string) $row['pharmacy'],
                'nome' => (string) $row['name'],
                'dosagem' => (string) ($row['dosage'] ?? ''),
                'volume' => isset($row['volume']) ? (string) $row['volume'] : null,
                'via_exclusiva' => isset($row['exclusiveRoute']) ? (string) $row['exclusiveRoute'] : null,
                'composicao' => isset($row['composition']) ? (string) $row['composition'] : null,
                'is_blend' => (bool) ($row['isBlend'] ?? false),
                'vias_permitidas' => json_encode(array_values($row['allowedRoutes'] ?? []), JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->chunk(200)
            ->each(fn ($chunk) => Injetavel::query()->upsert(
                $chunk->values()->all(),
                ['id'],
                ['farmacia_id', 'nome', 'dosagem', 'volume', 'via_exclusiva', 'composicao', 'is_blend', 'vias_permitidas', 'updated_at'],
            ));
    }
}
