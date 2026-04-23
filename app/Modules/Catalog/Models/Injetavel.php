<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\InjetavelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Injectable drug available from a partner pharmacy.
 *
 * @property string $id
 * @property string $farmacia_id
 * @property string $nome
 * @property string $dosagem
 * @property string|null $volume
 * @property string|null $via_exclusiva
 * @property string|null $composicao
 * @property bool $is_blend
 * @property array<int, string> $vias_permitidas
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Farmacia $farmacia
 */
class Injetavel extends Model
{
    use HasFactory;

    protected $table = 'catalogo_injetaveis';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'farmacia_id',
        'nome',
        'dosagem',
        'volume',
        'via_exclusiva',
        'composicao',
        'is_blend',
        'vias_permitidas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_blend' => 'boolean',
            'vias_permitidas' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Farmacia, $this>
     */
    public function farmacia(): BelongsTo
    {
        return $this->belongsTo(Farmacia::class, 'farmacia_id');
    }

    protected static function newFactory(): InjetavelFactory
    {
        return InjetavelFactory::new();
    }
}
