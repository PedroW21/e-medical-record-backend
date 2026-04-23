<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\InjetavelProtocoloFactory;
use App\Modules\Catalog\Enums\InjectableProtocolRoute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Predefined injectable protocol — an ordered composition of injectable
 * components belonging to a partner pharmacy.
 *
 * @property string $id
 * @property string $farmacia_id
 * @property string $categoria_terapeutica_id
 * @property string $nome
 * @property InjectableProtocolRoute $via
 * @property string|null $notas_aplicacao
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Farmacia $farmacia
 * @property-read CategoriaTerapeutica $categoriaTerapeutica
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InjetavelProtocoloComponente> $componentes
 */
class InjetavelProtocolo extends Model
{
    use HasFactory;

    protected $table = 'catalogo_injetaveis_protocolos';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'farmacia_id',
        'categoria_terapeutica_id',
        'nome',
        'via',
        'notas_aplicacao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'via' => InjectableProtocolRoute::class,
        ];
    }

    /**
     * @return BelongsTo<Farmacia, $this>
     */
    public function farmacia(): BelongsTo
    {
        return $this->belongsTo(Farmacia::class, 'farmacia_id');
    }

    /**
     * @return BelongsTo<CategoriaTerapeutica, $this>
     */
    public function categoriaTerapeutica(): BelongsTo
    {
        return $this->belongsTo(CategoriaTerapeutica::class, 'categoria_terapeutica_id');
    }

    /**
     * @return HasMany<InjetavelProtocoloComponente, $this>
     */
    public function componentes(): HasMany
    {
        return $this->hasMany(InjetavelProtocoloComponente::class, 'protocolo_id')
            ->orderBy('ordem');
    }

    protected static function newFactory(): InjetavelProtocoloFactory
    {
        return InjetavelProtocoloFactory::new();
    }
}
