# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

E-Medical Record Backend is a Laravel 12 API backend for medical records management. The application uses PostgreSQL and follows a modular architecture pattern.

## Common Commands

```bash
# Development server (runs: server, queue, logs, vite concurrently)
composer dev

# Initial project setup (install deps, generate key, migrate, build assets)
composer setup

# Run all tests
composer test

# Run specific test file
php artisan test app/Modules/ModuleName/Tests/Feature/TestFile.php

# Run tests with filter
php artisan test --filter=testName

# Code formatting
vendor/bin/pint --dirty
```

## Code Guidelines

- Follow existing module conventions when creating new code
- Use early returns over nested conditionals
- Evitar o uso de ternários por questões de legibilidade; prefira early returns, nullable, etc. Se não der para evitar, prefira condições nomeadas
- O uso de PHPDocs, DTOs e tipagem forte é incentivado para facilitar a manutenção e garantir dependência em contratos bem definidos
- Tests for each new feature are encouraged
- When creating new modules, create useful factories and seeders
- **Route Model Binding is prohibited** — never use implicit or explicit route model binding. Always receive the ID (or other identifier) as a primitive parameter and resolve the model manually (e.g., via repository, service, or query)
- Prefer dependency injection via constructor for services and repositories
- Prefer collections and higher-order functions over raw loops for array manipulations
- Prefer events and listeners for decoupling components (e.g., dispatching domain events after significant actions)
- Prefer Laravel Exceptions (`ModelNotFoundException`, `InvalidArgumentException`, etc.) for error handling — let the exception handler produce the response
- Always use Context7 MCP tools (`resolve-library-id` and `query-docs`) for non-Laravel library documentation, setup, or code generation. For Laravel ecosystem packages, use `search-docs` from Laravel Boost instead

## Language Policy (Code vs Data)

**All code MUST be in English.** The only exceptions are database-related names and user-facing strings.

### What MUST be in English

- **Module names** — `Patient`, `MedicalRecord`, `Auth` (never `Paciente`, `Prontuario`)
- **Class names** — `PatientController`, `CreatePatientAction`, `PatientService`, `PatientPolicy`
- **Method names** — `findForUser()`, `listPatients()`, `syncAllergies()` (never `getPaciente()`, `sincronizarAlergias()`)
- **Variable names** — `$patient`, `$allergies`, `$chronicConditions` (never `$paciente`, `$alergias`)
- **DTO property names** — `$firstName`, `$birthDate`, `$bloodType`
- **PHPDoc blocks** — all descriptions, `@param`, `@return`, `@throws` in English
- **Scribe/API documentation** — `@group`, descriptions, parameter docs all in English
- **Route URIs** — `/patients`, `/patients/{id}`, `/allergies`, `/addresses/zip/{zip}`
- **Enum case names** — `TitleCase` in English: `Male`, `Female`, `Active`, `Inactive`
- **Test descriptions** — `it('lists patients for the authenticated doctor')`
- **Schema names** — `auth`, `app`

### What MUST be in Portuguese

- **Database table names** — `pacientes`, `alergias`, `condicoes_cronicas`, `enderecos`
- **Database column names** — `nome`, `cpf`, `data_nascimento`, `tipo_sanguineo`
- **Eloquent Model class names** — `Paciente`, `Alergia`, `CondicaoCronica`, `Endereco` (to mirror the table)
- **Eloquent relationship method names** — `paciente->alergias()`, `paciente->condicoesCronicas()` (to mirror the table/model)
- **User-facing strings** — validation messages, API error messages returned to the frontend
- **Seeder data** — e.g., allergy names like `'Penicilina'`, condition names like `'Hipertensão Arterial'`

### Examples

```php
// ✅ Correct — English code, Portuguese model reflecting the table
class PatientController
{
    /**
     * List all patients for the authenticated doctor.
     */
    public function index(ListPatientRequest $request): AnonymousResourceCollection
    {
        $patients = $this->patientService->listForUser(
            userId: $request->user()->id,
            filters: $request->validated(),
        );

        return PatientListResource::collection($patients);
    }
}

// Model mirrors the Portuguese table name
class Paciente extends Model
{
    protected $table = 'pacientes';

    public function alergias(): BelongsToMany { /* ... */ }
}

// ❌ Wrong — mixing Portuguese in code
class PacienteController  // Should be PatientController
{
    public function getPacientes() { /* ... */ }  // Should be listPatients/index
}
```

### Summary Table

| Element | Language | Example |
|---------|----------|---------|
| Module folder | English | `app/Modules/Patient/` |
| Controller | English | `PatientController` |
| Service/Action/DTO | English | `CreatePatientAction`, `PatientDTO` |
| Method names | English | `findForUser()`, `syncAllergies()` |
| PHPDoc | English | `/** Retrieve a patient by ID. */` |
| Scribe docs | English | `@group Patients` |
| Route URIs | English | `/patients/{id}` |
| Test descriptions | English | `it('creates a patient with address')` |
| Model class | Portuguese | `Paciente`, `Alergia` |
| Relationship methods | Portuguese | `->alergias()`, `->condicoesCronicas()` |
| Table/column names | Portuguese | `pacientes.nome`, `data_nascimento` |
| Validation messages | Portuguese | `'O campo nome é obrigatório.'` |

## Diretrizes de Português

Todos os textos voltados ao usuário (mensagens de erro, labels, descrições, comentários em português) devem seguir rigorosamente as normas da língua portuguesa.

### Acentuação

- **Sempre utilizar acentos corretamente** — palavras como `código`, `número`, `médico`, `diagnóstico`, `histórico`
- **Atenção a palavras paroxítonas e proparoxítonas** — `paciente` (sem acento), `prontuário` (com acento)
- **Cuidado com acentos diferenciais** — `pôde` (passado) vs `pode` (presente)

### Pontuação

- **Usar vírgulas corretamente** — antes de conjunções adversativas, em enumerações, para isolar apostos
- **Ponto final obrigatório** — em frases completas e mensagens de erro
- **Dois-pontos** — antes de explicações ou listagens
- **Aspas** — usar aspas duplas para citações e valores literais em mensagens

### Gramática

- **Concordância verbal e nominal** — o verbo deve concordar com o sujeito; adjetivos com substantivos
- **Regência verbal** — atenção a verbos que exigem preposições específicas (ex: `assistir a`, `obedecer a`)
- **Uso correto de pronomes** — evitar `aonde` quando o correto é `onde`; usar `este/esse` adequadamente
- **Plural de palavras compostas** — seguir regras específicas (ex: `exames-padrão`, `prontuários-modelo`)

### Exemplos em Código

```php
// ✅ Correto
'required' => 'O campo :attribute é obrigatório.',
'email' => 'O campo :attribute deve ser um endereço de e-mail válido.',
'cpf.unique' => 'Este CPF já está cadastrado no sistema.',

// ❌ Incorreto
'required' => 'O campo :attribute e obrigatorio.',  // Falta acento
'email' => 'O campo :attribute deve ser um endereco de email valido',  // Falta acentos e ponto
'cpf.unique' => 'Esse CPF ja está cadastrado no sistema',  // Falta acento em "já" e ponto final
```

### Revisão Obrigatória

- **Revisar todas as strings em português** antes de finalizar alterações
- **Verificar mensagens de validação** — especialmente em Form Requests
- **Atenção a textos em migrations** — comentários e descrições de colunas
- **Nota:** PHPDoc e Scribe documentation are written in English (see Language Policy above). Portuguese rules apply only to user-facing strings (validation messages, error responses)

## Strong Typing and PHPDoc

### Strict Types

- **Always declare `strict_types`** at the top of every PHP file:

```php
<?php

declare(strict_types=1);
```

### Type Declarations

- **All parameters must have type hints** — no exceptions
- **All methods must have explicit return types** — including `void`
- **Use nullable types (`?Type`)** when a value can be null
- **Avoid `mixed`** — only use when truly necessary

```php
// ✅ Good
public function findPatient(int $id): ?Paciente
{
    return Paciente::find($id);
}

public function processItems(array $items): void
{
    // ...
}

// ❌ Bad
public function findPatient($id)
{
    return Paciente::find($id);
}
```

### PHPDoc Blocks

Use PHPDoc to supplement native types when additional context is needed:

- **Array shapes** — always document array structure
- **Generic collections** — specify collection item types
- **Exceptions** — document thrown exceptions with `@throws`
- **Complex return types** — when native types aren't expressive enough

```php
/**
 * Retrieve paginated patients with their records.
 *
 * @param array{
 *     page?: int,
 *     per_page?: int,
 *     filters?: array{status?: string, tipo?: string}
 * } $options Pagination and filter options
 *
 * @return Collection<int, Paciente>
 *
 * @throws InvalidArgumentException When page is less than 1
 */
public function getPatients(array $options = []): Collection
{
    // ...
}
```

### When to Use PHPDoc

| Scenario | Use PHPDoc? | Example |
|----------|-------------|---------|
| Simple scalar types | No | `public function getNome(): string` |
| Array with known structure | Yes | `@param array{id: int, nome: string} $data` |
| Collection of objects | Yes | `@return Collection<int, Paciente>` |
| Eloquent relationships | Yes | `@return HasMany<Prontuario, $this>` |
| Thrown exceptions | Yes | `@throws ValidationException` |
| Deprecated methods | Yes | `@deprecated Use newMethod() instead` |

### Property Types

- **Always type class properties** with PHP 8+ property types
- Use PHPDoc for additional context (array shapes, generics)

```php
final class PacienteService
{
    /**
     * @param Collection<int, Paciente> $cachedPacientes
     */
    public function __construct(
        private readonly PacienteRepository $repository,
        private Collection $cachedPacientes = new Collection(),
    ) {}
}
```

### Eloquent Models

Document relationships and casts for better IDE support:

```php
/**
 * @property int $id
 * @property string $nome
 * @property string $cpf
 * @property Carbon $data_nascimento
 *
 * @property-read Collection<int, Prontuario> $prontuarios
 * @property-read Endereco|null $endereco
 */
class Paciente extends Model
{
    /**
     * @return HasMany<Prontuario, $this>
     */
    public function prontuarios(): HasMany
    {
        return $this->hasMany(Prontuario::class);
    }
}
```

## DTOs (Data Transfer Objects)

- **Use DTOs** to provide strong typing and robustness at interface boundaries
- DTOs ensure type safety when passing data between layers (Controller → Service → Action)
- Place DTOs in `app/Modules/{ModuleName}/DTOs/`

```php
final readonly class CreatePacienteDTO
{
    public function __construct(
        public string $nome,
        public string $cpf,
        public Carbon $data_nascimento,
        public ?string $telefone = null,
    ) {}

    public static function fromRequest(CreatePacienteRequest $request): self
    {
        return new self(
            nome: $request->validated('nome'),
            cpf: $request->validated('cpf'),
            data_nascimento: Carbon::parse($request->validated('data_nascimento')),
            telefone: $request->validated('telefone'),
        );
    }
}
```

- Prefer `readonly` classes for immutability
- Include factory methods like `fromRequest()` or `fromArray()` for convenience

## API Documentation (Scribe)

- **Always update Scribe documentation** after creating or modifying API endpoints
- Run `php artisan scribe:generate` to regenerate docs after changes
- **All Scribe documentation must be in English** — descriptions, group names, parameter docs, scenario names
- Use PHPDoc annotations for documentation:

```php
/**
 * List all patients for the authenticated doctor.
 *
 * Retrieves a paginated list of patients,
 * ordered by creation date (most recent first).
 *
 * @authenticated
 * @group Patients
 *
 * @queryParam page int The page number. Example: 1
 * @queryParam per_page int Items per page (max 100). Example: 15
 * @queryParam search string Search by name or CPF. Example: Maria
 *
 * @response 200 scenario="Success" {
 *   "data": [
 *     {
 *       "id": 1,
 *       "name": "João Silva",
 *       "cpf": "123.456.789-00",
 *       "created_at": "2024-01-15T10:30:00Z"
 *     }
 *   ],
 *   "meta": {
 *     "current_page": 1,
 *     "total": 50
 *   }
 * }
 * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
 */
```

- Documentation must be **complete and descriptive** with concrete, realistic examples (real-looking names, CPFs, dates — not generic placeholders)
- Every endpoint MUST include `@response` blocks for ALL expected scenarios:
  - `200` — Success (with full realistic data payload)
  - `201` — Created (when applicable)
  - `401` — Unauthenticated
  - `403` — Forbidden (when authorization/policies apply)
  - `404` — Not found (when fetching by ID)
  - `422` — Validation error (with realistic `errors` object showing field-level messages)
  - Use `scenario="Description"` to label each response
- Document all query parameters, body parameters, and headers
- Any change that affects API behavior (new endpoints, modified responses, added/removed fields, changed status codes) **MUST** include updated Scribe documentation and regenerate docs with `php artisan scribe:generate`

## Commit Guidelines

- Must use conventional commits (e.g., `feat:`, `fix:`, `refactor:`, `chore:`, `docs:`, `test:`)
- Do NOT mention Claude Code in commit messages
- Do NOT add "Co-Authored-By: Claude" or similar
- **Versionamento de artefatos de IA/sessão**:
  - `/.serena` — NUNCA committar (cache de sessão do Serena MCP). Já no `.gitignore`.
  - `docs/`, `.claude/`, `.scribe/` — versionados. Planos, settings do Claude e cache do Scribe ficam no repositório para o time compartilhar.

## Pull Request Guidelines

- Title and description must be in Portuguese
- Read past PRs to understand the style and format used in this repository
- Do NOT mention Claude Code in PR descriptions
- PRs must be targeted to the `main` branch

## Architecture

### Modular Structure

The application uses a domain-driven modular architecture under `app/Modules/`. Each module is self-contained:

```
app/Modules/{ModuleName}/
├── Actions/           # Single-action classes
├── Database/
│   ├── Factories/
│   ├── Migrations/
│   └── Seeders/
├── DTOs/              # Data Transfer Objects
├── Events/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/      # Form Request validation
├── Jobs/
├── Listeners/
├── Models/
├── Observers/
├── Policies/
├── Providers/         # {ModuleName}ServiceProvider.php
├── Resources/views/   # Blade templates (emails, etc.)
├── Services/          # Business logic services
├── Tests/
│   ├── Feature/
│   └── Unit/
└── routes.php         # Module-specific routes
```

### Module Registration

Modules are auto-discovered by `ModulesServiceProvider` which:

- Registers each module's ServiceProvider (`App\Modules\{Name}\Providers\{Name}ServiceProvider`)
- Loads module routes from `routes.php`
- Loads migrations from `Database/Migrations/`

Directories prefixed with `_` (e.g., `_Contracts`, `_Helpers`, `_Traits`) are excluded from auto-loading.

### Database

- **Engine**: PostgreSQL
- **Connection**: Default connection is `pgsql`

### Creating a New Module Feature

1. Create the module structure (or enhance existing module)
2. Define the Eloquent model in `Models/`
3. Create migrations in `Database/Migrations/`
4. Create Form Request validation in `Http/Requests/`
5. Create service in `Services/` for business logic
6. Create controller in `Http/Controllers/`
7. Add routes to `routes.php`
8. Register bindings in `Providers/{ModuleName}ServiceProvider`
9. Create factories and seeders in `Database/`
10. Write feature tests in `Tests/`
11. Add Scribe documentation to controller methods
12. Run `vendor/bin/pint --dirty` to format code
13. Run `php artisan scribe:generate` to regenerate docs
14. Run tests: `php artisan test`

### Testing

- Module tests live in `app/Modules/{ModuleName}/Tests/`
- Standard tests in `tests/Feature/` and `tests/Unit/`

## Session Checkpoints (Serena Memories)

Always use Serena MCP and its memory system to maintain session continuity. This is **mandatory** for every working session.

### Checkpoint File

- Use `mcp__serena__write_memory` to create/update a checkpoint memory called `session-checkpoint.md`
- This file must track:
  - **What was done** — completed tasks, files changed, decisions made
  - **What is being done** — current task in progress, current state
  - **What will be done** — pending tasks, next steps planned
- Update the checkpoint **frequently** as work progresses (at minimum: when starting a task, completing a task, or before any natural pause)

### After Session Compaction

- **Every time the session context is compacted**, immediately read the checkpoint memory using `mcp__serena__read_memory` with `session-checkpoint.md` to recover the full context of where you were
- This ensures no loss of continuity even when conversation history is compressed

### Format Example

```markdown
# Session Checkpoint

## Completed
- [x] Created Patient module migration
- [x] Added Paciente model with relationships

## In Progress
- [ ] Building PatientController with CRUD endpoints
  - Status: index and show done, working on store

## Next Steps
- [ ] Create PatientService
- [ ] Write feature tests for Patient endpoints
- [ ] Add Scribe documentation
```

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5.0
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.
</laravel-boost-guidelines>
