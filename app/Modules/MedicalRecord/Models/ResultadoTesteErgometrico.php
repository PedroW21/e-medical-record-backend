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
 * @property float|null $pct_fc_max_prevista
 * @property int|null $fc_max
 * @property int|null $pas_max
 * @property int|null $pas_pre
 * @property float|null $vo2_max
 * @property float|null $mvo2_max
 * @property float|null $deficit_cronotropico
 * @property float|null $deficit_funcional_ve
 * @property float|null $debito_cardiaco
 * @property float|null $volume_sistolico
 * @property int|null $dp_max
 * @property float|null $met_max
 * @property string|null $aptidao_cardiorrespiratoria
 * @property string|null $observacoes
 * @property int|null $anexo_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 * @property-read Anexo|null $anexo
 */
class ResultadoTesteErgometrico extends Model
{
    use HasFactory;

    protected $table = 'resultados_teste_ergometrico';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'data',
        'protocolo',
        'pct_fc_max_prevista',
        'fc_max',
        'pas_max',
        'pas_pre',
        'vo2_max',
        'mvo2_max',
        'deficit_cronotropico',
        'deficit_funcional_ve',
        'debito_cardiaco',
        'volume_sistolico',
        'dp_max',
        'met_max',
        'aptidao_cardiorrespiratoria',
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
            'pct_fc_max_prevista' => 'decimal:2',
            'fc_max' => 'integer',
            'pas_max' => 'integer',
            'pas_pre' => 'integer',
            'vo2_max' => 'decimal:2',
            'mvo2_max' => 'decimal:2',
            'deficit_cronotropico' => 'decimal:2',
            'deficit_funcional_ve' => 'decimal:2',
            'debito_cardiaco' => 'decimal:2',
            'volume_sistolico' => 'decimal:2',
            'dp_max' => 'integer',
            'met_max' => 'decimal:2',
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

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\ErgometricTestResultFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\ErgometricTestResultFactory::new();
    }
}
