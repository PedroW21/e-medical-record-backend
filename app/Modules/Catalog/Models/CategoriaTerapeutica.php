<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\CategoriaTerapeuticaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Therapeutic category used to group injectables, protocols and other
 * catalog entries.
 *
 * @property string $id
 * @property string $nome
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InjetavelProtocolo> $protocolos
 */
class CategoriaTerapeutica extends Model
{
    use HasFactory;

    protected $table = 'catalogo_categorias_terapeuticas';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nome',
    ];

    /**
     * @return HasMany<InjetavelProtocolo, $this>
     */
    public function protocolos(): HasMany
    {
        return $this->hasMany(InjetavelProtocolo::class, 'categoria_terapeutica_id');
    }

    protected static function newFactory(): CategoriaTerapeuticaFactory
    {
        return CategoriaTerapeuticaFactory::new();
    }
}
