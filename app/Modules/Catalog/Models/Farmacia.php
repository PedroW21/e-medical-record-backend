<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\FarmaciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalog entry for an injectable-compounding pharmacy partner.
 *
 * @property string $id
 * @property string $nome
 * @property string|null $cor
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Injetavel> $injetaveis
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InjetavelProtocolo> $protocolos
 */
class Farmacia extends Model
{
    use HasFactory;

    protected $table = 'catalogo_farmacias';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nome',
        'cor',
    ];

    /**
     * @return HasMany<Injetavel, $this>
     */
    public function injetaveis(): HasMany
    {
        return $this->hasMany(Injetavel::class, 'farmacia_id');
    }

    /**
     * @return HasMany<InjetavelProtocolo, $this>
     */
    public function protocolos(): HasMany
    {
        return $this->hasMany(InjetavelProtocolo::class, 'farmacia_id');
    }

    protected static function newFactory(): FarmaciaFactory
    {
        return FarmaciaFactory::new();
    }
}
