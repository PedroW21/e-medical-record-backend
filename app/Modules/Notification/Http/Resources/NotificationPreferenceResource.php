<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NotificationPreferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{type: string, label: string, channels: array<string, array{enabled: bool, locked: bool}>} $resource */
        $resource = $this->resource;

        return [
            'type' => $resource['type'],
            'label' => $resource['label'],
            'channels' => $resource['channels'],
        ];
    }
}
