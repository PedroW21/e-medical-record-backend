<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Enums\RecipeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $prontuario_id
 * @property PrescriptionSubType $subtipo
 * @property RecipeType $tipo_receita
 * @property bool $tipo_receita_override
 * @property array<int, array<string, mixed>> $itens
 * @property string|null $observacoes
 * @property \Illuminate\Support\Carbon|null $impresso_em
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 */
class Prescricao extends Model
{
    use HasFactory;

    protected $table = 'prescricoes';

    protected $fillable = [
        'prontuario_id',
        'subtipo',
        'tipo_receita',
        'tipo_receita_override',
        'itens',
        'observacoes',
        'impresso_em',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtipo' => PrescriptionSubType::class,
            'tipo_receita' => RecipeType::class,
            'tipo_receita_override' => 'boolean',
            'itens' => 'array',
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

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\PrescriptionFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\PrescriptionFactory::new();
    }
}
