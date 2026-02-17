<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Models\User;
use App\Modules\Notification\Models\PreferenciaNotificacao;
use App\Modules\Notification\NotificationTypeRegistry;
use Illuminate\Support\Collection;

final class NotificationPreferenceService
{
    /**
     * Get all preferences for a user, grouped by type.
     *
     * Returns the full matrix of types x channels, merging DB records with defaults.
     *
     * @return Collection<int, array{
     *     type: string,
     *     label: string,
     *     channels: array<string, array{enabled: bool, locked: bool}>
     * }>
     */
    public function listForUser(User $user): Collection
    {
        $savedPreferences = PreferenciaNotificacao::query()
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('tipo_notificacao');

        $types = NotificationTypeRegistry::all();

        return collect($types)->map(function (array $config, string $slug) use ($savedPreferences) {
            $saved = $savedPreferences->get($slug, collect());

            $channels = [];
            foreach ($config['channels'] as $channel) {
                $preference = $saved->firstWhere('canal', $channel);
                $isDatabase = $channel === 'database';

                $channels[$channel] = [
                    'enabled' => $isDatabase ? true : ($preference ? $preference->ativo : true),
                    'locked' => $isDatabase,
                ];
            }

            return [
                'type' => $slug,
                'label' => $config['label'],
                'channels' => $channels,
            ];
        })->values();
    }

    /**
     * Update preferences for a user in batch.
     *
     * @param  array<int, array{type: string, channel: string, enabled: bool}>  $preferences
     */
    public function updateForUser(User $user, array $preferences): void
    {
        foreach ($preferences as $pref) {
            if ($pref['channel'] === 'database') {
                continue;
            }

            PreferenciaNotificacao::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'tipo_notificacao' => $pref['type'],
                    'canal' => $pref['channel'],
                ],
                [
                    'ativo' => $pref['enabled'],
                ],
            );
        }
    }
}
