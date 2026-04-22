<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use Illuminate\Database\Eloquent\Collection;

/**
 * Groups ValorLaboratorial rows into LabPanelExamResult v2 format.
 *
 * Input: Collection of ValorLaboratorial (all for one medical record)
 * Output: array of {date, panels[], loose[]}
 */
final class LabResultGroupedResource
{
    /**
     * Group lab values by date, then by panel vs loose.
     *
     * @param  Collection<int, \App\Modules\MedicalRecord\Models\ValorLaboratorial>  $values
     * @return array<int, array{date: string, panels: array, loose: array}>
     */
    public static function fromCollection(Collection $values): array
    {
        $byDate = $values->groupBy(fn ($v) => $v->data_coleta->format('Y-m-d'));

        $results = [];

        foreach ($byDate as $date => $dateValues) {
            $panelValues = $dateValues->whereNotNull('painel_id');
            $looseValues = $dateValues->whereNull('painel_id')->whereNotNull('nome_avulso');

            $panels = [];
            foreach ($panelValues->groupBy('painel_id') as $panelId => $pValues) {
                $panel = $pValues->first()->painel;
                $panels[] = [
                    'panel_id' => $panelId,
                    'panel_name' => $panel?->nome ?? $panelId,
                    'is_custom' => false,
                    'values' => $pValues->map(fn ($v) => [
                        'id' => $v->id,
                        'analyte_id' => $v->catalogo_exame_id,
                        'value' => $v->valor,
                        'anexo_id' => $v->anexo_id,
                    ])->values()->all(),
                ];
            }

            $loose = $looseValues->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->nome_avulso,
                'value' => $v->valor,
                'unit' => $v->unidade,
                'reference_range' => $v->faixa_referencia,
                'anexo_id' => $v->anexo_id,
            ])->values()->all();

            $results[] = [
                'date' => $date,
                'panels' => $panels,
                'loose' => $loose,
            ];
        }

        return $results;
    }
}
