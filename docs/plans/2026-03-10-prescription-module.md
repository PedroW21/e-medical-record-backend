# Prescription Module Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement the complete prescription sub-module inside MedicalRecord module — medications catalog (read-only), prescriptions (11 subtypes with ANVISA auto-guess), and prescription templates (per-doctor).

**Architecture:** New `app/Modules/MedicalRecord/` module with minimal `prontuarios` table as FK anchor. Medications are a global read-only catalog. Prescriptions store items as JSONB (schema varies by subtype). `RecipeTypeGuesser` auto-determines recipe type from ANVISA classification. Controllers delegate to Services, DTOs at boundaries, Resources map PT→EN.

**Tech Stack:** Laravel 12, PHP 8.5, PostgreSQL, Sanctum SPA auth, Pest 4

**Design doc:** Serena memory `medical-record-module/12-prescription-implementation-design`

---

## Task 1: Module Structure + Enums

**Files:**
- Create: `app/Modules/MedicalRecord/Providers/MedicalRecordServiceProvider.php`
- Create: `app/Modules/MedicalRecord/Enums/MedicalRecordType.php`
- Create: `app/Modules/MedicalRecord/Enums/MedicalRecordStatus.php`
- Create: `app/Modules/MedicalRecord/Enums/PrescriptionSubType.php`
- Create: `app/Modules/MedicalRecord/Enums/RecipeType.php`
- Create: `app/Modules/MedicalRecord/Enums/AnvisaList.php`
- Create: `app/Modules/MedicalRecord/routes.php`

**Step 1: Create the 5 enums**

```php
// app/Modules/MedicalRecord/Enums/MedicalRecordType.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum MedicalRecordType: string
{
    case FirstVisit = 'first_visit';
    case FollowUp = 'follow_up';
    case PreAnesthetic = 'pre_anesthetic';
}
```

```php
// app/Modules/MedicalRecord/Enums/MedicalRecordStatus.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum MedicalRecordStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
}
```

```php
// app/Modules/MedicalRecord/Enums/PrescriptionSubType.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum PrescriptionSubType: string
{
    case Allopathic = 'allopathic';
    case Magistral = 'magistral';
    case InjectableIm = 'injectable_im';
    case InjectableEv = 'injectable_ev';
    case InjectableCombined = 'injectable_combined';
    case InjectableProtocol = 'injectable_protocol';
    case Glp1 = 'glp1';
    case Steroid = 'steroid';
    case SubcutaneousImplant = 'subcutaneous_implant';
    case Ozonotherapy = 'ozonotherapy';
    case Procedure = 'procedure';

    /**
     * Subtypes that never reference medications catalog.
     *
     * @return list<self>
     */
    public static function nonMedicationTypes(): array
    {
        return [
            self::Procedure,
            self::Ozonotherapy,
            self::SubcutaneousImplant,
        ];
    }
}
```

```php
// app/Modules/MedicalRecord/Enums/RecipeType.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum RecipeType: string
{
    case Normal = 'normal';
    case WhiteC1 = 'white_c1';
    case BlueB = 'blue_b';
    case YellowA = 'yellow_a';

    /**
     * Priority for auto-guess (higher = more restrictive).
     */
    public function priority(): int
    {
        return match ($this) {
            self::Normal => 0,
            self::WhiteC1 => 1,
            self::BlueB => 2,
            self::YellowA => 3,
        };
    }
}
```

```php
// app/Modules/MedicalRecord/Enums/AnvisaList.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum AnvisaList: string
{
    case A1 = 'A1';
    case A2 = 'A2';
    case A3 = 'A3';
    case B1 = 'B1';
    case B2 = 'B2';
    case C1 = 'C1';
    case C2 = 'C2';
    case C3 = 'C3';
    case C4 = 'C4';
    case C5 = 'C5';

    /**
     * Map ANVISA list to the required recipe type.
     */
    public function requiredRecipeType(): RecipeType
    {
        return match ($this) {
            self::A1, self::A2, self::A3 => RecipeType::YellowA,
            self::B1, self::B2 => RecipeType::BlueB,
            self::C1, self::C2, self::C3, self::C4, self::C5 => RecipeType::WhiteC1,
        };
    }
}
```

**Step 2: Create ServiceProvider**

```php
// app/Modules/MedicalRecord/Providers/MedicalRecordServiceProvider.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Providers;

use App\Modules\MedicalRecord\Models\Prescricao;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ModeloPrescricao;
use App\Modules\MedicalRecord\Policies\PrescriptionPolicy;
use App\Modules\MedicalRecord\Policies\PrescriptionTemplatePolicy;
use App\Modules\MedicalRecord\Policies\MedicalRecordPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class MedicalRecordServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Prontuario::class, MedicalRecordPolicy::class);
        Gate::policy(Prescricao::class, PrescriptionPolicy::class);
        Gate::policy(ModeloPrescricao::class, PrescriptionTemplatePolicy::class);
    }
}
```

**Step 3: Create empty routes file**

```php
// app/Modules/MedicalRecord/routes.php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    // Prescription routes will be added in Task 7
});
```

**Step 4: Verify module auto-discovery**

Run: `php artisan about | grep -i medical`
Expected: MedicalRecordServiceProvider registered

**Step 5: Commit**

```bash
git add app/Modules/MedicalRecord/
git commit -m "feat(medical-record): add module structure with enums and service provider"
```

---

## Task 2: Migrations

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_10_000001_create_prontuarios_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_10_000002_create_medicamentos_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_10_000003_create_prescricoes_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_10_000004_create_modelos_prescricao_table.php`

**Step 1: Create prontuarios migration (minimal)**

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
        Schema::create('prontuarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes');
            $table->foreignId('user_id')->constrained('users');
            $table->string('tipo'); // first_visit, follow_up, pre_anesthetic
            $table->string('status')->default('draft'); // draft, finalized
            $table->timestamp('finalizado_em')->nullable();
            $table->foreignId('baseado_em_prontuario_id')->nullable()->constrained('prontuarios');
            $table->timestamps();

            $table->index(['paciente_id', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prontuarios');
    }
};
```

**Step 2: Create medicamentos migration**

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
        Schema::create('medicamentos', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('principio_ativo');
            $table->string('apresentacao')->nullable();
            $table->string('fabricante')->nullable();
            $table->string('codigo_anvisa')->nullable();
            $table->string('lista_anvisa')->nullable(); // A1,A2,A3,B1,B2,C1-C5
            $table->boolean('controlado')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('nome');
            $table->index('principio_ativo');
            $table->index('lista_anvisa');
            $table->index('ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicamentos');
    }
};
```

**Step 3: Create prescricoes migration**

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
        Schema::create('prescricoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->string('subtipo'); // 11 subtypes enum
            $table->string('tipo_receita'); // normal, white_c1, blue_b, yellow_a
            $table->boolean('tipo_receita_override')->default(false);
            $table->jsonb('itens');
            $table->text('observacoes')->nullable();
            $table->timestamp('impresso_em')->nullable();
            $table->timestamps();

            $table->index(['prontuario_id', 'subtipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricoes');
    }
};
```

**Step 4: Create modelos_prescricao migration**

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
        Schema::create('modelos_prescricao', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nome');
            $table->jsonb('tags')->nullable();
            $table->string('subtipo'); // same enum as prescricoes
            $table->jsonb('itens');
            $table->timestamps();

            $table->index(['user_id', 'subtipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos_prescricao');
    }
};
```

**Step 5: Run migrations**

Run: `php artisan migrate`
Expected: 4 tables created successfully

**Step 6: Commit**

```bash
git add app/Modules/MedicalRecord/Database/
git commit -m "feat(medical-record): add migrations for prontuarios, medicamentos, prescricoes, modelos_prescricao"
```

---

## Task 3: Models + Factories

**Files:**
- Create: `app/Modules/MedicalRecord/Models/Prontuario.php`
- Create: `app/Modules/MedicalRecord/Models/Medicamento.php`
- Create: `app/Modules/MedicalRecord/Models/Prescricao.php`
- Create: `app/Modules/MedicalRecord/Models/ModeloPrescricao.php`
- Create: `app/Modules/MedicalRecord/Database/Factories/MedicalRecordFactory.php`
- Create: `app/Modules/MedicalRecord/Database/Factories/MedicationFactory.php`
- Create: `app/Modules/MedicalRecord/Database/Factories/PrescriptionFactory.php`
- Create: `app/Modules/MedicalRecord/Database/Factories/PrescriptionTemplateFactory.php`

**Step 1: Create Prontuario model**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Models\User;
use App\Modules\MedicalRecord\Enums\MedicalRecordStatus;
use App\Modules\MedicalRecord\Enums\MedicalRecordType;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $paciente_id
 * @property int $user_id
 * @property MedicalRecordType $tipo
 * @property MedicalRecordStatus $status
 * @property \Illuminate\Support\Carbon|null $finalizado_em
 * @property int|null $baseado_em_prontuario_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Paciente $paciente
 * @property-read User $user
 * @property-read Prontuario|null $prontuarioBase
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Prescricao> $prescricoes
 */
class Prontuario extends Model
{
    use HasFactory;

    protected $table = 'prontuarios';

    protected $fillable = [
        'paciente_id',
        'user_id',
        'tipo',
        'status',
        'finalizado_em',
        'baseado_em_prontuario_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => MedicalRecordType::class,
            'status' => MedicalRecordStatus::class,
            'finalizado_em' => 'datetime',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === MedicalRecordStatus::Draft;
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Prontuario, $this>
     */
    public function prontuarioBase(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class, 'baseado_em_prontuario_id');
    }

    /**
     * @return HasMany<Prescricao, $this>
     */
    public function prescricoes(): HasMany
    {
        return $this->hasMany(Prescricao::class);
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\MedicalRecordFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\MedicalRecordFactory::new();
    }
}
```

**Step 2: Create Medicamento model**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\MedicalRecord\Enums\AnvisaList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nome
 * @property string $principio_ativo
 * @property string|null $apresentacao
 * @property string|null $fabricante
 * @property string|null $codigo_anvisa
 * @property AnvisaList|null $lista_anvisa
 * @property bool $controlado
 * @property bool $ativo
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Medicamento extends Model
{
    use HasFactory;

    protected $table = 'medicamentos';

    protected $fillable = [
        'nome',
        'principio_ativo',
        'apresentacao',
        'fabricante',
        'codigo_anvisa',
        'lista_anvisa',
        'controlado',
        'ativo',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lista_anvisa' => AnvisaList::class,
            'controlado' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Medicamento>  $query
     * @return Builder<Medicamento>
     */
    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    /**
     * @param  Builder<Medicamento>  $query
     * @return Builder<Medicamento>
     */
    public function scopeControlado(Builder $query): Builder
    {
        return $query->whereNotNull('lista_anvisa');
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\MedicationFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\MedicationFactory::new();
    }
}
```

**Step 3: Create Prescricao model**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Enums\RecipeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $prontuario_id
 * @property PrescriptionSubType $subtipo
 * @property RecipeType $tipo_receita
 * @property bool $tipo_receita_override
 * @property array<int, array<string, mixed>> $itens
 * @property string|null $observacoes
 * @property \Illuminate\Support\Carbon|null $impresso_em
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 */
class Prescricao extends Model
{
    use HasFactory;

    protected $table = 'prescricoes';

    protected $fillable = [
        'prontuario_id',
        'subtipo',
        'tipo_receita',
        'tipo_receita_override',
        'itens',
        'observacoes',
        'impresso_em',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtipo' => PrescriptionSubType::class,
            'tipo_receita' => RecipeType::class,
            'tipo_receita_override' => 'boolean',
            'itens' => 'array',
            'impresso_em' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Prontuario, $this>
     */
    public function prontuario(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class);
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\PrescriptionFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\PrescriptionFactory::new();
    }
}
```

**Step 4: Create ModeloPrescricao model**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Models\User;
use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $nome
 * @property array<int, string>|null $tags
 * @property PrescriptionSubType $subtipo
 * @property array<int, array<string, mixed>> $itens
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 */
class ModeloPrescricao extends Model
{
    use HasFactory;

    protected $table = 'modelos_prescricao';

    protected $fillable = [
        'user_id',
        'nome',
        'tags',
        'subtipo',
        'itens',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'subtipo' => PrescriptionSubType::class,
            'itens' => 'array',
        ];
    }

    /**
     * @param  Builder<ModeloPrescricao>  $query
     * @return Builder<ModeloPrescricao>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\PrescriptionTemplateFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\PrescriptionTemplateFactory::new();
    }
}
```

**Step 5: Create MedicalRecordFactory**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Models\User;
use App\Modules\MedicalRecord\Enums\MedicalRecordStatus;
use App\Modules\MedicalRecord\Enums\MedicalRecordType;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prontuario>
 */
final class MedicalRecordFactory extends Factory
{
    protected $model = Prontuario::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'user_id' => User::factory()->doctor(),
            'tipo' => MedicalRecordType::FirstVisit,
            'status' => MedicalRecordStatus::Draft,
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MedicalRecordStatus::Finalized,
            'finalizado_em' => now(),
        ]);
    }

    public function followUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => MedicalRecordType::FollowUp,
        ]);
    }
}
```

**Step 6: Create MedicationFactory**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Enums\AnvisaList;
use App\Modules\MedicalRecord\Models\Medicamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicamento>
 */
final class MedicationFactory extends Factory
{
    protected $model = Medicamento::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->words(2, true),
            'principio_ativo' => fake()->word(),
            'apresentacao' => fake()->optional(0.7)->randomElement(['Comprimido 500mg', 'Cápsula 200mg', 'Solução oral 100ml']),
            'fabricante' => fake()->optional(0.5)->company(),
            'codigo_anvisa' => null,
            'lista_anvisa' => null,
            'controlado' => false,
            'ativo' => true,
        ];
    }

    public function controlled(AnvisaList $list = AnvisaList::C1): static
    {
        return $this->state(fn (array $attributes) => [
            'lista_anvisa' => $list,
            'controlado' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'ativo' => false,
        ]);
    }
}
```

**Step 7: Create PrescriptionFactory**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Enums\RecipeType;
use App\Modules\MedicalRecord\Models\Prescricao;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescricao>
 */
final class PrescriptionFactory extends Factory
{
    protected $model = Prescricao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'subtipo' => PrescriptionSubType::Allopathic,
            'tipo_receita' => RecipeType::Normal,
            'tipo_receita_override' => false,
            'itens' => [
                [
                    'medication_name' => 'Paracetamol 500mg',
                    'dosage' => '1 comprimido',
                    'frequency' => '8/8h',
                    'duration' => '5 dias',
                    'instructions' => 'Tomar após as refeições.',
                    'is_controlled' => false,
                ],
            ],
        ];
    }

    public function magistral(): static
    {
        return $this->state(fn (array $attributes) => [
            'subtipo' => PrescriptionSubType::Magistral,
            'itens' => [
                [
                    'name' => 'Fórmula manipulada vitamina D',
                    'components' => [['name' => 'Vitamina D3', 'dose' => '50.000 UI']],
                    'posology' => '1 cápsula por semana',
                ],
            ],
        ]);
    }

    public function controlled(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_receita' => RecipeType::BlueB,
            'itens' => [
                [
                    'medication_name' => 'Clonazepam 2mg',
                    'dosage' => '1 comprimido',
                    'frequency' => 'à noite',
                    'duration' => '30 dias',
                    'is_controlled' => true,
                    'control_type' => 'B1',
                ],
            ],
        ]);
    }
}
```

**Step 8: Create PrescriptionTemplateFactory**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Models\User;
use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Models\ModeloPrescricao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeloPrescricao>
 */
final class PrescriptionTemplateFactory extends Factory
{
    protected $model = ModeloPrescricao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->doctor(),
            'nome' => fake()->sentence(3),
            'tags' => fake()->optional(0.5)->randomElements(['dor', 'inflamação', 'antibiótico', 'rotina'], 2),
            'subtipo' => PrescriptionSubType::Allopathic,
            'itens' => [
                [
                    'medication_name' => 'Ibuprofeno 600mg',
                    'dosage' => '1 comprimido',
                    'frequency' => '8/8h',
                    'duration' => '5 dias',
                    'is_controlled' => false,
                ],
            ],
        ];
    }
}
```

**Step 9: Run tests to verify factories work**

Run: `php artisan tinker --execute="App\Modules\MedicalRecord\Models\Prontuario::factory()->make()"`
Expected: No errors

**Step 10: Commit**

```bash
git add app/Modules/MedicalRecord/Models/ app/Modules/MedicalRecord/Database/Factories/
git commit -m "feat(medical-record): add models and factories for prontuario, medicamento, prescricao, modelo_prescricao"
```

---

## Task 4: RecipeTypeGuesser + Unit Tests

**Files:**
- Create: `app/Modules/MedicalRecord/Services/RecipeTypeGuesser.php`
- Create: `app/Modules/MedicalRecord/Tests/Unit/RecipeTypeGuesserTest.php`

**Step 1: Write failing tests**

```php
<?php

declare(strict_types=1);

use App\Modules\MedicalRecord\Enums\AnvisaList;
use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Enums\RecipeType;
use App\Modules\MedicalRecord\Models\Medicamento;
use App\Modules\MedicalRecord\Services\RecipeTypeGuesser;

it('returns normal for items without medication_id', function (): void {
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [['medication_name' => 'Paracetamol', 'dosage' => '500mg']],
        subtipo: PrescriptionSubType::Allopathic,
    );

    expect($result)->toBe(RecipeType::Normal);
});

it('returns yellow_a for A1 medication', function (): void {
    $med = Medicamento::factory()->controlled(AnvisaList::A1)->create();
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [['medication_id' => $med->id, 'medication_name' => $med->nome]],
        subtipo: PrescriptionSubType::Allopathic,
    );

    expect($result)->toBe(RecipeType::YellowA);
});

it('returns blue_b for B1 medication', function (): void {
    $med = Medicamento::factory()->controlled(AnvisaList::B1)->create();
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [['medication_id' => $med->id, 'medication_name' => $med->nome]],
        subtipo: PrescriptionSubType::Allopathic,
    );

    expect($result)->toBe(RecipeType::BlueB);
});

it('returns white_c1 for C1 medication', function (): void {
    $med = Medicamento::factory()->controlled(AnvisaList::C1)->create();
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [['medication_id' => $med->id, 'medication_name' => $med->nome]],
        subtipo: PrescriptionSubType::Allopathic,
    );

    expect($result)->toBe(RecipeType::WhiteC1);
});

it('picks most restrictive when mixing controlled levels', function (): void {
    $medC1 = Medicamento::factory()->controlled(AnvisaList::C1)->create();
    $medB1 = Medicamento::factory()->controlled(AnvisaList::B1)->create();
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [
            ['medication_id' => $medC1->id, 'medication_name' => $medC1->nome],
            ['medication_id' => $medB1->id, 'medication_name' => $medB1->nome],
        ],
        subtipo: PrescriptionSubType::Allopathic,
    );

    expect($result)->toBe(RecipeType::BlueB);
});

it('returns normal for procedure subtypes regardless of items', function (): void {
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [['type' => 'neural_therapy', 'description' => 'Test']],
        subtipo: PrescriptionSubType::Procedure,
    );

    expect($result)->toBe(RecipeType::Normal);
});

it('returns normal for ozonotherapy subtypes', function (): void {
    $guesser = app(RecipeTypeGuesser::class);

    $result = $guesser->guess(
        itens: [['description' => 'Protocolo ozônio']],
        subtipo: PrescriptionSubType::Ozonotherapy,
    );

    expect($result)->toBe(RecipeType::Normal);
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test app/Modules/MedicalRecord/Tests/Unit/RecipeTypeGuesserTest.php --compact`
Expected: FAIL (class not found)

**Step 3: Implement RecipeTypeGuesser**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Enums\RecipeType;
use App\Modules\MedicalRecord\Models\Medicamento;

final class RecipeTypeGuesser
{
    /**
     * Determine the most restrictive recipe type based on ANVISA classification.
     *
     * @param  array<int, array<string, mixed>>  $itens
     */
    public function guess(array $itens, PrescriptionSubType $subtipo): RecipeType
    {
        if (in_array($subtipo, PrescriptionSubType::nonMedicationTypes(), true)) {
            return RecipeType::Normal;
        }

        $medicationIds = collect($itens)
            ->pluck('medication_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($medicationIds)) {
            return RecipeType::Normal;
        }

        $medications = Medicamento::query()
            ->whereIn('id', $medicationIds)
            ->whereNotNull('lista_anvisa')
            ->get();

        if ($medications->isEmpty()) {
            return RecipeType::Normal;
        }

        return $medications
            ->map(fn (Medicamento $med) => $med->lista_anvisa->requiredRecipeType())
            ->sortByDesc(fn (RecipeType $type) => $type->priority())
            ->first();
    }
}
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test app/Modules/MedicalRecord/Tests/Unit/RecipeTypeGuesserTest.php --compact`
Expected: 7 PASS

**Step 5: Commit**

```bash
git add app/Modules/MedicalRecord/Services/RecipeTypeGuesser.php app/Modules/MedicalRecord/Tests/Unit/
git commit -m "feat(medical-record): add RecipeTypeGuesser with ANVISA auto-guess logic"
```

---

## Task 5: DTOs

**Files:**
- Create: `app/Modules/MedicalRecord/DTOs/CreatePrescriptionDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/UpdatePrescriptionDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/CreatePrescriptionTemplateDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/UpdatePrescriptionTemplateDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/MedicationFilterDTO.php`

**Step 1: Create all DTOs**

```php
// app/Modules/MedicalRecord/DTOs/CreatePrescriptionDTO.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Http\Requests\StorePrescriptionRequest;

final readonly class CreatePrescriptionDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $itens
     */
    public function __construct(
        public PrescriptionSubType $subtipo,
        public array $itens,
        public ?string $observacoes = null,
        public bool $tipoReceitaOverride = false,
        public ?string $tipoReceitaManual = null,
    ) {}

    public static function fromRequest(StorePrescriptionRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            subtipo: PrescriptionSubType::from($validated['subtype']),
            itens: $validated['items'],
            observacoes: $validated['notes'] ?? null,
            tipoReceitaOverride: (bool) ($validated['recipe_type_override'] ?? false),
            tipoReceitaManual: $validated['recipe_type'] ?? null,
        );
    }
}
```

```php
// app/Modules/MedicalRecord/DTOs/UpdatePrescriptionDTO.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Http\Requests\UpdatePrescriptionRequest;

final readonly class UpdatePrescriptionDTO
{
    /**
     * @param  array<int, array<string, mixed>>|null  $itens
     */
    public function __construct(
        public ?PrescriptionSubType $subtipo = null,
        public ?array $itens = null,
        public ?string $observacoes = null,
        public ?bool $tipoReceitaOverride = null,
        public ?string $tipoReceitaManual = null,
    ) {}

    public static function fromRequest(UpdatePrescriptionRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            subtipo: isset($validated['subtype']) ? PrescriptionSubType::from($validated['subtype']) : null,
            itens: $validated['items'] ?? null,
            observacoes: array_key_exists('notes', $validated) ? $validated['notes'] : null,
            tipoReceitaOverride: isset($validated['recipe_type_override']) ? (bool) $validated['recipe_type_override'] : null,
            tipoReceitaManual: $validated['recipe_type'] ?? null,
        );
    }
}
```

```php
// app/Modules/MedicalRecord/DTOs/CreatePrescriptionTemplateDTO.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Http\Requests\StorePrescriptionTemplateRequest;

final readonly class CreatePrescriptionTemplateDTO
{
    /**
     * @param  array<int, string>|null  $tags
     * @param  array<int, array<string, mixed>>  $itens
     */
    public function __construct(
        public string $nome,
        public PrescriptionSubType $subtipo,
        public array $itens,
        public ?array $tags = null,
    ) {}

    public static function fromRequest(StorePrescriptionTemplateRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            nome: $validated['name'],
            subtipo: PrescriptionSubType::from($validated['subtype']),
            itens: $validated['items'],
            tags: $validated['tags'] ?? null,
        );
    }
}
```

```php
// app/Modules/MedicalRecord/DTOs/UpdatePrescriptionTemplateDTO.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\UpdatePrescriptionTemplateRequest;

final readonly class UpdatePrescriptionTemplateDTO
{
    /**
     * @param  array<int, string>|null  $tags
     * @param  array<int, array<string, mixed>>|null  $itens
     */
    public function __construct(
        public ?string $nome = null,
        public ?array $itens = null,
        public ?array $tags = null,
    ) {}

    public static function fromRequest(UpdatePrescriptionTemplateRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            nome: $validated['name'] ?? null,
            itens: $validated['items'] ?? null,
            tags: array_key_exists('tags', $validated) ? $validated['tags'] : null,
        );
    }
}
```

```php
// app/Modules/MedicalRecord/DTOs/MedicationFilterDTO.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\ListMedicationRequest;

final readonly class MedicationFilterDTO
{
    public function __construct(
        public ?string $search = null,
        public ?bool $controlado = null,
        public int $perPage = 15,
    ) {}

    public static function fromRequest(ListMedicationRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            search: $validated['search'] ?? null,
            controlado: isset($validated['controlled']) ? (bool) $validated['controlled'] : null,
            perPage: (int) ($validated['per_page'] ?? 15),
        );
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/DTOs/
git commit -m "feat(medical-record): add DTOs for prescriptions, templates, and medication filters"
```

---

## Task 6: Services + Policies

**Files:**
- Create: `app/Modules/MedicalRecord/Services/PrescriptionService.php`
- Create: `app/Modules/MedicalRecord/Services/MedicationService.php`
- Create: `app/Modules/MedicalRecord/Services/PrescriptionTemplateService.php`
- Create: `app/Modules/MedicalRecord/Policies/MedicalRecordPolicy.php`
- Create: `app/Modules/MedicalRecord/Policies/PrescriptionPolicy.php`
- Create: `app/Modules/MedicalRecord/Policies/PrescriptionTemplatePolicy.php`

**Step 1: Create PrescriptionService**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\CreatePrescriptionDTO;
use App\Modules\MedicalRecord\DTOs\UpdatePrescriptionDTO;
use App\Modules\MedicalRecord\Enums\RecipeType;
use App\Modules\MedicalRecord\Models\Prescricao;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PrescriptionService
{
    public function __construct(
        private readonly RecipeTypeGuesser $recipeTypeGuesser,
    ) {}

    /**
     * @return Collection<int, Prescricao>
     */
    public function listByMedicalRecord(int $medicalRecordId): Collection
    {
        $this->findMedicalRecordOrFail($medicalRecordId);

        return Prescricao::query()
            ->where('prontuario_id', $medicalRecordId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(int $medicalRecordId, CreatePrescriptionDTO $dto): Prescricao
    {
        $prontuario = $this->findMedicalRecordOrFail($medicalRecordId);
        $this->ensureDraft($prontuario);

        $tipoReceita = $this->resolveRecipeType($dto->itens, $dto->subtipo, $dto->tipoReceitaOverride, $dto->tipoReceitaManual);

        return Prescricao::query()->create([
            'prontuario_id' => $medicalRecordId,
            'subtipo' => $dto->subtipo,
            'tipo_receita' => $tipoReceita,
            'tipo_receita_override' => $dto->tipoReceitaOverride,
            'itens' => $dto->itens,
            'observacoes' => $dto->observacoes,
        ]);
    }

    public function update(int $prescriptionId, UpdatePrescriptionDTO $dto): Prescricao
    {
        $prescription = $this->findOrFail($prescriptionId);
        $this->ensureDraft($prescription->prontuario);

        $data = array_filter([
            'subtipo' => $dto->subtipo,
            'itens' => $dto->itens,
            'observacoes' => $dto->observacoes,
            'tipo_receita_override' => $dto->tipoReceitaOverride,
        ], fn ($value) => $value !== null);

        $itens = $dto->itens ?? $prescription->itens;
        $subtipo = $dto->subtipo ?? $prescription->subtipo;
        $override = $dto->tipoReceitaOverride ?? $prescription->tipo_receita_override;
        $manual = $dto->tipoReceitaManual;

        $data['tipo_receita'] = $this->resolveRecipeType($itens, $subtipo, $override, $manual);

        $prescription->update($data);

        return $prescription->fresh();
    }

    public function delete(int $prescriptionId): void
    {
        $prescription = $this->findOrFail($prescriptionId);
        $this->ensureDraft($prescription->prontuario);

        $prescription->delete();
    }

    public function findOrFail(int $prescriptionId): Prescricao
    {
        $prescription = Prescricao::query()->with('prontuario')->find($prescriptionId);

        if (! $prescription) {
            throw new NotFoundHttpException('Prescrição não encontrada.');
        }

        return $prescription;
    }

    private function findMedicalRecordOrFail(int $medicalRecordId): Prontuario
    {
        $prontuario = Prontuario::query()->find($medicalRecordId);

        if (! $prontuario) {
            throw new NotFoundHttpException('Prontuário não encontrado.');
        }

        return $prontuario;
    }

    private function ensureDraft(Prontuario $prontuario): void
    {
        if (! $prontuario->isDraft()) {
            throw new ConflictHttpException('Não é possível modificar prescrições de um prontuário finalizado.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $itens
     */
    private function resolveRecipeType(
        array $itens,
        \App\Modules\MedicalRecord\Enums\PrescriptionSubType $subtipo,
        bool $override,
        ?string $manual,
    ): RecipeType {
        if ($override && $manual) {
            return RecipeType::from($manual);
        }

        return $this->recipeTypeGuesser->guess($itens, $subtipo);
    }
}
```

**Step 2: Create MedicationService**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\MedicationFilterDTO;
use App\Modules\MedicalRecord\Models\Medicamento;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MedicationService
{
    /**
     * @return LengthAwarePaginator<Medicamento>
     */
    public function list(MedicationFilterDTO $filters): LengthAwarePaginator
    {
        $query = Medicamento::query()->ativo();

        if ($filters->search) {
            $search = mb_strtolower($filters->search);
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('LOWER(nome) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(principio_ativo) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($filters->controlado !== null) {
            $filters->controlado
                ? $query->controlado()
                : $query->whereNull('lista_anvisa');
        }

        return $query
            ->orderBy('nome')
            ->paginate(perPage: $filters->perPage);
    }

    public function findOrFail(int $id): Medicamento
    {
        $medication = Medicamento::query()->ativo()->find($id);

        if (! $medication) {
            throw new NotFoundHttpException('Medicamento não encontrado.');
        }

        return $medication;
    }
}
```

**Step 3: Create PrescriptionTemplateService**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\CreatePrescriptionTemplateDTO;
use App\Modules\MedicalRecord\DTOs\UpdatePrescriptionTemplateDTO;
use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Models\ModeloPrescricao;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PrescriptionTemplateService
{
    /**
     * @return Collection<int, ModeloPrescricao>
     */
    public function listForUser(int $userId, ?PrescriptionSubType $subtipo = null): Collection
    {
        $query = ModeloPrescricao::query()->forUser($userId);

        if ($subtipo) {
            $query->where('subtipo', $subtipo);
        }

        return $query->orderBy('nome')->get();
    }

    public function create(int $userId, CreatePrescriptionTemplateDTO $dto): ModeloPrescricao
    {
        return ModeloPrescricao::query()->create([
            'user_id' => $userId,
            'nome' => $dto->nome,
            'subtipo' => $dto->subtipo,
            'itens' => $dto->itens,
            'tags' => $dto->tags,
        ]);
    }

    public function update(int $templateId, UpdatePrescriptionTemplateDTO $dto): ModeloPrescricao
    {
        $template = $this->findOrFail($templateId);

        $data = array_filter([
            'nome' => $dto->nome,
            'itens' => $dto->itens,
            'tags' => $dto->tags,
        ], fn ($value) => $value !== null);

        $template->update($data);

        return $template->fresh();
    }

    public function delete(int $templateId): void
    {
        $template = $this->findOrFail($templateId);
        $template->delete();
    }

    public function findOrFail(int $templateId): ModeloPrescricao
    {
        $template = ModeloPrescricao::query()->find($templateId);

        if (! $template) {
            throw new NotFoundHttpException('Modelo de prescrição não encontrado.');
        }

        return $template;
    }
}
```

**Step 4: Create Policies**

```php
// app/Modules/MedicalRecord/Policies/MedicalRecordPolicy.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;

final class MedicalRecordPolicy
{
    public function view(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    public function update(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }
}
```

```php
// app/Modules/MedicalRecord/Policies/PrescriptionPolicy.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prescricao;

final class PrescriptionPolicy
{
    public function view(User $user, Prescricao $prescription): bool
    {
        return $user->id === $prescription->prontuario->user_id;
    }

    public function create(User $user, \App\Modules\MedicalRecord\Models\Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    public function update(User $user, Prescricao $prescription): bool
    {
        return $user->id === $prescription->prontuario->user_id;
    }

    public function delete(User $user, Prescricao $prescription): bool
    {
        return $user->id === $prescription->prontuario->user_id;
    }
}
```

```php
// app/Modules/MedicalRecord/Policies/PrescriptionTemplatePolicy.php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\ModeloPrescricao;

final class PrescriptionTemplatePolicy
{
    public function view(User $user, ModeloPrescricao $template): bool
    {
        return $user->id === $template->user_id;
    }

    public function update(User $user, ModeloPrescricao $template): bool
    {
        return $user->id === $template->user_id;
    }

    public function delete(User $user, ModeloPrescricao $template): bool
    {
        return $user->id === $template->user_id;
    }
}
```

**Step 5: Commit**

```bash
git add app/Modules/MedicalRecord/Services/ app/Modules/MedicalRecord/Policies/
git commit -m "feat(medical-record): add services and policies for prescriptions, medications, and templates"
```

---

## Task 7: Form Requests + Resources + Controllers + Routes

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Requests/StorePrescriptionRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/UpdatePrescriptionRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/ListMedicationRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/StorePrescriptionTemplateRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/UpdatePrescriptionTemplateRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Resources/PrescriptionResource.php`
- Create: `app/Modules/MedicalRecord/Http/Resources/MedicationResource.php`
- Create: `app/Modules/MedicalRecord/Http/Resources/PrescriptionTemplateResource.php`
- Create: `app/Modules/MedicalRecord/Http/Controllers/PrescriptionController.php`
- Create: `app/Modules/MedicalRecord/Http/Controllers/MedicationController.php`
- Create: `app/Modules/MedicalRecord/Http/Controllers/PrescriptionTemplateController.php`
- Modify: `app/Modules/MedicalRecord/routes.php`

**Step 1: Create Form Requests**

The `StorePrescriptionRequest` should validate `items` dynamically based on `subtype`. Use array-based rules matching the existing project convention (see `StorePatientRequest`).

Key validation rules:
- `subtype`: required, in PrescriptionSubType enum values
- `items`: required, array, min:1
- `items.*.medication_name`: required_if subtype is allopathic
- `items.*.dosage`: required_if subtype is allopathic
- `items.*.frequency`: required_if subtype is allopathic
- `items.*.duration`: required_if subtype is allopathic
- `notes`: nullable, string
- `recipe_type_override`: nullable, boolean
- `recipe_type`: required_if recipe_type_override is true, in RecipeType enum values

For `ListMedicationRequest`: `search` nullable string, `controlled` nullable boolean, `per_page` nullable integer max:100.

For template requests: `name` required string, `subtype` required in enum, `items` required array min:1, `tags` nullable array of strings.

All messages in Portuguese following existing convention.

**Step 2: Create Resources**

`PrescriptionResource` maps PT→EN:
- `id`, `medical_record_id` (from prontuario_id), `subtype` (from subtipo), `recipe_type` (from tipo_receita), `recipe_type_override` (from tipo_receita_override), `items` (from itens), `notes` (from observacoes), `printed_at` (from impresso_em), `created_at`, `updated_at`

`MedicationResource` maps PT→EN:
- `id`, `name` (from nome), `active_ingredient` (from principio_ativo), `presentation` (from apresentacao), `manufacturer` (from fabricante), `anvisa_code` (from codigo_anvisa), `anvisa_list` (from lista_anvisa), `is_controlled` (from controlado)

`PrescriptionTemplateResource` maps PT→EN:
- `id`, `name` (from nome), `subtype` (from subtipo), `tags`, `items` (from itens), `created_at`, `updated_at`

**Step 3: Create Controllers**

Follow `PatientController` pattern: constructor injection, DTOs at boundary, Gate::authorize, no route model binding.

`PrescriptionController`:
- `index(Request $request, int $medicalRecordId)` → authorize view on prontuário, return collection
- `store(StorePrescriptionRequest $request, int $medicalRecordId)` → authorize create on prontuário, return 201
- `update(UpdatePrescriptionRequest $request, int $medicalRecordId, int $id)` → authorize update on prescription, return resource
- `destroy(Request $request, int $medicalRecordId, int $id)` → authorize delete, return message

`MedicationController`:
- `index(ListMedicationRequest $request)` → return paginated collection
- `show(int $id)` → return resource

`PrescriptionTemplateController`:
- `index(Request $request)` → list for authenticated user, optional `?subtype=` filter
- `store(StorePrescriptionTemplateRequest $request)` → create, return 201
- `update(UpdatePrescriptionTemplateRequest $request, int $id)` → authorize, update
- `destroy(Request $request, int $id)` → authorize, delete

**Step 4: Register routes**

```php
// app/Modules/MedicalRecord/routes.php
Route::middleware('auth:sanctum')->group(function (): void {
    // Medications (read-only catalog)
    Route::get('/medications', [MedicationController::class, 'index']);
    Route::get('/medications/{id}', [MedicationController::class, 'show']);

    // Prescriptions (nested under medical record)
    Route::get('/medical-records/{medicalRecordId}/prescriptions', [PrescriptionController::class, 'index']);
    Route::post('/medical-records/{medicalRecordId}/prescriptions', [PrescriptionController::class, 'store']);
    Route::put('/medical-records/{medicalRecordId}/prescriptions/{id}', [PrescriptionController::class, 'update']);
    Route::delete('/medical-records/{medicalRecordId}/prescriptions/{id}', [PrescriptionController::class, 'destroy']);

    // Prescription templates
    Route::get('/prescription-templates', [PrescriptionTemplateController::class, 'index']);
    Route::post('/prescription-templates', [PrescriptionTemplateController::class, 'store']);
    Route::put('/prescription-templates/{id}', [PrescriptionTemplateController::class, 'update']);
    Route::delete('/prescription-templates/{id}', [PrescriptionTemplateController::class, 'destroy']);
});
```

**Step 5: Commit**

```bash
git add app/Modules/MedicalRecord/Http/ app/Modules/MedicalRecord/routes.php
git commit -m "feat(medical-record): add controllers, requests, resources, and routes for prescriptions"
```

---

## Task 8: Feature Tests — Medications

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/ListMedicationTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ShowMedicationTest.php`

**Step 1: Write tests**

Test scenarios for `ListMedicationTest`:
- `it('lists active medications')` — create 3 active + 1 inactive, expect 3 returned
- `it('searches by name')` — create medications, search by partial name
- `it('searches by active ingredient')` — search by principio_ativo
- `it('filters controlled medications')` — `?controlled=true` returns only those with lista_anvisa
- `it('filters non-controlled medications')` — `?controlled=false`
- `it('paginates results')` — `?per_page=2` with 5 meds, assert meta pagination
- `it('rejects unauthenticated access')` — 401

Test scenarios for `ShowMedicationTest`:
- `it('shows a medication by id')` — assert all fields
- `it('returns 404 for inactive medication')` — inactive meds not found
- `it('returns 404 for nonexistent id')` — 404

**Step 2: Run tests, verify they fail, implement if needed, verify they pass**

Run: `php artisan test app/Modules/MedicalRecord/Tests/Feature/ListMedicationTest.php app/Modules/MedicalRecord/Tests/Feature/ShowMedicationTest.php --compact`

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/ListMedicationTest.php app/Modules/MedicalRecord/Tests/Feature/ShowMedicationTest.php
git commit -m "test(medical-record): add feature tests for medication listing and show"
```

---

## Task 9: Feature Tests — Prescriptions

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/CreatePrescriptionTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/UpdatePrescriptionTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/DeletePrescriptionTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ListPrescriptionTest.php`

**Step 1: Write tests**

Test scenarios for `CreatePrescriptionTest`:
- `it('creates an allopathic prescription')` — basic happy path
- `it('creates a magistral prescription')` — different subtype
- `it('creates an injectable_im prescription')` — injectable subtype
- `it('auto-guesses recipe type from ANVISA list')` — create med with B1, expect blue_b
- `it('respects recipe type override')` — override=true + manual type
- `it('rejects creation on finalized record')` — 409 Conflict
- `it('rejects creation by non-owner')` — 403 Forbidden
- `it('rejects creation with empty items')` — 422
- `it('rejects unauthenticated access')` — 401

Test scenarios for `UpdatePrescriptionTest`:
- `it('updates prescription items')` — change items, verify
- `it('re-guesses recipe type on update')` — change items to controlled, verify type changes
- `it('rejects update on finalized record')` — 409
- `it('rejects update by non-owner')` — 403

Test scenarios for `DeletePrescriptionTest`:
- `it('deletes a prescription from draft record')` — 200, assert deleted
- `it('rejects deletion on finalized record')` — 409
- `it('rejects deletion by non-owner')` — 403

Test scenarios for `ListPrescriptionTest`:
- `it('lists prescriptions for a medical record')` — create 3, list, expect 3
- `it('returns empty for record with no prescriptions')` — expect []
- `it('rejects listing by non-owner')` — 403

**Step 2: Run tests, fix issues, verify all pass**

Run: `php artisan test app/Modules/MedicalRecord/Tests/Feature/ --compact`

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/
git commit -m "test(medical-record): add feature tests for prescription CRUD"
```

---

## Task 10: Feature Tests — Prescription Templates

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/PrescriptionTemplateTest.php`

**Step 1: Write tests**

Test scenarios:
- `it('lists templates for authenticated doctor')` — create 3 for user, 2 for other, expect 3
- `it('filters templates by subtype')` — `?subtype=magistral`
- `it('creates a prescription template')` — 201
- `it('updates own template')` — 200
- `it('deletes own template')` — 200
- `it('rejects update of another doctor template')` — 403
- `it('rejects deletion of another doctor template')` — 403
- `it('rejects unauthenticated access')` — 401

**Step 2: Run tests, fix issues, verify all pass**

Run: `php artisan test app/Modules/MedicalRecord/Tests/Feature/PrescriptionTemplateTest.php --compact`

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/PrescriptionTemplateTest.php
git commit -m "test(medical-record): add feature tests for prescription templates"
```

---

## Task 11: Medication Seeder

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Seeders/MedicationSeeder.php`

**Step 1: Create seeder with curated medication list**

Include ~100-150 medications commonly prescribed, especially controlled ones for ANVISA auto-guess testing. Organize by category:
- Analgésicos/Anti-inflamatórios (Paracetamol, Ibuprofeno, Dipirona, etc.)
- Antibióticos (Amoxicilina, Azitromicina, Cefalexina, etc.)
- Anti-hipertensivos (Losartana, Enalapril, Anlodipino, etc.)
- Hipoglicemiantes (Metformina, Glibenclamida, etc.)
- Psicotrópicos B1 (Clonazepam, Diazepam, Alprazolam, etc.)
- Psicotrópicos B2 (Anfetamina, Metilfenidato, etc.)
- Entorpecentes A1/A2/A3 (Morfina, Codeína, Fentanil, etc.)
- Retinoides C2 (Isotretinoína, etc.)
- Imunossupressores C3 (Talidomida, etc.)
- Antirretrovirais C4
- Anabolizantes C5

Each medication must have: nome, principio_ativo, apresentacao, lista_anvisa (when applicable), controlado (derived from lista_anvisa).

Use `Medicamento::query()->upsert()` with nome+principio_ativo as unique key to be idempotent.

**Step 2: Run seeder**

Run: `php artisan db:seed --class="App\Modules\MedicalRecord\Database\Seeders\MedicationSeeder"`
Expected: Medications seeded successfully

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Seeders/
git commit -m "feat(medical-record): add curated medication seeder with ANVISA classifications"
```

---

## Task 12: Pint + Full Test Suite + Scribe

**Step 1: Run Pint**

Run: `vendor/bin/pint --dirty`

**Step 2: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass

**Step 3: Add Scribe documentation to all controllers**

Add PHPDoc blocks with `@group`, `@authenticated`, `@queryParam`, `@bodyParam`, and `@response` annotations to all controller methods following existing patterns.

Groups:
- `@group Medications`
- `@group Prescriptions`
- `@group Prescription Templates`

**Step 4: Regenerate docs**

Run: `php artisan scribe:generate`

**Step 5: Final commit**

```bash
git add -A
git commit -m "chore(medical-record): format with pint and add scribe documentation"
```

---

## Summary

| Task | What | Estimated Steps |
|------|------|----------------|
| 1 | Module structure + enums | 5 |
| 2 | Migrations (4 tables) | 6 |
| 3 | Models + Factories | 10 |
| 4 | RecipeTypeGuesser + unit tests | 5 |
| 5 | DTOs | 2 |
| 6 | Services + Policies | 5 |
| 7 | Requests + Resources + Controllers + Routes | 5 |
| 8 | Feature tests — Medications | 3 |
| 9 | Feature tests — Prescriptions | 3 |
| 10 | Feature tests — Templates | 3 |
| 11 | Medication seeder | 3 |
| 12 | Pint + tests + Scribe | 5 |
