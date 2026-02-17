<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Controllers;

use App\Modules\Notification\Http\Requests\UpdateNotificationPreferenceRequest;
use App\Modules\Notification\Http\Resources\NotificationPreferenceResource;
use App\Modules\Notification\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class NotificationPreferenceController
{
    public function __construct(
        private readonly NotificationPreferenceService $preferenceService,
    ) {}

    /**
     * List notification preferences for the authenticated user.
     *
     * Returns all notification types with their channel preferences.
     *
     * @authenticated
     *
     * @group Notification Preferences
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $preferences = $this->preferenceService->listForUser($request->user());

        return NotificationPreferenceResource::collection($preferences);
    }

    /**
     * Update notification preferences in batch.
     *
     * @authenticated
     *
     * @group Notification Preferences
     */
    public function update(UpdateNotificationPreferenceRequest $request): JsonResponse
    {
        $this->preferenceService->updateForUser(
            user: $request->user(),
            preferences: $request->validated('preferences'),
        );

        return response()->json(['message' => 'Preferências atualizadas com sucesso.']);
    }
}
