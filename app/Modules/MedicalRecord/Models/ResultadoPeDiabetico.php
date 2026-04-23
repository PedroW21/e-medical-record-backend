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
 * @property array<string, mixed>|null $anamnese
 * @property array<string, mixed>|null $sintomas_neuropaticos
 * @property array<string, mixed>|null $inspecao_visual
 * @property array<string, mixed>|null $deformidades
 * @property array<string, mixed>|null $neurologico
 * @property array<string, mixed>|null $vascular
 * @property array<string, mixed>|null $termometria
 * @property int|null $nss_score
 * @property bool $nss_override
 * @property int|null $nds_score
 * @property bool $nds_override
 * @property float|null $itb_direito
 * @property float|null $itb_esquerdo
 * @property bool $itb_direito_override
 * @property bool $itb_esquerdo_override
 * @property float|null $tbi_direito
 * @property float|null $tbi_esquerdo
 * @property bool $tbi_direito_override
 * @property bool $tbi_esquerdo_override
 * @property string|null $categoria_iwgdf
 * @property bool $categoria_iwgdf_override
 * @property string|null $observacoes
 * @property int|null $anexo_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 * @property-read Anexo|null $anexo
 */
class ResultadoPeDiabetico extends Model
{
    use HasFactory;

    protected $table = 'resultados_pe_diabetico';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'data',
        'anamnese',
        'sintomas_neuropaticos',
        'inspecao_visual',
        'deformidades',
        'neurologico',
        'vascular',
        'termometria',
        'nss_score',
        'nss_override',
        'nds_score',
        'nds_override',
        'itb_direito',
        'itb_esquerdo',
        'itb_direito_override',
        'itb_esquerdo_override',
        'tbi_direito',
        'tbi_esquerdo',
        'tbi_direito_override',
        'tbi_esquerdo_override',
        'categoria_iwgdf',
        'categoria_iwgdf_override',
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
            'anamnese' => 'array',
            'sintomas_neuropaticos' => 'array',
            'inspecao_visual' => 'array',
            'deformidades' => 'array',
            'neurologico' => 'array',
            'vascular' => 'array',
            'termometria' => 'array',
            'nss_score' => 'integer',
            'nss_override' => 'boolean',
            'nds_score' => 'integer',
            'nds_override' => 'boolean',
            'itb_direito' => 'decimal:4',
            'itb_esquerdo' => 'decimal:4',
            'itb_direito_override' => 'boolean',
            'itb_esquerdo_override' => 'boolean',
            'tbi_direito' => 'decimal:4',
            'tbi_esquerdo' => 'decimal:4',
            'tbi_direito_override' => 'boolean',
            'tbi_esquerdo_override' => 'boolean',
            'categoria_iwgdf_override' => 'boolean',
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

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\DiabeticFootResultFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\DiabeticFootResultFactory::new();
    }
}
