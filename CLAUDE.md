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

- Table and field names must be in Portuguese (e.g., `nome_paciente`, `data_nascimento`)
- Schema names must be in English (e.g., `auth`, `app`)
- Follow existing module conventions when creating new code
- Use early returns over nested conditionals
- Tests for each new feature are encouraged
- When creating new modules, create useful factories and seeders

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
- Use PHPDoc annotations for documentation:

```php
/**
 * List all patients for the authenticated user.
 *
 * Retrieves a paginated list of patients,
 * ordered by creation date (most recent first).
 *
 * @authenticated
 * @group Pacientes
 *
 * @queryParam page int The page number. Example: 1
 * @queryParam per_page int Items per page (max 100). Example: 15
 *
 * @response 200 scenario="Success" {
 *   "data": [
 *     {
 *       "id": 1,
 *       "nome": "João Silva",
 *       "cpf": "123.456.789-00",
 *       "criado_em": "2024-01-15T10:30:00Z"
 *     }
 *   ],
 *   "meta": {
 *     "current_page": 1,
 *     "total": 50
 *   }
 * }
 * @response 401 scenario="Unauthenticated" {"message": "Token inválido"}
 */
```

- Documentation must be **complete and descriptive** with concrete examples
- Include all possible response scenarios (success, validation errors, auth errors)
- Document all query parameters, body parameters, and headers

## Commit Guidelines

- Must use conventional commits (e.g., `feat:`, `fix:`, `refactor:`, `chore:`, `docs:`, `test:`)
- Do NOT mention Claude Code in commit messages
- Do NOT add "Co-Authored-By: Claude" or similar

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

### Testing

- Module tests live in `app/Modules/{ModuleName}/Tests/`
- Standard tests in `tests/Feature/` and `tests/Unit/`

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application.

## Foundational Context

This application is a Laravel application. Ensure you abide by these specific packages & versions.

- php - 8.2+
- laravel/framework (LARAVEL) - v12
- pestphp/pest (PEST) - v4

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isPacienteAtivo`, not `check()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments

- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks

- Add useful array shape type definitions for arrays when appropriate.

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `TipoSanguineo`, `StatusProntuario`, `Sexo`.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.).
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input.

### Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== laravel/v12 rules ===

## Laravel 12

- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure

- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest

- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- Tests should test all of the happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval.

### Running Tests

- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions

- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar:

```php
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
```

### Mocking

- When mocking, you can use the `Pest\Laravel\mock` function, but always import it via `use function Pest\Laravel\mock;` before using it.

### Datasets

- Use datasets in Pest to simplify tests which have a lot of duplicated data:

```php
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@example.com',
    'taylor' => 'taylor@example.com',
]);
```
</laravel-boost-guidelines>