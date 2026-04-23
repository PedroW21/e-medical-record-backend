<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\InjetavelProtocoloComponenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single component (injectable drug, dosage, ampoule count) of a protocol.
 *
 * @property int $id
 * @property string $protocolo_id
 * @property int $ordem
 * @property string $nome_farmaco
 * @property string $dosagem
 * @property int $quantidade_ampolas
 * @property string|null $via
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read InjetavelProtocolo $protocolo
 */
class InjetavelProtocoloComponente extends Model
{
    use HasFactory;

    protected $table = 'catalogo_injetaveis_protocolo_componentes';

    protected $fillable = [
        'protocolo_id',
        'ordem',
        'nome_farmaco',
        'dosagem',
        'quantidade_ampolas',
        'via',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordem' => 'integer',
            'quantidade_ampolas' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<InjetavelProtocolo, $this>
     */
    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(InjetavelProtocolo::class, 'protocolo_id');
    }

    protected static function newFactory(): InjetavelProtocoloComponenteFactory
    {
        return InjetavelProtocoloComponenteFactory::new();
    }
}
