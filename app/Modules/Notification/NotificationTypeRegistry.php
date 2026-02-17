<?php

declare(strict_types=1);

namespace App\Modules\Notification;

final class NotificationTypeRegistry
{
    /**
     * Registered notification types.
     *
     * @var array<string, array{label: string, channels: list<string>}>
     */
    private static array $types = [];

    /**
     * Register a notification type.
     *
     * @param  list<string>  $channels
     */
    public static function register(string $slug, string $label, array $channels = ['database', 'mail', 'broadcast']): void
    {
        self::$types[$slug] = [
            'label' => $label,
            'channels' => $channels,
        ];
    }

    /**
     * Get all registered types.
     *
     * @return array<string, array{label: string, channels: list<string>}>
     */
    public static function all(): array
    {
        return self::$types;
    }

    /**
     * Get a single type by slug.
     *
     * @return array{label: string, channels: list<string>}|null
     */
    public static function get(string $slug): ?array
    {
        return self::$types[$slug] ?? null;
    }

    /**
     * Check if a type slug is registered.
     */
    public static function has(string $slug): bool
    {
        return isset(self::$types[$slug]);
    }

    /**
     * Get all valid type slugs.
     *
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::$types);
    }

    /**
     * Reset the registry (for testing).
     */
    public static function flush(): void
    {
        self::$types = [];
    }
}
