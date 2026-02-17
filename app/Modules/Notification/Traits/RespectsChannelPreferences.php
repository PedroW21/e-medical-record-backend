<?php

declare(strict_types=1);

namespace App\Modules\Notification\Traits;

use App\Modules\Notification\Models\PreferenciaNotificacao;

trait RespectsChannelPreferences
{
    /**
     * Return the notification type slug used in the preferences table.
     */
    abstract public static function notificationType(): string;

    /**
     * Resolve channels based on user preferences.
     *
     * The 'database' channel is always active.
     *
     * @return list<string>
     */
    protected function resolveChannels(object $notifiable): array
    {
        $channels = ['database'];

        $disabledChannels = PreferenciaNotificacao::query()
            ->where('user_id', $notifiable->getKey())
            ->where('tipo_notificacao', static::notificationType())
            ->where('ativo', false)
            ->pluck('canal')
            ->map(fn ($canal) => $canal instanceof \App\Modules\Notification\Enums\NotificationChannel ? $canal->value : $canal)
            ->toArray();

        foreach (['mail', 'broadcast'] as $channel) {
            if (! in_array($channel, $disabledChannels, true)) {
                $channels[] = $channel;
            }
        }

        return $channels;
    }
}
