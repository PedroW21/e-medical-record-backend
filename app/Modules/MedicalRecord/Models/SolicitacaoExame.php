<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $prontuario_id
 * @property string|null $modelo_id
 * @property string|null $cid_10
 * @property string|null $indicacao_clinica
 * @property array<int, array<string, mixed>> $itens
 * @property array<string, mixed>|null $relatorio_medico
 * @property \Illuminate\Support\Carbon|null $impresso_em
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 */
class SolicitacaoExame extends Model
{
    use HasFactory;

    protected $table = 'solicitacoes_exames';

    protected $fillable = [
        'prontuario_id',
        'modelo_id',
        'cid_10',
        'indicacao_clinica',
        'itens',
        'relatorio_medico',
        'impresso_em',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'itens' => 'array',
            'relatorio_medico' => 'array',
            'impresso_em' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Prontuario, $this>
     */
    public function prontuario(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class);
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\ExamRequestFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\ExamRequestFactory::new();
    }
}
