# Appointment Module — Backend Design

> Date: 2026-02-16
> Status: Approved
> Modules: Appointment, Delegation

## Overview

Backend implementation for the schedule/appointments module. The frontend (`src/modules/appointments/`) is fully built with mock data. This design covers:

- CRUD of appointments (authenticated)
- Delegation system (secretary <-> doctor)
- Public schedule (availability view + external booking)
- Notifications for external bookings
- Time-slot conflict validation

## Decisions Log

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Appointment types | PHP Enum (fixed) | Simpler, no extra table. New types require code change. |
| Time conflict | Validate on backend | Prevent two appointments at the same date/time for the same doctor. |
| Scope | Per-doctor (user_id) | Each doctor sees only their own appointments. |
| Public identification | Slug/UUID on user | Avoids exposing sequential IDs. |
| Public booking data | Name + phone + email + notes | Minimal data; type is always `FirstConsultation`. |
| External patient record | Do NOT create patient | Appointment stores requester data in its own columns. Doctor/secretary links to a patient manually later. |
| External booking status | `Requested` | Distinct from `Pending`; requires triage before entering the official schedule. |
| Notification channels | Email + in-app (database) | Notify doctor and delegated secretaries when an external booking arrives. |
| Secretary access | Delegation table | N secretaries can manage N doctors' schedules via `delegacoes` table. |

---

## 1. Database Schema

### 1.1 Table `consultas`

```
consultas
├── id                      bigint PK auto-increment
├── user_id                 bigint FK → users (the doctor who owns this appointment)
├── paciente_id             bigint FK → pacientes (nullable; null for public bookings)
├── data                    date NOT NULL
├── horario                 time NOT NULL
├── tipo                    varchar NOT NULL (AppointmentType enum)
├── status                  varchar NOT NULL (AppointmentStatus enum)
├── observacoes             text NULLABLE
├── nome_solicitante        varchar NULLABLE (public booking: requester name)
├── telefone_solicitante    varchar NULLABLE (public booking: requester phone)
├── email_solicitante       varchar NULLABLE (public booking: requester email)
├── origem                  varchar NOT NULL (AppointmentOrigin enum)
├── created_at              timestamp
├── updated_at              timestamp
└── deleted_at              timestamp NULLABLE (soft delete)
```

**Indexes:**
- `consultas_user_id_data_horario_index` — composite index for conflict queries
- `consultas_user_id_data_index` — for date-range listing
- `consultas_paciente_id_index` — for patient-based lookups

**Conflict constraint (application-level):**
No two appointments with the same `(user_id, data, horario)` where `status NOT IN ('cancelled', 'requested')`. Enforced in `AppointmentService`, not as a DB constraint (because `Requested` appointments don't block slots).

### 1.2 Table `delegacoes`

```
delegacoes
├── id                      bigint PK auto-increment
├── medico_id               bigint FK → users (the doctor)
├── secretaria_id           bigint FK → users (the secretary)
├── created_at              timestamp
└── updated_at              timestamp
```

**Constraints:**
- UNIQUE `(medico_id, secretaria_id)`
- `medico_id` must reference a user with `role = doctor`
- `secretaria_id` must reference a user with `role = secretary`

### 1.3 Table `users` — new column

```
slug    varchar NULLABLE UNIQUE (e.g., "dr-joao-silva" or UUID)
```

Added via migration. Used for public schedule URLs.

### 1.4 Table `notifications`

Laravel's built-in notifications table (`php artisan notifications:table`). Used for in-app notifications.

---

## 2. Enums

### 2.1 AppointmentStatus

```php
enum AppointmentStatus: string
{
    case Requested  = 'requested';   // External booking awaiting triage
    case Pending    = 'pending';     // Accepted, awaiting confirmation
    case Confirmed  = 'confirmed';   // Confirmed by doctor/secretary
    case InProgress = 'in_progress'; // Currently happening
    case Completed  = 'completed';   // Done
    case Cancelled  = 'cancelled';   // Cancelled at any stage
}
```

**Status flow:**
```
Requested → Pending → Confirmed → InProgress → Completed
    ↓           ↓         ↓            ↓
 Cancelled  Cancelled  Cancelled    Cancelled
```

**Slot-blocking statuses:** `Pending`, `Confirmed`, `InProgress`, `Completed`.
**Non-blocking statuses:** `Requested`, `Cancelled`.

### 2.2 AppointmentType

```php
enum AppointmentType: string
{
    case Consultation      = 'consultation';
    case FollowUp          = 'follow_up';
    case Exams             = 'exams';
    case FirstConsultation = 'first_consultation';
}
```

Each case has a `label(): string` method returning the Portuguese name (`Consulta`, `Retorno`, `Exames`, `Primeira Consulta`).

### 2.3 AppointmentOrigin

```php
enum AppointmentOrigin: string
{
    case Internal = 'internal';
    case Public   = 'public';
}
```

---

## 3. API Endpoints

### 3.1 Authenticated — Appointments

All under `auth:sanctum` middleware.

| Method | URI | Controller@action | Description |
|--------|-----|-------------------|-------------|
| GET | `/appointments` | AppointmentController@index | List by date range |
| POST | `/appointments` | AppointmentController@store | Create appointment |
| GET | `/appointments/{id}` | AppointmentController@show | Show single |
| PUT | `/appointments/{id}` | AppointmentController@update | Update appointment |
| PATCH | `/appointments/{id}/status` | AppointmentController@updateStatus | Update status only |
| DELETE | `/appointments/{id}` | AppointmentController@destroy | Soft delete |
| GET | `/appointments/types` | AppointmentController@types | List available types |

**Query params for index:**
- `start_date` (required, date, format Y-m-d)
- `end_date` (required, date, format Y-m-d)
- `doctor_id` (optional, required for secretaries to filter by specific doctor)

**Doctor ID resolution:**
- `role == doctor` → always uses `auth()->id` as the doctor
- `role == secretary` → uses `doctor_id` from request, validated against active delegations. If no `doctor_id` provided, returns appointments for **all** delegated doctors.

### 3.2 Authenticated — Delegations

| Method | URI | Controller@action | Description |
|--------|-----|-------------------|-------------|
| GET | `/delegations` | DelegationController@index | List delegations for auth user |
| POST | `/delegations` | DelegationController@store | Create delegation (doctor only) |
| DELETE | `/delegations/{id}` | DelegationController@destroy | Remove delegation (doctor only) |

### 3.3 Public — Schedule

No authentication required.

| Method | URI | Controller@action | Description |
|--------|-----|-------------------|-------------|
| GET | `/public/schedule/{slug}/availability` | PublicScheduleController@availability | Returns occupied time slots (date + time only) |
| POST | `/public/schedule/{slug}/book` | PublicScheduleController@book | Create external booking request |

**Availability response:**
```json
{
  "data": [
    { "date": "2026-02-17", "time": "09:00" },
    { "date": "2026-02-17", "time": "10:30" }
  ]
}
```

Only returns slots with blocking statuses (`Pending`, `Confirmed`, `InProgress`). No patient data exposed.

**Book request body:**
```json
{
  "nome": "Maria da Silva",
  "telefone": "(11) 99999-0000",
  "email": "maria@email.com",
  "observacoes": "Gostaria de marcar uma primeira consulta",
  "data": "2026-02-20",
  "horario": "14:00"
}
```

**Book creates:**
- `status = Requested`
- `tipo = FirstConsultation`
- `origem = Public`
- `paciente_id = null`
- `nome_solicitante`, `telefone_solicitante`, `email_solicitante` filled from body
- Dispatches `PublicAppointmentRequested` event

---

## 4. Authorization

### 4.1 AppointmentPolicy

```
viewAny    → true (filtering is done in the service layer)
view       → user is owner OR has active delegation for the appointment's user_id
create     → true (doctor creates for self; secretary creates for delegated doctor)
update     → user is owner OR has active delegation
updateStatus → same as update
delete     → user is owner OR has active delegation
```

### 4.2 DelegationPolicy

```
viewAny → true
create  → user.role == doctor
delete  → user.id == delegation.medico_id (only the doctor can remove)
```

### 4.3 Doctor ID resolution helper

A trait or service method used by the AppointmentController:

```php
/**
 * Resolve the doctor ID based on the authenticated user's role.
 *
 * For doctors: returns their own ID.
 * For secretaries: returns the requested doctor_id, validated against active delegations.
 */
public function resolveDoctorId(User $user, ?int $requestedDoctorId): int|array
```

---

## 5. Conflict Validation

Checked on `store` and `update` of appointments.

**Rule:** No two appointments with the same `(user_id, data, horario)` where status is a blocking status (`Pending`, `Confirmed`, `InProgress`, `Completed`).

**Implementation:** `AppointmentService::checkTimeConflict(int $userId, string $date, string $time, ?int $excludeId = null): void`

Throws `ValidationException` with message: `"Ja existe uma consulta agendada para este horario."` if conflict found.

**Note:** `Requested` and `Cancelled` appointments do NOT cause conflicts. This means multiple external requests can exist for the same time slot — the doctor/secretary decides which to accept.

---

## 6. Notifications

### 6.1 Event: `PublicAppointmentRequested`

Dispatched when a public booking is created via `POST /public/schedule/{slug}/book`.

### 6.2 Notification: `NewPublicAppointmentRequested`

**Channels:** `mail`, `database`

**Recipients:**
- The doctor (owner of the slug)
- All secretaries with active delegation for that doctor

**Email content:**
- Subject: `"Nova solicitacao de consulta — {nome_solicitante}"`
- Body: requester name, phone, email, requested date/time, notes, link to the system

**Database (in-app) content:**
- Type: `new_public_appointment`
- Data: `{ appointment_id, nome_solicitante, data, horario }`
- Displayed as badge/bell icon in the frontend

### 6.3 Listener: `SendNewAppointmentNotification`

Listens to `PublicAppointmentRequested`, resolves recipients, sends the notification.

---

## 7. Module Structure

### 7.1 Appointment Module

```
app/Modules/Appointment/
├── Actions/
│   ├── CreateAppointmentAction.php
│   ├── UpdateAppointmentAction.php
│   ├── DeleteAppointmentAction.php
│   ├── UpdateAppointmentStatusAction.php
│   └── BookPublicAppointmentAction.php
├── Database/
│   ├── Factories/
│   │   └── AppointmentFactory.php
│   ├── Migrations/
│   │   ├── xxxx_xx_xx_create_consultas_table.php
│   │   └── xxxx_xx_xx_add_slug_to_users_table.php
│   └── Seeders/
│       └── AppointmentSeeder.php
├── DTOs/
│   ├── CreateAppointmentDTO.php
│   ├── UpdateAppointmentDTO.php
│   └── BookPublicAppointmentDTO.php
├── Enums/
│   ├── AppointmentStatus.php
│   ├── AppointmentType.php
│   └── AppointmentOrigin.php
├── Events/
│   └── PublicAppointmentRequested.php
├── Http/
│   ├── Controllers/
│   │   ├── AppointmentController.php
│   │   └── PublicScheduleController.php
│   ├── Requests/
│   │   ├── ListAppointmentRequest.php
│   │   ├── StoreAppointmentRequest.php
│   │   ├── UpdateAppointmentRequest.php
│   │   ├── UpdateAppointmentStatusRequest.php
│   │   └── BookPublicAppointmentRequest.php
│   └── Resources/
│       ├── AppointmentResource.php
│       └── AvailabilityResource.php
├── Listeners/
│   └── SendNewAppointmentNotification.php
├── Models/
│   └── Consulta.php
├── Notifications/
│   └── NewPublicAppointmentRequested.php
├── Policies/
│   └── AppointmentPolicy.php
├── Providers/
│   └── AppointmentServiceProvider.php
├── Services/
│   └── AppointmentService.php
├── Tests/
│   └── Feature/
│       ├── CreateAppointmentTest.php
│       ├── ListAppointmentTest.php
│       ├── ShowAppointmentTest.php
│       ├── UpdateAppointmentTest.php
│       ├── DeleteAppointmentTest.php
│       ├── UpdateAppointmentStatusTest.php
│       ├── AppointmentTypesTest.php
│       ├── SecretaryAppointmentTest.php
│       ├── TimeConflictTest.php
│       ├── PublicAvailabilityTest.php
│       └── PublicBookingTest.php
└── routes.php
```

### 7.2 Delegation Module

```
app/Modules/Delegation/
├── Database/
│   ├── Factories/
│   │   └── DelegationFactory.php
│   ├── Migrations/
│   │   └── xxxx_xx_xx_create_delegacoes_table.php
│   └── Seeders/
│       └── DelegationSeeder.php
├── Http/
│   ├── Controllers/
│   │   └── DelegationController.php
│   ├── Requests/
│   │   └── StoreDelegationRequest.php
│   └── Resources/
│       └── DelegationResource.php
├── Models/
│   └── Delegacao.php
├── Policies/
│   └── DelegationPolicy.php
├── Providers/
│   └── DelegationServiceProvider.php
├── Services/
│   └── DelegationService.php
├── Tests/
│   └── Feature/
│       ├── CreateDelegationTest.php
│       ├── ListDelegationTest.php
│       └── DeleteDelegationTest.php
└── routes.php
```

---

## 8. Resource Responses

### 8.1 AppointmentResource

```json
{
  "id": 1,
  "doctor_id": 1,
  "patient_id": 5,
  "patient_name": "Maria Silva",
  "date": "2026-02-20",
  "time": "14:00",
  "type": "consultation",
  "type_label": "Consulta",
  "status": "confirmed",
  "status_label": "Confirmado",
  "origin": "internal",
  "notes": "Paciente solicitou acompanhante",
  "requester_name": null,
  "requester_phone": null,
  "requester_email": null,
  "created_at": "2026-02-16T10:30:00Z",
  "updated_at": "2026-02-16T10:30:00Z"
}
```

**Display name logic:** If `patient_id` is set, `patient_name` comes from the patient relationship. Otherwise, falls back to `nome_solicitante`.

### 8.2 DelegationResource

```json
{
  "id": 1,
  "doctor": {
    "id": 1,
    "name": "Dr. Joao Silva",
    "specialty": "Cardiologia"
  },
  "secretary": {
    "id": 2,
    "name": "Ana Souza"
  },
  "created_at": "2026-02-16T10:30:00Z"
}
```

---

## 9. Frontend Service Mapping

How the existing frontend mock service maps to the new backend endpoints:

| Frontend function | Backend endpoint |
|-------------------|------------------|
| `getByDateRange(start, end)` | `GET /appointments?start_date=&end_date=` |
| `getById(id)` | `GET /appointments/{id}` |
| `create(data)` | `POST /appointments` |
| `update(id, data)` | `PUT /appointments/{id}` |
| `remove(id)` | `DELETE /appointments/{id}` |
| `updateStatus(id, status)` | `PATCH /appointments/{id}/status` |
| `searchPatients(query)` | `GET /patients?search=` (already exists) |
| `getAppointmentTypes()` | `GET /appointments/types` |
