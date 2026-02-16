# Schedule Settings (Working Hours) — Design

> Date: 2026-02-16
> Status: Approved
> Module: Appointment (extension)
> Depends on: `2026-02-16-appointment-module-design.md`

## Overview

Allows doctors to define their working schedule — which days of the week they work and during which time blocks. This configuration:

- Validates appointment creation (both internal and public)
- Powers the public availability endpoint to show **available** slots (not just occupied ones)
- Supports multiple time blocks per day (e.g., morning + afternoon)
- Uses 30-minute slot granularity (matching the frontend CalendarGrid)

## Decisions Log

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Format | Per day of week with time ranges | Maximum flexibility — each day can have different hours |
| Slot duration | 30 minutes (fixed) | Matches existing frontend CalendarGrid; keeps it simple |
| Scope | Validates both internal and public | Consistent rules — no one can book outside working hours |
| No config behavior | No restriction | Backwards compatible; doctors who haven't configured yet can still use the system |
| API style | Full replace (PUT) | Simpler than individual CRUD; frontend sends the entire config at once |
| Multiple blocks per day | Yes | Doctor can work 08:00-12:00 + 14:00-18:00 on the same day |

---

## 1. Database Schema

### Table `horarios_atendimento`

```
horarios_atendimento
├── id              bigint PK auto-increment
├── user_id         bigint FK → users (the doctor)
├── dia_semana      smallint NOT NULL (0=Sunday ... 6=Saturday)
├── hora_inicio     time NOT NULL
├── hora_fim        time NOT NULL
├── created_at      timestamp
└── updated_at      timestamp
```

**Constraints:**
- UNIQUE `(user_id, dia_semana, hora_inicio)` — prevents duplicate blocks
- `hora_fim > hora_inicio` — enforced at application level

**Indexes:**
- `horarios_atendimento_user_id_dia_semana_index` — fast lookup by doctor + day

### Example Data

Doctor works Tuesday afternoon and Friday morning:

| user_id | dia_semana | hora_inicio | hora_fim |
|---------|------------|-------------|----------|
| 1 | 2 (Tuesday) | 14:00 | 18:00 |
| 1 | 5 (Friday) | 08:00 | 12:00 |

Doctor works Monday to Friday with lunch break:

| user_id | dia_semana | hora_inicio | hora_fim |
|---------|------------|-------------|----------|
| 1 | 1 (Monday) | 08:00 | 12:00 |
| 1 | 1 (Monday) | 14:00 | 18:00 |
| 1 | 2 (Tuesday) | 08:00 | 12:00 |
| 1 | 2 (Tuesday) | 14:00 | 18:00 |
| ... | ... | ... | ... |

---

## 2. Enum

### DayOfWeek

```php
enum DayOfWeek: int
{
    case Sunday    = 0;
    case Monday    = 1;
    case Tuesday   = 2;
    case Wednesday = 3;
    case Thursday  = 4;
    case Friday    = 5;
    case Saturday  = 6;
}
```

Each case has a `label(): string` method returning the Portuguese name (`Domingo`, `Segunda-feira`, etc.).

---

## 3. Model

### HorarioAtendimento

```
App\Modules\Appointment\Models\HorarioAtendimento
```

- Table: `horarios_atendimento`
- Belongs to: `User` (doctor)
- Fillable: `user_id`, `dia_semana`, `hora_inicio`, `hora_fim`
- Casts: `dia_semana` → `DayOfWeek`

---

## 4. API Endpoints

All under `auth:sanctum` middleware.

| Method | URI | Controller@action | Description |
|--------|-----|-------------------|-------------|
| GET | `/schedule-settings` | ScheduleSettingsController@index | List working hours for auth user (doctor) or delegated doctor |
| PUT | `/schedule-settings` | ScheduleSettingsController@update | Replace entire working hours config |

### GET `/schedule-settings`

**Query params:**
- `doctor_id` (optional) — for secretaries to view a specific doctor's config

**Response:**

```json
{
  "data": {
    "slot_duration_minutes": 30,
    "blocks": [
      {
        "id": 1,
        "day_of_week": 2,
        "day_label": "Terça-feira",
        "start_time": "14:00",
        "end_time": "18:00"
      },
      {
        "id": 2,
        "day_of_week": 5,
        "day_label": "Sexta-feira",
        "start_time": "08:00",
        "end_time": "12:00"
      }
    ]
  }
}
```

### PUT `/schedule-settings`

Replaces all blocks for the doctor. Deletes existing blocks and creates the new ones in a transaction.

**Request body:**

```json
{
  "doctor_id": 1,
  "blocks": [
    { "day_of_week": 2, "start_time": "14:00", "end_time": "18:00" },
    { "day_of_week": 5, "start_time": "08:00", "end_time": "12:00" }
  ]
}
```

- `doctor_id` optional (required for secretaries, ignored for doctors — uses auth user)
- `blocks` can be an empty array (removes all working hours, effectively "no restrictions")
- Each block: `day_of_week` 0-6, `start_time` HH:MM, `end_time` HH:MM, `end_time > start_time`

**Response:** Same as GET (returns the new configuration).

**Validation rules:**
- `blocks` — required, array
- `blocks.*.day_of_week` — required, integer, between 0 and 6
- `blocks.*.start_time` — required, regex `HH:MM`, valid time
- `blocks.*.end_time` — required, regex `HH:MM`, valid time, after `start_time`
- No overlapping blocks on the same day (application-level validation)

**Validation messages (Portuguese):**
- `'blocks.*.day_of_week.required' => 'O dia da semana é obrigatório.'`
- `'blocks.*.start_time.required' => 'O horário de início é obrigatório.'`
- `'blocks.*.end_time.required' => 'O horário de fim é obrigatório.'`
- `'blocks.*.end_time.after' => 'O horário de fim deve ser posterior ao horário de início.'`
- Overlap: `'Existem blocos de horário sobrepostos no mesmo dia.'`

---

## 5. Authorization

- **GET:** Doctor sees own config; secretary sees delegated doctor's config (same delegation logic as appointments)
- **PUT:** Doctor updates own config; secretary updates delegated doctor's config (with `doctor_id` in body)

---

## 6. Impact on Appointment Module

### 6.1 Appointment Creation Validation

In `AppointmentService`, add method:

```php
public function checkWorkingHours(int $doctorId, string $date, string $time): void
```

Logic:
1. Get the day of week from `$date` (0-6)
2. Query `horarios_atendimento` for `user_id = $doctorId` and `dia_semana = dayOfWeek`
3. If **no blocks exist for this doctor at all** → skip validation (no restrictions configured)
4. If blocks exist but **none cover the requested time** → throw `ValidationException` with message: `"Este horário está fora da janela de atendimento do médico."`
5. A time is "covered" if there exists a block where `hora_inicio <= time < hora_fim`

Called from:
- `CreateAppointmentAction::execute()` — before creating
- `UpdateAppointmentAction::execute()` — when date/time changes
- `UpdateAppointmentStatusAction::execute()` — when moving from `Requested` to a blocking status
- `BookPublicAppointmentAction::execute()` — before creating public booking

### 6.2 Public Availability Endpoint (Enhanced)

The `GET /public/schedule/{slug}/availability` endpoint changes behavior:

**Before (design doc v1):** Returns only occupied slots.

**After:** Returns the full picture:

```json
{
  "data": {
    "slot_duration_minutes": 30,
    "schedule": [
      {
        "date": "2026-02-17",
        "day_of_week": 2,
        "day_label": "Terça-feira",
        "slots": [
          { "time": "14:00", "available": true },
          { "time": "14:30", "available": true },
          { "time": "15:00", "available": false },
          { "time": "15:30", "available": true },
          { "time": "16:00", "available": true },
          { "time": "16:30", "available": true },
          { "time": "17:00", "available": true },
          { "time": "17:30", "available": true }
        ]
      },
      {
        "date": "2026-02-20",
        "day_of_week": 5,
        "day_label": "Sexta-feira",
        "slots": [
          { "time": "08:00", "available": false },
          { "time": "08:30", "available": true },
          { "time": "09:00", "available": true },
          { "time": "09:30", "available": true },
          { "time": "10:00", "available": true },
          { "time": "10:30", "available": true },
          { "time": "11:00", "available": true },
          { "time": "11:30", "available": true }
        ]
      }
    ]
  }
}
```

Logic:
1. Get doctor's working hours blocks
2. For each day in the requested range, check if the doctor works that day of week
3. If yes, generate all 30-min slots within the blocks
4. Mark each slot as `available: true/false` based on existing blocking appointments
5. Days where the doctor doesn't work are simply omitted from the response

If the doctor has **no schedule configured**, fall back to the original behavior (return only occupied slots without generating available ones).

---

## 7. Module Structure (additions)

```
app/Modules/Appointment/
├── Enums/
│   └── DayOfWeek.php                          ← NEW
├── Models/
│   ├── Consulta.php
│   └── HorarioAtendimento.php                 ← NEW
├── Database/
│   ├── Factories/
│   │   ├── AppointmentFactory.php
│   │   └── ScheduleSettingsFactory.php         ← NEW
│   └── Migrations/
│       ├── xxxx_create_consultas_table.php
│       ├── xxxx_add_slug_to_users_table.php
│       └── xxxx_create_horarios_atendimento_table.php  ← NEW
├── Http/
│   ├── Controllers/
│   │   ├── AppointmentController.php
│   │   ├── PublicScheduleController.php        (modified)
│   │   └── ScheduleSettingsController.php      ← NEW
│   ├── Requests/
│   │   └── UpdateScheduleSettingsRequest.php   ← NEW
│   └── Resources/
│       ├── AppointmentResource.php
│       ├── AvailabilityResource.php            (modified)
│       └── ScheduleSettingsResource.php        ← NEW
├── Services/
│   └── AppointmentService.php                  (modified — add checkWorkingHours)
├── Tests/Feature/
│   ├── ...existing tests...
│   ├── ScheduleSettingsTest.php                ← NEW
│   └── WorkingHoursValidationTest.php          ← NEW
└── routes.php                                  (modified — add schedule-settings routes)
```

---

## 8. Test Scenarios

### ScheduleSettingsTest.php
- it('returns empty blocks when no schedule configured')
- it('returns schedule settings for the authenticated doctor')
- it('allows secretary to view delegated doctor schedule')
- it('replaces all schedule blocks on PUT')
- it('clears all blocks when empty array is sent')
- it('rejects overlapping blocks on the same day')
- it('rejects end_time before start_time')
- it('rejects invalid day_of_week')

### WorkingHoursValidationTest.php
- it('allows appointment within working hours')
- it('rejects appointment outside working hours')
- it('allows appointment when no schedule is configured')
- it('rejects public booking outside working hours')
- it('validates working hours on appointment update when time changes')
