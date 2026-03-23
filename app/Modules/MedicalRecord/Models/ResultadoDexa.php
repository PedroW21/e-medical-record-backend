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
 * @property float|null $peso_total
 * @property float|null $dmo
 * @property float|null $t_score
 * @property float|null $gordura_corporal_pct
 * @property float|null $gordura_total
 * @property float|null $imc
 * @property float|null $gordura_visceral
 * @property float|null $gordura_visceral_pct
 * @property float|null $massa_magra
 * @property float|null $massa_magra_pct
 * @property float|null $fmi
 * @property float|null $ffmi
 * @property float|null $rsmi
 * @property float|null $tmr
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 */
class ResultadoDexa extends Model
{
    use HasFactory;

    protected $table = 'resultados_dexa';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'data',
        'peso_total',
        'dmo',
        't_score',
        'gordura_corporal_pct',
        'gordura_total',
        'imc',
        'gordura_visceral',
        'gordura_visceral_pct',
        'massa_magra',
        'massa_magra_pct',
        'fmi',
        'ffmi',
        'rsmi',
        'tmr',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'date',
            'peso_total' => 'decimal:2',
            'dmo' => 'decimal:4',
            't_score' => 'decimal:2',
            'gordura_corporal_pct' => 'decimal:2',
            'gordura_total' => 'decimal:2',
            'imc' => 'decimal:2',
            'gordura_visceral' => 'decimal:2',
            'gordura_visceral_pct' => 'decimal:2',
            'massa_magra' => 'decimal:2',
            'massa_magra_pct' => 'decimal:2',
            'fmi' => 'decimal:2',
            'ffmi' => 'decimal:2',
            'rsmi' => 'decimal:2',
            'tmr' => 'decimal:2',
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

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\DexaResultFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\DexaResultFactory::new();
    }
}
