<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class NotificationService
{
    /**
     * List notifications for a user with filters.
     *
     * @param array{
     *     status?: string,
     *     type?: string,
     *     from?: string,
     *     to?: string,
     *     per_page?: int,
     * } $filters
     * @return LengthAwarePaginator<DatabaseNotification>
     */
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $user->notifications()
            ->whereNull('deleted_at');

        if (isset($filters['status'])) {
            match ($filters['status']) {
                'read' => $query->whereNotNull('read_at'),
                'unread' => $query->whereNull('read_at'),
                default => null,
            };
        }

        if (isset($filters['type'])) {
            $query->where('type', 'like', '%'.$filters['type'].'%');
        }

        if (isset($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('created_at', '<=', $filters['to'].' 23:59:59');
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Find a notification for a user, or throw 404.
     */
    public function findForUser(User $user, string $notificationId): DatabaseNotification
    {
        $notification = $user->notifications()
            ->whereNull('deleted_at')
            ->find($notificationId);

        if (! $notification) {
            throw new NotFoundHttpException('Notificação não encontrada.');
        }

        return $notification;
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(User $user, string $notificationId): DatabaseNotification
    {
        $notification = $this->findForUser($user, $notificationId);
        $notification->markAsRead();

        return $notification;
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()
            ->whereNull('deleted_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Soft delete a notification.
     */
    public function delete(User $user, string $notificationId): void
    {
        $notification = $this->findForUser($user, $notificationId);
        $notification->update(['deleted_at' => now()]);
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()
            ->whereNull('deleted_at')
            ->count();
    }
}
