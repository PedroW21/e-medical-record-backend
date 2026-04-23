<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $prontuario_id
 * @property int $paciente_id
 * @property \Illuminate\Support\Carbon $data
 * @property string|null $protocolo
 * @property string|null $modalidade_estresse
 * @property int|null $fc_max
 * @property float|null $pct_fc_max_prevista
 * @property int|null $pa_max
 * @property array<string, mixed>|null $sintomas_estresse
 * @property array<string, mixed>|null $alteracoes_ecg_estresse
 * @property string|null $perfusao_da_estresse
 * @property string|null $perfusao_da_repouso
 * @property string|null $perfusao_da_reversibilidade
 * @property string|null $perfusao_cx_estresse
 * @property string|null $perfusao_cx_repouso
 * @property string|null $perfusao_cx_reversibilidade
 * @property string|null $perfusao_cd_estresse
 * @property string|null $perfusao_cd_repouso
 * @property string|null $perfusao_cd_reversibilidade
 * @property int|null $sss
 * @property int|null $srs
 * @property int|null $sds
 * @property bool $sds_override
 * @property string|null $classificacao_sds
 * @property bool $classificacao_sds_override
 * @property float|null $fe_repouso
 * @property float|null $vdf_repouso
 * @property float|null $vsf_repouso
 * @property float|null $fe_estresse
 * @property float|null $vdf_estresse
 * @property float|null $vsf_estresse
 * @property bool|null $tid_presente
 * @property float|null $razao_tid
 * @property bool $tid_override
 * @property array<string, mixed>|null $segmentos
 * @property bool|null $captacao_pulmonar_aumentada
 * @property bool|null $dilatacao_vd
 * @property string|null $captacao_extracardiaca
 * @property string|null $resultado_global
 * @property string|null $extensao_defeito
 * @property string|null $observacoes
 * @property int|null $anexo_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 * @property-read Anexo|null $anexo
 */
class ResultadoCintilografia extends Model
{
    use HasFactory;

    protected $table = 'resultados_cintilografia';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'data',
        'protocolo',
        'modalidade_estresse',
        'fc_max',
        'pct_fc_max_prevista',
        'pa_max',
        'sintomas_estresse',
        'alteracoes_ecg_estresse',
        'perfusao_da_estresse',
        'perfusao_da_repouso',
        'perfusao_da_reversibilidade',
        'perfusao_cx_estresse',
        'perfusao_cx_repouso',
        'perfusao_cx_reversibilidade',
        'perfusao_cd_estresse',
        'perfusao_cd_repouso',
        'perfusao_cd_reversibilidade',
        'sss',
        'srs',
        'sds',
        'sds_override',
        'classificacao_sds',
        'classificacao_sds_override',
        'fe_repouso',
        'vdf_repouso',
        'vsf_repouso',
        'fe_estresse',
        'vdf_estresse',
        'vsf_estresse',
        'tid_presente',
        'razao_tid',
        'tid_override',
        'segmentos',
        'captacao_pulmonar_aumentada',
        'dilatacao_vd',
        'captacao_extracardiaca',
        'resultado_global',
        'extensao_defeito',
        'observacoes',
        'anexo_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'date',
            'fc_max' => 'integer',
            'pct_fc_max_prevista' => 'decimal:2',
            'pa_max' => 'integer',
            'sintomas_estresse' => 'array',
            'alteracoes_ecg_estresse' => 'array',
            'sss' => 'integer',
            'srs' => 'integer',
            'sds' => 'integer',
            'sds_override' => 'boolean',
            'classificacao_sds_override' => 'boolean',
            'fe_repouso' => 'decimal:2',
            'vdf_repouso' => 'decimal:2',
            'vsf_repouso' => 'decimal:2',
            'fe_estresse' => 'decimal:2',
            'vdf_estresse' => 'decimal:2',
            'vsf_estresse' => 'decimal:2',
            'tid_presente' => 'boolean',
            'razao_tid' => 'decimal:4',
            'tid_override' => 'boolean',
            'segmentos' => 'array',
            'captacao_pulmonar_aumentada' => 'boolean',
            'dilatacao_vd' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Prontuario, $this>
     */
    public function prontuario(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class);
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    /**
     * @return BelongsTo<Anexo, $this>
     */
    public function anexo(): BelongsTo
    {
        return $this->belongsTo(Anexo::class, 'anexo_id');
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\ScintigraphyResultFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\ScintigraphyResultFactory::new();
    }
}
