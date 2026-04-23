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
 * @property float|null $fracao_gordura
 * @property float|null $tsi
 * @property float|null $kpa
 * @property string|null $observacoes
 * @property int|null $anexo_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 * @property-read Anexo|null $anexo
 */
class ResultadoElastografiaHepatica extends Model
{
    use HasFactory;

    protected $table = 'resultados_elastografia_hepatica';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'data',
        'fracao_gordura',
        'tsi',
        'kpa',
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
            'fracao_gordura' => 'decimal:2',
            'tsi' => 'decimal:2',
            'kpa' => 'decimal:2',
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

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\HepaticElastographyResultFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\HepaticElastographyResultFactory::new();
    }
}
