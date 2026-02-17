<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Controllers;

use App\Modules\Notification\Http\Requests\ListNotificationRequest;
use App\Modules\Notification\Http\Resources\NotificationResource;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class NotificationController
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * List notifications for the authenticated user.
     *
     * Returns a paginated list of notifications with optional filters.
     *
     * @authenticated
     *
     * @group Notifications
     *
     * @queryParam status string Filter by status: read, unread, all. Example: unread
     * @queryParam type string Filter by notification type slug. Example: new_public_appointment_requested
     * @queryParam from string Filter from date (Y-m-d). Example: 2026-01-01
     * @queryParam to string Filter to date (Y-m-d). Example: 2026-12-31
     * @queryParam per_page int Items per page (max 100). Example: 15
     */
    public function index(ListNotificationRequest $request): AnonymousResourceCollection
    {
        $notifications = $this->notificationService->listForUser(
            user: $request->user(),
            filters: $request->validated(),
        );

        return NotificationResource::collection($notifications);
    }

    /**
     * Get unread notifications count.
     *
     * @authenticated
     *
     * @group Notifications
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->unreadCount($request->user());

        return response()->json(['data' => ['count' => $count]]);
    }

    /**
     * Mark a notification as read.
     *
     * @authenticated
     *
     * @group Notifications
     */
    public function markAsRead(string $id, Request $request): NotificationResource
    {
        $notification = $this->notificationService->markAsRead(
            user: $request->user(),
            notificationId: $id,
        );

        return new NotificationResource($notification);
    }

    /**
     * Mark all notifications as read.
     *
     * @authenticated
     *
     * @group Notifications
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'message' => 'Todas as notificações foram marcadas como lidas.',
            'data' => ['count' => $count],
        ]);
    }

    /**
     * Delete a notification (soft delete).
     *
     * @authenticated
     *
     * @group Notifications
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $this->notificationService->delete(
            user: $request->user(),
            notificationId: $id,
        );

        return response()->json(['message' => 'Notificação excluída com sucesso.']);
    }
}
