# Módulo de Pacientes — Plano de Implementação

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implementar o backend completo do módulo de pacientes (CRUD, alergias, condições crônicas, endereço, busca CEP) para substituir os dados mock do frontend.

**Architecture:** Módulo auto-descoberto em `app/Modules/Paciente/` seguindo os padrões do módulo Auth existente. Controllers delegam para Services/Actions, DTOs na fronteira, Resources mapeiam PT→EN para o frontend. Tabelas normalizadas para alergias/condições crônicas com pivot many-to-many.

**Tech Stack:** Laravel 12, PHP 8.5, PostgreSQL, Sanctum SPA auth, Pest 4

**Design doc:** `docs/plans/2026-02-16-modulo-paciente-design.md`

---

## Task 1: Enums do Módulo

**Files:**
- Create: `app/Modules/Paciente/Enums/Sexo.php`
- Create: `app/Modules/Paciente/Enums/TipoSanguineo.php`
- Create: `app/Modules/Paciente/Enums/StatusPaciente.php`
- Create: `app/Modules/Paciente/Enums/IntensidadeHabito.php`

**Step 1: Criar os 4 enums**

```php
// app/Modules/Paciente/Enums/Sexo.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Enums;

enum Sexo: string
{
    case Masculino = 'masculino';
    case Feminino = 'feminino';

    public function toFrontend(): string
    {
        return match ($this) {
            self::Masculino => 'male',
            self::Feminino => 'female',
        };
    }

    public static function fromFrontend(string $value): self
    {
        return match ($value) {
            'male' => self::Masculino,
            'female' => self::Feminino,
            default => throw new \ValueError("Valor inválido para sexo: {$value}"),
        };
    }
}
```

```php
// app/Modules/Paciente/Enums/TipoSanguineo.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Enums;

enum TipoSanguineo: string
{
    case APositivo = 'A+';
    case ANegativo = 'A-';
    case BPositivo = 'B+';
    case BNegativo = 'B-';
    case ABPositivo = 'AB+';
    case ABNegativo = 'AB-';
    case OPositivo = 'O+';
    case ONegativo = 'O-';
}
```

```php
// app/Modules/Paciente/Enums/StatusPaciente.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Enums;

enum StatusPaciente: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
```

```php
// app/Modules/Paciente/Enums/IntensidadeHabito.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Enums;

enum IntensidadeHabito: string
{
    case None = 'none';
    case Light = 'light';
    case Moderate = 'moderate';
    case Intense = 'intense';
}
```

**Step 2: Commit**

```bash
git add app/Modules/Paciente/Enums/
git commit -m "feat(paciente): adicionar enums do módulo de pacientes"
```

---

## Task 2: Migrations

**Files:**
- Create: `app/Modules/Paciente/Database/Migrations/2026_02_16_200000_create_alergias_table.php`
- Create: `app/Modules/Paciente/Database/Migrations/2026_02_16_200001_create_condicoes_cronicas_table.php`
- Create: `app/Modules/Paciente/Database/Migrations/2026_02_16_200002_create_pacientes_table.php`
- Create: `app/Modules/Paciente/Database/Migrations/2026_02_16_200003_create_enderecos_table.php`
- Create: `app/Modules/Paciente/Database/Migrations/2026_02_16_200004_create_alergia_paciente_table.php`
- Create: `app/Modules/Paciente/Database/Migrations/2026_02_16_200005_create_condicao_cronica_paciente_table.php`

**Step 1: Criar as 6 migrations**

```php
// 2026_02_16_200000_create_alergias_table.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alergias', function (Blueprint $table): void {
            $table->id();
            $table->string('nome')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alergias');
    }
};
```

```php
// 2026_02_16_200001_create_condicoes_cronicas_table.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condicoes_cronicas', function (Blueprint $table): void {
            $table->id();
            $table->string('nome')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condicoes_cronicas');
    }
};
```

```php
// 2026_02_16_200002_create_pacientes_table.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->string('cpf', 14);
            $table->string('telefone', 20);
            $table->string('email')->nullable();
            $table->date('data_nascimento');
            $table->string('sexo', 10);
            $table->string('tipo_sanguineo', 5)->nullable();
            $table->string('historico_tabagismo', 20)->nullable();
            $table->string('historico_alcool', 20)->nullable();
            $table->string('status', 10)->default('active');
            $table->timestamp('ultima_consulta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'cpf']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
```

```php
// 2026_02_16_200003_create_enderecos_table.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enderecos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('paciente_id')->unique()->constrained('pacientes')->cascadeOnDelete();
            $table->string('cep', 10);
            $table->string('logradouro');
            $table->string('numero', 20);
            $table->string('complemento')->nullable();
            $table->string('bairro');
            $table->string('cidade');
            $table->string('estado', 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enderecos');
    }
};
```

```php
// 2026_02_16_200004_create_alergia_paciente_table.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alergia_paciente', function (Blueprint $table): void {
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('alergia_id')->constrained('alergias')->cascadeOnDelete();

            $table->primary(['paciente_id', 'alergia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alergia_paciente');
    }
};
```

```php
// 2026_02_16_200005_create_condicao_cronica_paciente_table.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condicao_cronica_paciente', function (Blueprint $table): void {
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('condicao_cronica_id')->constrained('condicoes_cronicas')->cascadeOnDelete();

            $table->primary(['paciente_id', 'condicao_cronica_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condicao_cronica_paciente');
    }
};
```

**Step 2: Rodar as migrations**

```bash
php artisan migrate
```

Expected: Todas as 6 tabelas criadas sem erros.

**Step 3: Commit**

```bash
git add app/Modules/Paciente/Database/Migrations/
git commit -m "feat(paciente): adicionar migrations do módulo de pacientes"
```

---

## Task 3: Models

**Files:**
- Create: `app/Modules/Paciente/Models/Alergia.php`
- Create: `app/Modules/Paciente/Models/CondicaoCronica.php`
- Create: `app/Modules/Paciente/Models/Paciente.php`
- Create: `app/Modules/Paciente/Models/Endereco.php`

**Step 1: Criar os 4 models**

```php
// app/Modules/Paciente/Models/Alergia.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $nome
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Paciente> $pacientes
 */
class Alergia extends Model
{
    use HasFactory;

    protected $table = 'alergias';

    protected $fillable = [
        'nome',
    ];

    /**
     * @return BelongsToMany<Paciente, $this>
     */
    public function pacientes(): BelongsToMany
    {
        return $this->belongsToMany(Paciente::class, 'alergia_paciente');
    }
}
```

```php
// app/Modules/Paciente/Models/CondicaoCronica.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $nome
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Paciente> $pacientes
 */
class CondicaoCronica extends Model
{
    use HasFactory;

    protected $table = 'condicoes_cronicas';

    protected $fillable = [
        'nome',
    ];

    /**
     * @return BelongsToMany<Paciente, $this>
     */
    public function pacientes(): BelongsToMany
    {
        return $this->belongsToMany(Paciente::class, 'condicao_cronica_paciente');
    }
}
```

```php
// app/Modules/Paciente/Models/Endereco.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $paciente_id
 * @property string $cep
 * @property string $logradouro
 * @property string $numero
 * @property string|null $complemento
 * @property string $bairro
 * @property string $cidade
 * @property string $estado
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read Paciente $paciente
 */
class Endereco extends Model
{
    use HasFactory;

    protected $table = 'enderecos';

    protected $fillable = [
        'paciente_id',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
    ];

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
}
```

```php
// app/Modules/Paciente/Models/Paciente.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Models;

use App\Models\User;
use App\Modules\Paciente\Enums\IntensidadeHabito;
use App\Modules\Paciente\Enums\Sexo;
use App\Modules\Paciente\Enums\StatusPaciente;
use App\Modules\Paciente\Enums\TipoSanguineo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $nome
 * @property string $cpf
 * @property string $telefone
 * @property string|null $email
 * @property \Illuminate\Support\Carbon $data_nascimento
 * @property Sexo $sexo
 * @property TipoSanguineo|null $tipo_sanguineo
 * @property IntensidadeHabito|null $historico_tabagismo
 * @property IntensidadeHabito|null $historico_alcool
 * @property StatusPaciente $status
 * @property \Illuminate\Support\Carbon|null $ultima_consulta
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read User $user
 * @property-read Endereco|null $endereco
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Alergia> $alergias
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CondicaoCronica> $condicoesCronicas
 */
class Paciente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pacientes';

    protected $fillable = [
        'user_id',
        'nome',
        'cpf',
        'telefone',
        'email',
        'data_nascimento',
        'sexo',
        'tipo_sanguineo',
        'historico_tabagismo',
        'historico_alcool',
        'status',
        'ultima_consulta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'sexo' => Sexo::class,
            'tipo_sanguineo' => TipoSanguineo::class,
            'historico_tabagismo' => IntensidadeHabito::class,
            'historico_alcool' => IntensidadeHabito::class,
            'status' => StatusPaciente::class,
            'ultima_consulta' => 'datetime',
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
     * @return HasOne<Endereco, $this>
     */
    public function endereco(): HasOne
    {
        return $this->hasOne(Endereco::class);
    }

    /**
     * @return BelongsToMany<Alergia, $this>
     */
    public function alergias(): BelongsToMany
    {
        return $this->belongsToMany(Alergia::class, 'alergia_paciente');
    }

    /**
     * @return BelongsToMany<CondicaoCronica, $this>
     */
    public function condicoesCronicas(): BelongsToMany
    {
        return $this->belongsToMany(CondicaoCronica::class, 'condicao_cronica_paciente');
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/Paciente/Models/
git commit -m "feat(paciente): adicionar models do módulo de pacientes"
```

---

## Task 4: Factories

**Files:**
- Create: `app/Modules/Paciente/Database/Factories/AlergiaFactory.php`
- Create: `app/Modules/Paciente/Database/Factories/CondicaoCronicaFactory.php`
- Create: `app/Modules/Paciente/Database/Factories/EnderecoFactory.php`
- Create: `app/Modules/Paciente/Database/Factories/PacienteFactory.php`

**Step 1: Criar as 4 factories**

As factories devem ficar em `app/Modules/Paciente/Database/Factories/`. Os models precisam do método `newFactory()` para que o Laravel encontre as factories fora do path padrão.

```php
// app/Modules/Paciente/Database/Factories/AlergiaFactory.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Factories;

use App\Modules\Paciente\Models\Alergia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alergia>
 */
final class AlergiaFactory extends Factory
{
    protected $model = Alergia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->randomElement([
                'Penicilina', 'Dipirona', 'AAS', 'Sulfa', 'Latex',
                'Ibuprofeno', 'Contraste Iodado', 'Frutos do Mar',
                'Amendoim', 'Gluten', 'Nimesulida', 'Amoxicilina',
                'Cefalosporina', 'Paracetamol', 'Diclofenaco',
            ]),
        ];
    }
}
```

```php
// app/Modules/Paciente/Database/Factories/CondicaoCronicaFactory.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Factories;

use App\Modules\Paciente\Models\CondicaoCronica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CondicaoCronica>
 */
final class CondicaoCronicaFactory extends Factory
{
    protected $model = CondicaoCronica::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->randomElement([
                'Hipertensão Arterial', 'Diabetes Tipo 2', 'Diabetes Tipo 1',
                'Asma', 'DPOC', 'Insuficiência Cardíaca', 'Hipotireoidismo',
                'Hipertireoidismo', 'Artrite Reumatoide', 'Lúpus Eritematoso Sistêmico',
                'Fibromialgia', 'Doença Celíaca', 'Epilepsia', 'Depressão',
                'Ansiedade Generalizada',
            ]),
        ];
    }
}
```

```php
// app/Modules/Paciente/Database/Factories/EnderecoFactory.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Factories;

use App\Modules\Paciente\Models\Endereco;
use App\Modules\Paciente\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Endereco>
 */
final class EnderecoFactory extends Factory
{
    protected $model = Endereco::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'paciente_id' => Paciente::factory(),
            'cep' => fake()->numerify('#####-###'),
            'logradouro' => fake()->streetName(),
            'numero' => (string) fake()->buildingNumber(),
            'complemento' => fake()->optional(0.3)->secondaryAddress(),
            'bairro' => fake()->citySuffix().' '.fake()->lastName(),
            'cidade' => fake()->city(),
            'estado' => fake()->randomElement(['SP', 'RJ', 'MG', 'RS', 'PR', 'BA', 'PE', 'CE', 'GO', 'SC']),
        ];
    }
}
```

```php
// app/Modules/Paciente/Database/Factories/PacienteFactory.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Factories;

use App\Models\User;
use App\Modules\Paciente\Enums\IntensidadeHabito;
use App\Modules\Paciente\Enums\Sexo;
use App\Modules\Paciente\Enums\StatusPaciente;
use App\Modules\Paciente\Enums\TipoSanguineo;
use App\Modules\Paciente\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paciente>
 */
final class PacienteFactory extends Factory
{
    protected $model = Paciente::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nome' => fake()->name(),
            'cpf' => fake()->unique()->numerify('###.###.###-##'),
            'telefone' => fake()->numerify('(##) #####-####'),
            'email' => fake()->optional(0.8)->safeEmail(),
            'data_nascimento' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'sexo' => fake()->randomElement(Sexo::cases()),
            'tipo_sanguineo' => fake()->optional(0.7)->randomElement(TipoSanguineo::cases()),
            'historico_tabagismo' => fake()->optional(0.5)->randomElement(IntensidadeHabito::cases()),
            'historico_alcool' => fake()->optional(0.5)->randomElement(IntensidadeHabito::cases()),
            'status' => StatusPaciente::Active,
            'ultima_consulta' => fake()->optional(0.6)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusPaciente::Inactive,
        ]);
    }

    public function withEndereco(): static
    {
        return $this->afterCreating(function (Paciente $paciente): void {
            Endereco::factory()->create(['paciente_id' => $paciente->id]);
        });
    }
}
```

**Step 2: Adicionar `newFactory()` em cada model**

Adicionar nos models `Paciente`, `Endereco`, `Alergia` e `CondicaoCronica` o seguinte método (adaptando namespace e classe):

```php
// Em cada model, adicionar:
protected static function newFactory(): \App\Modules\Paciente\Database\Factories\{Model}Factory
{
    return \App\Modules\Paciente\Database\Factories\{Model}Factory::new();
}
```

Exemplo concreto para `Paciente`:
```php
protected static function newFactory(): \App\Modules\Paciente\Database\Factories\PacienteFactory
{
    return \App\Modules\Paciente\Database\Factories\PacienteFactory::new();
}
```

**Step 3: Commit**

```bash
git add app/Modules/Paciente/Database/Factories/ app/Modules/Paciente/Models/
git commit -m "feat(paciente): adicionar factories do módulo de pacientes"
```

---

## Task 5: ServiceProvider, Routes e Policy (scaffold base)

**Files:**
- Create: `app/Modules/Paciente/Providers/PacienteServiceProvider.php`
- Create: `app/Modules/Paciente/Policies/PacientePolicy.php`
- Create: `app/Modules/Paciente/routes.php`

**Step 1: Criar o ServiceProvider**

```php
// app/Modules/Paciente/Providers/PacienteServiceProvider.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Providers;

use App\Modules\Paciente\Models\Paciente;
use App\Modules\Paciente\Policies\PacientePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class PacienteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Paciente::class, PacientePolicy::class);
    }
}
```

**Step 2: Criar a Policy**

```php
// app/Modules/Paciente/Policies/PacientePolicy.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Policies;

use App\Models\User;
use App\Modules\Paciente\Models\Paciente;

final class PacientePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Paciente $paciente): bool
    {
        return $user->id === $paciente->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Paciente $paciente): bool
    {
        return $user->id === $paciente->user_id;
    }

    public function delete(User $user, Paciente $paciente): bool
    {
        return $user->id === $paciente->user_id;
    }
}
```

**Step 3: Criar o arquivo de rotas (vazio por enquanto, será preenchido nas tasks seguintes)**

```php
// app/Modules/Paciente/routes.php
<?php

declare(strict_types=1);

use App\Modules\Paciente\Http\Controllers\AlergiaController;
use App\Modules\Paciente\Http\Controllers\CondicaoCronicaController;
use App\Modules\Paciente\Http\Controllers\EnderecoController;
use App\Modules\Paciente\Http\Controllers\PacienteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/pacientes', [PacienteController::class, 'index']);
    Route::post('/pacientes', [PacienteController::class, 'store']);
    Route::get('/pacientes/{id}', [PacienteController::class, 'show']);
    Route::put('/pacientes/{id}', [PacienteController::class, 'update']);
    Route::delete('/pacientes/{id}', [PacienteController::class, 'destroy']);

    Route::get('/alergias', [AlergiaController::class, 'index']);
    Route::get('/condicoes-cronicas', [CondicaoCronicaController::class, 'index']);

    Route::get('/enderecos/cep/{cep}', [EnderecoController::class, 'buscarPorCep']);
});
```

Nota: as rotas usam `{id}` como parâmetro primitivo, **não** route model binding, conforme CLAUDE.md.

**Step 4: Commit**

```bash
git add app/Modules/Paciente/Providers/ app/Modules/Paciente/Policies/ app/Modules/Paciente/routes.php
git commit -m "feat(paciente): adicionar service provider, policy e rotas"
```

---

## Task 6: DTOs e Resources

**Files:**
- Create: `app/Modules/Paciente/DTOs/EnderecoDTO.php`
- Create: `app/Modules/Paciente/DTOs/CreatePacienteDTO.php`
- Create: `app/Modules/Paciente/DTOs/UpdatePacienteDTO.php`
- Create: `app/Modules/Paciente/Http/Resources/EnderecoResource.php`
- Create: `app/Modules/Paciente/Http/Resources/AlergiaResource.php`
- Create: `app/Modules/Paciente/Http/Resources/CondicaoCronicaResource.php`
- Create: `app/Modules/Paciente/Http/Resources/PacienteResource.php`
- Create: `app/Modules/Paciente/Http/Resources/PacienteListResource.php`

**Step 1: Criar DTOs**

```php
// app/Modules/Paciente/DTOs/EnderecoDTO.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\DTOs;

final readonly class EnderecoDTO
{
    public function __construct(
        public string $cep,
        public string $logradouro,
        public string $numero,
        public ?string $complemento,
        public string $bairro,
        public string $cidade,
        public string $estado,
    ) {}

    /**
     * @param array{cep: string, street: string, number: string, complement?: string|null, neighborhood: string, city: string, state: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cep: $data['cep'],
            logradouro: $data['street'],
            numero: $data['number'],
            complemento: $data['complement'] ?? null,
            bairro: $data['neighborhood'],
            cidade: $data['city'],
            estado: $data['state'],
        );
    }
}
```

```php
// app/Modules/Paciente/DTOs/CreatePacienteDTO.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\DTOs;

use App\Modules\Paciente\Enums\IntensidadeHabito;
use App\Modules\Paciente\Enums\Sexo;
use App\Modules\Paciente\Enums\StatusPaciente;
use App\Modules\Paciente\Enums\TipoSanguineo;
use App\Modules\Paciente\Http\Requests\StorePacienteRequest;
use Illuminate\Support\Carbon;

final readonly class CreatePacienteDTO
{
    /**
     * @param string[] $alergias
     * @param string[] $condicoesCronicas
     */
    public function __construct(
        public string $nome,
        public string $cpf,
        public string $telefone,
        public ?string $email,
        public Carbon $dataNascimento,
        public Sexo $sexo,
        public ?TipoSanguineo $tipoSanguineo,
        public ?IntensidadeHabito $historicoTabagismo,
        public ?IntensidadeHabito $historicoAlcool,
        public StatusPaciente $status,
        public array $alergias,
        public array $condicoesCronicas,
        public ?EnderecoDTO $endereco,
    ) {}

    public static function fromRequest(StorePacienteRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            nome: $validated['name'],
            cpf: $validated['cpf'],
            telefone: $validated['phone'],
            email: $validated['email'] ?? null,
            dataNascimento: Carbon::parse($validated['birth_date']),
            sexo: Sexo::fromFrontend($validated['gender']),
            tipoSanguineo: isset($validated['blood_type']) ? TipoSanguineo::from($validated['blood_type']) : null,
            historicoTabagismo: isset($validated['medical_history']['smoking']) ? IntensidadeHabito::from($validated['medical_history']['smoking']) : null,
            historicoAlcool: isset($validated['medical_history']['alcohol']) ? IntensidadeHabito::from($validated['medical_history']['alcohol']) : null,
            status: StatusPaciente::from($validated['status'] ?? 'active'),
            alergias: $validated['allergies'] ?? [],
            condicoesCronicas: $validated['chronic_conditions'] ?? [],
            endereco: isset($validated['address']) ? EnderecoDTO::fromArray($validated['address']) : null,
        );
    }
}
```

```php
// app/Modules/Paciente/DTOs/UpdatePacienteDTO.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\DTOs;

use App\Modules\Paciente\Enums\IntensidadeHabito;
use App\Modules\Paciente\Enums\Sexo;
use App\Modules\Paciente\Enums\StatusPaciente;
use App\Modules\Paciente\Enums\TipoSanguineo;
use App\Modules\Paciente\Http\Requests\UpdatePacienteRequest;
use Illuminate\Support\Carbon;

final readonly class UpdatePacienteDTO
{
    /**
     * @param string[] $alergias
     * @param string[] $condicoesCronicas
     */
    public function __construct(
        public string $nome,
        public string $cpf,
        public string $telefone,
        public ?string $email,
        public Carbon $dataNascimento,
        public Sexo $sexo,
        public ?TipoSanguineo $tipoSanguineo,
        public ?IntensidadeHabito $historicoTabagismo,
        public ?IntensidadeHabito $historicoAlcool,
        public StatusPaciente $status,
        public array $alergias,
        public array $condicoesCronicas,
        public ?EnderecoDTO $endereco,
    ) {}

    public static function fromRequest(UpdatePacienteRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            nome: $validated['name'],
            cpf: $validated['cpf'],
            telefone: $validated['phone'],
            email: $validated['email'] ?? null,
            dataNascimento: Carbon::parse($validated['birth_date']),
            sexo: Sexo::fromFrontend($validated['gender']),
            tipoSanguineo: isset($validated['blood_type']) ? TipoSanguineo::from($validated['blood_type']) : null,
            historicoTabagismo: isset($validated['medical_history']['smoking']) ? IntensidadeHabito::from($validated['medical_history']['smoking']) : null,
            historicoAlcool: isset($validated['medical_history']['alcohol']) ? IntensidadeHabito::from($validated['medical_history']['alcohol']) : null,
            status: StatusPaciente::from($validated['status'] ?? 'active'),
            alergias: $validated['allergies'] ?? [],
            condicoesCronicas: $validated['chronic_conditions'] ?? [],
            endereco: isset($validated['address']) ? EnderecoDTO::fromArray($validated['address']) : null,
        );
    }
}
```

**Step 2: Criar Resources**

```php
// app/Modules/Paciente/Http/Resources/EnderecoResource.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Paciente\Models\Endereco
 */
final class EnderecoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'cep' => $this->cep,
            'street' => $this->logradouro,
            'number' => $this->numero,
            'complement' => $this->complemento,
            'neighborhood' => $this->bairro,
            'city' => $this->cidade,
            'state' => $this->estado,
        ];
    }
}
```

```php
// app/Modules/Paciente/Http/Resources/AlergiaResource.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Paciente\Models\Alergia
 */
final class AlergiaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nome,
        ];
    }
}
```

```php
// app/Modules/Paciente/Http/Resources/CondicaoCronicaResource.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Paciente\Models\CondicaoCronica
 */
final class CondicaoCronicaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nome,
        ];
    }
}
```

```php
// app/Modules/Paciente/Http/Resources/PacienteResource.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Paciente\Models\Paciente
 */
final class PacienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nome,
            'cpf' => $this->cpf,
            'phone' => $this->telefone,
            'email' => $this->email,
            'birth_date' => $this->data_nascimento->format('Y-m-d'),
            'gender' => $this->sexo->toFrontend(),
            'blood_type' => $this->tipo_sanguineo?->value,
            'allergies' => $this->alergias->pluck('nome')->toArray(),
            'chronic_conditions' => $this->condicoesCronicas->pluck('nome')->toArray(),
            'medical_history' => [
                'smoking' => $this->historico_tabagismo?->value ?? 'none',
                'alcohol' => $this->historico_alcool?->value ?? 'none',
            ],
            'last_visit' => $this->ultima_consulta?->toISOString(),
            'address' => $this->whenLoaded('endereco', fn () => $this->endereco ? new EnderecoResource($this->endereco) : null),
            'status' => $this->status->value,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

```php
// app/Modules/Paciente/Http/Resources/PacienteListResource.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versão leve do PacienteResource para listagem (sem carregar relações desnecessárias).
 *
 * @mixin \App\Modules\Paciente\Models\Paciente
 */
final class PacienteListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->nome,
            'cpf' => $this->cpf,
            'phone' => $this->telefone,
            'email' => $this->email,
            'birth_date' => $this->data_nascimento->format('Y-m-d'),
            'gender' => $this->sexo->toFrontend(),
            'blood_type' => $this->tipo_sanguineo?->value,
            'allergies' => $this->alergias->pluck('nome')->toArray(),
            'chronic_conditions' => $this->condicoesCronicas->pluck('nome')->toArray(),
            'last_visit' => $this->ultima_consulta?->toISOString(),
            'status' => $this->status->value,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

**Step 3: Commit**

```bash
git add app/Modules/Paciente/DTOs/ app/Modules/Paciente/Http/Resources/
git commit -m "feat(paciente): adicionar DTOs e API resources"
```

---

## Task 7: Form Requests

**Files:**
- Create: `app/Modules/Paciente/Http/Requests/StorePacienteRequest.php`
- Create: `app/Modules/Paciente/Http/Requests/UpdatePacienteRequest.php`
- Create: `app/Modules/Paciente/Http/Requests/ListPacienteRequest.php`

**Step 1: Criar Form Requests**

```php
// app/Modules/Paciente/Http/Requests/StorePacienteRequest.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Requests;

use App\Modules\Paciente\Enums\IntensidadeHabito;
use App\Modules\Paciente\Enums\TipoSanguineo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePacienteRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => [
                'required',
                'string',
                'max:14',
                Rule::unique('pacientes', 'cpf')->where('user_id', $userId)->whereNull('deleted_at'),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'blood_type' => ['nullable', 'string', Rule::in(array_column(TipoSanguineo::cases(), 'value'))],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:255'],
            'chronic_conditions' => ['nullable', 'array'],
            'chronic_conditions.*' => ['string', 'max:255'],
            'medical_history' => ['nullable', 'array'],
            'medical_history.smoking' => ['nullable', 'string', Rule::in(array_column(IntensidadeHabito::cases(), 'value'))],
            'medical_history.alcohol' => ['nullable', 'string', Rule::in(array_column(IntensidadeHabito::cases(), 'value'))],
            'address' => ['nullable', 'array'],
            'address.cep' => ['required_with:address', 'string', 'max:10'],
            'address.street' => ['required_with:address', 'string', 'max:255'],
            'address.number' => ['required_with:address', 'string', 'max:20'],
            'address.complement' => ['nullable', 'string', 'max:255'],
            'address.neighborhood' => ['required_with:address', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'string', 'max:255'],
            'address.state' => ['required_with:address', 'string', 'size:2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.max' => 'O campo nome não pode ter mais de 255 caracteres.',
            'cpf.required' => 'O campo CPF é obrigatório.',
            'cpf.unique' => 'Este CPF já está cadastrado para este médico.',
            'phone.required' => 'O campo telefone é obrigatório.',
            'email.email' => 'O campo e-mail deve ser um endereço de e-mail válido.',
            'birth_date.required' => 'O campo data de nascimento é obrigatório.',
            'birth_date.date' => 'O campo data de nascimento deve ser uma data válida.',
            'birth_date.before' => 'A data de nascimento deve ser anterior a hoje.',
            'gender.required' => 'O campo sexo é obrigatório.',
            'gender.in' => 'O campo sexo deve ser "male" ou "female".',
            'blood_type.in' => 'O tipo sanguíneo informado é inválido.',
            'status.in' => 'O status informado é inválido.',
            'medical_history.smoking.in' => 'O valor do histórico de tabagismo é inválido.',
            'medical_history.alcohol.in' => 'O valor do histórico de álcool é inválido.',
            'address.cep.required_with' => 'O campo CEP é obrigatório quando o endereço é informado.',
            'address.street.required_with' => 'O campo logradouro é obrigatório quando o endereço é informado.',
            'address.number.required_with' => 'O campo número é obrigatório quando o endereço é informado.',
            'address.neighborhood.required_with' => 'O campo bairro é obrigatório quando o endereço é informado.',
            'address.city.required_with' => 'O campo cidade é obrigatório quando o endereço é informado.',
            'address.state.required_with' => 'O campo estado é obrigatório quando o endereço é informado.',
            'address.state.size' => 'O campo estado deve ter exatamente 2 caracteres.',
        ];
    }
}
```

```php
// app/Modules/Paciente/Http/Requests/UpdatePacienteRequest.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Requests;

use App\Modules\Paciente\Enums\IntensidadeHabito;
use App\Modules\Paciente\Enums\TipoSanguineo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePacienteRequest extends FormRequest
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
        $userId = $this->user()?->id;
        $pacienteId = (int) $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => [
                'required',
                'string',
                'max:14',
                Rule::unique('pacientes', 'cpf')
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')
                    ->ignore($pacienteId),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'blood_type' => ['nullable', 'string', Rule::in(array_column(TipoSanguineo::cases(), 'value'))],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:255'],
            'chronic_conditions' => ['nullable', 'array'],
            'chronic_conditions.*' => ['string', 'max:255'],
            'medical_history' => ['nullable', 'array'],
            'medical_history.smoking' => ['nullable', 'string', Rule::in(array_column(IntensidadeHabito::cases(), 'value'))],
            'medical_history.alcohol' => ['nullable', 'string', Rule::in(array_column(IntensidadeHabito::cases(), 'value'))],
            'address' => ['nullable', 'array'],
            'address.cep' => ['required_with:address', 'string', 'max:10'],
            'address.street' => ['required_with:address', 'string', 'max:255'],
            'address.number' => ['required_with:address', 'string', 'max:20'],
            'address.complement' => ['nullable', 'string', 'max:255'],
            'address.neighborhood' => ['required_with:address', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'string', 'max:255'],
            'address.state' => ['required_with:address', 'string', 'size:2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.max' => 'O campo nome não pode ter mais de 255 caracteres.',
            'cpf.required' => 'O campo CPF é obrigatório.',
            'cpf.unique' => 'Este CPF já está cadastrado para este médico.',
            'phone.required' => 'O campo telefone é obrigatório.',
            'email.email' => 'O campo e-mail deve ser um endereço de e-mail válido.',
            'birth_date.required' => 'O campo data de nascimento é obrigatório.',
            'birth_date.date' => 'O campo data de nascimento deve ser uma data válida.',
            'birth_date.before' => 'A data de nascimento deve ser anterior a hoje.',
            'gender.required' => 'O campo sexo é obrigatório.',
            'gender.in' => 'O campo sexo deve ser "male" ou "female".',
            'blood_type.in' => 'O tipo sanguíneo informado é inválido.',
            'status.in' => 'O status informado é inválido.',
            'medical_history.smoking.in' => 'O valor do histórico de tabagismo é inválido.',
            'medical_history.alcohol.in' => 'O valor do histórico de álcool é inválido.',
            'address.cep.required_with' => 'O campo CEP é obrigatório quando o endereço é informado.',
            'address.street.required_with' => 'O campo logradouro é obrigatório quando o endereço é informado.',
            'address.number.required_with' => 'O campo número é obrigatório quando o endereço é informado.',
            'address.neighborhood.required_with' => 'O campo bairro é obrigatório quando o endereço é informado.',
            'address.city.required_with' => 'O campo cidade é obrigatório quando o endereço é informado.',
            'address.state.required_with' => 'O campo estado é obrigatório quando o endereço é informado.',
            'address.state.size' => 'O campo estado deve ter exatamente 2 caracteres.',
        ];
    }
}
```

```php
// app/Modules/Paciente/Http/Requests/ListPacienteRequest.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListPacienteRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'busca' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'O campo página deve ser um número inteiro.',
            'page.min' => 'O campo página deve ser no mínimo 1.',
            'per_page.integer' => 'O campo itens por página deve ser um número inteiro.',
            'per_page.min' => 'O campo itens por página deve ser no mínimo 1.',
            'per_page.max' => 'O campo itens por página deve ser no máximo 100.',
            'status.in' => 'O status informado é inválido.',
        ];
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/Paciente/Http/Requests/
git commit -m "feat(paciente): adicionar form requests de validação"
```

---

## Task 8: Service e Actions (CRUD)

**Files:**
- Create: `app/Modules/Paciente/Services/PacienteService.php`
- Create: `app/Modules/Paciente/Actions/CreatePacienteAction.php`
- Create: `app/Modules/Paciente/Actions/UpdatePacienteAction.php`
- Create: `app/Modules/Paciente/Actions/DeletePacienteAction.php`

**Step 1: Criar a action de sincronização de alergias/condições (lógica compartilhada entre create e update)**

A lógica de sync de alergias e condições crônicas deve:
1. Receber array de nomes (strings) do frontend
2. Usar `firstOrCreate` para cada nome (cria se não existir)
3. Sincronizar os IDs no pivot

**Step 2: Criar Actions**

```php
// app/Modules/Paciente/Actions/CreatePacienteAction.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Actions;

use App\Modules\Paciente\DTOs\CreatePacienteDTO;
use App\Modules\Paciente\Models\Alergia;
use App\Modules\Paciente\Models\CondicaoCronica;
use App\Modules\Paciente\Models\Paciente;
use Illuminate\Support\Facades\DB;

final class CreatePacienteAction
{
    public function execute(int $userId, CreatePacienteDTO $dto): Paciente
    {
        return DB::transaction(function () use ($userId, $dto): Paciente {
            $paciente = Paciente::query()->create([
                'user_id' => $userId,
                'nome' => $dto->nome,
                'cpf' => $dto->cpf,
                'telefone' => $dto->telefone,
                'email' => $dto->email,
                'data_nascimento' => $dto->dataNascimento,
                'sexo' => $dto->sexo,
                'tipo_sanguineo' => $dto->tipoSanguineo,
                'historico_tabagismo' => $dto->historicoTabagismo,
                'historico_alcool' => $dto->historicoAlcool,
                'status' => $dto->status,
            ]);

            if ($dto->endereco) {
                $paciente->endereco()->create([
                    'cep' => $dto->endereco->cep,
                    'logradouro' => $dto->endereco->logradouro,
                    'numero' => $dto->endereco->numero,
                    'complemento' => $dto->endereco->complemento,
                    'bairro' => $dto->endereco->bairro,
                    'cidade' => $dto->endereco->cidade,
                    'estado' => $dto->endereco->estado,
                ]);
            }

            $this->syncAlergias($paciente, $dto->alergias);
            $this->syncCondicoesCronicas($paciente, $dto->condicoesCronicas);

            return $paciente->load(['endereco', 'alergias', 'condicoesCronicas']);
        });
    }

    /**
     * @param string[] $nomes
     */
    private function syncAlergias(Paciente $paciente, array $nomes): void
    {
        if (empty($nomes)) {
            return;
        }

        $ids = collect($nomes)->map(
            fn (string $nome) => Alergia::query()->firstOrCreate(['nome' => $nome])->id
        );

        $paciente->alergias()->sync($ids);
    }

    /**
     * @param string[] $nomes
     */
    private function syncCondicoesCronicas(Paciente $paciente, array $nomes): void
    {
        if (empty($nomes)) {
            return;
        }

        $ids = collect($nomes)->map(
            fn (string $nome) => CondicaoCronica::query()->firstOrCreate(['nome' => $nome])->id
        );

        $paciente->condicoesCronicas()->sync($ids);
    }
}
```

```php
// app/Modules/Paciente/Actions/UpdatePacienteAction.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Actions;

use App\Modules\Paciente\DTOs\UpdatePacienteDTO;
use App\Modules\Paciente\Models\Alergia;
use App\Modules\Paciente\Models\CondicaoCronica;
use App\Modules\Paciente\Models\Paciente;
use Illuminate\Support\Facades\DB;

final class UpdatePacienteAction
{
    public function execute(Paciente $paciente, UpdatePacienteDTO $dto): Paciente
    {
        return DB::transaction(function () use ($paciente, $dto): Paciente {
            $paciente->update([
                'nome' => $dto->nome,
                'cpf' => $dto->cpf,
                'telefone' => $dto->telefone,
                'email' => $dto->email,
                'data_nascimento' => $dto->dataNascimento,
                'sexo' => $dto->sexo,
                'tipo_sanguineo' => $dto->tipoSanguineo,
                'historico_tabagismo' => $dto->historicoTabagismo,
                'historico_alcool' => $dto->historicoAlcool,
                'status' => $dto->status,
            ]);

            if ($dto->endereco) {
                $paciente->endereco()->updateOrCreate(
                    ['paciente_id' => $paciente->id],
                    [
                        'cep' => $dto->endereco->cep,
                        'logradouro' => $dto->endereco->logradouro,
                        'numero' => $dto->endereco->numero,
                        'complemento' => $dto->endereco->complemento,
                        'bairro' => $dto->endereco->bairro,
                        'cidade' => $dto->endereco->cidade,
                        'estado' => $dto->endereco->estado,
                    ]
                );
            } else {
                $paciente->endereco()?->delete();
            }

            $this->syncAlergias($paciente, $dto->alergias);
            $this->syncCondicoesCronicas($paciente, $dto->condicoesCronicas);

            return $paciente->load(['endereco', 'alergias', 'condicoesCronicas']);
        });
    }

    /**
     * @param string[] $nomes
     */
    private function syncAlergias(Paciente $paciente, array $nomes): void
    {
        $ids = collect($nomes)->map(
            fn (string $nome) => Alergia::query()->firstOrCreate(['nome' => $nome])->id
        );

        $paciente->alergias()->sync($ids);
    }

    /**
     * @param string[] $nomes
     */
    private function syncCondicoesCronicas(Paciente $paciente, array $nomes): void
    {
        $ids = collect($nomes)->map(
            fn (string $nome) => CondicaoCronica::query()->firstOrCreate(['nome' => $nome])->id
        );

        $paciente->condicoesCronicas()->sync($ids);
    }
}
```

```php
// app/Modules/Paciente/Actions/DeletePacienteAction.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Actions;

use App\Modules\Paciente\Models\Paciente;

final class DeletePacienteAction
{
    public function execute(Paciente $paciente): void
    {
        $paciente->delete();
    }
}
```

**Step 3: Criar PacienteService**

```php
// app/Modules/Paciente/Services/PacienteService.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Services;

use App\Modules\Paciente\Models\Paciente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PacienteService
{
    /**
     * @param array{busca?: string|null, status?: string|null, per_page?: int|null} $filters
     *
     * @return LengthAwarePaginator<Paciente>
     */
    public function listForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Paciente::query()
            ->where('user_id', $userId)
            ->with(['alergias', 'condicoesCronicas']);

        if (! empty($filters['busca'])) {
            $busca = $filters['busca'];
            $query->where(function ($q) use ($busca): void {
                $q->where('nome', 'ilike', "%{$busca}%")
                    ->orWhere('cpf', 'like', "%{$busca}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate(perPage: (int) ($filters['per_page'] ?? 15));
    }

    public function findForUser(int $userId, int $pacienteId): Paciente
    {
        $paciente = Paciente::query()
            ->where('user_id', $userId)
            ->with(['endereco', 'alergias', 'condicoesCronicas'])
            ->find($pacienteId);

        if (! $paciente) {
            throw new NotFoundHttpException('Paciente não encontrado.');
        }

        return $paciente;
    }
}
```

**Step 4: Commit**

```bash
git add app/Modules/Paciente/Services/ app/Modules/Paciente/Actions/
git commit -m "feat(paciente): adicionar service e actions do CRUD"
```

---

## Task 9: Controllers

**Files:**
- Create: `app/Modules/Paciente/Http/Controllers/PacienteController.php`
- Create: `app/Modules/Paciente/Http/Controllers/AlergiaController.php`
- Create: `app/Modules/Paciente/Http/Controllers/CondicaoCronicaController.php`
- Create: `app/Modules/Paciente/Http/Controllers/EnderecoController.php`
- Create: `app/Modules/Paciente/Services/CepService.php`

**Step 1: Criar CepService**

```php
// app/Modules/Paciente/Services/CepService.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Services;

use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CepService
{
    /**
     * @return array{cep: string, logradouro: string, bairro: string, cidade: string, estado: string}
     *
     * @throws NotFoundHttpException
     */
    public function buscar(string $cep): array
    {
        $cepLimpo = preg_replace('/\D/', '', $cep);

        $response = Http::get("https://viacep.com.br/ws/{$cepLimpo}/json/");

        if ($response->failed() || $response->json('erro')) {
            throw new NotFoundHttpException('CEP não encontrado.');
        }

        $data = $response->json();

        return [
            'cep' => $data['cep'],
            'logradouro' => $data['logradouro'] ?? '',
            'bairro' => $data['bairro'] ?? '',
            'cidade' => $data['localidade'] ?? '',
            'estado' => $data['uf'] ?? '',
        ];
    }
}
```

**Step 2: Criar controllers**

```php
// app/Modules/Paciente/Http/Controllers/PacienteController.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Controllers;

use App\Modules\Paciente\Actions\CreatePacienteAction;
use App\Modules\Paciente\Actions\DeletePacienteAction;
use App\Modules\Paciente\Actions\UpdatePacienteAction;
use App\Modules\Paciente\DTOs\CreatePacienteDTO;
use App\Modules\Paciente\DTOs\UpdatePacienteDTO;
use App\Modules\Paciente\Http\Requests\ListPacienteRequest;
use App\Modules\Paciente\Http\Requests\StorePacienteRequest;
use App\Modules\Paciente\Http\Requests\UpdatePacienteRequest;
use App\Modules\Paciente\Http\Resources\PacienteListResource;
use App\Modules\Paciente\Http\Resources\PacienteResource;
use App\Modules\Paciente\Services\PacienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class PacienteController
{
    public function __construct(
        private readonly PacienteService $pacienteService,
        private readonly CreatePacienteAction $createAction,
        private readonly UpdatePacienteAction $updateAction,
        private readonly DeletePacienteAction $deleteAction,
    ) {}

    public function index(ListPacienteRequest $request): AnonymousResourceCollection
    {
        $pacientes = $this->pacienteService->listForUser(
            userId: $request->user()->id,
            filters: $request->validated(),
        );

        return PacienteListResource::collection($pacientes);
    }

    public function store(StorePacienteRequest $request): PacienteResource
    {
        $dto = CreatePacienteDTO::fromRequest($request);

        $paciente = $this->createAction->execute(
            userId: $request->user()->id,
            dto: $dto,
        );

        return new PacienteResource($paciente);
    }

    public function show(Request $request, int $id): PacienteResource
    {
        $paciente = $this->pacienteService->findForUser(
            userId: $request->user()->id,
            pacienteId: $id,
        );

        Gate::authorize('view', $paciente);

        return new PacienteResource($paciente);
    }

    public function update(UpdatePacienteRequest $request, int $id): PacienteResource
    {
        $paciente = $this->pacienteService->findForUser(
            userId: $request->user()->id,
            pacienteId: $id,
        );

        Gate::authorize('update', $paciente);

        $dto = UpdatePacienteDTO::fromRequest($request);
        $paciente = $this->updateAction->execute($paciente, $dto);

        return new PacienteResource($paciente);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $paciente = $this->pacienteService->findForUser(
            userId: $request->user()->id,
            pacienteId: $id,
        );

        Gate::authorize('delete', $paciente);

        $this->deleteAction->execute($paciente);

        return response()->json(['message' => 'Paciente excluído com sucesso.']);
    }
}
```

```php
// app/Modules/Paciente/Http/Controllers/AlergiaController.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Controllers;

use App\Modules\Paciente\Http\Resources\AlergiaResource;
use App\Modules\Paciente\Models\Alergia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AlergiaController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Alergia::query()->orderBy('nome');

        if ($request->filled('busca')) {
            $query->where('nome', 'ilike', '%'.$request->string('busca').'%');
        }

        return AlergiaResource::collection($query->limit(50)->get());
    }
}
```

```php
// app/Modules/Paciente/Http/Controllers/CondicaoCronicaController.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Controllers;

use App\Modules\Paciente\Http\Resources\CondicaoCronicaResource;
use App\Modules\Paciente\Models\CondicaoCronica;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CondicaoCronicaController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CondicaoCronica::query()->orderBy('nome');

        if ($request->filled('busca')) {
            $query->where('nome', 'ilike', '%'.$request->string('busca').'%');
        }

        return CondicaoCronicaResource::collection($query->limit(50)->get());
    }
}
```

```php
// app/Modules/Paciente/Http/Controllers/EnderecoController.php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Controllers;

use App\Modules\Paciente\Services\CepService;
use Illuminate\Http\JsonResponse;

final class EnderecoController
{
    public function __construct(
        private readonly CepService $cepService,
    ) {}

    public function buscarPorCep(string $cep): JsonResponse
    {
        $endereco = $this->cepService->buscar($cep);

        return response()->json(['data' => $endereco]);
    }
}
```

**Step 3: Commit**

```bash
git add app/Modules/Paciente/Http/Controllers/ app/Modules/Paciente/Services/CepService.php
git commit -m "feat(paciente): adicionar controllers e CepService"
```

---

## Task 10: Seeders

**Files:**
- Create: `app/Modules/Paciente/Database/Seeders/AlergiaSeeder.php`
- Create: `app/Modules/Paciente/Database/Seeders/CondicaoCronicaSeeder.php`
- Create: `app/Modules/Paciente/Database/Seeders/PacienteSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` — adicionar chamada aos seeders do módulo

**Step 1: Criar seeders**

`AlergiaSeeder` — insere alergias comuns:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Seeders;

use App\Modules\Paciente\Models\Alergia;
use Illuminate\Database\Seeder;

final class AlergiaSeeder extends Seeder
{
    public function run(): void
    {
        $alergias = [
            'Penicilina', 'Dipirona', 'AAS', 'Sulfa', 'Latex',
            'Ibuprofeno', 'Contraste Iodado', 'Frutos do Mar', 'Amendoim',
            'Gluten', 'Nimesulida', 'Amoxicilina', 'Cefalosporina',
            'Paracetamol', 'Diclofenaco',
        ];

        foreach ($alergias as $nome) {
            Alergia::query()->firstOrCreate(['nome' => $nome]);
        }
    }
}
```

`CondicaoCronicaSeeder` — insere condições crônicas comuns:
```php
<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Database\Seeders;

use App\Modules\Paciente\Models\CondicaoCronica;
use Illuminate\Database\Seeder;

final class CondicaoCronicaSeeder extends Seeder
{
    public function run(): void
    {
        $condicoes = [
            'Hipertensão Arterial', 'Diabetes Tipo 2', 'Diabetes Tipo 1',
            'Asma', 'DPOC', 'Insuficiência Cardíaca', 'Hipotireoidismo',
            'Hipertireoidismo', 'Artrite Reumatoide', 'Lúpus Eritematoso Sistêmico',
            'Fibromialgia', 'Doença Celíaca', 'Epilepsia', 'Depressão',
            'Ansiedade Generalizada',
        ];

        foreach ($condicoes as $nome) {
            CondicaoCronica::query()->firstOrCreate(['nome' => $nome]);
        }
    }
}
```

`PacienteSeeder` — cria pacientes de exemplo para os médicos de teste. Verificar quais médicos existem via `UserSeeder` (Glayson e Pedro Verner) e criar 3-5 pacientes para cada, com alergias, condições e endereço.

**Step 2: Adicionar no `DatabaseSeeder.php`**

Chamar `AlergiaSeeder`, `CondicaoCronicaSeeder` e `PacienteSeeder` após o `UserSeeder`.

**Step 3: Rodar os seeders**

```bash
php artisan db:seed
```

**Step 4: Commit**

```bash
git add app/Modules/Paciente/Database/Seeders/ database/seeders/DatabaseSeeder.php
git commit -m "feat(paciente): adicionar seeders de alergias, condições crônicas e pacientes"
```

---

## Task 11: Testes — Listagem de Pacientes

**Files:**
- Create: `app/Modules/Paciente/Tests/Feature/ListPacienteTest.php`

**Step 1: Escrever os testes**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Paciente\Models\Paciente;

it('lista pacientes do médico autenticado', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/pacientes');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'name', 'cpf', 'phone', 'birth_date', 'gender', 'status']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

it('não lista pacientes de outro médico', function (): void {
    $user = User::factory()->doctor()->create();
    $otherUser = User::factory()->doctor()->create();
    Paciente::factory()->count(2)->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->getJson('/api/pacientes');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('filtra pacientes por busca de nome', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->create(['user_id' => $user->id, 'nome' => 'Maria da Silva']);
    Paciente::factory()->create(['user_id' => $user->id, 'nome' => 'João Santos']);

    $response = $this->actingAs($user)->getJson('/api/pacientes?busca=Maria');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Maria da Silva');
});

it('filtra pacientes por status', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    Paciente::factory()->create(['user_id' => $user->id, 'status' => 'inactive']);

    $response = $this->actingAs($user)->getJson('/api/pacientes?status=active');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('pagina os resultados', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->count(20)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/pacientes?per_page=5&page=1');

    $response->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.total', 20);
});

it('requer autenticação para listar pacientes', function (): void {
    $response = $this->getJson('/api/pacientes');

    $response->assertUnauthorized();
});
```

**Step 2: Rodar os testes e verificar que falham (controllers ainda não estão criados — neste plano os controllers já foram criados na Task 9, então devem passar)**

```bash
php artisan test app/Modules/Paciente/Tests/Feature/ListPacienteTest.php --compact
```

Expected: PASS (todos os 6 testes).

**Step 3: Commit**

```bash
git add app/Modules/Paciente/Tests/Feature/ListPacienteTest.php
git commit -m "test(paciente): adicionar testes de listagem de pacientes"
```

---

## Task 12: Testes — Criação de Pacientes

**Files:**
- Create: `app/Modules/Paciente/Tests/Feature/CreatePacienteTest.php`

**Step 1: Escrever os testes**

Testar: criação com dados válidos, criação com endereço, criação com alergias/condições, validação de campos obrigatórios, CPF duplicado, dados inválidos.

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Paciente\Models\Alergia;
use App\Modules\Paciente\Models\Paciente;

it('cria um paciente com dados mínimos', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->postJson('/api/pacientes', [
        'name' => 'Maria da Silva',
        'cpf' => '123.456.789-00',
        'phone' => '(11) 99876-5432',
        'birth_date' => '1985-03-15',
        'gender' => 'female',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Maria da Silva')
        ->assertJsonPath('data.cpf', '123.456.789-00')
        ->assertJsonPath('data.gender', 'female')
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('pacientes', [
        'user_id' => $user->id,
        'nome' => 'Maria da Silva',
        'cpf' => '123.456.789-00',
    ]);
});

it('cria um paciente com endereço', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->postJson('/api/pacientes', [
        'name' => 'João Santos',
        'cpf' => '987.654.321-00',
        'phone' => '(11) 98765-4321',
        'birth_date' => '1978-07-22',
        'gender' => 'male',
        'address' => [
            'cep' => '04101-000',
            'street' => 'Rua das Flores',
            'number' => '123',
            'neighborhood' => 'Vila Mariana',
            'city' => 'São Paulo',
            'state' => 'SP',
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.address.street', 'Rua das Flores')
        ->assertJsonPath('data.address.city', 'São Paulo');
});

it('cria um paciente com alergias e condições crônicas', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->postJson('/api/pacientes', [
        'name' => 'Ana Ferreira',
        'cpf' => '456.789.123-00',
        'phone' => '(21) 99123-4567',
        'birth_date' => '1990-11-08',
        'gender' => 'female',
        'allergies' => ['Penicilina', 'Dipirona'],
        'chronic_conditions' => ['Hipertensão Arterial'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.allergies', ['Penicilina', 'Dipirona'])
        ->assertJsonPath('data.chronic_conditions', ['Hipertensão Arterial']);

    $this->assertDatabaseHas('alergias', ['nome' => 'Penicilina']);
    $this->assertDatabaseHas('alergias', ['nome' => 'Dipirona']);
});

it('rejeita criação sem campos obrigatórios', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->postJson('/api/pacientes', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'cpf', 'phone', 'birth_date', 'gender']);
});

it('rejeita CPF duplicado para o mesmo médico', function (): void {
    $user = User::factory()->doctor()->create();
    Paciente::factory()->create(['user_id' => $user->id, 'cpf' => '123.456.789-00']);

    $response = $this->actingAs($user)->postJson('/api/pacientes', [
        'name' => 'Outro Paciente',
        'cpf' => '123.456.789-00',
        'phone' => '(11) 99999-0000',
        'birth_date' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('cpf');
});

it('permite mesmo CPF para médicos diferentes', function (): void {
    $user1 = User::factory()->doctor()->create();
    $user2 = User::factory()->doctor()->create();
    Paciente::factory()->create(['user_id' => $user1->id, 'cpf' => '123.456.789-00']);

    $response = $this->actingAs($user2)->postJson('/api/pacientes', [
        'name' => 'Mesmo CPF',
        'cpf' => '123.456.789-00',
        'phone' => '(11) 99999-0000',
        'birth_date' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertCreated();
});
```

**Step 2: Rodar os testes**

```bash
php artisan test app/Modules/Paciente/Tests/Feature/CreatePacienteTest.php --compact
```

Expected: PASS.

**Step 3: Commit**

```bash
git add app/Modules/Paciente/Tests/Feature/CreatePacienteTest.php
git commit -m "test(paciente): adicionar testes de criação de pacientes"
```

---

## Task 13: Testes — Show, Update, Delete

**Files:**
- Create: `app/Modules/Paciente/Tests/Feature/ShowPacienteTest.php`
- Create: `app/Modules/Paciente/Tests/Feature/UpdatePacienteTest.php`
- Create: `app/Modules/Paciente/Tests/Feature/DeletePacienteTest.php`

**Step 1: ShowPacienteTest**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Paciente\Models\Paciente;

it('retorna detalhes do paciente com endereço e relações', function (): void {
    $user = User::factory()->doctor()->create();
    $paciente = Paciente::factory()->withEndereco()->create(['user_id' => $user->id]);
    $paciente->alergias()->create(['nome' => 'Penicilina']);

    $response = $this->actingAs($user)->getJson("/api/pacientes/{$paciente->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'cpf', 'address', 'allergies', 'chronic_conditions', 'medical_history'],
        ])
        ->assertJsonPath('data.id', $paciente->id);
});

it('retorna 404 para paciente de outro médico', function (): void {
    $user = User::factory()->doctor()->create();
    $otherUser = User::factory()->doctor()->create();
    $paciente = Paciente::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->getJson("/api/pacientes/{$paciente->id}");

    $response->assertNotFound();
});

it('retorna 404 para paciente inexistente', function (): void {
    $user = User::factory()->doctor()->create();

    $response = $this->actingAs($user)->getJson('/api/pacientes/99999');

    $response->assertNotFound();
});
```

**Step 2: UpdatePacienteTest**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Paciente\Models\Paciente;

it('atualiza um paciente existente', function (): void {
    $user = User::factory()->doctor()->create();
    $paciente = Paciente::factory()->create(['user_id' => $user->id, 'nome' => 'Nome Antigo']);

    $response = $this->actingAs($user)->putJson("/api/pacientes/{$paciente->id}", [
        'name' => 'Nome Novo',
        'cpf' => $paciente->cpf,
        'phone' => $paciente->telefone,
        'birth_date' => $paciente->data_nascimento->format('Y-m-d'),
        'gender' => $paciente->sexo->toFrontend(),
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Nome Novo');

    $this->assertDatabaseHas('pacientes', ['id' => $paciente->id, 'nome' => 'Nome Novo']);
});

it('não permite atualizar paciente de outro médico', function (): void {
    $user = User::factory()->doctor()->create();
    $otherUser = User::factory()->doctor()->create();
    $paciente = Paciente::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->putJson("/api/pacientes/{$paciente->id}", [
        'name' => 'Hackeado',
        'cpf' => '111.111.111-11',
        'phone' => '(11) 99999-0000',
        'birth_date' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertNotFound();
});
```

**Step 3: DeletePacienteTest**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Paciente\Models\Paciente;

it('exclui (soft delete) um paciente', function (): void {
    $user = User::factory()->doctor()->create();
    $paciente = Paciente::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->deleteJson("/api/pacientes/{$paciente->id}");

    $response->assertOk()
        ->assertJsonPath('message', 'Paciente excluído com sucesso.');

    $this->assertSoftDeleted('pacientes', ['id' => $paciente->id]);
});

it('não permite excluir paciente de outro médico', function (): void {
    $user = User::factory()->doctor()->create();
    $otherUser = User::factory()->doctor()->create();
    $paciente = Paciente::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->deleteJson("/api/pacientes/{$paciente->id}");

    $response->assertNotFound();
});
```

**Step 4: Rodar todos os testes**

```bash
php artisan test app/Modules/Paciente/Tests/ --compact
```

Expected: Todos passam.

**Step 5: Commit**

```bash
git add app/Modules/Paciente/Tests/
git commit -m "test(paciente): adicionar testes de show, update e delete"
```

---

## Task 14: Teste — Busca de CEP

**Files:**
- Create: `app/Modules/Paciente/Tests/Feature/BuscaCepTest.php`

**Step 1: Escrever testes com Http::fake()**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;

it('retorna endereço para CEP válido', function (): void {
    Http::fake([
        'viacep.com.br/*' => Http::response([
            'cep' => '04101-000',
            'logradouro' => 'Rua Vergueiro',
            'bairro' => 'Vila Mariana',
            'localidade' => 'São Paulo',
            'uf' => 'SP',
        ]),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/enderecos/cep/04101000');

    $response->assertOk()
        ->assertJsonPath('data.cep', '04101-000')
        ->assertJsonPath('data.logradouro', 'Rua Vergueiro')
        ->assertJsonPath('data.cidade', 'São Paulo')
        ->assertJsonPath('data.estado', 'SP');
});

it('retorna 404 para CEP inválido', function (): void {
    Http::fake([
        'viacep.com.br/*' => Http::response(['erro' => true]),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/enderecos/cep/00000000');

    $response->assertNotFound();
});
```

**Step 2: Rodar os testes**

```bash
php artisan test app/Modules/Paciente/Tests/Feature/BuscaCepTest.php --compact
```

Expected: PASS.

**Step 3: Commit**

```bash
git add app/Modules/Paciente/Tests/Feature/BuscaCepTest.php
git commit -m "test(paciente): adicionar testes de busca de CEP"
```

---

## Task 15: Pint + Rodar Todos os Testes + Commit Final

**Step 1: Rodar Pint**

```bash
vendor/bin/pint --dirty
```

**Step 2: Rodar todos os testes do projeto**

```bash
php artisan test --compact
```

Expected: Todos passam (incluindo testes do módulo Auth existente).

**Step 3: Commit final de formatação (se Pint fez mudanças)**

```bash
git add -A
git commit -m "style(paciente): aplicar formatação Pint"
```

---

## Resumo das Tasks

| # | Task | Arquivos |
|---|------|----------|
| 1 | Enums | 4 enums |
| 2 | Migrations | 6 migrations |
| 3 | Models | 4 models |
| 4 | Factories | 4 factories + newFactory() nos models |
| 5 | ServiceProvider, Policy, Routes | 3 arquivos base |
| 6 | DTOs e Resources | 3 DTOs + 5 resources |
| 7 | Form Requests | 3 form requests |
| 8 | Service e Actions | 1 service + 3 actions |
| 9 | Controllers | 4 controllers + CepService |
| 10 | Seeders | 3 seeders + DatabaseSeeder |
| 11 | Testes — Listagem | 6 testes |
| 12 | Testes — Criação | 6 testes |
| 13 | Testes — Show/Update/Delete | 7 testes |
| 14 | Testes — CEP | 2 testes |
| 15 | Pint + teste completo | Formatação e validação |
