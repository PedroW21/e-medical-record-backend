<?php

declare(strict_types=1);

namespace App\Modules\Notification\Enums;

enum NotificationChannel: string
{
    case Database = 'database';
    case Mail = 'mail';
    case Broadcast = 'broadcast';

    public function label(): string
    {
        return match ($this) {
            self::Database => 'No aplicativo',
            self::Mail => 'E-mail',
            self::Broadcast => 'Tempo real',
        };
    }

    /**
     * Channels that users can disable.
     *
     * @return list<self>
     */
    public static function disableable(): array
    {
        return [
            self::Mail,
            self::Broadcast,
        ];
    }
}
