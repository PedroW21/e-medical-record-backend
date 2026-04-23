<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\MagistralCategoriaFactory;
use App\Modules\Catalog\Enums\MagistralType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Magistral (compounded medications) category grouping formulas by active
 * ingredient (`farmaco`) or therapeutic target (`alvo`).
 *
 * @property string $id
 * @property MagistralType $tipo
 * @property string $rotulo
 * @property string|null $icone
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MagistralFormula> $formulas
 */
class MagistralCategoria extends Model
{
    use HasFactory;

    protected $table = 'catalogo_magistral_categorias';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tipo',
        'rotulo',
        'icone',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => MagistralType::class,
        ];
    }

    /**
     * @return HasMany<MagistralFormula, $this>
     */
    public function formulas(): HasMany
    {
        return $this->hasMany(MagistralFormula::class, 'categoria_id');
    }

    protected static function newFactory(): MagistralCategoriaFactory
    {
        return MagistralCategoriaFactory::new();
    }
}
