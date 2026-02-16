<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $medico_id
 * @property int $secretaria_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $medico
 * @property-read User $secretaria
 */
class Delegacao extends Model
{
    use HasFactory;

    protected $table = 'delegacoes';

    protected $fillable = [
        'medico_id',
        'secretaria_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medico_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function secretaria(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secretaria_id');
    }

    protected static function newFactory(): \App\Modules\Delegation\Database\Factories\DelegationFactory
    {
        return \App\Modules\Delegation\Database\Factories\DelegationFactory::new();
    }
}
