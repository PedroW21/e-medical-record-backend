# Lab Results Module — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement normalized lab results with catalog/panel read-only endpoints and CRUD for lab values nested under medical records.

**Architecture:** Three new tables (`catalogo_exames_laboratoriais` string PK, `paineis_laboratoriais` string PK, `valores_laboratoriais` bigint PK). Catalog/panels are seeded from frontend data (254 analytes, 46 panels). Lab values support both catalog exams (`catalogo_exame_id` FK) and free-form entries (`nome_avulso`). All nested under `MedicalRecord` module.

**API Format (v2 — LabPanelExamResult):** The frontend sends/receives lab results in panel-grouped format:
```ts
// Input/Output shape
interface LabPanelExamResult {
  date: string
  panels: LabPanelEntry[]   // panel-based results (analytes from catalog)
  loose: LabLooseEntry[]    // free-form results (no catalog binding)
}
interface LabPanelEntry {
  panelId: string
  panelName: string
  isCustom: boolean
  values: { analyteId: string, value: string | number }[]
}
interface LabLooseEntry {
  name: string
  value: string | number
  unit: string
  referenceRange?: string
}
```
The backend explodes panels into individual `valores_laboratoriais` rows (enriching with unit/referenceRange from catalog) and re-groups them on read.

**Tech Stack:** Laravel 12, PostgreSQL, Pest 4, Sanctum auth

---

## API Endpoints Summary

### Catalog (read-only)
- `GET /lab-catalog` — list/search catalog items (paginated, filterable by `category`, `search`)
- `GET /lab-catalog/{id}` — show single catalog item
- `GET /lab-panels` — list all predefined panels
- `GET /lab-panels/{id}` — show panel with subsections/analytes

### Lab Results (nested under medical record)
- `POST /medical-records/{medicalRecordId}/lab-results` — batch store (v2 panel format)
- `GET /medical-records/{medicalRecordId}/lab-results` — list grouped in v2 panel format
- `PUT /medical-records/{medicalRecordId}/lab-results/{id}` — update single value
- `DELETE /medical-records/{medicalRecordId}/lab-results/{id}` — delete single value

---

### Task 1: Enums — LabCategory, LabResultType

**Files:**
- Create: `app/Modules/MedicalRecord/Enums/LabCategory.php`
- Create: `app/Modules/MedicalRecord/Enums/LabResultType.php`

**Step 1: Create LabCategory enum**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum LabCategory: string
{
    case Hematologia = 'hematologia';
    case Bioquimica = 'bioquimica';
    case Endocrinologia = 'endocrinologia';
    case Hormonal = 'hormonal';
    case Imunologia = 'imunologia';
    case Coprologia = 'coprologia';
    case Microbiologia = 'microbiologia';
    case Liquidos = 'liquidos';
    case MarcadoresTumorais = 'marcadores_tumorais';
    case Outros = 'outros';
}
```

**Step 2: Create LabResultType enum**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum LabResultType: string
{
    case Numeric = 'numeric';
    case Qualitative = 'qualitative';
    case Titer = 'titer';
    case Descriptive = 'descriptive';
}
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Enums/LabCategory.php app/Modules/MedicalRecord/Enums/LabResultType.php
git commit -m "feat(medical-record): add LabCategory and LabResultType enums"
```

---

### Task 2: Migration — `catalogo_exames_laboratoriais`

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_13_000001_create_catalogo_exames_laboratoriais_table.php`

**Step 1: Create migration**

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
        Schema::create('catalogo_exames_laboratoriais', function (Blueprint $table): void {
            $table->string('id')->primary(); // e.g. 'hemo-hemoglobina'
            $table->string('nome');
            $table->string('categoria'); // LabCategory enum value
            $table->string('unidade');
            $table->string('faixa_referencia')->nullable();
            $table->string('tipo_resultado')->default('numeric'); // LabResultType enum value
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_exames_laboratoriais');
    }
};
```

**Step 2: Run migration**

Run: `php artisan migrate`

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Migrations/2026_03_13_000001_create_catalogo_exames_laboratoriais_table.php
git commit -m "feat(medical-record): add catalogo_exames_laboratoriais migration"
```

---

### Task 3: Migration — `paineis_laboratoriais`

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_13_000002_create_paineis_laboratoriais_table.php`

**Step 1: Create migration**

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
        Schema::create('paineis_laboratoriais', function (Blueprint $table): void {
            $table->string('id')->primary(); // e.g. 'hemograma-completo'
            $table->string('nome');
            $table->string('categoria'); // LabCategory enum value
            $table->jsonb('subsecoes'); // array of {label, analytes[]}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paineis_laboratoriais');
    }
};
```

**Step 2: Run migration**

Run: `php artisan migrate`

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Migrations/2026_03_13_000002_create_paineis_laboratoriais_table.php
git commit -m "feat(medical-record): add paineis_laboratoriais migration"
```

---

### Task 4: Migration — `valores_laboratoriais`

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_13_000003_create_valores_laboratoriais_table.php`

**Step 1: Create migration**

The key table. Supports both catalog-based and free-form entries via check constraint.

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valores_laboratoriais', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete(); // denormalized for evolution queries
            $table->string('catalogo_exame_id')->nullable();
            $table->string('nome_avulso')->nullable(); // for free-form exams
            $table->date('data_coleta');
            $table->string('valor'); // supports "14.5", "Negativo", "1:320"
            $table->decimal('valor_numerico', 12, 4)->nullable(); // auto-extracted numeric value
            $table->string('unidade');
            $table->string('faixa_referencia')->nullable();
            $table->string('painel_id')->nullable(); // informational — which panel this came from
            $table->timestamps();

            $table->foreign('catalogo_exame_id')->references('id')->on('catalogo_exames_laboratoriais')->nullOnDelete();
            $table->foreign('painel_id')->references('id')->on('paineis_laboratoriais')->nullOnDelete();

            // Evolution query index: patient + exam + date
            $table->index(['paciente_id', 'catalogo_exame_id', 'data_coleta'], 'idx_valores_lab_evolucao');
            $table->index(['prontuario_id', 'data_coleta'], 'idx_valores_lab_prontuario');
        });

        // CHECK constraint: at least one of catalogo_exame_id or nome_avulso must be set
        // Only apply on PostgreSQL (tests may use SQLite)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE valores_laboratoriais ADD CONSTRAINT chk_exame_ou_avulso CHECK (catalogo_exame_id IS NOT NULL OR nome_avulso IS NOT NULL)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('valores_laboratoriais');
    }
};
```

**Step 2: Run migration**

Run: `php artisan migrate`

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Migrations/2026_03_13_000003_create_valores_laboratoriais_table.php
git commit -m "feat(medical-record): add valores_laboratoriais migration with check constraint"
```

---

### Task 5: Models — CatalogoExameLaboratorial, PainelLaboratorial, ValorLaboratorial

**Files:**
- Create: `app/Modules/MedicalRecord/Models/CatalogoExameLaboratorial.php`
- Create: `app/Modules/MedicalRecord/Models/PainelLaboratorial.php`
- Create: `app/Modules/MedicalRecord/Models/ValorLaboratorial.php`
- Modify: `app/Modules/MedicalRecord/Models/Prontuario.php` (add `valoresLaboratoriais` relationship)

**Step 1: Create CatalogoExameLaboratorial model**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\MedicalRecord\Enums\LabCategory;
use App\Modules\MedicalRecord\Enums\LabResultType;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $nome
 * @property LabCategory $categoria
 * @property string $unidade
 * @property string|null $faixa_referencia
 * @property LabResultType $tipo_resultado
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class CatalogoExameLaboratorial extends Model
{
    protected $table = 'catalogo_exames_laboratoriais';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nome',
        'categoria',
        'unidade',
        'faixa_referencia',
        'tipo_resultado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categoria' => LabCategory::class,
            'tipo_resultado' => LabResultType::class,
        ];
    }
}
```

**Step 2: Create PainelLaboratorial model**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\MedicalRecord\Enums\LabCategory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $nome
 * @property LabCategory $categoria
 * @property array<int, array{label: string, analytes: array<int, array<string, mixed>>}> $subsecoes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PainelLaboratorial extends Model
{
    protected $table = 'paineis_laboratoriais';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nome',
        'categoria',
        'subsecoes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categoria' => LabCategory::class,
            'subsecoes' => 'array',
        ];
    }
}
```

**Step 3: Create ValorLaboratorial model**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $prontuario_id
 * @property int $paciente_id
 * @property string|null $catalogo_exame_id
 * @property string|null $nome_avulso
 * @property \Illuminate\Support\Carbon $data_coleta
 * @property string $valor
 * @property float|null $valor_numerico
 * @property string $unidade
 * @property string|null $faixa_referencia
 * @property string|null $painel_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 * @property-read CatalogoExameLaboratorial|null $catalogoExame
 * @property-read PainelLaboratorial|null $painel
 */
class ValorLaboratorial extends Model
{
    use HasFactory;

    protected $table = 'valores_laboratoriais';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'catalogo_exame_id',
        'nome_avulso',
        'data_coleta',
        'valor',
        'valor_numerico',
        'unidade',
        'faixa_referencia',
        'painel_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_coleta' => 'date',
            'valor_numerico' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<Prontuario, $this>
     */
    public function prontuario(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class);
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    /**
     * @return BelongsTo<CatalogoExameLaboratorial, $this>
     */
    public function catalogoExame(): BelongsTo
    {
        return $this->belongsTo(CatalogoExameLaboratorial::class, 'catalogo_exame_id');
    }

    /**
     * @return BelongsTo<PainelLaboratorial, $this>
     */
    public function painel(): BelongsTo
    {
        return $this->belongsTo(PainelLaboratorial::class, 'painel_id');
    }

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\LabResultFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\LabResultFactory::new();
    }
}
```

**Step 4: Add relationship to Prontuario model**

Add `valoresLaboratoriais()` relationship:

```php
/**
 * @return HasMany<ValorLaboratorial, $this>
 */
public function valoresLaboratoriais(): HasMany
{
    return $this->hasMany(ValorLaboratorial::class);
}
```

Also add to the `@property-read` PHPDoc:
```
@property-read \Illuminate\Database\Eloquent\Collection<int, ValorLaboratorial> $valoresLaboratoriais
```

And add the import:
```php
use App\Modules\MedicalRecord\Models\ValorLaboratorial; // (not needed since same namespace)
```

**Step 5: Commit**

```bash
git add app/Modules/MedicalRecord/Models/CatalogoExameLaboratorial.php \
       app/Modules/MedicalRecord/Models/PainelLaboratorial.php \
       app/Modules/MedicalRecord/Models/ValorLaboratorial.php \
       app/Modules/MedicalRecord/Models/Prontuario.php
git commit -m "feat(medical-record): add lab catalog, panel and value models"
```

---

### Task 6: Factory — LabResultFactory

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Factories/LabResultFactory.php`

**Step 1: Create factory**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ValorLaboratorial>
 */
final class LabResultFactory extends Factory
{
    protected $model = ValorLaboratorial::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'catalogo_exame_id' => 'hemo-hemoglobina',
            'nome_avulso' => null,
            'data_coleta' => $this->faker->date(),
            'valor' => (string) $this->faker->randomFloat(1, 10, 18),
            'valor_numerico' => $this->faker->randomFloat(4, 10, 18),
            'unidade' => 'g/dL',
            'faixa_referencia' => '13,5-17,5 (H) / 12,0-16,0 (M)',
            'painel_id' => null,
        ];
    }

    /**
     * Create a free-form (loose) lab result not linked to catalog.
     */
    public function loose(): static
    {
        return $this->state(fn (array $attributes) => [
            'catalogo_exame_id' => null,
            'nome_avulso' => 'Exame avulso ' . $this->faker->word(),
            'unidade' => $this->faker->randomElement(['mg/dL', 'U/L', 'mEq/L']),
            'faixa_referencia' => null,
        ]);
    }

    /**
     * Create a qualitative result.
     */
    public function qualitative(): static
    {
        return $this->state(fn (array $attributes) => [
            'catalogo_exame_id' => null,
            'nome_avulso' => 'VDRL',
            'valor' => $this->faker->randomElement(['Reagente', 'Não reagente']),
            'valor_numerico' => null,
            'unidade' => '-',
            'faixa_referencia' => 'Não reagente',
        ]);
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Factories/LabResultFactory.php
git commit -m "feat(medical-record): add LabResultFactory with loose and qualitative states"
```

---

### Task 7: DTOs — StoreLabResultDTO (v2 panel format)

**Files:**
- Create: `app/Modules/MedicalRecord/DTOs/LabPanelEntryDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/LabPanelValueDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/LabLooseEntryDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/StoreLabResultDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/UpdateLabValueDTO.php`

**Step 1: Create LabPanelValueDTO**

Represents a single analyte value within a panel.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class LabPanelValueDTO
{
    public function __construct(
        public string $analyteId,
        public string $value,
    ) {}

    /**
     * @param array<string, mixed> $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            analyteId: $item['analyte_id'],
            value: (string) $item['value'],
        );
    }
}
```

**Step 2: Create LabPanelEntryDTO**

Represents a panel with its values.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class LabPanelEntryDTO
{
    /**
     * @param array<int, LabPanelValueDTO> $values
     */
    public function __construct(
        public string $panelId,
        public string $panelName,
        public bool $isCustom,
        public array $values,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            panelId: $data['panel_id'],
            panelName: $data['panel_name'],
            isCustom: (bool) ($data['is_custom'] ?? false),
            values: array_map(
                fn (array $v): LabPanelValueDTO => LabPanelValueDTO::fromArray($v),
                $data['values'],
            ),
        );
    }
}
```

**Step 3: Create LabLooseEntryDTO**

Represents a free-form (loose) lab entry without a panel.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class LabLooseEntryDTO
{
    public function __construct(
        public string $name,
        public string $value,
        public string $unit,
        public ?string $referenceRange = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            value: (string) $data['value'],
            unit: $data['unit'],
            referenceRange: $data['reference_range'] ?? null,
        );
    }
}
```

**Step 4: Create StoreLabResultDTO**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\StoreLabResultRequest;

final readonly class StoreLabResultDTO
{
    /**
     * @param array<int, LabPanelEntryDTO> $panels
     * @param array<int, LabLooseEntryDTO> $loose
     */
    public function __construct(
        public string $date,
        public array $panels,
        public array $loose,
    ) {}

    public static function fromRequest(StoreLabResultRequest $request): self
    {
        $validated = $request->validated();

        $panels = array_map(
            fn (array $p): LabPanelEntryDTO => LabPanelEntryDTO::fromArray($p),
            $validated['panels'] ?? [],
        );

        $loose = array_map(
            fn (array $l): LabLooseEntryDTO => LabLooseEntryDTO::fromArray($l),
            $validated['loose'] ?? [],
        );

        return new self(
            date: $validated['date'],
            panels: $panels,
            loose: $loose,
        );
    }
}
```

**Step 5: Create UpdateLabValueDTO** (unchanged — updates single row)

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\UpdateLabValueRequest;

final readonly class UpdateLabValueDTO
{
    public function __construct(
        public ?string $value = null,
        public ?string $unit = null,
        public ?string $referenceRange = null,
        public ?string $collectionDate = null,
        public bool $hasReferenceRange = false,
    ) {}

    public static function fromRequest(UpdateLabValueRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            value: $validated['value'] ?? null,
            unit: $validated['unit'] ?? null,
            referenceRange: $validated['reference_range'] ?? null,
            collectionDate: $validated['collection_date'] ?? null,
            hasReferenceRange: $request->has('reference_range'),
        );
    }
}
```

**Step 6: Commit**

```bash
git add app/Modules/MedicalRecord/DTOs/LabPanelValueDTO.php \
       app/Modules/MedicalRecord/DTOs/LabPanelEntryDTO.php \
       app/Modules/MedicalRecord/DTOs/LabLooseEntryDTO.php \
       app/Modules/MedicalRecord/DTOs/StoreLabResultDTO.php \
       app/Modules/MedicalRecord/DTOs/UpdateLabValueDTO.php
git commit -m "feat(medical-record): add lab result DTOs for v2 panel format"
```

---

### Task 8: Form Requests — StoreLabResultRequest (v2), UpdateLabValueRequest, ListLabCatalogRequest

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Requests/StoreLabResultRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/UpdateLabValueRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/ListLabCatalogRequest.php`

**Step 1: Create StoreLabResultRequest (v2 panel format)**

Validates the `LabPanelExamResult` shape: `{date, panels[], loose[]}`.
At least one panel or one loose entry must be present.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreLabResultRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],

            // Panels (optional array)
            'panels' => ['nullable', 'array'],
            'panels.*.panel_id' => [
                'required',
                'string',
                Rule::exists('paineis_laboratoriais', 'id'),
            ],
            'panels.*.panel_name' => ['required', 'string', 'max:255'],
            'panels.*.is_custom' => ['nullable', 'boolean'],
            'panels.*.values' => ['required', 'array', 'min:1'],
            'panels.*.values.*.analyte_id' => [
                'required',
                'string',
                Rule::exists('catalogo_exames_laboratoriais', 'id'),
            ],
            'panels.*.values.*.value' => ['required', 'string', 'max:255'],

            // Loose entries (optional array)
            'loose' => ['nullable', 'array'],
            'loose.*.name' => ['required', 'string', 'max:255'],
            'loose.*.value' => ['required', 'string', 'max:255'],
            'loose.*.unit' => ['required', 'string', 'max:50'],
            'loose.*.reference_range' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'A data de coleta é obrigatória.',
            'date.date' => 'A data de coleta deve ser uma data válida.',
            'date.before_or_equal' => 'A data de coleta não pode ser futura.',
            'panels.*.panel_id.required' => 'O ID do painel é obrigatório.',
            'panels.*.panel_id.exists' => 'O painel informado não existe.',
            'panels.*.panel_name.required' => 'O nome do painel é obrigatório.',
            'panels.*.values.required' => 'Cada painel deve ter ao menos um valor.',
            'panels.*.values.min' => 'Cada painel deve ter ao menos um valor.',
            'panels.*.values.*.analyte_id.required' => 'O ID do analito é obrigatório.',
            'panels.*.values.*.analyte_id.exists' => 'O analito informado não existe no catálogo.',
            'panels.*.values.*.value.required' => 'O valor do resultado é obrigatório.',
            'loose.*.name.required' => 'O nome do exame avulso é obrigatório.',
            'loose.*.value.required' => 'O valor do resultado é obrigatório.',
            'loose.*.unit.required' => 'A unidade de medida é obrigatória.',
        ];
    }

    /**
     * Custom validation: at least one panel or one loose entry must be present.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $panels = $this->input('panels', []);
            $loose = $this->input('loose', []);

            if (empty($panels) && empty($loose)) {
                $validator->errors()->add(
                    'panels',
                    'É necessário informar ao menos um painel ou um exame avulso.',
                );
            }
        });
    }
}
```

**Step 2: Create UpdateLabValueRequest**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateLabValueRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'value' => ['sometimes', 'string', 'max:255'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'reference_range' => ['nullable', 'string', 'max:255'],
            'collection_date' => ['sometimes', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'value.string' => 'O valor do resultado deve ser um texto.',
            'unit.string' => 'A unidade de medida deve ser um texto.',
            'collection_date.date' => 'A data de coleta deve ser uma data válida.',
            'collection_date.before_or_equal' => 'A data de coleta não pode ser futura.',
        ];
    }
}
```

**Step 3: Create ListLabCatalogRequest**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\LabCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListLabCatalogRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', Rule::in(LabCategory::cases())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.in' => 'A categoria informada é inválida.',
            'per_page.max' => 'O campo itens por página deve ser no máximo 100.',
        ];
    }
}
```

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Requests/StoreLabResultRequest.php \
       app/Modules/MedicalRecord/Http/Requests/UpdateLabValueRequest.php \
       app/Modules/MedicalRecord/Http/Requests/ListLabCatalogRequest.php
git commit -m "feat(medical-record): add lab result form requests with validation"
```

---

### Task 9: Resources — LabCatalogResource, LabPanelResource, LabResultResource, LabResultGroupedResource

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Resources/LabCatalogResource.php`
- Create: `app/Modules/MedicalRecord/Http/Resources/LabPanelResource.php`
- Create: `app/Modules/MedicalRecord/Http/Resources/LabResultResource.php` (single row, for update)
- Create: `app/Modules/MedicalRecord/Http/Resources/LabResultGroupedResource.php` (v2 grouped format)

**Step 1: Create LabCatalogResource**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial
 */
final class LabCatalogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nome,
            'category' => $this->categoria?->value,
            'unit' => $this->unidade,
            'reference_range' => $this->faixa_referencia,
            'result_type' => $this->tipo_resultado?->value,
        ];
    }
}
```

**Step 2: Create LabPanelResource**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\PainelLaboratorial
 */
final class LabPanelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nome,
            'category' => $this->categoria?->value,
            'subsections' => $this->subsecoes,
        ];
    }
}
```

**Step 3: Create LabResultResource (single row — used for update/delete responses)**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\MedicalRecord\Models\ValorLaboratorial
 */
final class LabResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medical_record_id' => $this->prontuario_id,
            'patient_id' => $this->paciente_id,
            'catalog_exam_id' => $this->catalogo_exame_id,
            'name' => $this->nome_avulso ?? $this->whenLoaded('catalogoExame', fn () => $this->catalogoExame?->nome),
            'collection_date' => $this->data_coleta->format('Y-m-d'),
            'value' => $this->valor,
            'numeric_value' => $this->valor_numerico,
            'unit' => $this->unidade,
            'reference_range' => $this->faixa_referencia,
            'panel_id' => $this->painel_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

**Step 4: Create LabResultGroupedResource (v2 panel format — used for list/store responses)**

This resource groups `ValorLaboratorial` rows back into the `LabPanelExamResult` shape.
It is NOT a JsonResource — it's a utility class that takes a Collection and produces the grouped array.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use Illuminate\Database\Eloquent\Collection;

/**
 * Groups ValorLaboratorial rows into LabPanelExamResult v2 format.
 *
 * Input: Collection<ValorLaboratorial> (all for one medical record)
 * Output: array of {date, panels[{panel_id, panel_name, is_custom, values[{id, analyte_id, value}]}], loose[{id, name, value, unit, reference_range}]}
 */
final class LabResultGroupedResource
{
    /**
     * Group lab values by date, then by panel vs loose.
     *
     * @param Collection<int, \App\Modules\MedicalRecord\Models\ValorLaboratorial> $values
     * @return array<int, array{date: string, panels: array, loose: array}>
     */
    public static function fromCollection(Collection $values): array
    {
        // Group by collection date
        $byDate = $values->groupBy(fn ($v) => $v->data_coleta->format('Y-m-d'));

        $results = [];

        foreach ($byDate as $date => $dateValues) {
            // Separate panel vs loose
            $panelValues = $dateValues->whereNotNull('painel_id');
            $looseValues = $dateValues->whereNull('painel_id')->whereNotNull('nome_avulso');

            // Group panel values by panel_id
            $panels = [];
            foreach ($panelValues->groupBy('painel_id') as $panelId => $pValues) {
                $panel = $pValues->first()->painel;
                $panels[] = [
                    'panel_id' => $panelId,
                    'panel_name' => $panel?->nome ?? $panelId,
                    'is_custom' => false, // predefined panels only for now
                    'values' => $pValues->map(fn ($v) => [
                        'id' => $v->id,
                        'analyte_id' => $v->catalogo_exame_id,
                        'value' => $v->valor,
                    ])->values()->all(),
                ];
            }

            // Loose entries
            $loose = $looseValues->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->nome_avulso,
                'value' => $v->valor,
                'unit' => $v->unidade,
                'reference_range' => $v->faixa_referencia,
            ])->values()->all();

            $results[] = [
                'date' => $date,
                'panels' => $panels,
                'loose' => $loose,
            ];
        }

        return $results;
    }
}
```

**Step 5: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Resources/LabCatalogResource.php \
       app/Modules/MedicalRecord/Http/Resources/LabPanelResource.php \
       app/Modules/MedicalRecord/Http/Resources/LabResultResource.php \
       app/Modules/MedicalRecord/Http/Resources/LabResultGroupedResource.php
git commit -m "feat(medical-record): add lab resources with v2 grouped format"
```

---

### Task 10: Service — LabResultService (v2 panel format), LabCatalogService

**Files:**
- Create: `app/Modules/MedicalRecord/Services/LabCatalogService.php`
- Create: `app/Modules/MedicalRecord/Services/LabResultService.php`

**Step 1: Create LabCatalogService** (unchanged)

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;
use App\Modules\MedicalRecord\Models\PainelLaboratorial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class LabCatalogService
{
    /**
     * @return LengthAwarePaginator<CatalogoExameLaboratorial>
     */
    public function listCatalog(?string $search, ?string $category, int $perPage = 15): LengthAwarePaginator
    {
        $query = CatalogoExameLaboratorial::query()->orderBy('nome');

        if ($search) {
            $query->where('nome', 'ilike', "%{$search}%");
        }

        if ($category) {
            $query->where('categoria', $category);
        }

        return $query->paginate($perPage);
    }

    public function findCatalogOrFail(string $id): CatalogoExameLaboratorial
    {
        $exam = CatalogoExameLaboratorial::query()->find($id);

        if (! $exam) {
            throw new NotFoundHttpException('Exame laboratorial não encontrado no catálogo.');
        }

        return $exam;
    }

    /**
     * @return Collection<int, PainelLaboratorial>
     */
    public function listPanels(?string $category = null): Collection
    {
        $query = PainelLaboratorial::query()->orderBy('nome');

        if ($category) {
            $query->where('categoria', $category);
        }

        return $query->get();
    }

    public function findPanelOrFail(string $id): PainelLaboratorial
    {
        $panel = PainelLaboratorial::query()->find($id);

        if (! $panel) {
            throw new NotFoundHttpException('Painel laboratorial não encontrado.');
        }

        return $panel;
    }
}
```

**Step 2: Create LabResultService (v2 panel format)**

Key changes from v1:
- `batchStore()` now processes `panels[]` and `loose[]` separately
- Panel values: looks up catalog for unit/referenceRange, stores with `catalogo_exame_id` + `painel_id`
- Loose values: stores with `nome_avulso` + provided unit/referenceRange
- `listByMedicalRecord()` now eager-loads `painel` for grouping

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\LabLooseEntryDTO;
use App\Modules\MedicalRecord\DTOs\LabPanelEntryDTO;
use App\Modules\MedicalRecord\DTOs\StoreLabResultDTO;
use App\Modules\MedicalRecord\DTOs\UpdateLabValueDTO;
use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class LabResultService
{
    public function findMedicalRecordOrFail(int $medicalRecordId): Prontuario
    {
        $prontuario = Prontuario::query()->find($medicalRecordId);

        if (! $prontuario) {
            throw new NotFoundHttpException('Prontuário não encontrado.');
        }

        return $prontuario;
    }

    /**
     * List lab values for a medical record, with relationships needed for v2 grouping.
     *
     * @return Collection<int, ValorLaboratorial>
     */
    public function listByMedicalRecord(int $medicalRecordId): Collection
    {
        return ValorLaboratorial::query()
            ->with(['catalogoExame', 'painel'])
            ->where('prontuario_id', $medicalRecordId)
            ->orderByDesc('data_coleta')
            ->orderBy('id')
            ->get();
    }

    /**
     * Batch store lab values from v2 panel format.
     *
     * Explodes panels into individual catalog rows and stores loose entries directly.
     *
     * @return Collection<int, ValorLaboratorial>
     */
    public function batchStore(int $medicalRecordId, StoreLabResultDTO $dto): Collection
    {
        $prontuario = $this->findMedicalRecordOrFail($medicalRecordId);
        $this->ensureDraft($prontuario);

        return DB::transaction(function () use ($prontuario, $dto): Collection {
            $created = new Collection();

            // Process panel entries — look up catalog for unit/referenceRange
            foreach ($dto->panels as $panelEntry) {
                $created = $created->merge(
                    $this->storePanelEntry($prontuario, $dto->date, $panelEntry),
                );
            }

            // Process loose entries — use provided unit/referenceRange directly
            foreach ($dto->loose as $looseEntry) {
                $created->push(
                    $this->storeLooseEntry($prontuario, $dto->date, $looseEntry),
                );
            }

            return $created->load(['catalogoExame', 'painel']);
        });
    }

    /**
     * Store all analyte values from a single panel.
     *
     * @return Collection<int, ValorLaboratorial>
     */
    private function storePanelEntry(Prontuario $prontuario, string $date, LabPanelEntryDTO $panel): Collection
    {
        $created = new Collection();

        // Pre-load all catalog items for this panel's analytes in one query
        $analyteIds = array_map(fn ($v) => $v->analyteId, $panel->values);
        $catalogItems = CatalogoExameLaboratorial::query()
            ->whereIn('id', $analyteIds)
            ->get()
            ->keyBy('id');

        foreach ($panel->values as $value) {
            $catalog = $catalogItems->get($value->analyteId);
            $numericValue = $this->extractNumericValue($value->value);

            $labValue = ValorLaboratorial::query()->create([
                'prontuario_id' => $prontuario->id,
                'paciente_id' => $prontuario->paciente_id,
                'catalogo_exame_id' => $value->analyteId,
                'nome_avulso' => null,
                'data_coleta' => $date,
                'valor' => $value->value,
                'valor_numerico' => $numericValue,
                'unidade' => $catalog?->unidade ?? '',
                'faixa_referencia' => $catalog?->faixa_referencia,
                'painel_id' => $panel->panelId,
            ]);

            $created->push($labValue);
        }

        return $created;
    }

    /**
     * Store a single loose (free-form) lab entry.
     */
    private function storeLooseEntry(Prontuario $prontuario, string $date, LabLooseEntryDTO $entry): ValorLaboratorial
    {
        $numericValue = $this->extractNumericValue($entry->value);

        return ValorLaboratorial::query()->create([
            'prontuario_id' => $prontuario->id,
            'paciente_id' => $prontuario->paciente_id,
            'catalogo_exame_id' => null,
            'nome_avulso' => $entry->name,
            'data_coleta' => $date,
            'valor' => $entry->value,
            'valor_numerico' => $numericValue,
            'unidade' => $entry->unit,
            'faixa_referencia' => $entry->referenceRange,
            'painel_id' => null,
        ]);
    }

    public function update(int $labValueId, UpdateLabValueDTO $dto): ValorLaboratorial
    {
        $labValue = $this->findOrFail($labValueId);
        $this->ensureDraft($labValue->prontuario);

        $data = [];

        if ($dto->value !== null) {
            $data['valor'] = $dto->value;
            $data['valor_numerico'] = $this->extractNumericValue($dto->value);
        }

        if ($dto->unit !== null) {
            $data['unidade'] = $dto->unit;
        }

        if ($dto->hasReferenceRange) {
            $data['faixa_referencia'] = $dto->referenceRange;
        }

        if ($dto->collectionDate !== null) {
            $data['data_coleta'] = $dto->collectionDate;
        }

        $labValue->update($data);

        return $labValue->fresh()->load('catalogoExame');
    }

    public function delete(int $labValueId): void
    {
        $labValue = $this->findOrFail($labValueId);
        $this->ensureDraft($labValue->prontuario);

        $labValue->delete();
    }

    public function findOrFail(int $labValueId): ValorLaboratorial
    {
        $labValue = ValorLaboratorial::query()->with('prontuario')->find($labValueId);

        if (! $labValue) {
            throw new NotFoundHttpException('Valor laboratorial não encontrado.');
        }

        return $labValue;
    }

    public function findForMedicalRecordOrFail(int $labValueId, int $medicalRecordId): ValorLaboratorial
    {
        $labValue = $this->findOrFail($labValueId);

        if ($labValue->prontuario_id !== $medicalRecordId) {
            throw new NotFoundHttpException('Valor laboratorial não encontrado.');
        }

        return $labValue;
    }

    /**
     * Extract numeric value from string result (e.g. "14.5" -> 14.5, "Negativo" -> null).
     */
    private function extractNumericValue(string $value): ?float
    {
        $normalized = str_replace(',', '.', trim($value));

        if (is_numeric($normalized)) {
            return (float) $normalized;
        }

        return null;
    }

    private function ensureDraft(Prontuario $prontuario): void
    {
        if (! $prontuario->isDraft()) {
            throw new ConflictHttpException('Não é possível modificar resultados laboratoriais de um prontuário finalizado.');
        }
    }
}
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Services/LabCatalogService.php \
       app/Modules/MedicalRecord/Services/LabResultService.php
git commit -m "feat(medical-record): add LabCatalogService and LabResultService with v2 panel format"
```

---

### Task 11: Policy — LabResultPolicy

**Files:**
- Create: `app/Modules/MedicalRecord/Policies/LabResultPolicy.php`
- Modify: `app/Modules/MedicalRecord/Providers/MedicalRecordServiceProvider.php` (register policy)

**Step 1: Create LabResultPolicy**

Follow the exact same pattern as `PrescriptionPolicy`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

final class LabResultPolicy
{
    public function view(User $user, ValorLaboratorial $labResult): bool
    {
        return $user->id === $labResult->prontuario->user_id;
    }

    public function create(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    public function update(User $user, ValorLaboratorial $labResult): bool
    {
        return $user->id === $labResult->prontuario->user_id;
    }

    public function delete(User $user, ValorLaboratorial $labResult): bool
    {
        return $user->id === $labResult->prontuario->user_id;
    }
}
```

**Step 2: Register policy in MedicalRecordServiceProvider**

Add to the `boot()` method:

```php
Gate::policy(ValorLaboratorial::class, LabResultPolicy::class);
```

Add imports:
```php
use App\Modules\MedicalRecord\Models\ValorLaboratorial;
use App\Modules\MedicalRecord\Policies\LabResultPolicy;
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Policies/LabResultPolicy.php \
       app/Modules/MedicalRecord/Providers/MedicalRecordServiceProvider.php
git commit -m "feat(medical-record): add LabResultPolicy and register it"
```

---

### Task 12: Controllers — LabCatalogController, LabResultController (v2 format)

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Controllers/LabCatalogController.php`
- Create: `app/Modules/MedicalRecord/Http/Controllers/LabResultController.php`

**Step 1: Create LabCatalogController** (unchanged from v1)

Follow `MedicationController` pattern — read-only catalog.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\Http\Requests\ListLabCatalogRequest;
use App\Modules\MedicalRecord\Http\Resources\LabCatalogResource;
use App\Modules\MedicalRecord\Http\Resources\LabPanelResource;
use App\Modules\MedicalRecord\Services\LabCatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class LabCatalogController
{
    public function __construct(
        private readonly LabCatalogService $labCatalogService,
    ) {}

    /**
     * List lab exam catalog items.
     *
     * Returns a paginated list of available lab exams, optionally filtered by name or category.
     *
     * @authenticated
     * @group Lab Catalog
     *
     * @queryParam search string Filter by exam name. Example: Hemoglobina
     * @queryParam category string Filter by category. Example: hematologia
     * @queryParam per_page int Number of items per page (max 100). Example: 15
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": "hemo-hemoglobina",
     *       "name": "Hemoglobina",
     *       "category": "hematologia",
     *       "unit": "g/dL",
     *       "reference_range": "13,5-17,5 (H) / 12,0-16,0 (M)",
     *       "result_type": "numeric"
     *     }
     *   ],
     *   "links": {"first": "...", "last": "...", "prev": null, "next": null},
     *   "meta": {"current_page": 1, "from": 1, "last_page": 1, "per_page": 15, "to": 1, "total": 254}
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     */
    public function indexCatalog(ListLabCatalogRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $catalog = $this->labCatalogService->listCatalog(
            search: $validated['search'] ?? null,
            category: $validated['category'] ?? null,
            perPage: (int) ($validated['per_page'] ?? 15),
        );

        return LabCatalogResource::collection($catalog);
    }

    /**
     * Get a single lab exam from the catalog.
     *
     * @authenticated
     * @group Lab Catalog
     *
     * @urlParam id string required The catalog exam ID. Example: hemo-hemoglobina
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": "hemo-hemoglobina",
     *     "name": "Hemoglobina",
     *     "category": "hematologia",
     *     "unit": "g/dL",
     *     "reference_range": "13,5-17,5 (H) / 12,0-16,0 (M)",
     *     "result_type": "numeric"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 404 scenario="Not Found" {"message": "Exame laboratorial não encontrado no catálogo."}
     */
    public function showCatalog(string $id): LabCatalogResource
    {
        $exam = $this->labCatalogService->findCatalogOrFail($id);

        return new LabCatalogResource($exam);
    }

    /**
     * List all predefined lab panels.
     *
     * Returns all predefined lab panels with their subsections and analytes.
     *
     * @authenticated
     * @group Lab Catalog
     *
     * @queryParam category string Filter by category. Example: hematologia
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": "hemograma-completo",
     *       "name": "Hemograma Completo",
     *       "category": "hematologia",
     *       "subsections": [
     *         {
     *           "label": "Série Vermelha",
     *           "analytes": [
     *             {"id": "hemo-hemacias", "name": "Hemácias", "unit": "milhões/mm³", "resultType": "numeric", "referenceRange": "4,5-5,9 (H) / 4,0-5,2 (M)"}
     *           ]
     *         }
     *       ]
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     */
    public function indexPanels(ListLabCatalogRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $panels = $this->labCatalogService->listPanels($validated['category'] ?? null);

        return LabPanelResource::collection($panels);
    }

    /**
     * Get a single lab panel with its analytes.
     *
     * @authenticated
     * @group Lab Catalog
     *
     * @urlParam id string required The panel ID. Example: hemograma-completo
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": "hemograma-completo",
     *     "name": "Hemograma Completo",
     *     "category": "hematologia",
     *     "subsections": [
     *       {
     *         "label": "Série Vermelha",
     *         "analytes": [
     *           {"id": "hemo-hemacias", "name": "Hemácias", "unit": "milhões/mm³", "resultType": "numeric", "referenceRange": "4,5-5,9 (H) / 4,0-5,2 (M)"}
     *         ]
     *       }
     *     ]
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 404 scenario="Not Found" {"message": "Painel laboratorial não encontrado."}
     */
    public function showPanel(string $id): LabPanelResource
    {
        $panel = $this->labCatalogService->findPanelOrFail($id);

        return new LabPanelResource($panel);
    }
}
```

**Step 2: Create LabResultController (v2 panel format)**

Key changes:
- `index()` returns v2 grouped format via `LabResultGroupedResource`
- `store()` accepts v2 panel format `{date, panels[], loose[]}`
- `update()` and `destroy()` are unchanged (operate on individual rows)

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\DTOs\StoreLabResultDTO;
use App\Modules\MedicalRecord\DTOs\UpdateLabValueDTO;
use App\Modules\MedicalRecord\Http\Requests\StoreLabResultRequest;
use App\Modules\MedicalRecord\Http\Requests\UpdateLabValueRequest;
use App\Modules\MedicalRecord\Http\Resources\LabResultGroupedResource;
use App\Modules\MedicalRecord\Http\Resources\LabResultResource;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;
use App\Modules\MedicalRecord\Services\LabResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class LabResultController
{
    public function __construct(
        private readonly LabResultService $labResultService,
    ) {}

    /**
     * List all lab results for a medical record (v2 panel format).
     *
     * Returns lab results grouped by date, with panel-based and loose entries separated.
     *
     * @authenticated
     * @group Lab Results
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "date": "2026-03-10",
     *       "panels": [
     *         {
     *           "panel_id": "hemograma-completo",
     *           "panel_name": "Hemograma Completo",
     *           "is_custom": false,
     *           "values": [
     *             {"id": 1, "analyte_id": "hemo-hemoglobina", "value": "14.5"},
     *             {"id": 2, "analyte_id": "hemo-hemacias", "value": "4.8"}
     *           ]
     *         }
     *       ],
     *       "loose": [
     *         {"id": 3, "name": "Exame especial", "value": "Negativo", "unit": "-", "reference_range": null}
     *       ]
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     */
    public function index(Request $request, int $medicalRecordId): JsonResponse
    {
        $prontuario = $this->labResultService->findMedicalRecordOrFail($medicalRecordId);

        Gate::authorize('view', $prontuario);

        $results = $this->labResultService->listByMedicalRecord($medicalRecordId);
        $grouped = LabResultGroupedResource::fromCollection($results);

        return response()->json(['data' => $grouped]);
    }

    /**
     * Batch store lab results for a medical record (v2 panel format).
     *
     * Accepts panel-grouped and loose entries. Panel values are enriched with
     * unit/referenceRange from the catalog. All values are stored as individual rows.
     *
     * @authenticated
     * @group Lab Results
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     *
     * @bodyParam date string required The date of sample collection. Example: 2026-03-10
     * @bodyParam panels array Panels with their analyte values.
     * @bodyParam panels[].panel_id string required Panel ID. Example: hemograma-completo
     * @bodyParam panels[].panel_name string required Panel display name. Example: Hemograma Completo
     * @bodyParam panels[].is_custom boolean Whether this is a custom panel. Example: false
     * @bodyParam panels[].values array required Analyte values (min 1).
     * @bodyParam panels[].values[].analyte_id string required Catalog analyte ID. Example: hemo-hemoglobina
     * @bodyParam panels[].values[].value string required The result value. Example: 14.5
     * @bodyParam loose array Free-form entries not linked to a panel.
     * @bodyParam loose[].name string required Exam name. Example: Exame especial XYZ
     * @bodyParam loose[].value string required The result value. Example: Negativo
     * @bodyParam loose[].unit string required The measurement unit. Example: -
     * @bodyParam loose[].reference_range string Reference range. Example: Não reagente
     *
     * @response 201 scenario="Created" {
     *   "data": [
     *     {
     *       "date": "2026-03-10",
     *       "panels": [
     *         {
     *           "panel_id": "hemograma-completo",
     *           "panel_name": "Hemograma Completo",
     *           "is_custom": false,
     *           "values": [{"id": 1, "analyte_id": "hemo-hemoglobina", "value": "14.5"}]
     *         }
     *       ],
     *       "loose": [
     *         {"id": 2, "name": "Exame especial XYZ", "value": "Negativo", "unit": "-", "reference_range": null}
     *       ]
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar resultados laboratoriais de um prontuário finalizado."}
     * @response 422 scenario="Validation Error" {"message": "A data de coleta é obrigatória.", "errors": {"date": ["A data de coleta é obrigatória."]}}
     */
    public function store(StoreLabResultRequest $request, int $medicalRecordId): JsonResponse
    {
        $prontuario = $this->labResultService->findMedicalRecordOrFail($medicalRecordId);

        Gate::authorize('create', [ValorLaboratorial::class, $prontuario]);

        $dto = StoreLabResultDTO::fromRequest($request);
        $results = $this->labResultService->batchStore($medicalRecordId, $dto);
        $grouped = LabResultGroupedResource::fromCollection($results);

        return response()->json(['data' => $grouped], 201);
    }

    /**
     * Update a single lab result value.
     *
     * @authenticated
     * @group Lab Results
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam id int required The lab value ID. Example: 1
     *
     * @bodyParam value string The result value. Example: 15.2
     * @bodyParam unit string The measurement unit. Example: g/dL
     * @bodyParam reference_range string nullable Reference range. Example: 13,5-17,5
     * @bodyParam collection_date string The collection date. Example: 2026-03-11
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "medical_record_id": 1,
     *     "patient_id": 5,
     *     "catalog_exam_id": "hemo-hemoglobina",
     *     "name": "Hemoglobina",
     *     "collection_date": "2026-03-11",
     *     "value": "15.2",
     *     "numeric_value": "15.2000",
     *     "unit": "g/dL",
     *     "reference_range": "13,5-17,5",
     *     "panel_id": "hemograma-completo",
     *     "created_at": "2026-03-13T10:00:00.000000Z",
     *     "updated_at": "2026-03-13T10:30:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Valor laboratorial não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar resultados laboratoriais de um prontuário finalizado."}
     */
    public function update(UpdateLabValueRequest $request, int $medicalRecordId, int $id): LabResultResource
    {
        $labValue = $this->labResultService->findForMedicalRecordOrFail($id, $medicalRecordId);

        Gate::authorize('update', $labValue);

        $dto = UpdateLabValueDTO::fromRequest($request);
        $labValue = $this->labResultService->update($id, $dto);

        return new LabResultResource($labValue);
    }

    /**
     * Delete a single lab result value.
     *
     * @authenticated
     * @group Lab Results
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam id int required The lab value ID. Example: 1
     *
     * @response 200 scenario="Success" {"message": "Valor laboratorial excluído com sucesso."}
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Valor laboratorial não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar resultados laboratoriais de um prontuário finalizado."}
     */
    public function destroy(Request $request, int $medicalRecordId, int $id): JsonResponse
    {
        $labValue = $this->labResultService->findForMedicalRecordOrFail($id, $medicalRecordId);

        Gate::authorize('delete', $labValue);

        $this->labResultService->delete($id);

        return response()->json(['message' => 'Valor laboratorial excluído com sucesso.']);
    }
}
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Controllers/LabCatalogController.php \
       app/Modules/MedicalRecord/Http/Controllers/LabResultController.php
git commit -m "feat(medical-record): add LabCatalogController and LabResultController with v2 panel format"
```

---

### Task 13: Routes

**Files:**
- Modify: `app/Modules/MedicalRecord/routes.php`

**Step 1: Add lab catalog and lab result routes**

Add these imports at the top:
```php
use App\Modules\MedicalRecord\Http\Controllers\LabCatalogController;
use App\Modules\MedicalRecord\Http\Controllers\LabResultController;
```

Add inside the `auth:sanctum` middleware group:
```php
    // Lab Catalog (read-only)
    Route::get('/lab-catalog', [LabCatalogController::class, 'indexCatalog']);
    Route::get('/lab-catalog/{id}', [LabCatalogController::class, 'showCatalog']);
    Route::get('/lab-panels', [LabCatalogController::class, 'indexPanels']);
    Route::get('/lab-panels/{id}', [LabCatalogController::class, 'showPanel']);

    // Lab Results (nested under medical record)
    Route::get('/medical-records/{medicalRecordId}/lab-results', [LabResultController::class, 'index']);
    Route::post('/medical-records/{medicalRecordId}/lab-results', [LabResultController::class, 'store']);
    Route::put('/medical-records/{medicalRecordId}/lab-results/{id}', [LabResultController::class, 'update']);
    Route::delete('/medical-records/{medicalRecordId}/lab-results/{id}', [LabResultController::class, 'destroy']);
```

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/routes.php
git commit -m "feat(medical-record): add lab catalog and lab result routes"
```

---

### Task 14: Seeders — LabCatalogSeeder, LabPanelSeeder

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Seeders/LabCatalogSeeder.php`
- Create: `app/Modules/MedicalRecord/Database/Seeders/LabPanelSeeder.php`

**Step 1: Create LabCatalogSeeder**

Port the 254 catalog items from `e-medical-record-frontend/src/modules/medical-records/types/defaults/lab-catalog-data.ts`.

Use `upsert` with string PK matching frontend IDs exactly.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Seeders;

use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;
use Illuminate\Database\Seeder;

final class LabCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Data ported from frontend: lab-catalog-data.ts (254 items)
        // Each entry matches the frontend ID exactly for cross-system compatibility
        $exams = [
            // === Hematologia ===
            ['id' => 'hemo-hemacias', 'nome' => 'Hemácias', 'categoria' => 'hematologia', 'unidade' => 'milhões/mm³', 'faixa_referencia' => '4,5-6,1 (H) / 4,0-5,4 (M)', 'tipo_resultado' => 'numeric'],
            // ... (all 254 items from the frontend data file)
            // NOTE TO IMPLEMENTER: Port ALL items from the frontend file at:
            // e-medical-record-frontend/src/modules/medical-records/types/defaults/lab-catalog-data.ts
        ];

        CatalogoExameLaboratorial::query()->upsert(
            $exams,
            uniqueBy: ['id'],
            update: ['nome', 'categoria', 'unidade', 'faixa_referencia', 'tipo_resultado'],
        );
    }
}
```

**Step 2: Create LabPanelSeeder**

Port the ~46 panels from `e-medical-record-frontend/src/modules/medical-records/types/defaults/lab-panel-definitions.ts`.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Seeders;

use App\Modules\MedicalRecord\Models\PainelLaboratorial;
use Illuminate\Database\Seeder;

final class LabPanelSeeder extends Seeder
{
    public function run(): void
    {
        // Data ported from frontend: lab-panel-definitions.ts (~46 panels)
        $panels = [
            [
                'id' => 'hemograma-completo',
                'nome' => 'Hemograma Completo',
                'categoria' => 'hematologia',
                'subsecoes' => json_encode([/* subsections from frontend */]),
            ],
            // ... (all panels from the frontend data file)
            // NOTE TO IMPLEMENTER: Port ALL panels from the frontend file at:
            // e-medical-record-frontend/src/modules/medical-records/types/defaults/lab-panel-definitions.ts
        ];

        PainelLaboratorial::query()->upsert(
            $panels,
            uniqueBy: ['id'],
            update: ['nome', 'categoria', 'subsecoes'],
        );
    }
}
```

**Step 3: Register seeders in DatabaseSeeder**

Check `database/seeders/DatabaseSeeder.php` and add the new seeders.

**Step 4: Run seeders**

Run: `php artisan db:seed --class="App\Modules\MedicalRecord\Database\Seeders\LabCatalogSeeder"`
Run: `php artisan db:seed --class="App\Modules\MedicalRecord\Database\Seeders\LabPanelSeeder"`

**Step 5: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Seeders/LabCatalogSeeder.php \
       app/Modules/MedicalRecord/Database/Seeders/LabPanelSeeder.php \
       database/seeders/DatabaseSeeder.php
git commit -m "feat(medical-record): add lab catalog and panel seeders from frontend data"
```

---

### Task 15: Tests — Lab Catalog (List, Show, Panels)

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/ListLabCatalogTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ShowLabCatalogTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ListLabPanelTest.php`

**Step 1: Create ListLabCatalogTest**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;

it('lists lab catalog items', function (): void {
    $doctor = User::factory()->doctor()->create();
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina',
        'nome' => 'Hemoglobina',
        'categoria' => 'hematologia',
        'unidade' => 'g/dL',
        'faixa_referencia' => '13,5-17,5',
        'tipo_resultado' => 'numeric',
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-catalog');

    $response->assertOk()
        ->assertJsonPath('data.0.id', 'hemo-hemoglobina')
        ->assertJsonPath('data.0.name', 'Hemoglobina')
        ->assertJsonPath('data.0.category', 'hematologia');
});

it('filters lab catalog by category', function (): void {
    $doctor = User::factory()->doctor()->create();
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina', 'nome' => 'Hemoglobina', 'categoria' => 'hematologia',
        'unidade' => 'g/dL', 'tipo_resultado' => 'numeric',
    ]);
    CatalogoExameLaboratorial::query()->create([
        'id' => 'bio-glicemia', 'nome' => 'Glicemia de jejum', 'categoria' => 'bioquimica',
        'unidade' => 'mg/dL', 'tipo_resultado' => 'numeric',
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-catalog?category=hematologia');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'hemo-hemoglobina');
});

it('searches lab catalog by name', function (): void {
    $doctor = User::factory()->doctor()->create();
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina', 'nome' => 'Hemoglobina', 'categoria' => 'hematologia',
        'unidade' => 'g/dL', 'tipo_resultado' => 'numeric',
    ]);
    CatalogoExameLaboratorial::query()->create([
        'id' => 'bio-glicemia', 'nome' => 'Glicemia de jejum', 'categoria' => 'bioquimica',
        'unidade' => 'mg/dL', 'tipo_resultado' => 'numeric',
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-catalog?search=glicemia');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', 'bio-glicemia');
});

it('rejects unauthenticated access', function (): void {
    $this->getJson('/api/lab-catalog')->assertUnauthorized();
});
```

**Step 2: Create ShowLabCatalogTest**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;

it('shows a single lab catalog item', function (): void {
    $doctor = User::factory()->doctor()->create();
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina', 'nome' => 'Hemoglobina', 'categoria' => 'hematologia',
        'unidade' => 'g/dL', 'faixa_referencia' => '13,5-17,5', 'tipo_resultado' => 'numeric',
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-catalog/hemo-hemoglobina');

    $response->assertOk()
        ->assertJsonPath('data.id', 'hemo-hemoglobina')
        ->assertJsonPath('data.name', 'Hemoglobina')
        ->assertJsonPath('data.unit', 'g/dL');
});

it('returns 404 for non-existent catalog item', function (): void {
    $doctor = User::factory()->doctor()->create();

    $this->actingAs($doctor)->getJson('/api/lab-catalog/non-existent')->assertNotFound();
});
```

**Step 3: Create ListLabPanelTest**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\PainelLaboratorial;

it('lists lab panels', function (): void {
    $doctor = User::factory()->doctor()->create();
    PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo', 'nome' => 'Hemograma Completo', 'categoria' => 'hematologia',
        'subsecoes' => [['label' => 'Série Vermelha', 'analytes' => [['id' => 'hemo-hemacias', 'name' => 'Hemácias']]]],
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-panels');

    $response->assertOk()
        ->assertJsonPath('data.0.id', 'hemograma-completo')
        ->assertJsonPath('data.0.name', 'Hemograma Completo')
        ->assertJsonPath('data.0.subsections.0.label', 'Série Vermelha');
});

it('shows a single lab panel', function (): void {
    $doctor = User::factory()->doctor()->create();
    PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo', 'nome' => 'Hemograma Completo', 'categoria' => 'hematologia',
        'subsecoes' => [['label' => 'Série Vermelha', 'analytes' => []]],
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-panels/hemograma-completo');

    $response->assertOk()->assertJsonPath('data.id', 'hemograma-completo');
});

it('returns 404 for non-existent panel', function (): void {
    $doctor = User::factory()->doctor()->create();

    $this->actingAs($doctor)->getJson('/api/lab-panels/non-existent')->assertNotFound();
});

it('filters panels by category', function (): void {
    $doctor = User::factory()->doctor()->create();
    PainelLaboratorial::query()->create([
        'id' => 'hemograma', 'nome' => 'Hemograma', 'categoria' => 'hematologia',
        'subsecoes' => [],
    ]);
    PainelLaboratorial::query()->create([
        'id' => 'lipidios', 'nome' => 'Lipídios', 'categoria' => 'bioquimica',
        'subsecoes' => [],
    ]);

    $response = $this->actingAs($doctor)->getJson('/api/lab-panels?category=hematologia');

    $response->assertOk()->assertJsonCount(1, 'data');
});
```

**Step 4: Run tests**

Run: `php artisan test app/Modules/MedicalRecord/Tests/Feature/ListLabCatalogTest.php app/Modules/MedicalRecord/Tests/Feature/ShowLabCatalogTest.php app/Modules/MedicalRecord/Tests/Feature/ListLabPanelTest.php --compact`

**Step 5: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/ListLabCatalogTest.php \
       app/Modules/MedicalRecord/Tests/Feature/ShowLabCatalogTest.php \
       app/Modules/MedicalRecord/Tests/Feature/ListLabPanelTest.php
git commit -m "test(medical-record): add lab catalog and panel endpoint tests"
```

---

### Task 16: Tests — Lab Results CRUD (Store v2, List v2, Update, Delete)

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/StoreLabResultTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ListLabResultTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/UpdateLabResultTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/DeleteLabResultTest.php`

**Step 1: Create StoreLabResultTest (v2 panel format)**

Tests now use `{date, panels[], loose[]}` format.

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;
use App\Modules\MedicalRecord\Models\PainelLaboratorial;
use App\Modules\MedicalRecord\Models\Prontuario;

it('stores panel-based lab results', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina', 'nome' => 'Hemoglobina', 'categoria' => 'hematologia',
        'unidade' => 'g/dL', 'faixa_referencia' => '13,5-17,5', 'tipo_resultado' => 'numeric',
    ]);
    PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo', 'nome' => 'Hemograma Completo', 'categoria' => 'hematologia',
        'subsecoes' => [['label' => 'Série Vermelha', 'analytes' => [['id' => 'hemo-hemoglobina', 'name' => 'Hemoglobina']]]],
    ]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/lab-results", [
        'date' => '2026-03-10',
        'panels' => [
            [
                'panel_id' => 'hemograma-completo',
                'panel_name' => 'Hemograma Completo',
                'is_custom' => false,
                'values' => [
                    ['analyte_id' => 'hemo-hemoglobina', 'value' => '14.5'],
                ],
            ],
        ],
        'loose' => [],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.0.date', '2026-03-10')
        ->assertJsonPath('data.0.panels.0.panel_id', 'hemograma-completo')
        ->assertJsonPath('data.0.panels.0.values.0.analyte_id', 'hemo-hemoglobina')
        ->assertJsonPath('data.0.panels.0.values.0.value', '14.5');

    // Verify catalog enrichment stored in DB
    $this->assertDatabaseHas('valores_laboratoriais', [
        'prontuario_id' => $prontuario->id,
        'catalogo_exame_id' => 'hemo-hemoglobina',
        'unidade' => 'g/dL',
        'faixa_referencia' => '13,5-17,5',
        'painel_id' => 'hemograma-completo',
    ]);
});

it('stores loose (free-form) lab results', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/lab-results", [
        'date' => '2026-03-10',
        'panels' => [],
        'loose' => [
            ['name' => 'Exame especial XYZ', 'value' => 'Negativo', 'unit' => '-'],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.0.loose.0.name', 'Exame especial XYZ')
        ->assertJsonPath('data.0.loose.0.value', 'Negativo');

    $this->assertDatabaseHas('valores_laboratoriais', [
        'prontuario_id' => $prontuario->id,
        'nome_avulso' => 'Exame especial XYZ',
        'catalogo_exame_id' => null,
        'painel_id' => null,
    ]);
});

it('stores panels and loose entries in a single request', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina', 'nome' => 'Hemoglobina', 'categoria' => 'hematologia',
        'unidade' => 'g/dL', 'tipo_resultado' => 'numeric',
    ]);
    PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo', 'nome' => 'Hemograma Completo', 'categoria' => 'hematologia',
        'subsecoes' => [],
    ]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/lab-results", [
        'date' => '2026-03-10',
        'panels' => [
            [
                'panel_id' => 'hemograma-completo',
                'panel_name' => 'Hemograma Completo',
                'values' => [['analyte_id' => 'hemo-hemoglobina', 'value' => '14.5']],
            ],
        ],
        'loose' => [
            ['name' => 'Exame avulso', 'value' => '120', 'unit' => 'mg/dL'],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonCount(1, 'data') // 1 date group
        ->assertJsonCount(1, 'data.0.panels')
        ->assertJsonCount(1, 'data.0.loose');
});

it('rejects store on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/lab-results", [
        'date' => '2026-03-10',
        'loose' => [['name' => 'Test', 'value' => '10', 'unit' => 'mg']],
    ]);

    $response->assertStatus(409);
});

it('rejects store by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $response = $this->actingAs($doctorB)->postJson("/api/medical-records/{$prontuario->id}/lab-results", [
        'date' => '2026-03-10',
        'loose' => [['name' => 'Test', 'value' => '10', 'unit' => 'mg']],
    ]);

    $response->assertForbidden();
});

it('rejects store with empty panels and empty loose', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/lab-results", [
        'date' => '2026-03-10',
        'panels' => [],
        'loose' => [],
    ]);

    $response->assertUnprocessable();
});

it('rejects future collection date', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/lab-results", [
        'date' => '2099-01-01',
        'loose' => [['name' => 'Test', 'value' => '10', 'unit' => 'mg']],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['date']);
});

it('rejects unauthenticated access', function (): void {
    $prontuario = Prontuario::factory()->create();

    $this->postJson("/api/medical-records/{$prontuario->id}/lab-results", [
        'date' => '2026-03-10',
        'loose' => [['name' => 'Test', 'value' => '10', 'unit' => 'mg']],
    ])->assertUnauthorized();
});

it('extracts numeric value from comma-separated string', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/lab-results", [
        'date' => '2026-03-10',
        'loose' => [['name' => 'Teste', 'value' => '14,5', 'unit' => 'mg/dL']],
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('valores_laboratoriais', [
        'prontuario_id' => $prontuario->id,
        'valor' => '14,5',
        'valor_numerico' => 14.5,
    ]);
});

it('rejects panel with non-existent analyte_id', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo', 'nome' => 'Hemograma Completo', 'categoria' => 'hematologia',
        'subsecoes' => [],
    ]);

    $response = $this->actingAs($doctor)->postJson("/api/medical-records/{$prontuario->id}/lab-results", [
        'date' => '2026-03-10',
        'panels' => [
            [
                'panel_id' => 'hemograma-completo',
                'panel_name' => 'Hemograma Completo',
                'values' => [['analyte_id' => 'non-existent', 'value' => '14.5']],
            ],
        ],
    ]);

    $response->assertUnprocessable();
});
```

**Step 2: Create ListLabResultTest (v2 grouped format)**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\CatalogoExameLaboratorial;
use App\Modules\MedicalRecord\Models\PainelLaboratorial;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

it('lists lab results grouped by date in v2 format', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    CatalogoExameLaboratorial::query()->create([
        'id' => 'hemo-hemoglobina', 'nome' => 'Hemoglobina', 'categoria' => 'hematologia',
        'unidade' => 'g/dL', 'tipo_resultado' => 'numeric',
    ]);
    PainelLaboratorial::query()->create([
        'id' => 'hemograma-completo', 'nome' => 'Hemograma Completo', 'categoria' => 'hematologia',
        'subsecoes' => [],
    ]);

    // Panel value
    ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'catalogo_exame_id' => 'hemo-hemoglobina',
        'painel_id' => 'hemograma-completo',
        'data_coleta' => '2026-03-10',
    ]);
    // Loose value
    ValorLaboratorial::factory()->loose()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'data_coleta' => '2026-03-10',
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/medical-records/{$prontuario->id}/lab-results");

    $response->assertOk()
        ->assertJsonCount(1, 'data') // 1 date group
        ->assertJsonPath('data.0.date', '2026-03-10')
        ->assertJsonCount(1, 'data.0.panels')
        ->assertJsonCount(1, 'data.0.loose');
});

it('does not list lab results from another medical record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $otherProntuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    ValorLaboratorial::factory()->create([
        'prontuario_id' => $otherProntuario->id,
        'paciente_id' => $otherProntuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->getJson("/api/medical-records/{$prontuario->id}/lab-results");

    $response->assertOk()->assertJsonCount(0, 'data');
});

it('rejects list by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $this->actingAs($doctorB)->getJson("/api/medical-records/{$prontuario->id}/lab-results")->assertForbidden();
});
```

**Step 3: Create UpdateLabResultTest** (unchanged — operates on individual rows)

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

it('updates a lab result value', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
        'valor' => '14.5',
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        ['value' => '15.2'],
    );

    $response->assertOk()
        ->assertJsonPath('data.value', '15.2')
        ->assertJsonPath('data.numeric_value', '15.2000');
});

it('updates collection date', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        ['collection_date' => '2026-03-12'],
    );

    $response->assertOk()->assertJsonPath('data.collection_date', '2026-03-12');
});

it('rejects update on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
        ['value' => '99'],
    );

    $response->assertStatus(409);
});

it('rejects update of value belonging to different medical record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuarioA = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $prontuarioB = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuarioB->id,
        'paciente_id' => $prontuarioB->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->putJson(
        "/api/medical-records/{$prontuarioA->id}/lab-results/{$labValue->id}",
        ['value' => '99'],
    );

    $response->assertNotFound();
});
```

**Step 4: Create DeleteLabResultTest** (unchanged)

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ValorLaboratorial;

it('deletes a lab result value', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $response = $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
    );

    $response->assertOk()->assertJsonPath('message', 'Valor laboratorial excluído com sucesso.');
    $this->assertDatabaseMissing('valores_laboratoriais', ['id' => $labValue->id]);
});

it('rejects delete on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $this->actingAs($doctor)->deleteJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
    )->assertStatus(409);
});

it('rejects delete by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);
    $labValue = ValorLaboratorial::factory()->create([
        'prontuario_id' => $prontuario->id,
        'paciente_id' => $prontuario->paciente_id,
    ]);

    $this->actingAs($doctorB)->deleteJson(
        "/api/medical-records/{$prontuario->id}/lab-results/{$labValue->id}",
    )->assertForbidden();
});
```

**Step 5: Run all tests**

Run: `php artisan test app/Modules/MedicalRecord/Tests/Feature/ --compact`

**Step 6: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/StoreLabResultTest.php \
       app/Modules/MedicalRecord/Tests/Feature/ListLabResultTest.php \
       app/Modules/MedicalRecord/Tests/Feature/UpdateLabResultTest.php \
       app/Modules/MedicalRecord/Tests/Feature/DeleteLabResultTest.php
git commit -m "test(medical-record): add lab result CRUD endpoint tests"
```

---

### Task 17: Pint + Scribe + Full Test Run

**Step 1: Run Pint formatter**

Run: `vendor/bin/pint --dirty`

**Step 2: Regenerate Scribe docs**

Run: `php artisan scribe:generate`

**Step 3: Run full test suite**

Run: `php artisan test --compact`

**Step 4: Commit final formatting and docs**

```bash
git add -A
git commit -m "chore(medical-record): format code and regenerate API docs for lab results"
```

---

## Files Summary

| Type | Count | Files |
|------|-------|-------|
| Enums | 2 | LabCategory, LabResultType |
| Migrations | 3 | catalogo_exames_laboratoriais, paineis_laboratoriais, valores_laboratoriais |
| Models | 3 (+1 modified) | CatalogoExameLaboratorial, PainelLaboratorial, ValorLaboratorial, Prontuario (relationship) |
| Factory | 1 | LabResultFactory |
| DTOs | 5 | LabPanelValueDTO, LabPanelEntryDTO, LabLooseEntryDTO, StoreLabResultDTO, UpdateLabValueDTO |
| Requests | 3 | StoreLabResultRequest (v2), UpdateLabValueRequest, ListLabCatalogRequest |
| Resources | 4 | LabCatalogResource, LabPanelResource, LabResultResource, LabResultGroupedResource |
| Services | 2 | LabCatalogService, LabResultService (v2 panel format) |
| Policy | 1 | LabResultPolicy |
| Controllers | 2 | LabCatalogController, LabResultController (v2 panel format) |
| Seeders | 2 | LabCatalogSeeder, LabPanelSeeder |
| Tests | 7 | ListLabCatalog, ShowLabCatalog, ListLabPanel, StoreLabResult (v2), ListLabResult (v2), UpdateLabResult, DeleteLabResult |
| Routes | 1 (modified) | routes.php (+8 endpoints) |
| Provider | 1 (modified) | MedicalRecordServiceProvider |
| **Total** | **~35 files** | |
