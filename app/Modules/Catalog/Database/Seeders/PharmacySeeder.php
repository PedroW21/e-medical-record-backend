<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Models\Farmacia;
use Illuminate\Database\Seeder;

final class PharmacySeeder extends Seeder
{
    public function run(): void
    {
        $path = resource_path('catalog/data/injectable.json');

        if (! file_exists($path)) {
            return;
        }

        /** @var array{pharmacies: array<int, array{id: string, label: string, color?: string}>} $data */
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        collect($data['pharmacies'] ?? [])
            ->map(fn (array $row): array => [
                'id' => $row['id'],
                'nome' => $row['label'],
                'cor' => $row['color'] ?? null,
            ])
            ->each(fn (array $row) => Farmacia::query()->updateOrCreate(
                ['id' => $row['id']],
                $row,
            ));
    }
}
