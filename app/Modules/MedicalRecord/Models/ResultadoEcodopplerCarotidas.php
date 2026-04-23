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
 * @property float|null $espessura_intimal_carotida_comum_e
 * @property float|null $grau_estenose_carotida_comum_e
 * @property float|null $espessura_intimal_carotida_comum_d
 * @property float|null $grau_estenose_carotida_comum_d
 * @property float|null $espessura_intimal_carotida_externa_e
 * @property float|null $grau_estenose_carotida_externa_e
 * @property float|null $espessura_intimal_carotida_externa_d
 * @property float|null $grau_estenose_carotida_externa_d
 * @property float|null $espessura_intimal_bulbo_interna_e
 * @property float|null $grau_estenose_bulbo_interna_e
 * @property float|null $espessura_intimal_bulbo_interna_d
 * @property float|null $grau_estenose_bulbo_interna_d
 * @property float|null $espessura_intimal_vertebral_e
 * @property float|null $grau_estenose_vertebral_e
 * @property float|null $espessura_intimal_vertebral_d
 * @property float|null $grau_estenose_vertebral_d
 * @property string|null $observacoes
 * @property int|null $anexo_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 * @property-read Anexo|null $anexo
 */
class ResultadoEcodopplerCarotidas extends Model
{
    use HasFactory;

    protected $table = 'resultados_ecodoppler_carotidas';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'data',
        'espessura_intimal_carotida_comum_e',
        'grau_estenose_carotida_comum_e',
        'espessura_intimal_carotida_comum_d',
        'grau_estenose_carotida_comum_d',
        'espessura_intimal_carotida_externa_e',
        'grau_estenose_carotida_externa_e',
        'espessura_intimal_carotida_externa_d',
        'grau_estenose_carotida_externa_d',
        'espessura_intimal_bulbo_interna_e',
        'grau_estenose_bulbo_interna_e',
        'espessura_intimal_bulbo_interna_d',
        'grau_estenose_bulbo_interna_d',
        'espessura_intimal_vertebral_e',
        'grau_estenose_vertebral_e',
        'espessura_intimal_vertebral_d',
        'grau_estenose_vertebral_d',
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
            'espessura_intimal_carotida_comum_e' => 'decimal:2',
            'grau_estenose_carotida_comum_e' => 'decimal:2',
            'espessura_intimal_carotida_comum_d' => 'decimal:2',
            'grau_estenose_carotida_comum_d' => 'decimal:2',
            'espessura_intimal_carotida_externa_e' => 'decimal:2',
            'grau_estenose_carotida_externa_e' => 'decimal:2',
            'espessura_intimal_carotida_externa_d' => 'decimal:2',
            'grau_estenose_carotida_externa_d' => 'decimal:2',
            'espessura_intimal_bulbo_interna_e' => 'decimal:2',
            'grau_estenose_bulbo_interna_e' => 'decimal:2',
            'espessura_intimal_bulbo_interna_d' => 'decimal:2',
            'grau_estenose_bulbo_interna_d' => 'decimal:2',
            'espessura_intimal_vertebral_e' => 'decimal:2',
            'grau_estenose_vertebral_e' => 'decimal:2',
            'espessura_intimal_vertebral_d' => 'decimal:2',
            'grau_estenose_vertebral_d' => 'decimal:2',
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

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\CarotidEcodopplerResultFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\CarotidEcodopplerResultFactory::new();
    }
}
