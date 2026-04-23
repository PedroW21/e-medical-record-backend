<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Models\ListaProblema;
use Illuminate\Database\Seeder;

final class ProblemListSeeder extends Seeder
{
    public function run(): void
    {
        $path = resource_path('catalog/data/problem-list.json');

        if (! file_exists($path)) {
            return;
        }

        /** @var array<int, array{id: string, category: string, label: string, variation?: array{id: string, label: string, options: array<int, string>}}> $data */
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        foreach ($data as $entry) {
            ListaProblema::query()->updateOrCreate(
                ['id' => $entry['id']],
                [
                    'id' => $entry['id'],
                    'categoria' => $entry['category'],
                    'rotulo' => $entry['label'],
                    'variacao' => $entry['variation'] ?? null,
                ],
            );
        }
    }
}
