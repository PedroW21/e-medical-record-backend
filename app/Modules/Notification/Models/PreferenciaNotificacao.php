<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models;

use App\Models\User;
use App\Modules\Notification\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $tipo_notificacao
 * @property NotificationChannel $canal
 * @property bool $ativo
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 */
class PreferenciaNotificacao extends Model
{
    protected $table = 'preferencias_notificacao';

    protected $fillable = [
        'user_id',
        'tipo_notificacao',
        'canal',
        'ativo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'canal' => NotificationChannel::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
