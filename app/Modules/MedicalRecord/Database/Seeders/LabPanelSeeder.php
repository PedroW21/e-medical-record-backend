<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Seeders;

use App\Modules\MedicalRecord\Models\PainelLaboratorial;
use Illuminate\Database\Seeder;

final class LabPanelSeeder extends Seeder
{
    public function run(): void
    {
        $path = resource_path('catalog/data/lab-panels.json');

        if (! file_exists($path)) {
            return;
        }

        /** @var array<int, array{id: string, name: string, category: string, subsections: array<int, array{label: string, analytes: array<int, array<string, mixed>>}>}> $data */
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        foreach ($data as $panel) {
            PainelLaboratorial::query()->updateOrCreate(
                ['id' => $panel['id']],
                [
                    'id' => $panel['id'],
                    'nome' => $panel['name'],
                    'categoria' => $panel['category'],
                    'subsecoes' => $panel['subsections'],
                ],
            );
        }
    }
}
