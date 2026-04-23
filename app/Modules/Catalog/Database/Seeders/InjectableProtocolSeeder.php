<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Models\InjetavelProtocolo;
use App\Modules\Catalog\Models\InjetavelProtocoloComponente;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

final class InjectableProtocolSeeder extends Seeder
{
    public function run(): void
    {
        $path = resource_path('catalog/data/injectable.json');

        if (! file_exists($path)) {
            return;
        }

        /** @var array{protocols: array{im?: array<int, array<string, mixed>>, ev?: array<int, array<string, mixed>>, combined?: array<int, array<string, mixed>>}} $data */
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $protocols = array_merge(
            $data['protocols']['im'] ?? [],
            $data['protocols']['ev'] ?? [],
            $data['protocols']['combined'] ?? [],
        );

        $this->seedProtocols($protocols);
    }

    /**
     * @param  array<int, array<string, mixed>>  $protocols
     */
    private function seedProtocols(array $protocols): void
    {
        $now = CarbonImmutable::now();
        $protocolRows = [];
        $componentRows = [];

        foreach ($protocols as $protocol) {
            $protocolId = (string) $protocol['id'];

            $protocolRows[] = [
                'id' => $protocolId,
                'farmacia_id' => (string) $protocol['pharmacy'],
                'categoria_terapeutica_id' => (string) $protocol['therapeuticCategory'],
                'nome' => (string) $protocol['name'],
                'via' => (string) $protocol['route'],
                'notas_aplicacao' => isset($protocol['applicationNotes']) ? (string) $protocol['applicationNotes'] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach (($protocol['components'] ?? []) as $index => $component) {
                $componentRows[] = [
                    'protocolo_id' => $protocolId,
                    'ordem' => $index + 1,
                    'nome_farmaco' => (string) $component['farmacoName'],
                    'dosagem' => (string) $component['dosage'],
                    'quantidade_ampolas' => (int) $component['ampouleCount'],
                    'via' => isset($component['route']) ? (string) $component['route'] : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        collect($protocolRows)
            ->chunk(200)
            ->each(fn ($chunk) => InjetavelProtocolo::query()->upsert(
                $chunk->values()->all(),
                ['id'],
                ['farmacia_id', 'categoria_terapeutica_id', 'nome', 'via', 'notas_aplicacao', 'updated_at'],
            ));

        InjetavelProtocoloComponente::query()
            ->whereIn('protocolo_id', array_map(fn (array $row): string => (string) $row['id'], $protocolRows))
            ->delete();

        collect($componentRows)
            ->chunk(500)
            ->each(fn ($chunk) => InjetavelProtocoloComponente::query()->insert($chunk->values()->all()));
    }
}
