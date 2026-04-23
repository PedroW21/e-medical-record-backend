<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Enums\MagistralType;
use App\Modules\Catalog\Models\MagistralCategoria;
use App\Modules\Catalog\Models\MagistralFormula;
use Illuminate\Database\Seeder;

final class MagistralCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $path = resource_path('catalog/data/magistral.json');

        if (! file_exists($path)) {
            return;
        }

        /** @var array{
         *     farmacos: array<int, array{id: string, label: string, icon?: string, formulas: array<int, array{id: string, name: string, components: array<int, array{name: string, dose: string}>, excipient?: string, posology?: string, instructions?: string, notes?: string}>}>,
         *     alvos: array<int, array{id: string, label: string, icon?: string, formulas: array<int, array{id: string, name: string, components: array<int, array{name: string, dose: string}>, excipient?: string, posology?: string, instructions?: string, notes?: string}>}>
         * } $data
         */
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $this->seedTree(MagistralType::Farmaco, $data['farmacos'] ?? []);
        $this->seedTree(MagistralType::Alvo, $data['alvos'] ?? []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     */
    private function seedTree(MagistralType $type, array $categories): void
    {
        foreach ($categories as $category) {
            MagistralCategoria::query()->updateOrCreate(
                ['id' => $category['id']],
                [
                    'id' => $category['id'],
                    'tipo' => $type,
                    'rotulo' => $category['label'],
                    'icone' => $category['icon'] ?? null,
                ],
            );

            foreach ($category['formulas'] ?? [] as $formula) {
                MagistralFormula::query()->updateOrCreate(
                    ['id' => $formula['id']],
                    [
                        'id' => $formula['id'],
                        'categoria_id' => $category['id'],
                        'nome' => $formula['name'],
                        'componentes' => $formula['components'] ?? [],
                        'excipiente' => $formula['excipient'] ?? null,
                        'posologia' => $formula['posology'] ?? null,
                        'instrucoes' => $formula['instructions'] ?? null,
                        'notas' => $formula['notes'] ?? null,
                    ],
                );
            }
        }
    }
}
