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
 * @property string|null $catalogo_exame_id
 * @property string|null $nome_avulso
 * @property \Illuminate\Support\Carbon $data_coleta
 * @property string $valor
 * @property float|null $valor_numerico
 * @property string $unidade
 * @property string|null $faixa_referencia
 * @property string|null $painel_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 * @property-read CatalogoExameLaboratorial|null $catalogoExame
 * @property-read PainelLaboratorial|null $painel
 */
class ValorLaboratorial extends Model
{
    use HasFactory;

    protected $table = 'valores_laboratoriais';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'catalogo_exame_id',
        'nome_avulso',
        'data_coleta',
        'valor',
        'valor_numerico',
        'unidade',
        'faixa_referencia',
        'painel_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_coleta' => 'date',
            'valor_numerico' => 'decimal:4',
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
     * @return BelongsTo<CatalogoExameLaboratorial, $this>
     */
    public function catalogoExame(): BelongsTo
    {
        return $this->belongsTo(CatalogoExameLaboratorial::class, 'catalogo_exame_id');
    }

    /**
     * @return BelongsTo<PainelLaboratorial, $this>
     */
    public function painel(): BelongsTo
    {
        return $this->belongsTo(PainelLaboratorial::class, 'painel_id');
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\LabResultFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\LabResultFactory::new();
    }
}
