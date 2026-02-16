# Appointment Module — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement the full Appointment + Delegation backend modules as described in `docs/plans/2026-02-16-appointment-module-design.md`.

**Architecture:** Modular Laravel structure under `app/Modules/`. Each module has Actions, DTOs, Enums, Http (Controllers, Requests, Resources), Models, Policies, Services, Tests. Follow existing patterns from the Patient module exactly.

**Tech Stack:** Laravel 12, PostgreSQL, Pest 4, Sanctum, PHP 8.5

**Reference files for patterns:**
- Controller: `app/Modules/Patient/Http/Controllers/PatientController.php`
- Service: `app/Modules/Patient/Services/PatientService.php`
- Action: `app/Modules/Patient/Actions/CreatePatientAction.php`
- DTO: `app/Modules/Patient/DTOs/CreatePatientDTO.php`
- Model: `app/Modules/Patient/Models/Paciente.php`
- Policy: `app/Modules/Patient/Policies/PatientPolicy.php`
- Factory: `app/Modules/Patient/Database/Factories/PatientFactory.php`
- Migration: `app/Modules/Patient/Database/Migrations/2026_02_16_200002_create_pacientes_table.php`
- Request: `app/Modules/Patient/Http/Requests/StorePatientRequest.php`
- Resource: `app/Modules/Patient/Http/Resources/PatientResource.php`
- ServiceProvider: `app/Modules/Patient/Providers/PatientServiceProvider.php`
- Test: `app/Modules/Patient/Tests/Feature/CreatePatientTest.php`
- Enum: `app/Modules/Patient/Enums/PatientStatus.php`
- Routes: `app/Modules/Patient/routes.php`
- UserFactory: `database/factories/UserFactory.php`

**Key conventions (from CLAUDE.md):**
- `declare(strict_types=1)` on every PHP file
- All code in English; tables/columns/models in Portuguese
- No route model binding — receive ID as primitive, resolve manually
- DTOs with `readonly` classes and `fromRequest()` factory methods
- Form Requests with array-based rules and Portuguese messages
- Pest tests with `it()` syntax and English descriptions
- Run `vendor/bin/pint --dirty` before each commit

---

## Phase 1: Foundation (Enums + Migrations + Models)

### Task 1: Create Appointment Enums

**Files:**
- Create: `app/Modules/Appointment/Enums/AppointmentStatus.php`
- Create: `app/Modules/Appointment/Enums/AppointmentType.php`
- Create: `app/Modules/Appointment/Enums/AppointmentOrigin.php`

**Step 1: Create AppointmentStatus enum**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Enums;

enum AppointmentStatus: string
{
    case Requested = 'requested';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Solicitado',
            self::Pending => 'Pendente',
            self::Confirmed => 'Confirmado',
            self::InProgress => 'Em andamento',
            self::Completed => 'Concluído',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Statuses that block a time slot from being booked.
     *
     * @return list<self>
     */
    public static function blockingStatuses(): array
    {
        return [
            self::Pending,
            self::Confirmed,
            self::InProgress,
            self::Completed,
        ];
    }
}
```

**Step 2: Create AppointmentType enum**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Enums;

enum AppointmentType: string
{
    case Consultation = 'consultation';
    case FollowUp = 'follow_up';
    case Exams = 'exams';
    case FirstConsultation = 'first_consultation';

    public function label(): string
    {
        return match ($this) {
            self::Consultation => 'Consulta',
            self::FollowUp => 'Retorno',
            self::Exams => 'Exames',
            self::FirstConsultation => 'Primeira Consulta',
        };
    }
}
```

**Step 3: Create AppointmentOrigin enum**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Enums;

enum AppointmentOrigin: string
{
    case Internal = 'internal';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Interno',
            self::Public => 'Público',
        };
    }
}
```

**Step 4: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Appointment/Enums/
git commit -m "feat(appointment): add enums for status, type, and origin"
```

---

### Task 2: Create Migrations

**Files:**
- Create: `app/Modules/Appointment/Database/Migrations/2026_02_16_300000_create_consultas_table.php`
- Create: `app/Modules/Appointment/Database/Migrations/2026_02_16_300001_add_slug_to_users_table.php`
- Create: `app/Modules/Delegation/Database/Migrations/2026_02_16_300002_create_delegacoes_table.php`
- Create: `app/Modules/Appointment/Database/Migrations/2026_02_16_300003_create_notifications_table.php`

**Step 1: Create consultas migration**

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
        Schema::create('consultas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->date('data');
            $table->time('horario');
            $table->string('tipo', 30);
            $table->string('status', 20);
            $table->text('observacoes')->nullable();
            $table->string('nome_solicitante')->nullable();
            $table->string('telefone_solicitante', 20)->nullable();
            $table->string('email_solicitante')->nullable();
            $table->string('origem', 10);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'data', 'horario']);
            $table->index(['user_id', 'data']);
            $table->index('paciente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};
```

**Step 2: Create slug migration for users**

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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique()->after('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('slug');
        });
    }
};
```

**Step 3: Create delegacoes migration**

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
        Schema::create('delegacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medico_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('secretaria_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['medico_id', 'secretaria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegacoes');
    }
};
```

**Step 4: Create notifications table migration**

Run: `php artisan notifications:table --no-interaction`

If it already exists, skip this step.

**Step 5: Run migrations**

```bash
php artisan migrate --no-interaction
```

**Step 6: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Appointment/Database/Migrations/ app/Modules/Delegation/Database/Migrations/
git add database/migrations/ # if notifications table was generated here
git commit -m "feat(appointment): add migrations for consultas, delegacoes, and slug"
```

---

### Task 3: Create Consulta Model

**Files:**
- Create: `app/Modules/Appointment/Models/Consulta.php`
- Create: `app/Modules/Appointment/Database/Factories/AppointmentFactory.php`

**Step 1: Create Consulta model**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Models;

use App\Models\User;
use App\Modules\Appointment\Enums\AppointmentOrigin;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Enums\AppointmentType;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $paciente_id
 * @property string $data
 * @property string $horario
 * @property AppointmentType $tipo
 * @property AppointmentStatus $status
 * @property string|null $observacoes
 * @property string|null $nome_solicitante
 * @property string|null $telefone_solicitante
 * @property string|null $email_solicitante
 * @property AppointmentOrigin $origem
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User $user
 * @property-read Paciente|null $paciente
 */
class Consulta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consultas';

    protected $fillable = [
        'user_id',
        'paciente_id',
        'data',
        'horario',
        'tipo',
        'status',
        'observacoes',
        'nome_solicitante',
        'telefone_solicitante',
        'email_solicitante',
        'origem',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => AppointmentType::class,
            'status' => AppointmentStatus::class,
            'origem' => AppointmentOrigin::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    protected static function newFactory(): \App\Modules\Appointment\Database\Factories\AppointmentFactory
    {
        return \App\Modules\Appointment\Database\Factories\AppointmentFactory::new();
    }
}
```

**Step 2: Create AppointmentFactory**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Database\Factories;

use App\Models\User;
use App\Modules\Appointment\Enums\AppointmentOrigin;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Enums\AppointmentType;
use App\Modules\Appointment\Models\Consulta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consulta>
 */
final class AppointmentFactory extends Factory
{
    protected $model = Consulta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->doctor(),
            'paciente_id' => null,
            'data' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'horario' => fake()->randomElement(['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00']),
            'tipo' => fake()->randomElement(AppointmentType::cases()),
            'status' => AppointmentStatus::Pending,
            'observacoes' => fake()->optional(0.3)->sentence(),
            'origem' => AppointmentOrigin::Internal,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Confirmed,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Cancelled,
        ]);
    }

    public function requested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Requested,
            'origem' => AppointmentOrigin::Public,
            'tipo' => AppointmentType::FirstConsultation,
            'nome_solicitante' => fake()->name(),
            'telefone_solicitante' => fake()->numerify('(##) #####-####'),
            'email_solicitante' => fake()->safeEmail(),
        ]);
    }

    public function forDoctor(User $doctor): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $doctor->id,
        ]);
    }
}
```

**Step 3: Update User model — add slug to fillable and PHPDoc**

In `app/Models/User.php`:
- Add `'slug'` to `$fillable`
- Add `@property string|null $slug` to PHPDoc
- Add `Notifiable` trait is already there (needed for notifications)

**Step 4: Update UserFactory — add slug**

In `database/factories/UserFactory.php`, add to `definition()`:
```php
'slug' => fake()->unique()->slug(2),
```

**Step 5: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Appointment/Models/ app/Modules/Appointment/Database/Factories/ app/Models/User.php database/factories/UserFactory.php
git commit -m "feat(appointment): add Consulta model with factory and user slug"
```

---

### Task 4: Create Delegacao Model

**Files:**
- Create: `app/Modules/Delegation/Models/Delegacao.php`
- Create: `app/Modules/Delegation/Database/Factories/DelegationFactory.php`

**Step 1: Create Delegacao model**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $medico_id
 * @property int $secretaria_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $medico
 * @property-read User $secretaria
 */
class Delegacao extends Model
{
    use HasFactory;

    protected $table = 'delegacoes';

    protected $fillable = [
        'medico_id',
        'secretaria_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medico_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function secretaria(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secretaria_id');
    }

    protected static function newFactory(): \App\Modules\Delegation\Database\Factories\DelegationFactory
    {
        return \App\Modules\Delegation\Database\Factories\DelegationFactory::new();
    }
}
```

**Step 2: Create DelegationFactory**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Database\Factories;

use App\Models\User;
use App\Modules\Delegation\Models\Delegacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delegacao>
 */
final class DelegationFactory extends Factory
{
    protected $model = Delegacao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medico_id' => User::factory()->doctor(),
            'secretaria_id' => User::factory()->secretary(),
        ];
    }
}
```

**Step 3: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Delegation/Models/ app/Modules/Delegation/Database/Factories/
git commit -m "feat(delegation): add Delegacao model with factory"
```

---

## Phase 2: Delegation Module (simpler, needed by Appointment)

### Task 5: Delegation Service, Policy, and ServiceProvider

**Files:**
- Create: `app/Modules/Delegation/Services/DelegationService.php`
- Create: `app/Modules/Delegation/Policies/DelegationPolicy.php`
- Create: `app/Modules/Delegation/Providers/DelegationServiceProvider.php`

**Step 1: Create DelegationService**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Services;

use App\Models\User;
use App\Modules\Delegation\Models\Delegacao;
use Illuminate\Database\Eloquent\Collection;

final class DelegationService
{
    /**
     * List delegations for a user (as doctor or secretary).
     *
     * @return Collection<int, Delegacao>
     */
    public function listForUser(User $user): Collection
    {
        return Delegacao::query()
            ->where('medico_id', $user->id)
            ->orWhere('secretaria_id', $user->id)
            ->with(['medico', 'secretaria'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get the doctor IDs a secretary has delegation for.
     *
     * @return list<int>
     */
    public function getDelegatedDoctorIds(int $secretaryId): array
    {
        return Delegacao::query()
            ->where('secretaria_id', $secretaryId)
            ->pluck('medico_id')
            ->all();
    }

    /**
     * Check if a secretary has delegation for a specific doctor.
     */
    public function hasDelegation(int $secretaryId, int $doctorId): bool
    {
        return Delegacao::query()
            ->where('secretaria_id', $secretaryId)
            ->where('medico_id', $doctorId)
            ->exists();
    }
}
```

**Step 2: Create DelegationPolicy**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Policies;

use App\Models\User;
use App\Modules\Auth\Enums\UserRole;
use App\Modules\Delegation\Models\Delegacao;

final class DelegationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Doctor;
    }

    public function delete(User $user, Delegacao $delegation): bool
    {
        return $user->id === $delegation->medico_id;
    }
}
```

**Step 3: Create DelegationServiceProvider**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Providers;

use App\Modules\Delegation\Models\Delegacao;
use App\Modules\Delegation\Policies\DelegationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class DelegationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Delegacao::class, DelegationPolicy::class);
    }
}
```

**Step 4: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Delegation/Services/ app/Modules/Delegation/Policies/ app/Modules/Delegation/Providers/
git commit -m "feat(delegation): add service, policy, and service provider"
```

---

### Task 6: Delegation HTTP Layer (Controller, Request, Resource, Routes)

**Files:**
- Create: `app/Modules/Delegation/Http/Controllers/DelegationController.php`
- Create: `app/Modules/Delegation/Http/Requests/StoreDelegationRequest.php`
- Create: `app/Modules/Delegation/Http/Resources/DelegationResource.php`
- Create: `app/Modules/Delegation/routes.php`

**Step 1: Create StoreDelegationRequest**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDelegationRequest extends FormRequest
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
            'secretary_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'secretary'),
                Rule::unique('delegacoes', 'secretaria_id')->where('medico_id', $this->user()?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'secretary_id.required' => 'O campo secretária é obrigatório.',
            'secretary_id.exists' => 'A secretária informada não existe ou não possui o perfil de secretária.',
            'secretary_id.unique' => 'Esta secretária já está vinculada a você.',
        ];
    }
}
```

**Step 2: Create DelegationResource**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Delegation\Models\Delegacao
 */
final class DelegationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor' => [
                'id' => $this->medico->id,
                'name' => $this->medico->name,
                'specialty' => $this->medico->specialty,
            ],
            'secretary' => [
                'id' => $this->secretaria->id,
                'name' => $this->secretaria->name,
            ],
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

**Step 3: Create DelegationController**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Delegation\Http\Controllers;

use App\Modules\Delegation\Http\Requests\StoreDelegationRequest;
use App\Modules\Delegation\Http\Resources\DelegationResource;
use App\Modules\Delegation\Models\Delegacao;
use App\Modules\Delegation\Services\DelegationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class DelegationController
{
    public function __construct(
        private readonly DelegationService $delegationService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $delegations = $this->delegationService->listForUser($request->user());

        return DelegationResource::collection($delegations);
    }

    public function store(StoreDelegationRequest $request): JsonResponse
    {
        Gate::authorize('create', Delegacao::class);

        $delegation = Delegacao::query()->create([
            'medico_id' => $request->user()->id,
            'secretaria_id' => $request->validated('secretary_id'),
        ]);

        $delegation->load(['medico', 'secretaria']);

        return (new DelegationResource($delegation))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $delegation = Delegacao::query()->findOrFail($id);

        Gate::authorize('delete', $delegation);

        $delegation->delete();

        return response()->json(['message' => 'Delegação removida com sucesso.']);
    }
}
```

**Step 4: Create routes.php**

```php
<?php

declare(strict_types=1);

use App\Modules\Delegation\Http\Controllers\DelegationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/delegations', [DelegationController::class, 'index']);
    Route::post('/delegations', [DelegationController::class, 'store']);
    Route::delete('/delegations/{id}', [DelegationController::class, 'destroy']);
});
```

**Step 5: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Delegation/Http/ app/Modules/Delegation/routes.php
git commit -m "feat(delegation): add controller, request, resource, and routes"
```

---

### Task 7: Delegation Tests

**Files:**
- Create: `app/Modules/Delegation/Tests/Feature/CreateDelegationTest.php`
- Create: `app/Modules/Delegation/Tests/Feature/ListDelegationTest.php`
- Create: `app/Modules/Delegation/Tests/Feature/DeleteDelegationTest.php`

**Step 1: Write tests**

`CreateDelegationTest.php`:
```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Delegation\Models\Delegacao;

it('allows a doctor to create a delegation for a secretary', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();

    $response = $this->actingAs($doctor)->postJson('/api/delegations', [
        'secretary_id' => $secretary->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.doctor.id', $doctor->id)
        ->assertJsonPath('data.secretary.id', $secretary->id);

    $this->assertDatabaseHas('delegacoes', [
        'medico_id' => $doctor->id,
        'secretaria_id' => $secretary->id,
    ]);
});

it('prevents a secretary from creating a delegation', function (): void {
    $secretary = User::factory()->secretary()->create();
    $otherSecretary = User::factory()->secretary()->create();

    $response = $this->actingAs($secretary)->postJson('/api/delegations', [
        'secretary_id' => $otherSecretary->id,
    ]);

    $response->assertForbidden();
});

it('prevents duplicate delegation', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($doctor)->postJson('/api/delegations', [
        'secretary_id' => $secretary->id,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('secretary_id');
});

it('rejects delegation to a non-secretary user', function (): void {
    $doctor = User::factory()->doctor()->create();
    $otherDoctor = User::factory()->doctor()->create();

    $response = $this->actingAs($doctor)->postJson('/api/delegations', [
        'secretary_id' => $otherDoctor->id,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('secretary_id');
});
```

`ListDelegationTest.php`:
```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Delegation\Models\Delegacao;

it('lists delegations for a doctor', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($doctor)->getJson('/api/delegations');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('lists delegations for a secretary', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($secretary)->getJson('/api/delegations');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});
```

`DeleteDelegationTest.php`:
```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Delegation\Models\Delegacao;

it('allows a doctor to delete their delegation', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    $delegation = Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($doctor)->deleteJson("/api/delegations/{$delegation->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('delegacoes', ['id' => $delegation->id]);
});

it('prevents a secretary from deleting a delegation', function (): void {
    $doctor = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    $delegation = Delegacao::factory()->create(['medico_id' => $doctor->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($secretary)->deleteJson("/api/delegations/{$delegation->id}");

    $response->assertForbidden();
});

it('prevents a doctor from deleting another doctor delegation', function (): void {
    $doctor1 = User::factory()->doctor()->create();
    $doctor2 = User::factory()->doctor()->create();
    $secretary = User::factory()->secretary()->create();
    $delegation = Delegacao::factory()->create(['medico_id' => $doctor1->id, 'secretaria_id' => $secretary->id]);

    $response = $this->actingAs($doctor2)->deleteJson("/api/delegations/{$delegation->id}");

    $response->assertForbidden();
});
```

**Step 2: Run tests**

```bash
php artisan test app/Modules/Delegation/Tests/ --compact
```

Expected: All pass.

**Step 3: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Delegation/Tests/
git commit -m "test(delegation): add feature tests for CRUD"
```

---

## Phase 3: Appointment Module — Core CRUD

### Task 8: Appointment Service

**Files:**
- Create: `app/Modules/Appointment/Services/AppointmentService.php`

**Step 1: Create AppointmentService**

The service handles:
- Listing appointments by date range (resolving doctor IDs for secretary)
- Finding a single appointment
- Checking time slot conflicts

```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Services;

use App\Models\User;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Auth\Enums\UserRole;
use App\Modules\Delegation\Services\DelegationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AppointmentService
{
    public function __construct(
        private readonly DelegationService $delegationService,
    ) {}

    /**
     * List appointments by date range for the authenticated user.
     *
     * @param array{start_date: string, end_date: string, doctor_id?: int|null} $filters
     *
     * @return Collection<int, Consulta>
     */
    public function listByDateRange(User $user, array $filters): Collection
    {
        $doctorIds = $this->resolveDoctorIds($user, $filters['doctor_id'] ?? null);

        return Consulta::query()
            ->whereIn('user_id', $doctorIds)
            ->whereBetween('data', [$filters['start_date'], $filters['end_date']])
            ->with('paciente')
            ->orderBy('data')
            ->orderBy('horario')
            ->get();
    }

    /**
     * Find a single appointment, verifying access.
     */
    public function findForUser(User $user, int $appointmentId): Consulta
    {
        $doctorIds = $this->resolveDoctorIds($user);

        $appointment = Consulta::query()
            ->whereIn('user_id', $doctorIds)
            ->with('paciente')
            ->find($appointmentId);

        if (! $appointment) {
            throw new NotFoundHttpException('Consulta não encontrada.');
        }

        return $appointment;
    }

    /**
     * Check if a time slot is already occupied by a blocking appointment.
     *
     * @throws ValidationException
     */
    public function checkTimeConflict(int $doctorId, string $date, string $time, ?int $excludeId = null): void
    {
        $query = Consulta::query()
            ->where('user_id', $doctorId)
            ->where('data', $date)
            ->where('horario', $time)
            ->whereIn('status', array_map(
                fn (AppointmentStatus $s) => $s->value,
                AppointmentStatus::blockingStatuses(),
            ));

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'horario' => 'Já existe uma consulta agendada para este horário.',
            ]);
        }
    }

    /**
     * Resolve which doctor IDs the user can access.
     *
     * @return list<int>
     */
    public function resolveDoctorIds(User $user, ?int $requestedDoctorId = null): array
    {
        if ($user->role === UserRole::Doctor) {
            return [$user->id];
        }

        // Secretary
        $delegatedIds = $this->delegationService->getDelegatedDoctorIds($user->id);

        if ($requestedDoctorId !== null) {
            if (! in_array($requestedDoctorId, $delegatedIds, true)) {
                throw ValidationException::withMessages([
                    'doctor_id' => 'Você não possui delegação para este médico.',
                ]);
            }

            return [$requestedDoctorId];
        }

        return $delegatedIds;
    }

    /**
     * Resolve a single doctor ID for creating/updating appointments.
     */
    public function resolveSingleDoctorId(User $user, ?int $requestedDoctorId): int
    {
        if ($user->role === UserRole::Doctor) {
            return $user->id;
        }

        if ($requestedDoctorId === null) {
            throw ValidationException::withMessages([
                'doctor_id' => 'O campo médico é obrigatório para secretárias.',
            ]);
        }

        if (! $this->delegationService->hasDelegation($user->id, $requestedDoctorId)) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Você não possui delegação para este médico.',
            ]);
        }

        return $requestedDoctorId;
    }
}
```

**Step 2: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Appointment/Services/
git commit -m "feat(appointment): add AppointmentService with conflict check and doctor resolution"
```

---

### Task 9: Appointment DTOs

**Files:**
- Create: `app/Modules/Appointment/DTOs/CreateAppointmentDTO.php`
- Create: `app/Modules/Appointment/DTOs/UpdateAppointmentDTO.php`
- Create: `app/Modules/Appointment/DTOs/BookPublicAppointmentDTO.php`

**Step 1: Create all three DTOs**

`CreateAppointmentDTO.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\DTOs;

use App\Modules\Appointment\Enums\AppointmentType;
use App\Modules\Appointment\Http\Requests\StoreAppointmentRequest;

final readonly class CreateAppointmentDTO
{
    public function __construct(
        public ?int $patientId,
        public string $date,
        public string $time,
        public AppointmentType $type,
        public ?string $notes,
        public ?int $doctorId,
    ) {}

    public static function fromRequest(StoreAppointmentRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            patientId: $validated['patient_id'] ?? null,
            date: $validated['date'],
            time: $validated['time'],
            type: AppointmentType::from($validated['type']),
            notes: $validated['notes'] ?? null,
            doctorId: $validated['doctor_id'] ?? null,
        );
    }
}
```

`UpdateAppointmentDTO.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\DTOs;

use App\Modules\Appointment\Enums\AppointmentType;
use App\Modules\Appointment\Http\Requests\UpdateAppointmentRequest;

final readonly class UpdateAppointmentDTO
{
    public function __construct(
        public ?int $patientId,
        public ?string $date,
        public ?string $time,
        public ?AppointmentType $type,
        public ?string $notes,
    ) {}

    public static function fromRequest(UpdateAppointmentRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            patientId: array_key_exists('patient_id', $validated) ? $validated['patient_id'] : null,
            date: $validated['date'] ?? null,
            time: $validated['time'] ?? null,
            type: isset($validated['type']) ? AppointmentType::from($validated['type']) : null,
            notes: array_key_exists('notes', $validated) ? $validated['notes'] : null,
        );
    }
}
```

`BookPublicAppointmentDTO.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\DTOs;

use App\Modules\Appointment\Http\Requests\BookPublicAppointmentRequest;

final readonly class BookPublicAppointmentDTO
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $email,
        public ?string $notes,
        public string $date,
        public string $time,
    ) {}

    public static function fromRequest(BookPublicAppointmentRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['nome'],
            phone: $validated['telefone'],
            email: $validated['email'],
            notes: $validated['observacoes'] ?? null,
            date: $validated['data'],
            time: $validated['horario'],
        );
    }
}
```

**Step 2: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Appointment/DTOs/
git commit -m "feat(appointment): add DTOs for create, update, and public booking"
```

---

### Task 10: Appointment Actions

**Files:**
- Create: `app/Modules/Appointment/Actions/CreateAppointmentAction.php`
- Create: `app/Modules/Appointment/Actions/UpdateAppointmentAction.php`
- Create: `app/Modules/Appointment/Actions/DeleteAppointmentAction.php`
- Create: `app/Modules/Appointment/Actions/UpdateAppointmentStatusAction.php`
- Create: `app/Modules/Appointment/Actions/BookPublicAppointmentAction.php`

**Step 1: Create all actions**

`CreateAppointmentAction.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Actions;

use App\Modules\Appointment\DTOs\CreateAppointmentDTO;
use App\Modules\Appointment\Enums\AppointmentOrigin;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Services\AppointmentService;

final class CreateAppointmentAction
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    public function execute(int $doctorId, CreateAppointmentDTO $dto): Consulta
    {
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
}
```

`UpdateAppointmentAction.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Actions;

use App\Modules\Appointment\DTOs\UpdateAppointmentDTO;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Services\AppointmentService;

final class UpdateAppointmentAction
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    public function execute(Consulta $appointment, UpdateAppointmentDTO $dto): Consulta
    {
        $date = $dto->date ?? $appointment->data;
        $time = $dto->time ?? $appointment->horario;

        if ($dto->date !== null || $dto->time !== null) {
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
}
```

`DeleteAppointmentAction.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Actions;

use App\Modules\Appointment\Models\Consulta;

final class DeleteAppointmentAction
{
    public function execute(Consulta $appointment): void
    {
        $appointment->delete();
    }
}
```

`UpdateAppointmentStatusAction.php`:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Actions;

use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Appointment\Services\AppointmentService;

final class UpdateAppointmentStatusAction
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    public function execute(Consulta $appointment, AppointmentStatus $newStatus): Consulta
    {
        if (in_array($newStatus, AppointmentStatus::blockingStatuses(), true)
            && ! in_array($appointment->status, AppointmentStatus::blockingStatuses(), true)) {
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
}
```

`BookPublicAppointmentAction.php`:
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

final class BookPublicAppointmentAction
{
    public function execute(int $doctorId, BookPublicAppointmentDTO $dto): Consulta
    {
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

**Step 2: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Appointment/Actions/
git commit -m "feat(appointment): add actions for CRUD, status update, and public booking"
```

---

### Task 11: Appointment HTTP Layer (Requests, Resources, Policy, ServiceProvider)

**Files:**
- Create: `app/Modules/Appointment/Http/Requests/ListAppointmentRequest.php`
- Create: `app/Modules/Appointment/Http/Requests/StoreAppointmentRequest.php`
- Create: `app/Modules/Appointment/Http/Requests/UpdateAppointmentRequest.php`
- Create: `app/Modules/Appointment/Http/Requests/UpdateAppointmentStatusRequest.php`
- Create: `app/Modules/Appointment/Http/Requests/BookPublicAppointmentRequest.php`
- Create: `app/Modules/Appointment/Http/Resources/AppointmentResource.php`
- Create: `app/Modules/Appointment/Http/Resources/AvailabilityResource.php`
- Create: `app/Modules/Appointment/Policies/AppointmentPolicy.php`
- Create: `app/Modules/Appointment/Providers/AppointmentServiceProvider.php`

This task creates all supporting HTTP files. See the design doc for field details.

Key rules for Form Requests:
- `ListAppointmentRequest`: `start_date` required date, `end_date` required date after_or_equal start_date, `doctor_id` optional integer
- `StoreAppointmentRequest`: `patient_id` optional, `date` required date after_or_equal today, `time` required regex HH:MM, `type` required in enum values, `notes` optional, `doctor_id` optional integer
- `UpdateAppointmentRequest`: same fields but all optional (except at least one must be present)
- `UpdateAppointmentStatusRequest`: `status` required in enum values
- `BookPublicAppointmentRequest`: `nome` required, `telefone` required, `email` required email, `observacoes` optional, `data` required date after_or_equal today, `horario` required regex HH:MM

All Portuguese validation messages.

`AppointmentResource` returns: `id`, `doctor_id` (user_id), `patient_id`, `patient_name` (from paciente or nome_solicitante), `date`, `time`, `type`, `type_label`, `status`, `status_label`, `origin`, `notes`, `requester_name`, `requester_phone`, `requester_email`, `created_at`, `updated_at`.

`AvailabilityResource` returns: `date`, `time`.

`AppointmentPolicy`: ownership check via `resolveDoctorIds` from the service — or simply check `user_id == auth.id || hasDelegation`.

**Step N: Run pint and commit after creating all files**

```bash
vendor/bin/pint --dirty
git add app/Modules/Appointment/Http/ app/Modules/Appointment/Policies/ app/Modules/Appointment/Providers/
git commit -m "feat(appointment): add HTTP layer — requests, resources, policy, and provider"
```

---

### Task 12: Appointment Controllers and Routes

**Files:**
- Create: `app/Modules/Appointment/Http/Controllers/AppointmentController.php`
- Create: `app/Modules/Appointment/Http/Controllers/PublicScheduleController.php`
- Create: `app/Modules/Appointment/routes.php`

`AppointmentController` methods: `index`, `store`, `show`, `update`, `updateStatus`, `destroy`, `types`.

`PublicScheduleController` methods: `availability`, `book`.

Routes:
```php
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/types', [AppointmentController::class, 'types']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
    Route::put('/appointments/{id}', [AppointmentController::class, 'update']);
    Route::patch('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
});

Route::get('/public/schedule/{slug}/availability', [PublicScheduleController::class, 'availability']);
Route::post('/public/schedule/{slug}/book', [PublicScheduleController::class, 'book']);
```

**Step N: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Appointment/Http/Controllers/ app/Modules/Appointment/routes.php
git commit -m "feat(appointment): add controllers and routes for auth and public endpoints"
```

---

## Phase 4: Notifications

### Task 13: Event, Notification, and Listener

**Files:**
- Create: `app/Modules/Appointment/Events/PublicAppointmentRequested.php`
- Create: `app/Modules/Appointment/Notifications/NewPublicAppointmentRequested.php`
- Create: `app/Modules/Appointment/Listeners/SendNewAppointmentNotification.php`

The event is dispatched by `BookPublicAppointmentAction`. The listener resolves the doctor + all delegated secretaries and sends the notification via `mail` + `database` channels.

Register event-listener mapping in `AppointmentServiceProvider::boot()`.

**Step N: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Appointment/Events/ app/Modules/Appointment/Notifications/ app/Modules/Appointment/Listeners/
git commit -m "feat(appointment): add notification system for public booking requests"
```

---

## Phase 5: Tests

### Task 14: Appointment Feature Tests

**Files:**
- Create: `app/Modules/Appointment/Tests/Feature/CreateAppointmentTest.php`
- Create: `app/Modules/Appointment/Tests/Feature/ListAppointmentTest.php`
- Create: `app/Modules/Appointment/Tests/Feature/ShowAppointmentTest.php`
- Create: `app/Modules/Appointment/Tests/Feature/UpdateAppointmentTest.php`
- Create: `app/Modules/Appointment/Tests/Feature/DeleteAppointmentTest.php`
- Create: `app/Modules/Appointment/Tests/Feature/UpdateAppointmentStatusTest.php`
- Create: `app/Modules/Appointment/Tests/Feature/AppointmentTypesTest.php`
- Create: `app/Modules/Appointment/Tests/Feature/TimeConflictTest.php`
- Create: `app/Modules/Appointment/Tests/Feature/SecretaryAppointmentTest.php`
- Create: `app/Modules/Appointment/Tests/Feature/PublicAvailabilityTest.php`
- Create: `app/Modules/Appointment/Tests/Feature/PublicBookingTest.php`

**Test scenarios per file:**

`CreateAppointmentTest.php`:
- it('creates an appointment as a doctor')
- it('creates an appointment with a patient')
- it('rejects creation without required fields')
- it('rejects creation for unauthenticated user')

`ListAppointmentTest.php`:
- it('lists appointments by date range')
- it('does not list appointments from another doctor')
- it('returns empty when no appointments in range')

`ShowAppointmentTest.php`:
- it('shows a single appointment')
- it('returns 404 for another doctor appointment')

`UpdateAppointmentTest.php`:
- it('updates an appointment date and time')
- it('updates appointment patient link')
- it('does not allow updating another doctor appointment')

`DeleteAppointmentTest.php`:
- it('soft deletes an appointment')
- it('does not allow deleting another doctor appointment')

`UpdateAppointmentStatusTest.php`:
- it('updates status from pending to confirmed')
- it('updates status from requested to pending')
- it('updates status to cancelled')

`AppointmentTypesTest.php`:
- it('returns all appointment types')

`TimeConflictTest.php`:
- it('rejects appointment at conflicting time slot')
- it('allows appointment at cancelled slot')
- it('allows appointment at requested slot')
- it('allows appointment at different time same day')

`SecretaryAppointmentTest.php`:
- it('allows secretary to list delegated doctor appointments')
- it('allows secretary to create appointment for delegated doctor')
- it('rejects secretary creating appointment without doctor_id')
- it('rejects secretary accessing non-delegated doctor appointments')

`PublicAvailabilityTest.php`:
- it('returns occupied time slots for a doctor by slug')
- it('does not include cancelled slots in availability')
- it('does not include requested slots in availability')
- it('returns 404 for invalid slug')

`PublicBookingTest.php`:
- it('creates a public booking request')
- it('sends notification to doctor and secretaries on public booking')
- it('rejects public booking without required fields')

**Step N: Run all tests**

```bash
php artisan test app/Modules/Appointment/Tests/ app/Modules/Delegation/Tests/ --compact
```

**Step N+1: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Appointment/Tests/
git commit -m "test(appointment): add comprehensive feature tests for all endpoints"
```

---

## Phase 6: Seeders

### Task 15: Seeders

**Files:**
- Create: `app/Modules/Appointment/Database/Seeders/AppointmentSeeder.php`
- Create: `app/Modules/Delegation/Database/Seeders/DelegationSeeder.php`

The AppointmentSeeder creates sample appointments for existing seeded doctors. The DelegationSeeder creates a delegation between the seeded doctor and a new secretary user.

**Step N: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add app/Modules/Appointment/Database/Seeders/ app/Modules/Delegation/Database/Seeders/
git commit -m "feat(appointment): add seeders for appointments and delegations"
```

---

## Phase 7: Final Verification

### Task 16: Full test suite and cleanup

**Step 1: Run the full test suite**

```bash
php artisan test --compact
```

All tests must pass.

**Step 2: Run pint on everything**

```bash
vendor/bin/pint
```

**Step 3: Verify migrations work from scratch**

```bash
php artisan migrate:fresh --seed --no-interaction
```

**Step 4: Final commit if any changes**

```bash
vendor/bin/pint --dirty
git add -A
git commit -m "chore(appointment): final cleanup and formatting"
```

---

## Execution Order Summary

| # | Task | Module | Depends on |
|---|------|--------|------------|
| 1 | Enums | Appointment | — |
| 2 | Migrations | Appointment + Delegation | 1 |
| 3 | Consulta Model + Factory | Appointment | 1, 2 |
| 4 | Delegacao Model + Factory | Delegation | 2 |
| 5 | Delegation Service, Policy, Provider | Delegation | 4 |
| 6 | Delegation HTTP Layer | Delegation | 5 |
| 7 | Delegation Tests | Delegation | 6 |
| 8 | Appointment Service | Appointment | 3, 5 |
| 9 | Appointment DTOs | Appointment | 8 |
| 10 | Appointment Actions | Appointment | 8, 9 |
| 11 | Appointment HTTP Layer (Requests, Resources, Policy) | Appointment | 10 |
| 12 | Appointment Controllers + Routes | Appointment | 11 |
| 13 | Event + Notification + Listener | Appointment | 12 |
| 14 | Appointment Tests | Appointment | 13 |
| 15 | Seeders | Both | 14 |
| 16 | Final Verification | Both | 15 |
