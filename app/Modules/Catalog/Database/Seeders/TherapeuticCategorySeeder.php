<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Models\CategoriaTerapeutica;
use Illuminate\Database\Seeder;

final class TherapeuticCategorySeeder extends Seeder
{
    public function run(): void
    {
        $path = resource_path('catalog/data/injectable.json');

        if (! file_exists($path)) {
            return;
        }

        /** @var array{therapeuticCategories: array<int, array{id: string, label: string}>} $data */
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        collect($data['therapeuticCategories'] ?? [])
            ->map(fn (array $row): array => [
                'id' => $row['id'],
                'nome' => $row['label'],
            ])
            ->each(fn (array $row) => CategoriaTerapeutica::query()->updateOrCreate(
                ['id' => $row['id']],
                $row,
            ));
    }
}
