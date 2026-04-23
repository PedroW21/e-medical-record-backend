<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\MagistralFormulaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compounded (magistral) formula that references a category and carries a
 * list of active components plus preparation and usage instructions.
 *
 * @property string $id
 * @property string $categoria_id
 * @property string $nome
 * @property array<int, array{name: string, dose: string}> $componentes
 * @property string|null $excipiente
 * @property string|null $posologia
 * @property string|null $instrucoes
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read MagistralCategoria $categoria
 */
class MagistralFormula extends Model
{
    use HasFactory;

    protected $table = 'catalogo_magistral_formulas';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'categoria_id',
        'nome',
        'componentes',
        'excipiente',
        'posologia',
        'instrucoes',
        'notas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'componentes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<MagistralCategoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(MagistralCategoria::class, 'categoria_id');
    }

    protected static function newFactory(): MagistralFormulaFactory
    {
        return MagistralFormulaFactory::new();
    }
}
