# Notification Module Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Create a Notification module that exposes CRUD endpoints for notifications, channel preferences, and real-time broadcasting via Laravel Reverb.

**Architecture:** A self-contained module under `app/Modules/Notification/` following the existing modular pattern. Notification classes stay in their domain modules (e.g., `Appointment/Notifications/`). This module manages reading, deletion, preferences, and broadcasting. A `RespectsChannelPreferences` trait is shared for `via()` logic.

**Tech Stack:** Laravel 12, PostgreSQL, Laravel Reverb, Sanctum auth, Pest 4

---

### Task 1: Install and Configure Laravel Reverb

**Files:**
- Modify: `composer.json`
- Modify: `bootstrap/app.php`
- Create: `routes/channels.php`

**Step 1: Install Reverb**

Run: `composer require laravel/reverb --no-interaction`
Expected: Package installed successfully.

**Step 2: Install and publish Reverb config**

Run: `php artisan install:broadcasting --no-interaction`
Expected: Creates `config/broadcasting.php`, `routes/channels.php`, and updates `.env`.

**Step 3: Define the private notification channel in `routes/channels.php`**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});
```

**Step 4: Update `bootstrap/app.php` to load the channels file**

Add `channels: __DIR__.'/../routes/channels.php'` to `withRouting()`. After editing:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    channels: __DIR__.'/../routes/channels.php',
    health: '/up',
)
```

**Step 5: Ensure `.env` has Reverb variables**

Check that `php artisan install:broadcasting` added the Reverb env vars. If not, add:

```
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

**Step 6: Commit**

```bash
git add -A && git commit -m "feat(notification): install and configure Laravel Reverb broadcasting"
```

---

### Task 2: Create Database Migrations

**Files:**
- Create: `app/Modules/Notification/Database/Migrations/2026_02_16_000001_add_soft_deletes_to_notifications_table.php`
- Create: `app/Modules/Notification/Database/Migrations/2026_02_16_000002_create_preferencias_notificacao_table.php`

**Step 1: Create migration to add soft deletes to notifications**

Create file `app/Modules/Notification/Database/Migrations/2026_02_16_000001_add_soft_deletes_to_notifications_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
```

**Step 2: Create migration for `preferencias_notificacao` table**

Create file `app/Modules/Notification/Database/Migrations/2026_02_16_000002_create_preferencias_notificacao_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preferencias_notificacao', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo_notificacao');
            $table->string('canal');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'tipo_notificacao', 'canal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferencias_notificacao');
    }
};
```

**Step 3: Run migrations**

Run: `php artisan migrate`
Expected: Both migrations run successfully.

**Step 4: Commit**

```bash
git add app/Modules/Notification/Database/Migrations/ && git commit -m "feat(notification): add migrations for soft deletes and preferences table"
```

---

### Task 3: Create Model and Enums

**Files:**
- Create: `app/Modules/Notification/Models/PreferenciaNotificacao.php`
- Create: `app/Modules/Notification/Enums/NotificationChannel.php`

**Step 1: Create the `PreferenciaNotificacao` model**

```php
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
```

**Step 2: Create the `NotificationChannel` enum**

```php
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
```

**Step 3: Add `preferenciasNotificacao` relationship to User model**

In `app/Models/User.php`, add the import and relationship method:

```php
use App\Modules\Notification\Models\PreferenciaNotificacao;
use Illuminate\Database\Eloquent\Relations\HasMany;
```

Add method:

```php
/**
 * @return HasMany<PreferenciaNotificacao, $this>
 */
public function preferenciasNotificacao(): HasMany
{
    return $this->hasMany(PreferenciaNotificacao::class);
}
```

Add to the `@property-read` PHPDoc block:

```php
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PreferenciaNotificacao> $preferenciasNotificacao
```

**Step 4: Commit**

```bash
git add app/Modules/Notification/Models/ app/Modules/Notification/Enums/ app/Models/User.php && git commit -m "feat(notification): add PreferenciaNotificacao model and NotificationChannel enum"
```

---

### Task 4: Create the Notification Type Registry

**Files:**
- Create: `app/Modules/Notification/NotificationTypeRegistry.php`

This class maps notification type slugs to labels and available channels.

**Step 1: Create the registry**

```php
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
     * @param list<string> $channels
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
```

**Step 2: Register the existing notification type in `AppointmentServiceProvider`**

In `app/Modules/Appointment/Providers/AppointmentServiceProvider.php`, in the `boot()` method, add:

```php
use App\Modules\Notification\NotificationTypeRegistry;

// Inside boot():
NotificationTypeRegistry::register(
    slug: 'new_public_appointment_requested',
    label: 'Nova solicitação de agendamento',
    channels: ['database', 'mail', 'broadcast'],
);
```

**Step 3: Commit**

```bash
git add app/Modules/Notification/NotificationTypeRegistry.php app/Modules/Appointment/Providers/AppointmentServiceProvider.php && git commit -m "feat(notification): add notification type registry"
```

---

### Task 5: Create the RespectsChannelPreferences Trait

**Files:**
- Create: `app/Modules/Notification/Traits/RespectsChannelPreferences.php`
- Modify: `app/Modules/Appointment/Notifications/NewPublicAppointmentRequested.php`

**Step 1: Create the trait**

```php
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
```

**Step 2: Update `NewPublicAppointmentRequested` to use the trait**

Replace the `via()` method and add the trait + `notificationType()` method:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Notifications;

use App\Modules\Appointment\Models\Consulta;
use App\Modules\Notification\Traits\RespectsChannelPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewPublicAppointmentRequested extends Notification
{
    use Queueable;
    use RespectsChannelPreferences;

    public function __construct(
        private readonly Consulta $appointment,
    ) {}

    public static function notificationType(): string
    {
        return 'new_public_appointment_requested';
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->resolveChannels($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nova solicitação de consulta')
            ->greeting('Olá!')
            ->line("Uma nova solicitação de consulta foi recebida de {$this->appointment->nome_solicitante}.")
            ->line("Data: {$this->appointment->data}")
            ->line("Horário: {$this->appointment->horario}")
            ->line("Telefone: {$this->appointment->telefone_solicitante}")
            ->line("E-mail: {$this->appointment->email_solicitante}")
            ->action('Ver consultas', url('/appointments'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'requester_name' => $this->appointment->nome_solicitante,
            'date' => $this->appointment->data,
            'time' => $this->appointment->horario,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
```

**Step 3: Commit**

```bash
git add app/Modules/Notification/Traits/ app/Modules/Appointment/Notifications/NewPublicAppointmentRequested.php && git commit -m "feat(notification): add RespectsChannelPreferences trait and update existing notification"
```

---

### Task 6: Create Notification Service

**Files:**
- Create: `app/Modules/Notification/Services/NotificationService.php`

**Step 1: Create the service**

```php
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
     *
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
            $query->where('type', 'like', '%' . $filters['type'] . '%');
        }

        if (isset($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('created_at', '<=', $filters['to'] . ' 23:59:59');
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
```

**Step 2: Commit**

```bash
git add app/Modules/Notification/Services/NotificationService.php && git commit -m "feat(notification): add NotificationService"
```

---

### Task 7: Create Notification Preference Service

**Files:**
- Create: `app/Modules/Notification/Services/NotificationPreferenceService.php`

**Step 1: Create the service**

```php
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
     * @param array<int, array{type: string, channel: string, enabled: bool}> $preferences
     */
    public function updateForUser(User $user, array $preferences): void
    {
        foreach ($preferences as $pref) {
            // Skip database channel — always locked
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
```

**Step 2: Commit**

```bash
git add app/Modules/Notification/Services/NotificationPreferenceService.php && git commit -m "feat(notification): add NotificationPreferenceService"
```

---

### Task 8: Create Form Requests

**Files:**
- Create: `app/Modules/Notification/Http/Requests/ListNotificationRequest.php`
- Create: `app/Modules/Notification/Http/Requests/UpdateNotificationPreferenceRequest.php`

**Step 1: Create `ListNotificationRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:read,unread,all'],
            'type' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'O status deve ser: read, unread ou all.',
            'to.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'per_page.min' => 'A quantidade por página deve ser no mínimo 1.',
            'per_page.max' => 'A quantidade por página deve ser no máximo 100.',
        ];
    }
}
```

**Step 2: Create `UpdateNotificationPreferenceRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.type' => ['required', 'string', 'max:255'],
            'preferences.*.channel' => ['required', 'string', 'in:mail,broadcast,sms,whatsapp'],
            'preferences.*.enabled' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preferences.required' => 'O campo preferências é obrigatório.',
            'preferences.array' => 'O campo preferências deve ser um array.',
            'preferences.min' => 'É necessário informar ao menos uma preferência.',
            'preferences.*.type.required' => 'O tipo de notificação é obrigatório.',
            'preferences.*.channel.required' => 'O canal é obrigatório.',
            'preferences.*.channel.in' => 'O canal informado é inválido.',
            'preferences.*.enabled.required' => 'O campo habilitado é obrigatório.',
            'preferences.*.enabled.boolean' => 'O campo habilitado deve ser verdadeiro ou falso.',
        ];
    }
}
```

**Step 3: Commit**

```bash
git add app/Modules/Notification/Http/Requests/ && git commit -m "feat(notification): add form requests for notification endpoints"
```

---

### Task 9: Create API Resources

**Files:**
- Create: `app/Modules/Notification/Http/Resources/NotificationResource.php`
- Create: `app/Modules/Notification/Http/Resources/NotificationPreferenceResource.php`

**Step 1: Create `NotificationResource`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Illuminate\Notifications\DatabaseNotification
 */
final class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->data['notification_type'] ?? $this->type,
            'data' => $this->data,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

**Step 2: Create `NotificationPreferenceResource`**

```php
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
```

**Step 3: Commit**

```bash
git add app/Modules/Notification/Http/Resources/ && git commit -m "feat(notification): add API resources"
```

---

### Task 10: Create Controllers

**Files:**
- Create: `app/Modules/Notification/Http/Controllers/NotificationController.php`
- Create: `app/Modules/Notification/Http/Controllers/NotificationPreferenceController.php`

**Step 1: Create `NotificationController`**

```php
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
```

**Step 2: Create `NotificationPreferenceController`**

```php
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
```

**Step 3: Commit**

```bash
git add app/Modules/Notification/Http/Controllers/ && git commit -m "feat(notification): add controllers for notifications and preferences"
```

---

### Task 11: Create Routes and ServiceProvider

**Files:**
- Create: `app/Modules/Notification/routes.php`
- Create: `app/Modules/Notification/Providers/NotificationServiceProvider.php`

**Step 1: Create `routes.php`**

```php
<?php

declare(strict_types=1);

use App\Modules\Notification\Http\Controllers\NotificationController;
use App\Modules\Notification\Http\Controllers\NotificationPreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    Route::get('/notifications/preferences', [NotificationPreferenceController::class, 'index']);
    Route::put('/notifications/preferences', [NotificationPreferenceController::class, 'update']);
});
```

**Step 2: Create `NotificationServiceProvider`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Notification\Providers;

use Illuminate\Support\ServiceProvider;

final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
```

**Step 3: Commit**

```bash
git add app/Modules/Notification/routes.php app/Modules/Notification/Providers/ && git commit -m "feat(notification): add routes and service provider"
```

---

### Task 12: Update Notification `toArray()` to Include Type Slug

**Files:**
- Modify: `app/Modules/Appointment/Notifications/NewPublicAppointmentRequested.php`

Each notification should include its `notification_type` slug in the `data` JSON so the frontend can identify and filter by type.

**Step 1: Update `toArray()` in `NewPublicAppointmentRequested`**

Add `'notification_type' => self::notificationType()` to the returned array:

```php
public function toArray(object $notifiable): array
{
    return [
        'notification_type' => self::notificationType(),
        'appointment_id' => $this->appointment->id,
        'requester_name' => $this->appointment->nome_solicitante,
        'date' => $this->appointment->data,
        'time' => $this->appointment->horario,
    ];
}
```

**Step 2: Commit**

```bash
git add app/Modules/Appointment/Notifications/NewPublicAppointmentRequested.php && git commit -m "feat(notification): include notification_type slug in toArray data"
```

---

### Task 13: Write Feature Tests for Notification CRUD

**Files:**
- Create: `app/Modules/Notification/Tests/Feature/ListNotificationsTest.php`
- Create: `app/Modules/Notification/Tests/Feature/MarkNotificationReadTest.php`
- Create: `app/Modules/Notification/Tests/Feature/DeleteNotificationTest.php`

**Step 1: Create `ListNotificationsTest.php`**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Notifications\NewPublicAppointmentRequested;
use App\Modules\Appointment\Models\Consulta;

beforeEach(function (): void {
    $this->doctor = User::factory()->doctor()->create();
});

it('lists notifications for the authenticated user', function (): void {
    $appointment = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment));

    $response = $this->actingAs($this->doctor)->getJson('/api/notifications');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'type', 'data', 'read_at', 'created_at']],
            'meta',
        ]);
});

it('filters notifications by unread status', function (): void {
    $appointment = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment));

    // Mark as read
    $this->doctor->notifications()->first()->markAsRead();

    $response = $this->actingAs($this->doctor)->getJson('/api/notifications?status=unread');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('filters notifications by read status', function (): void {
    $appointment = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment));
    $this->doctor->notifications()->first()->markAsRead();

    $response = $this->actingAs($this->doctor)->getJson('/api/notifications?status=read');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns unread count', function (): void {
    $appointment = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment));

    $response = $this->actingAs($this->doctor)->getJson('/api/notifications/unread-count');

    $response->assertOk()
        ->assertJsonPath('data.count', 1);
});

it('rejects unauthenticated access', function (): void {
    $this->getJson('/api/notifications')->assertUnauthorized();
});
```

**Step 2: Create `MarkNotificationReadTest.php`**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Notifications\NewPublicAppointmentRequested;
use App\Modules\Appointment\Models\Consulta;

beforeEach(function (): void {
    $this->doctor = User::factory()->doctor()->create();
});

it('marks a notification as read', function (): void {
    $appointment = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment));

    $notificationId = $this->doctor->notifications()->first()->id;

    $response = $this->actingAs($this->doctor)->patchJson("/api/notifications/{$notificationId}/read");

    $response->assertOk()
        ->assertJsonPath('data.read_at', fn ($value) => $value !== null);
});

it('marks all notifications as read', function (): void {
    $appointment1 = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $appointment2 = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment1));
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment2));

    $response = $this->actingAs($this->doctor)->patchJson('/api/notifications/read-all');

    $response->assertOk()
        ->assertJsonPath('data.count', 2);

    expect($this->doctor->unreadNotifications()->count())->toBe(0);
});

it('returns 404 for nonexistent notification', function (): void {
    $response = $this->actingAs($this->doctor)->patchJson('/api/notifications/nonexistent-uuid/read');

    $response->assertNotFound();
});
```

**Step 3: Create `DeleteNotificationTest.php`**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Notifications\NewPublicAppointmentRequested;
use App\Modules\Appointment\Models\Consulta;

beforeEach(function (): void {
    $this->doctor = User::factory()->doctor()->create();
});

it('soft deletes a notification', function (): void {
    $appointment = Consulta::factory()->requested()->forDoctor($this->doctor)->create();
    $this->doctor->notify(new NewPublicAppointmentRequested($appointment));

    $notificationId = $this->doctor->notifications()->first()->id;

    $response = $this->actingAs($this->doctor)->deleteJson("/api/notifications/{$notificationId}");

    $response->assertOk()
        ->assertJsonPath('message', 'Notificação excluída com sucesso.');

    // Notification still exists in DB but is soft deleted
    $this->assertDatabaseHas('notifications', [
        'id' => $notificationId,
    ]);

    // But not visible in the list
    $this->actingAs($this->doctor)->getJson('/api/notifications')
        ->assertJsonCount(0, 'data');
});

it('returns 404 when deleting nonexistent notification', function (): void {
    $response = $this->actingAs($this->doctor)->deleteJson('/api/notifications/nonexistent-uuid');

    $response->assertNotFound();
});

it('cannot delete another users notification', function (): void {
    $otherDoctor = User::factory()->doctor()->create();
    $appointment = Consulta::factory()->requested()->forDoctor($otherDoctor)->create();
    $otherDoctor->notify(new NewPublicAppointmentRequested($appointment));

    $notificationId = $otherDoctor->notifications()->first()->id;

    $response = $this->actingAs($this->doctor)->deleteJson("/api/notifications/{$notificationId}");

    $response->assertNotFound();
});
```

**Step 4: Run tests**

Run: `php artisan test app/Modules/Notification/Tests/Feature/ --compact`
Expected: All tests pass.

**Step 5: Commit**

```bash
git add app/Modules/Notification/Tests/ && git commit -m "test(notification): add feature tests for notification CRUD"
```

---

### Task 14: Write Feature Tests for Notification Preferences

**Files:**
- Create: `app/Modules/Notification/Tests/Feature/NotificationPreferencesTest.php`

**Step 1: Create the test file**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Notification\Models\PreferenciaNotificacao;
use App\Modules\Notification\NotificationTypeRegistry;

beforeEach(function (): void {
    $this->doctor = User::factory()->doctor()->create();

    NotificationTypeRegistry::flush();
    NotificationTypeRegistry::register(
        slug: 'new_public_appointment_requested',
        label: 'Nova solicitação de agendamento',
        channels: ['database', 'mail', 'broadcast'],
    );
});

it('lists notification preferences with defaults', function (): void {
    $response = $this->actingAs($this->doctor)->getJson('/api/notifications/preferences');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'new_public_appointment_requested')
        ->assertJsonPath('data.0.channels.database.enabled', true)
        ->assertJsonPath('data.0.channels.database.locked', true)
        ->assertJsonPath('data.0.channels.mail.enabled', true)
        ->assertJsonPath('data.0.channels.mail.locked', false);
});

it('updates notification preferences', function (): void {
    $response = $this->actingAs($this->doctor)->putJson('/api/notifications/preferences', [
        'preferences' => [
            ['type' => 'new_public_appointment_requested', 'channel' => 'mail', 'enabled' => false],
        ],
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('preferencias_notificacao', [
        'user_id' => $this->doctor->id,
        'tipo_notificacao' => 'new_public_appointment_requested',
        'canal' => 'mail',
        'ativo' => false,
    ]);
});

it('ignores database channel updates', function (): void {
    $response = $this->actingAs($this->doctor)->putJson('/api/notifications/preferences', [
        'preferences' => [
            ['type' => 'new_public_appointment_requested', 'channel' => 'database', 'enabled' => false],
        ],
    ]);

    // The request validation rejects 'database' as a channel option
    $response->assertUnprocessable();
});

it('reflects updated preferences in listing', function (): void {
    PreferenciaNotificacao::query()->create([
        'user_id' => $this->doctor->id,
        'tipo_notificacao' => 'new_public_appointment_requested',
        'canal' => 'mail',
        'ativo' => false,
    ]);

    $response = $this->actingAs($this->doctor)->getJson('/api/notifications/preferences');

    $response->assertOk()
        ->assertJsonPath('data.0.channels.mail.enabled', false);
});
```

**Step 2: Run tests**

Run: `php artisan test app/Modules/Notification/Tests/Feature/ --compact`
Expected: All tests pass.

**Step 3: Commit**

```bash
git add app/Modules/Notification/Tests/Feature/NotificationPreferencesTest.php && git commit -m "test(notification): add feature tests for notification preferences"
```

---

### Task 15: Run Full Test Suite and Fix Formatting

**Step 1: Run pint**

Run: `vendor/bin/pint --dirty`

**Step 2: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass.

**Step 3: Commit if any formatting changes**

```bash
git add -A && git commit -m "style(notification): apply pint formatting"
```

---

### Task 16: Generate Scribe Documentation

**Step 1: Generate API docs**

Run: `php artisan scribe:generate`
Expected: Documentation generated successfully with the new Notification endpoints.

**Step 2: Commit**

```bash
git add -A && git commit -m "docs(notification): regenerate API documentation with notification endpoints"
```
