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
 * @property float|null $pas_vigilia
 * @property float|null $pad_vigilia
 * @property float|null $pas_sono
 * @property float|null $pad_sono
 * @property float|null $pas_24h
 * @property float|null $pad_24h
 * @property bool $pas_24h_override
 * @property bool $pad_24h_override
 * @property float|null $descenso_noturno_pas
 * @property bool $descenso_noturno_pas_override
 * @property float|null $descenso_noturno_pad
 * @property bool $descenso_noturno_pad_override
 * @property string|null $observacoes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 */
class ResultadoMapa extends Model
{
    use HasFactory;

    protected $table = 'resultados_mapa';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'data',
        'pas_vigilia',
        'pad_vigilia',
        'pas_sono',
        'pad_sono',
        'pas_24h',
        'pad_24h',
        'pas_24h_override',
        'pad_24h_override',
        'descenso_noturno_pas',
        'descenso_noturno_pas_override',
        'descenso_noturno_pad',
        'descenso_noturno_pad_override',
        'observacoes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'date',
            'pas_vigilia' => 'decimal:2',
            'pad_vigilia' => 'decimal:2',
            'pas_sono' => 'decimal:2',
            'pad_sono' => 'decimal:2',
            'pas_24h' => 'decimal:2',
            'pad_24h' => 'decimal:2',
            'pas_24h_override' => 'boolean',
            'pad_24h_override' => 'boolean',
            'descenso_noturno_pas' => 'decimal:2',
            'descenso_noturno_pas_override' => 'boolean',
            'descenso_noturno_pad' => 'decimal:2',
            'descenso_noturno_pad_override' => 'boolean',
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

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\MapaResultFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\MapaResultFactory::new();
    }
}
