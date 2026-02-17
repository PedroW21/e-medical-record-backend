# Notification Module Design

**Date:** 2026-02-16
**Status:** Approved

## Overview

Create a dedicated `Notification` module that centralizes notification reading, management, and channel preferences. Notification classes remain in their domain modules (e.g., `Appointment/Notifications/`), but the `Notification` module handles display, CRUD operations, and user preferences. Broadcasting is handled via Laravel Reverb on private channels.

## Module Structure

```
app/Modules/Notification/
├── Database/
│   ├── Migrations/
│   │   ├── add_soft_deletes_to_notifications_table.php
│   │   └── create_preferencias_notificacao_table.php
│   └── Seeders/
├── DTOs/
│   └── NotificationPreferenceDTO.php
├── Http/
│   ├── Controllers/
│   │   ├── NotificationController.php
│   │   └── NotificationPreferenceController.php
│   ├── Requests/
│   │   ├── ListNotificationRequest.php
│   │   └── UpdateNotificationPreferenceRequest.php
│   └── Resources/
│       ├── NotificationResource.php
│       └── NotificationPreferenceResource.php
├── Models/
│   └── PreferenciaNotificacao.php
├── Providers/
│   └── NotificationServiceProvider.php
├── Services/
│   ├── NotificationService.php
│   └── NotificationPreferenceService.php
├── Traits/
│   └── RespectsChannelPreferences.php
├── Tests/
│   └── Feature/
├── Events/
│   └── NotificationCreated.php
└── routes.php
```

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| `GET` | `/api/notifications` | List notifications (paginated, with filters) |
| `GET` | `/api/notifications/unread-count` | Unread notifications count |
| `PATCH` | `/api/notifications/{id}/read` | Mark as read |
| `PATCH` | `/api/notifications/read-all` | Mark all as read |
| `DELETE` | `/api/notifications/{id}` | Soft delete a notification |
| `GET` | `/api/notifications/preferences` | List user preferences |
| `PUT` | `/api/notifications/preferences` | Update preferences (batch) |

All endpoints require `auth:sanctum` middleware.

### Filters for `GET /api/notifications`

| Query Param | Type | Description |
|-------------|------|-------------|
| `status` | `read` \| `unread` \| `all` | Filter by read status (default: `all`) |
| `type` | string | Filter by notification type slug |
| `from` | date | Period start date |
| `to` | date | Period end date |
| `per_page` | int | Items per page (default: 15, max: 100) |

## Data Model

### Table `notifications` (existing, with modification)

Add soft delete support:

```sql
ALTER TABLE notifications ADD COLUMN deleted_at TIMESTAMP NULL;
```

### Table `preferencias_notificacao` (new)

| Column | Type | Description |
|--------|------|-------------|
| `id` | `bigint` PK | Auto-increment |
| `user_id` | `bigint` FK | Reference to `users.id` |
| `tipo_notificacao` | `varchar` | Type slug (e.g., `new_public_appointment_requested`) |
| `canal` | `varchar` | Channel: `mail`, `database`, `sms`, `whatsapp` |
| `ativo` | `boolean` | Whether channel is enabled (default: `true`) |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

**Unique constraint:** `(user_id, tipo_notificacao, canal)`

The `database` channel is always active (not disableable). Users can disable `mail`, `broadcast`, etc., but will always receive in-app notifications.

## Broadcasting with Reverb

### Private Channel

Each user listens on a private channel: `App.Models.User.{userId}` (Laravel's default for notification broadcasting).

### Flow

1. Notification is created (e.g., `NewPublicAppointmentRequested`)
2. Laravel saves to database (`database` channel) and sends email (`mail` channel)
3. `broadcast` channel sends event via Reverb to user's private channel
4. Frontend (Laravel Echo) receives event and updates badge/list in real-time

### Channel Preferences in `via()` Method

Notification classes use a `RespectsChannelPreferences` trait:

```php
trait RespectsChannelPreferences
{
    protected function resolveChannels(object $notifiable): array
    {
        $channels = ['database']; // always active

        $disabledChannels = $notifiable->preferenciasNotificacao()
            ->where('tipo_notificacao', static::notificationType())
            ->where('ativo', false)
            ->pluck('canal')
            ->toArray();

        foreach (['mail', 'broadcast'] as $channel) {
            if (!in_array($channel, $disabledChannels)) {
                $channels[] = $channel;
            }
        }

        return $channels;
    }
}
```

Each notification class implements a static `notificationType()` method returning its slug.

## Notification Preferences

### `PUT /api/notifications/preferences` (request body)

```json
{
  "preferences": [
    { "type": "new_public_appointment_requested", "channel": "mail", "enabled": false },
    { "type": "new_public_appointment_requested", "channel": "broadcast", "enabled": true }
  ]
}
```

### `GET /api/notifications/preferences` (response)

```json
{
  "data": [
    {
      "type": "new_public_appointment_requested",
      "label": "Nova solicitação de agendamento",
      "channels": {
        "database": { "enabled": true, "locked": true },
        "mail": { "enabled": true, "locked": false },
        "broadcast": { "enabled": true, "locked": false }
      }
    }
  ]
}
```

A notification type registry (config array or class) maps slugs to Portuguese labels. New modules register their types when loaded.

## Design Decisions

- **Notification classes stay in domain modules** — the Notification module only manages reading/preferences
- **Soft delete** for notification removal (reversible, auditable)
- **`database` channel always active** — users cannot disable in-app notifications
- **Extensible for SMS/WhatsApp** — just add new channel values to the preferences table
- **Trait-based channel preferences** — avoids duplicating `via()` logic across notification classes
- **No route model binding** — IDs received as primitives per project convention
