# Schedule Settings (Working Hours) Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Allow doctors to define their working schedule (days/times) so appointment creation is validated against working hours and the public availability endpoint returns available slots.

**Architecture:** New `DayOfWeek` enum, `HorarioAtendimento` model, `ScheduleSettingsController` with GET/PUT endpoints inside the existing Appointment module. A `checkWorkingHours` method on `AppointmentService` validates appointments. The public availability endpoint is enhanced to generate slot grids from working hours.

**Tech Stack:** Laravel 12, PostgreSQL, Pest 4, PHP 8.5

**Design doc:** `docs/plans/2026-02-16-schedule-settings-design.md`

---

## Phase 1: Foundation

### Task 1: Create DayOfWeek Enum

**Files:**
- Create: `app/Modules/Appointment/Enums/DayOfWeek.php`

**Step 1: Create the enum**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Enums;

enum DayOfWeek: int
{
    case Sunday = 0;
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    /**
     * Return the Portuguese label for the day.
     */
    public function label(): string
    {
        return match ($this) {
            self::Sunday => 'Domingo',
            self::Monday => 'Segunda-feira',
            self::Tuesday => 'Terça-feira',
            self::Wednesday => 'Quarta-feira',
            self::Thursday => 'Quinta-feira',
            self::Friday => 'Sexta-feira',
            self::Saturday => 'Sábado',
        };
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/Appointment/Enums/DayOfWeek.php
git commit -m "feat(schedule-settings): add DayOfWeek enum"
```

---

### Task 2: Create Migration

**Files:**
- Create: `app/Modules/Appointment/Database/Migrations/2026_02_16_300002_create_horarios_atendimento_table.php`

**Step 1: Create migration**

```bash
php artisan make:migration create_horarios_atendimento_table --path=app/Modules/Appointment/Database/Migrations --no-interaction
```

**Step 2: Write the migration**

Edit the created migration file to contain:

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
        Schema::create('horarios_atendimento', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->smallInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->timestamps();

            $table->unique(['user_id', 'dia_semana', 'hora_inicio']);
            $table->index(['user_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_atendimento');
    }
};
```

**Step 3: Run migration**

```bash
php artisan migrate --no-interaction
```
Expected: Migration runs successfully.

**Step 4: Commit**

```bash
git add app/Modules/Appointment/Database/Migrations/*horarios_atendimento*
git commit -m "feat(schedule-settings): add horarios_atendimento migration"
```

---

### Task 3: Create HorarioAtendimento Model

**Files:**
- Create: `app/Modules/Appointment/Models/HorarioAtendimento.php`

**Step 1: Create the model**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Models;

use App\Models\User;
use App\Modules\Appointment\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Schedule settings block for a doctor.
 *
 * @property int $id
 * @property int $user_id
 * @property DayOfWeek $dia_semana
 * @property string $hora_inicio
 * @property string $hora_fim
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 */
class HorarioAtendimento extends Model
{
    use HasFactory;

    protected $table = 'horarios_atendimento';

    protected $fillable = [
        'user_id',
        'dia_semana',
        'hora_inicio',
        'hora_fim',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dia_semana' => DayOfWeek::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): \App\Modules\Appointment\Database\Factories\ScheduleSettingsFactory
    {
        return \App\Modules\Appointment\Database\Factories\ScheduleSettingsFactory::new();
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/Appointment/Models/HorarioAtendimento.php
git commit -m "feat(schedule-settings): add HorarioAtendimento model"
```

---

### Task 4: Create ScheduleSettingsFactory

**Files:**
- Create: `app/Modules/Appointment/Database/Factories/ScheduleSettingsFactory.php`

**Step 1: Create the factory**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Database\Factories;

use App\Models\User;
use App\Modules\Appointment\Enums\DayOfWeek;
use App\Modules\Appointment\Models\HorarioAtendimento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HorarioAtendimento>
 */
final class ScheduleSettingsFactory extends Factory
{
    protected $model = HorarioAtendimento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->doctor(),
            'dia_semana' => fake()->randomElement(DayOfWeek::cases()),
            'hora_inicio' => '08:00',
            'hora_fim' => '12:00',
        ];
    }

    public function forDoctor(User $doctor): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $doctor->id,
        ]);
    }

    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'hora_inicio' => '14:00',
            'hora_fim' => '18:00',
        ]);
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/Appointment/Database/Factories/ScheduleSettingsFactory.php
git commit -m "feat(schedule-settings): add ScheduleSettingsFactory"
```

---

## Phase 2: HTTP Layer — Schedule Settings CRUD

### Task 5: Create UpdateScheduleSettingsRequest

**Files:**
- Create: `app/Modules/Appointment/Http/Requests/UpdateScheduleSettingsRequest.php`

**Step 1: Create the form request**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateScheduleSettingsRequest extends FormRequest
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
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
            'blocks' => ['required', 'array'],
            'blocks.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'blocks.*.start_time' => ['required', 'date_format:H:i'],
            'blocks.*.end_time' => ['required', 'date_format:H:i', 'after:blocks.*.start_time'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'blocks.required' => 'Os blocos de horário são obrigatórios.',
            'blocks.*.day_of_week.required' => 'O dia da semana é obrigatório.',
            'blocks.*.day_of_week.between' => 'O dia da semana deve estar entre 0 (domingo) e 6 (sábado).',
            'blocks.*.start_time.required' => 'O horário de início é obrigatório.',
            'blocks.*.start_time.date_format' => 'O horário de início deve estar no formato HH:MM.',
            'blocks.*.end_time.required' => 'O horário de fim é obrigatório.',
            'blocks.*.end_time.date_format' => 'O horário de fim deve estar no formato HH:MM.',
            'blocks.*.end_time.after' => 'O horário de fim deve ser posterior ao horário de início.',
        ];
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/Appointment/Http/Requests/UpdateScheduleSettingsRequest.php
git commit -m "feat(schedule-settings): add UpdateScheduleSettingsRequest"
```

---

### Task 6: Create ScheduleSettingsResource

**Files:**
- Create: `app/Modules/Appointment/Http/Resources/ScheduleSettingsResource.php`

**Step 1: Create the resource**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Appointment\Models\HorarioAtendimento
 */
final class ScheduleSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->dia_semana->value,
            'day_label' => $this->dia_semana->label(),
            'start_time' => $this->hora_inicio,
            'end_time' => $this->hora_fim,
        ];
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/Appointment/Http/Resources/ScheduleSettingsResource.php
git commit -m "feat(schedule-settings): add ScheduleSettingsResource"
```

---

### Task 7: Create ScheduleSettingsController

**Files:**
- Create: `app/Modules/Appointment/Http/Controllers/ScheduleSettingsController.php`
- Modify: `app/Modules/Appointment/Services/AppointmentService.php` (add `getScheduleSettings` and `replaceScheduleSettings`)

**Step 1: Add schedule settings methods to AppointmentService**

Add these two methods to `AppointmentService.php` (after the existing `resolveSingleDoctorId` method):

```php
/**
 * Get all schedule settings blocks for a doctor.
 *
 * @return \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Appointment\Models\HorarioAtendimento>
 */
public function getScheduleSettings(int $doctorId): \Illuminate\Database\Eloquent\Collection
{
    return \App\Modules\Appointment\Models\HorarioAtendimento::query()
        ->where('user_id', $doctorId)
        ->orderBy('dia_semana')
        ->orderBy('hora_inicio')
        ->get();
}

/**
 * Replace all schedule settings blocks for a doctor.
 *
 * @param  array<int, array{day_of_week: int, start_time: string, end_time: string}>  $blocks
 * @return \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Appointment\Models\HorarioAtendimento>
 *
 * @throws \Illuminate\Validation\ValidationException
 */
public function replaceScheduleSettings(int $doctorId, array $blocks): \Illuminate\Database\Eloquent\Collection
{
    $this->validateNoOverlappingBlocks($blocks);

    \Illuminate\Support\Facades\DB::transaction(function () use ($doctorId, $blocks): void {
        \App\Modules\Appointment\Models\HorarioAtendimento::query()
            ->where('user_id', $doctorId)
            ->delete();

        foreach ($blocks as $block) {
            \App\Modules\Appointment\Models\HorarioAtendimento::query()->create([
                'user_id' => $doctorId,
                'dia_semana' => $block['day_of_week'],
                'hora_inicio' => $block['start_time'],
                'hora_fim' => $block['end_time'],
            ]);
        }
    });

    return $this->getScheduleSettings($doctorId);
}

/**
 * Validate that no blocks overlap on the same day.
 *
 * @param  array<int, array{day_of_week: int, start_time: string, end_time: string}>  $blocks
 *
 * @throws \Illuminate\Validation\ValidationException
 */
private function validateNoOverlappingBlocks(array $blocks): void
{
    $groupedByDay = [];
    foreach ($blocks as $block) {
        $groupedByDay[$block['day_of_week']][] = $block;
    }

    foreach ($groupedByDay as $dayBlocks) {
        $count = count($dayBlocks);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                if ($dayBlocks[$i]['start_time'] < $dayBlocks[$j]['end_time']
                    && $dayBlocks[$j]['start_time'] < $dayBlocks[$i]['end_time']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'blocks' => 'Existem blocos de horário sobrepostos no mesmo dia.',
                    ]);
                }
            }
        }
    }
}
```

**Step 2: Create the controller**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Controllers;

use App\Modules\Appointment\Http\Requests\UpdateScheduleSettingsRequest;
use App\Modules\Appointment\Http\Resources\ScheduleSettingsResource;
use App\Modules\Appointment\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manage doctor schedule settings (working hours).
 *
 * @group Schedule Settings
 * @authenticated
 */
final class ScheduleSettingsController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    /**
     * List working hours for the authenticated doctor.
     *
     * @queryParam doctor_id int Optional doctor ID (for secretaries). Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $requestedDoctorId = $request->query('doctor_id') !== null
            ? (int) $request->query('doctor_id')
            : null;

        $doctorId = $this->appointmentService->resolveSingleDoctorId($user, $requestedDoctorId);

        $blocks = $this->appointmentService->getScheduleSettings($doctorId);

        return response()->json([
            'data' => [
                'slot_duration_minutes' => 30,
                'blocks' => ScheduleSettingsResource::collection($blocks),
            ],
        ]);
    }

    /**
     * Replace all working hours for a doctor.
     */
    public function update(UpdateScheduleSettingsRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $requestedDoctorId = $request->validated('doctor_id') !== null
            ? (int) $request->validated('doctor_id')
            : null;

        $doctorId = $this->appointmentService->resolveSingleDoctorId($user, $requestedDoctorId);

        $blocks = $this->appointmentService->replaceScheduleSettings(
            $doctorId,
            $request->validated('blocks'),
        );

        return response()->json([
            'data' => [
                'slot_duration_minutes' => 30,
                'blocks' => ScheduleSettingsResource::collection($blocks),
            ],
        ]);
    }
}
```

**Step 3: Add routes**

Add to `app/Modules/Appointment/routes.php`, inside the `auth:sanctum` group:

```php
use App\Modules\Appointment\Http\Controllers\ScheduleSettingsController;

// Inside the existing auth:sanctum group, after the appointment routes:
Route::get('/schedule-settings', [ScheduleSettingsController::class, 'index']);
Route::put('/schedule-settings', [ScheduleSettingsController::class, 'update']);
```

**Step 4: Run existing tests to ensure nothing breaks**

```bash
php artisan test app/Modules/Appointment/Tests/ --compact
```
Expected: All existing tests pass.

**Step 5: Commit**

```bash
git add app/Modules/Appointment/Services/AppointmentService.php \
       app/Modules/Appointment/Http/Controllers/ScheduleSettingsController.php \
       app/Modules/Appointment/routes.php
git commit -m "feat(schedule-settings): add controller, service methods, and routes"
```

---

## Phase 3: Schedule Settings Tests

### Task 8: Write ScheduleSettingsTest

**Files:**
- Create: `app/Modules/Appointment/Tests/Feature/ScheduleSettingsTest.php`

**Step 1: Create the test file**

```bash
php artisan make:test --pest --no-interaction app/Modules/Appointment/Tests/Feature/ScheduleSettingsTest
```

**Step 2: Write all schedule settings tests**

Replace the file contents with:

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\HorarioAtendimento;
use App\Modules\Delegation\Models\Delegacao;

it('returns empty blocks when no schedule configured', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->getJson('/api/schedule-settings');

    $response->assertOk()
        ->assertJsonPath('data.slot_duration_minutes', 30)
        ->assertJsonPath('data.blocks', []);
});

it('returns schedule settings for the authenticated doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '18:00',
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/schedule-settings');

    $response->assertOk()
        ->assertJsonCount(1, 'data.blocks')
        ->assertJsonPath('data.blocks.0.day_of_week', 2)
        ->assertJsonPath('data.blocks.0.day_label', 'Terça-feira')
        ->assertJsonPath('data.blocks.0.start_time', '14:00')
        ->assertJsonPath('data.blocks.0.end_time', '18:00');
});

it('allows secretary to view delegated doctor schedule', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fim' => '12:00',
    ]);

    $response = $this->actingAs($secretary)->getJson('/api/schedule-settings?doctor_id='.$doctor->id);

    $response->assertOk()
        ->assertJsonCount(1, 'data.blocks');
});

it('replaces all schedule blocks on PUT', function (): void {
    $doctor = User::factory()->doctor()->create();

    // Create an existing block that should be replaced
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fim' => '12:00',
    ]);

    $response = $this->actingAs($doctor)->putJson('/api/schedule-settings', [
        'blocks' => [
            ['day_of_week' => 2, 'start_time' => '14:00', 'end_time' => '18:00'],
            ['day_of_week' => 5, 'start_time' => '08:00', 'end_time' => '12:00'],
        ],
    ]);

    $response->assertOk()
        ->assertJsonCount(2, 'data.blocks')
        ->assertJsonPath('data.blocks.0.day_of_week', 2)
        ->assertJsonPath('data.blocks.1.day_of_week', 5);

    // Old block should be gone
    $this->assertDatabaseMissing('horarios_atendimento', [
        'user_id' => $doctor->id,
        'dia_semana' => 1,
    ]);
});

it('clears all blocks when empty array is sent', function (): void {
    $doctor = User::factory()->doctor()->create();

    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fim' => '12:00',
    ]);

    $response = $this->actingAs($doctor)->putJson('/api/schedule-settings', [
        'blocks' => [],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.blocks', []);

    $this->assertDatabaseCount('horarios_atendimento', 0);
});

it('rejects overlapping blocks on the same day', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->putJson('/api/schedule-settings', [
        'blocks' => [
            ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '12:00'],
            ['day_of_week' => 1, 'start_time' => '11:00', 'end_time' => '15:00'],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('blocks');
});

it('rejects end_time before start_time', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->putJson('/api/schedule-settings', [
        'blocks' => [
            ['day_of_week' => 1, 'start_time' => '14:00', 'end_time' => '10:00'],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('blocks.0.end_time');
});

it('rejects invalid day_of_week', function (): void {
    $doctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->putJson('/api/schedule-settings', [
        'blocks' => [
            ['day_of_week' => 7, 'start_time' => '08:00', 'end_time' => '12:00'],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('blocks.0.day_of_week');
});

it('rejects unauthenticated access', function (): void {
    $this->getJson('/api/schedule-settings')->assertUnauthorized();
    $this->putJson('/api/schedule-settings', ['blocks' => []])->assertUnauthorized();
});
```

**Step 3: Run the tests**

```bash
php artisan test app/Modules/Appointment/Tests/Feature/ScheduleSettingsTest.php --compact
```
Expected: All tests pass.

**Step 4: Commit**

```bash
git add app/Modules/Appointment/Tests/Feature/ScheduleSettingsTest.php
git commit -m "test(schedule-settings): add schedule settings feature tests"
```

---

## Phase 4: Working Hours Validation on Appointments

### Task 9: Add checkWorkingHours to AppointmentService

**Files:**
- Modify: `app/Modules/Appointment/Services/AppointmentService.php`

**Step 1: Add the checkWorkingHours method**

Add this method to `AppointmentService`, after `checkTimeConflict`:

```php
/**
 * Check if the requested time is within the doctor's working hours.
 *
 * If the doctor has no schedule configured, validation is skipped (no restrictions).
 *
 * @throws ValidationException
 */
public function checkWorkingHours(int $doctorId, string $date, string $time): void
{
    $allBlocks = \App\Modules\Appointment\Models\HorarioAtendimento::query()
        ->where('user_id', $doctorId)
        ->get();

    // No schedule configured — skip validation
    if ($allBlocks->isEmpty()) {
        return;
    }

    $dayOfWeek = (int) \Illuminate\Support\Carbon::parse($date)->dayOfWeek;

    $dayBlocks = $allBlocks->filter(
        fn (\App\Modules\Appointment\Models\HorarioAtendimento $block) => $block->dia_semana->value === $dayOfWeek
    );

    $covered = $dayBlocks->contains(
        fn (\App\Modules\Appointment\Models\HorarioAtendimento $block) => $time >= $block->hora_inicio && $time < $block->hora_fim
    );

    if (! $covered) {
        throw ValidationException::withMessages([
            'horario' => 'Este horário está fora da janela de atendimento do médico.',
        ]);
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/Appointment/Services/AppointmentService.php
git commit -m "feat(schedule-settings): add checkWorkingHours validation"
```

---

### Task 10: Integrate checkWorkingHours into Actions

**Files:**
- Modify: `app/Modules/Appointment/Actions/CreateAppointmentAction.php`
- Modify: `app/Modules/Appointment/Actions/UpdateAppointmentAction.php`
- Modify: `app/Modules/Appointment/Actions/UpdateAppointmentStatusAction.php`
- Modify: `app/Modules/Appointment/Actions/BookPublicAppointmentAction.php`

**Step 1: Update CreateAppointmentAction**

Add the working hours check right before the time conflict check in `execute()`:

```php
public function execute(int $doctorId, CreateAppointmentDTO $dto): Consulta
{
    $this->appointmentService->checkWorkingHours($doctorId, $dto->date, $dto->time);
    $this->appointmentService->checkTimeConflict($doctorId, $dto->date, $dto->time);

    return Consulta::query()->create([
        'user_id' => $doctorId,
        'paciente_id' => $dto->patientId,
        'data' => $dto->date,
        'horario' => $dto->time,
        'tipo' => $dto->type,
        'status' => AppointmentStatus::Pending,
        'observacoes' => $dto->notes,
        'origem' => AppointmentOrigin::Internal,
    ]);
}
```

**Step 2: Update UpdateAppointmentAction**

Add the working hours check inside the date/time change condition:

```php
public function execute(Consulta $appointment, UpdateAppointmentDTO $dto): Consulta
{
    $date = $dto->date ?? $appointment->data;
    $time = $dto->time ?? $appointment->horario;

    if ($dto->date !== null || $dto->time !== null) {
        $this->appointmentService->checkWorkingHours(
            $appointment->user_id,
            $date,
            $time,
        );
        $this->appointmentService->checkTimeConflict(
            $appointment->user_id,
            $date,
            $time,
            $appointment->id,
        );
    }

    $appointment->update(array_filter([
        'paciente_id' => $dto->patientId,
        'data' => $dto->date,
        'horario' => $dto->time,
        'tipo' => $dto->type,
        'observacoes' => $dto->notes,
    ], fn ($value) => $value !== null));

    return $appointment->refresh();
}
```

**Step 3: Update UpdateAppointmentStatusAction**

Add the working hours check when transitioning to a blocking status:

```php
public function execute(Consulta $appointment, AppointmentStatus $newStatus): Consulta
{
    if (in_array($newStatus, AppointmentStatus::blockingStatuses(), true)
        && ! in_array($appointment->status, AppointmentStatus::blockingStatuses(), true)) {
        $this->appointmentService->checkWorkingHours(
            $appointment->user_id,
            $appointment->data,
            $appointment->horario,
        );
        $this->appointmentService->checkTimeConflict(
            $appointment->user_id,
            $appointment->data,
            $appointment->horario,
            $appointment->id,
        );
    }

    $appointment->update(['status' => $newStatus]);

    return $appointment->refresh();
}
```

**Step 4: Update BookPublicAppointmentAction**

Inject `AppointmentService` and add the check:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Actions;

use App\Modules\Appointment\DTOs\BookPublicAppointmentDTO;
use App\Modules\Appointment\Enums\AppointmentOrigin;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Enums\AppointmentType;
use App\Modules\Appointment\Events\PublicAppointmentRequested;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Services\AppointmentService;

final class BookPublicAppointmentAction
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    public function execute(int $doctorId, BookPublicAppointmentDTO $dto): Consulta
    {
        $this->appointmentService->checkWorkingHours($doctorId, $dto->date, $dto->time);

        $appointment = Consulta::query()->create([
            'user_id' => $doctorId,
            'paciente_id' => null,
            'data' => $dto->date,
            'horario' => $dto->time,
            'tipo' => AppointmentType::FirstConsultation,
            'status' => AppointmentStatus::Requested,
            'observacoes' => $dto->notes,
            'nome_solicitante' => $dto->name,
            'telefone_solicitante' => $dto->phone,
            'email_solicitante' => $dto->email,
            'origem' => AppointmentOrigin::Public,
        ]);

        event(new PublicAppointmentRequested($appointment));

        return $appointment;
    }
}
```

**Step 5: Run all existing tests to ensure nothing breaks**

```bash
php artisan test app/Modules/Appointment/Tests/ --compact
```
Expected: All tests pass. (Existing tests create no schedule settings, so `checkWorkingHours` skips validation — backwards compatible.)

**Step 6: Commit**

```bash
git add app/Modules/Appointment/Actions/CreateAppointmentAction.php \
       app/Modules/Appointment/Actions/UpdateAppointmentAction.php \
       app/Modules/Appointment/Actions/UpdateAppointmentStatusAction.php \
       app/Modules/Appointment/Actions/BookPublicAppointmentAction.php
git commit -m "feat(schedule-settings): integrate working hours validation into actions"
```

---

### Task 11: Write WorkingHoursValidationTest

**Files:**
- Create: `app/Modules/Appointment/Tests/Feature/WorkingHoursValidationTest.php`

**Step 1: Create the test file**

```bash
php artisan make:test --pest --no-interaction app/Modules/Appointment/Tests/Feature/WorkingHoursValidationTest
```

**Step 2: Write the working hours validation tests**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Models\HorarioAtendimento;

it('allows appointment within working hours', function (): void {
    $doctor = User::factory()->doctor()->create();

    // Doctor works Tuesday 14:00-18:00
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '18:00',
    ]);

    // Find the next Tuesday
    $tuesday = now()->next('Tuesday')->format('Y-m-d');

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => $tuesday,
        'time' => '15:00',
        'type' => 'consultation',
    ]);

    $response->assertCreated();
});

it('rejects appointment outside working hours', function (): void {
    $doctor = User::factory()->doctor()->create();

    // Doctor works Tuesday 14:00-18:00
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '18:00',
    ]);

    // Try to book on Tuesday morning (outside working hours)
    $tuesday = now()->next('Tuesday')->format('Y-m-d');

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => $tuesday,
        'time' => '09:00',
        'type' => 'consultation',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('horario');
});

it('rejects appointment on a day the doctor does not work', function (): void {
    $doctor = User::factory()->doctor()->create();

    // Doctor works only Tuesday
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '18:00',
    ]);

    // Try to book on Wednesday
    $wednesday = now()->next('Wednesday')->format('Y-m-d');

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => $wednesday,
        'time' => '15:00',
        'type' => 'consultation',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('horario');
});

it('allows appointment when no schedule is configured', function (): void {
    $doctor = User::factory()->doctor()->create();
    // No schedule settings — no restrictions

    $response = $this->actingAs($doctor)->postJson('/api/appointments', [
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => '10:00',
        'type' => 'consultation',
    ]);

    $response->assertCreated();
});

it('rejects public booking outside working hours', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-horario']);

    // Doctor works Friday 08:00-12:00
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 5,
        'hora_inicio' => '08:00',
        'hora_fim' => '12:00',
    ]);

    // Try to book on Friday afternoon (outside working hours)
    $friday = now()->next('Friday')->format('Y-m-d');

    $response = $this->postJson('/api/public/schedule/dr-horario/book', [
        'nome' => 'Maria da Silva',
        'telefone' => '(11) 99999-0000',
        'email' => 'maria@email.com',
        'data' => $friday,
        'horario' => '15:00',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('horario');
});

it('validates working hours on appointment update when time changes', function (): void {
    $doctor = User::factory()->doctor()->create();

    // Doctor works Tuesday 14:00-18:00
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '18:00',
    ]);

    $tuesday = now()->next('Tuesday')->format('Y-m-d');

    // Create appointment within working hours
    $appointment = Consulta::factory()->forDoctor($doctor)->create([
        'data' => $tuesday,
        'horario' => '15:00',
    ]);

    // Try to update to a time outside working hours
    $response = $this->actingAs($doctor)->putJson("/api/appointments/{$appointment->id}", [
        'time' => '09:00',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('horario');
});
```

**Step 3: Run the tests**

```bash
php artisan test app/Modules/Appointment/Tests/Feature/WorkingHoursValidationTest.php --compact
```
Expected: All tests pass.

**Step 4: Run all appointment tests to ensure nothing is broken**

```bash
php artisan test app/Modules/Appointment/Tests/ --compact
```
Expected: All tests pass.

**Step 5: Commit**

```bash
git add app/Modules/Appointment/Tests/Feature/WorkingHoursValidationTest.php
git commit -m "test(schedule-settings): add working hours validation tests"
```

---

## Phase 5: Enhanced Public Availability

### Task 12: Enhance Public Availability Endpoint

**Files:**
- Modify: `app/Modules/Appointment/Http/Controllers/PublicScheduleController.php`

**Step 1: Update the availability method**

Replace the `availability` method in `PublicScheduleController.php` with:

```php
/**
 * Get availability for a doctor.
 *
 * If the doctor has schedule settings configured, returns available/occupied slots per day.
 * If not configured, falls back to returning only occupied slots.
 *
 * @group Public Schedule
 *
 * @queryParam start_date string required Start date (Y-m-d). Example: 2026-02-16
 * @queryParam end_date string required End date (Y-m-d). Example: 2026-02-28
 */
public function availability(Request $request, string $slug): JsonResponse
{
    $doctor = $this->findDoctorBySlug($slug);

    $startDate = $request->query('start_date', now()->format('Y-m-d'));
    $endDate = $request->query('end_date', now()->addDays(30)->format('Y-m-d'));

    $scheduleBlocks = \App\Modules\Appointment\Models\HorarioAtendimento::query()
        ->where('user_id', $doctor->id)
        ->get();

    // Fallback: no schedule configured — return only occupied slots (original behavior)
    if ($scheduleBlocks->isEmpty()) {
        $occupiedSlots = Consulta::query()
            ->where('user_id', $doctor->id)
            ->whereBetween('data', [$startDate, $endDate])
            ->whereIn('status', array_map(
                fn (AppointmentStatus $s) => $s->value,
                AppointmentStatus::blockingStatuses(),
            ))
            ->orderBy('data')
            ->orderBy('horario')
            ->get();

        return response()->json([
            'data' => AvailabilityResource::collection($occupiedSlots),
        ]);
    }

    // Enhanced: generate full slot grid
    $occupiedSlots = Consulta::query()
        ->where('user_id', $doctor->id)
        ->whereBetween('data', [$startDate, $endDate])
        ->whereIn('status', array_map(
            fn (AppointmentStatus $s) => $s->value,
            AppointmentStatus::blockingStatuses(),
        ))
        ->get()
        ->map(fn (Consulta $c) => $c->data.'|'.$c->horario)
        ->toArray();

    $schedule = [];
    $current = \Illuminate\Support\Carbon::parse($startDate);
    $end = \Illuminate\Support\Carbon::parse($endDate);

    while ($current->lte($end)) {
        $dayOfWeek = $current->dayOfWeek;

        $dayBlocks = $scheduleBlocks->filter(
            fn (\App\Modules\Appointment\Models\HorarioAtendimento $block) => $block->dia_semana->value === $dayOfWeek
        );

        if ($dayBlocks->isNotEmpty()) {
            $slots = [];
            foreach ($dayBlocks as $block) {
                $slotTime = \Illuminate\Support\Carbon::parse($block->hora_inicio);
                $blockEnd = \Illuminate\Support\Carbon::parse($block->hora_fim);

                while ($slotTime->lt($blockEnd)) {
                    $timeStr = $slotTime->format('H:i');
                    $key = $current->format('Y-m-d').'|'.$timeStr;
                    $slots[] = [
                        'time' => $timeStr,
                        'available' => ! in_array($key, $occupiedSlots, true),
                    ];
                    $slotTime->addMinutes(30);
                }
            }

            $dayEnum = \App\Modules\Appointment\Enums\DayOfWeek::from($dayOfWeek);
            $schedule[] = [
                'date' => $current->format('Y-m-d'),
                'day_of_week' => $dayOfWeek,
                'day_label' => $dayEnum->label(),
                'slots' => $slots,
            ];
        }

        $current->addDay();
    }

    return response()->json([
        'data' => [
            'slot_duration_minutes' => 30,
            'schedule' => $schedule,
        ],
    ]);
}
```

Also add the necessary import at the top of the file:

```php
use Illuminate\Http\JsonResponse;
```

And update the return type of `availability` from `AnonymousResourceCollection` to `JsonResponse`.

**Step 2: Run existing public availability tests**

```bash
php artisan test app/Modules/Appointment/Tests/Feature/PublicAvailabilityTest.php --compact
```
Expected: All existing tests pass (they use doctors without schedule settings — fallback behavior).

**Step 3: Commit**

```bash
git add app/Modules/Appointment/Http/Controllers/PublicScheduleController.php
git commit -m "feat(schedule-settings): enhance public availability with slot grid"
```

---

### Task 13: Write Enhanced Public Availability Tests

**Files:**
- Create: `app/Modules/Appointment/Tests/Feature/PublicAvailabilityEnhancedTest.php`

**Step 1: Create the test file**

```bash
php artisan make:test --pest --no-interaction app/Modules/Appointment/Tests/Feature/PublicAvailabilityEnhancedTest
```

**Step 2: Write the tests**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Models\HorarioAtendimento;

it('returns slot grid when doctor has schedule configured', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-grid']);

    // Doctor works Tuesday 14:00-16:00 (4 slots: 14:00, 14:30, 15:00, 15:30)
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '16:00',
    ]);

    $tuesday = now()->next('Tuesday')->format('Y-m-d');

    $response = $this->getJson("/api/public/schedule/dr-grid/availability?start_date={$tuesday}&end_date={$tuesday}");

    $response->assertOk()
        ->assertJsonPath('data.slot_duration_minutes', 30)
        ->assertJsonCount(1, 'data.schedule')
        ->assertJsonPath('data.schedule.0.date', $tuesday)
        ->assertJsonPath('data.schedule.0.day_of_week', 2)
        ->assertJsonPath('data.schedule.0.day_label', 'Terça-feira')
        ->assertJsonCount(4, 'data.schedule.0.slots')
        ->assertJsonPath('data.schedule.0.slots.0.time', '14:00')
        ->assertJsonPath('data.schedule.0.slots.0.available', true);
});

it('marks occupied slots as unavailable in the grid', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-occupied']);

    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '16:00',
    ]);

    $tuesday = now()->next('Tuesday')->format('Y-m-d');

    // Create a blocking appointment at 14:30
    Consulta::factory()->forDoctor($doctor)->confirmed()->create([
        'data' => $tuesday,
        'horario' => '14:30',
    ]);

    $response = $this->getJson("/api/public/schedule/dr-occupied/availability?start_date={$tuesday}&end_date={$tuesday}");

    $response->assertOk()
        ->assertJsonPath('data.schedule.0.slots.0.time', '14:00')
        ->assertJsonPath('data.schedule.0.slots.0.available', true)
        ->assertJsonPath('data.schedule.0.slots.1.time', '14:30')
        ->assertJsonPath('data.schedule.0.slots.1.available', false);
});

it('omits days where the doctor does not work', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-omit']);

    // Doctor works only Tuesday
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 2,
        'hora_inicio' => '14:00',
        'hora_fim' => '16:00',
    ]);

    $tuesday = now()->next('Tuesday')->format('Y-m-d');
    $wednesday = now()->next('Wednesday')->format('Y-m-d');

    $response = $this->getJson("/api/public/schedule/dr-omit/availability?start_date={$tuesday}&end_date={$wednesday}");

    $response->assertOk()
        ->assertJsonCount(1, 'data.schedule')
        ->assertJsonPath('data.schedule.0.date', $tuesday);
});

it('supports multiple blocks per day in the grid', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-multi']);

    // Morning block: 08:00-10:00 (4 slots)
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 1,
        'hora_inicio' => '08:00',
        'hora_fim' => '10:00',
    ]);

    // Afternoon block: 14:00-16:00 (4 slots)
    HorarioAtendimento::factory()->forDoctor($doctor)->create([
        'dia_semana' => 1,
        'hora_inicio' => '14:00',
        'hora_fim' => '16:00',
    ]);

    $monday = now()->next('Monday')->format('Y-m-d');

    $response = $this->getJson("/api/public/schedule/dr-multi/availability?start_date={$monday}&end_date={$monday}");

    $response->assertOk()
        ->assertJsonCount(8, 'data.schedule.0.slots');
});

it('falls back to occupied-only response when no schedule configured', function (): void {
    $doctor = User::factory()->doctor()->create(['slug' => 'dr-fallback']);
    $date = now()->addDay()->format('Y-m-d');

    Consulta::factory()->forDoctor($doctor)->confirmed()->create([
        'data' => $date,
        'horario' => '10:00',
    ]);

    $response = $this->getJson("/api/public/schedule/dr-fallback/availability?start_date={$date}&end_date={$date}");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.date', $date)
        ->assertJsonPath('data.0.time', '10:00');
});
```

**Step 3: Run the tests**

```bash
php artisan test app/Modules/Appointment/Tests/Feature/PublicAvailabilityEnhancedTest.php --compact
```
Expected: All tests pass.

**Step 4: Run the full test suite**

```bash
php artisan test app/Modules/Appointment/Tests/ --compact
```
Expected: All tests pass.

**Step 5: Commit**

```bash
git add app/Modules/Appointment/Tests/Feature/PublicAvailabilityEnhancedTest.php
git commit -m "test(schedule-settings): add enhanced public availability tests"
```

---

## Phase 6: Code Formatting & Final Verification

### Task 14: Run Pint and full test suite

**Step 1: Run Pint formatter**

```bash
vendor/bin/pint --dirty
```

**Step 2: Run full test suite**

```bash
php artisan test --compact
```
Expected: All tests pass.

**Step 3: Commit any formatting fixes**

```bash
git add -A
git commit -m "style: apply pint formatting to schedule settings"
```
(Only if pint made changes.)

---

## Summary of Files

### Created (8 files):
1. `app/Modules/Appointment/Enums/DayOfWeek.php`
2. `app/Modules/Appointment/Database/Migrations/2026_02_16_300002_create_horarios_atendimento_table.php`
3. `app/Modules/Appointment/Models/HorarioAtendimento.php`
4. `app/Modules/Appointment/Database/Factories/ScheduleSettingsFactory.php`
5. `app/Modules/Appointment/Http/Requests/UpdateScheduleSettingsRequest.php`
6. `app/Modules/Appointment/Http/Resources/ScheduleSettingsResource.php`
7. `app/Modules/Appointment/Http/Controllers/ScheduleSettingsController.php`
8. `app/Modules/Appointment/Tests/Feature/ScheduleSettingsTest.php`
9. `app/Modules/Appointment/Tests/Feature/WorkingHoursValidationTest.php`
10. `app/Modules/Appointment/Tests/Feature/PublicAvailabilityEnhancedTest.php`

### Modified (5 files):
1. `app/Modules/Appointment/routes.php` — add schedule-settings routes
2. `app/Modules/Appointment/Services/AppointmentService.php` — add `getScheduleSettings`, `replaceScheduleSettings`, `validateNoOverlappingBlocks`, `checkWorkingHours`
3. `app/Modules/Appointment/Actions/CreateAppointmentAction.php` — add working hours check
4. `app/Modules/Appointment/Actions/UpdateAppointmentAction.php` — add working hours check
5. `app/Modules/Appointment/Actions/UpdateAppointmentStatusAction.php` — add working hours check
6. `app/Modules/Appointment/Actions/BookPublicAppointmentAction.php` — inject service, add working hours check
7. `app/Modules/Appointment/Http/Controllers/PublicScheduleController.php` — enhance availability
