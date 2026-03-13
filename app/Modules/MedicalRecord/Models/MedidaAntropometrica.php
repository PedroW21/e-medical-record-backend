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
 * @property float|null $peso
 * @property float|null $altura
 * @property float|null $imc
 * @property string|null $classificacao_imc
 * @property int|null $fc
 * @property float|null $spo2
 * @property float|null $temperatura
 * @property int|null $pa_sentado_d_pas
 * @property int|null $pa_sentado_d_pad
 * @property int|null $pa_sentado_e_pas
 * @property int|null $pa_sentado_e_pad
 * @property int|null $pa_em_pe_d_pas
 * @property int|null $pa_em_pe_d_pad
 * @property int|null $pa_em_pe_e_pas
 * @property int|null $pa_em_pe_e_pad
 * @property int|null $pa_deitado_d_pas
 * @property int|null $pa_deitado_d_pad
 * @property int|null $pa_deitado_e_pas
 * @property int|null $pa_deitado_e_pad
 * @property float|null $circunferencia_pescoco
 * @property float|null $circunferencia_cintura
 * @property float|null $circunferencia_quadril
 * @property float|null $circunferencia_abdominal
 * @property float|null $circunferencia_braco_d
 * @property float|null $circunferencia_braco_e
 * @property float|null $circunferencia_coxa_d
 * @property float|null $circunferencia_coxa_e
 * @property float|null $circunferencia_panturrilha_d
 * @property float|null $circunferencia_panturrilha_e
 * @property float|null $relacao_cintura_quadril
 * @property float|null $relacao_cintura_altura
 * @property float|null $dobra_tricipital
 * @property float|null $dobra_subescapular
 * @property float|null $dobra_suprailica
 * @property float|null $dobra_abdominal
 * @property float|null $dobra_peitoral
 * @property float|null $dobra_coxa
 * @property float|null $dobra_axilar_media
 * @property float|null $abertura_bucal
 * @property float|null $distancia_tireomentual
 * @property float|null $distancia_mentoesternal
 * @property string|null $deslocamento_mandibular
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 */
class MedidaAntropometrica extends Model
{
    use HasFactory;

    protected $table = 'medidas_antropometricas';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'peso',
        'altura',
        'imc',
        'classificacao_imc',
        'fc',
        'spo2',
        'temperatura',
        'pa_sentado_d_pas',
        'pa_sentado_d_pad',
        'pa_sentado_e_pas',
        'pa_sentado_e_pad',
        'pa_em_pe_d_pas',
        'pa_em_pe_d_pad',
        'pa_em_pe_e_pas',
        'pa_em_pe_e_pad',
        'pa_deitado_d_pas',
        'pa_deitado_d_pad',
        'pa_deitado_e_pas',
        'pa_deitado_e_pad',
        'circunferencia_pescoco',
        'circunferencia_cintura',
        'circunferencia_quadril',
        'circunferencia_abdominal',
        'circunferencia_braco_d',
        'circunferencia_braco_e',
        'circunferencia_coxa_d',
        'circunferencia_coxa_e',
        'circunferencia_panturrilha_d',
        'circunferencia_panturrilha_e',
        'relacao_cintura_quadril',
        'relacao_cintura_altura',
        'dobra_tricipital',
        'dobra_subescapular',
        'dobra_suprailica',
        'dobra_abdominal',
        'dobra_peitoral',
        'dobra_coxa',
        'dobra_axilar_media',
        'abertura_bucal',
        'distancia_tireomentual',
        'distancia_mentoesternal',
        'deslocamento_mandibular',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'peso' => 'decimal:2',
            'altura' => 'decimal:2',
            'imc' => 'decimal:2',
            'spo2' => 'decimal:2',
            'temperatura' => 'decimal:2',
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

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\AnthropometryFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\AnthropometryFactory::new();
    }
}
