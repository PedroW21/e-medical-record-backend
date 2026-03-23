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
 * @property string $tipo
 * @property float|null $raiz_aorta
 * @property float|null $aorta_ascendente
 * @property float|null $arco_aortico
 * @property float|null $ae_mm
 * @property float|null $ae_ml
 * @property float|null $ae_indexado
 * @property float|null $septo
 * @property float|null $dvd
 * @property float|null $ddve
 * @property float|null $dsve
 * @property float|null $pp
 * @property float|null $erp
 * @property float|null $indice_massa_ve
 * @property float|null $fe
 * @property float|null $psap
 * @property float|null $tapse
 * @property float|null $onda_e_mitral
 * @property float|null $onda_a
 * @property float|null $relacao_e_a
 * @property bool $relacao_e_a_override
 * @property float|null $e_septal
 * @property float|null $e_lateral
 * @property float|null $relacao_e_e
 * @property float|null $s_tricuspide
 * @property array<string, mixed>|null $valva_aortica
 * @property array<string, mixed>|null $valva_mitral
 * @property array<string, mixed>|null $valva_tricuspide
 * @property string|null $analise_qualitativa
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 */
class ResultadoEcocardiograma extends Model
{
    use HasFactory;

    protected $table = 'resultados_ecocardiograma';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'data',
        'tipo',
        'raiz_aorta',
        'aorta_ascendente',
        'arco_aortico',
        'ae_mm',
        'ae_ml',
        'ae_indexado',
        'septo',
        'dvd',
        'ddve',
        'dsve',
        'pp',
        'erp',
        'indice_massa_ve',
        'fe',
        'psap',
        'tapse',
        'onda_e_mitral',
        'onda_a',
        'relacao_e_a',
        'relacao_e_a_override',
        'e_septal',
        'e_lateral',
        'relacao_e_e',
        's_tricuspide',
        'valva_aortica',
        'valva_mitral',
        'valva_tricuspide',
        'analise_qualitativa',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'date',
            'raiz_aorta' => 'decimal:2',
            'aorta_ascendente' => 'decimal:2',
            'arco_aortico' => 'decimal:2',
            'ae_mm' => 'decimal:2',
            'ae_ml' => 'decimal:2',
            'ae_indexado' => 'decimal:2',
            'septo' => 'decimal:2',
            'dvd' => 'decimal:2',
            'ddve' => 'decimal:2',
            'dsve' => 'decimal:2',
            'pp' => 'decimal:2',
            'erp' => 'decimal:4',
            'indice_massa_ve' => 'decimal:2',
            'fe' => 'decimal:2',
            'psap' => 'decimal:2',
            'tapse' => 'decimal:2',
            'onda_e_mitral' => 'decimal:2',
            'onda_a' => 'decimal:2',
            'relacao_e_a' => 'decimal:4',
            'relacao_e_a_override' => 'boolean',
            'e_septal' => 'decimal:2',
            'e_lateral' => 'decimal:2',
            'relacao_e_e' => 'decimal:4',
            's_tricuspide' => 'decimal:2',
            'valva_aortica' => 'array',
            'valva_mitral' => 'array',
            'valva_tricuspide' => 'array',
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

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\EchoResultFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\EchoResultFactory::new();
    }
}
