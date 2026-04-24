# Exam Requests Module Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement the exam requests sub-module inside MedicalRecord — TUSS catalog (read-only), exam requests (nested under prontuários), exam request models (per-doctor templates), medical report templates (per-doctor), and seeders with 7 default models + 2 report templates from the frontend.

**Architecture:** Follows existing MedicalRecord module patterns (see PrescriptionTemplateController/Service as reference). Exam requests store items as JSONB (consumed as block for printing, no numeric evolution). Doctor can select predefined models, add custom exams, attach CID-10 and clinical indication, and generate medical reports from templates. Controllers delegate to Services, DTOs at boundaries, Resources map PT→EN.

**Tech Stack:** Laravel 12, PHP 8.5, PostgreSQL, Sanctum SPA auth, Pest 4

**Design doc:** Serena memory `medical-record-module/09-exam-requests-design`

**Reference patterns:**
- Controller: `app/Modules/MedicalRecord/Http/Controllers/PrescriptionTemplateController.php`
- Service: `app/Modules/MedicalRecord/Services/PrescriptionTemplateService.php`
- Model: `app/Modules/MedicalRecord/Models/ModeloPrescricao.php`
- Policy: `app/Modules/MedicalRecord/Policies/PrescriptionTemplatePolicy.php`

---

## Task 1: Migrations

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_23_000001_create_solicitacoes_exames_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_23_000002_create_modelos_solicitacao_exames_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_23_000003_create_modelos_relatorio_medico_table.php`

**Step 1: Create migrations via artisan**

```bash
php artisan make:migration create_solicitacoes_exames_table --path=app/Modules/MedicalRecord/Database/Migrations --no-interaction
php artisan make:migration create_modelos_solicitacao_exames_table --path=app/Modules/MedicalRecord/Database/Migrations --no-interaction
php artisan make:migration create_modelos_relatorio_medico_table --path=app/Modules/MedicalRecord/Database/Migrations --no-interaction
```

**Step 2: Write migration contents**

`solicitacoes_exames`:
```php
Schema::create('solicitacoes_exames', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->string('modelo_id')->nullable();
    $table->string('cid_10')->nullable();
    $table->text('indicacao_clinica')->nullable();
    $table->jsonb('itens');
    $table->jsonb('relatorio_medico')->nullable();
    $table->timestamp('impresso_em')->nullable();
    $table->timestamps();

    $table->index('prontuario_id');
});
```

`modelos_solicitacao_exames`:
```php
Schema::create('modelos_solicitacao_exames', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('nome');
    $table->string('categoria')->nullable();
    $table->jsonb('itens');
    $table->timestamps();

    $table->index('user_id');
});
```

`modelos_relatorio_medico`:
```php
Schema::create('modelos_relatorio_medico', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('nome');
    $table->text('corpo_template');
    $table->timestamps();

    $table->index('user_id');
});
```

**Step 3: Run migrations**

```bash
php artisan migrate
```

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Migrations/
git commit -m "feat(medical-record): add migrations for exam requests, models, and report templates"
```

---

## Task 2: Models + Factories

**Files:**
- Create: `app/Modules/MedicalRecord/Models/SolicitacaoExame.php`
- Create: `app/Modules/MedicalRecord/Models/ModeloSolicitacaoExame.php`
- Create: `app/Modules/MedicalRecord/Models/ModeloRelatorioMedico.php`
- Modify: `app/Modules/MedicalRecord/Models/Prontuario.php` (add relationship)
- Create: `app/Modules/MedicalRecord/Database/Factories/ExamRequestFactory.php`
- Create: `app/Modules/MedicalRecord/Database/Factories/ExamRequestModelFactory.php`
- Create: `app/Modules/MedicalRecord/Database/Factories/MedicalReportTemplateFactory.php`

**Step 1: Create models**

`SolicitacaoExame` — follows `ModeloPrescricao` pattern:
```php
/**
 * @property int $id
 * @property int $prontuario_id
 * @property string|null $modelo_id
 * @property string|null $cid_10
 * @property string|null $indicacao_clinica
 * @property array<int, array{id: string, name: string, tuss_code?: string, selected: bool}> $itens
 * @property array{template_id?: string, body: string}|null $relatorio_medico
 * @property \Illuminate\Support\Carbon|null $impresso_em
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 */
class SolicitacaoExame extends Model
{
    protected $table = 'solicitacoes_exames';

    protected $fillable = [
        'prontuario_id', 'modelo_id', 'cid_10', 'indicacao_clinica',
        'itens', 'relatorio_medico', 'impresso_em',
    ];

    protected function casts(): array
    {
        return [
            'itens' => 'array',
            'relatorio_medico' => 'array',
            'impresso_em' => 'datetime',
        ];
    }
}
```

Relationships:
- `SolicitacaoExame->prontuario()`: BelongsTo Prontuario
- `Prontuario->solicitacoesExames()`: HasMany SolicitacaoExame

`ModeloSolicitacaoExame` — follows `ModeloPrescricao` pattern:
```php
/**
 * @property int $id
 * @property int $user_id
 * @property string $nome
 * @property string|null $categoria
 * @property array<int, array{id: string, name: string, tuss_code?: string}> $itens
 * @property-read User $user
 */
class ModeloSolicitacaoExame extends Model
{
    protected $table = 'modelos_solicitacao_exames';

    protected $fillable = ['user_id', 'nome', 'categoria', 'itens'];

    // casts: itens => array
    // scope: forUser($userId)
    // relationship: user() BelongsTo User
}
```

`ModeloRelatorioMedico` — same pattern:
```php
/**
 * @property int $id
 * @property int $user_id
 * @property string $nome
 * @property string $corpo_template
 * @property-read User $user
 */
class ModeloRelatorioMedico extends Model
{
    protected $table = 'modelos_relatorio_medico';

    protected $fillable = ['user_id', 'nome', 'corpo_template'];

    // scope: forUser($userId)
    // relationship: user() BelongsTo User
}
```

**Step 2: Create factories**

`ExamRequestFactory`: generates realistic exam request with 3-5 items, random CID-10, clinical indication, optional report.

`ExamRequestModelFactory`: generates a model with 5-10 exam items, category from ['geral', 'cardiologico', 'alergologico', 'gastrointestinal', 'reumatologico'].

`MedicalReportTemplateFactory`: generates a report template with `{{CID_10}}` placeholder.

**Step 3: Run tests to verify models load**

```bash
php artisan test --compact --filter=ExamRequest
```

Expected: no tests yet (0 tests), no errors.

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Models/ app/Modules/MedicalRecord/Database/Factories/
git commit -m "feat(medical-record): add models and factories for exam requests sub-module"
```

---

## Task 3: DTOs

**Files:**
- Create: `app/Modules/MedicalRecord/DTOs/CreateExamRequestDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/UpdateExamRequestDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/CreateExamRequestModelDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/UpdateExamRequestModelDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/CreateMedicalReportTemplateDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/UpdateMedicalReportTemplateDTO.php`

**Step 1: Create DTOs**

All DTOs are `final readonly class` with `fromRequest()` factory method.

`CreateExamRequestDTO`:
```php
final readonly class CreateExamRequestDTO
{
    /**
     * @param array<int, array{id: string, name: string, tuss_code?: string, selected: bool}> $itens
     * @param array{template_id?: string, body: string}|null $relatorioMedico
     */
    public function __construct(
        public ?string $modeloId,
        public array $itens,
        public ?string $cid10,
        public ?string $indicacaoClinica,
        public ?array $relatorioMedico,
    ) {}

    public static function fromRequest(StoreExamRequestRequest $request): self
    {
        $validated = $request->validated();
        return new self(
            modeloId: $validated['model_id'] ?? null,
            itens: $validated['items'],
            cid10: $validated['cid_10'] ?? null,
            indicacaoClinica: $validated['clinical_indication'] ?? null,
            relatorioMedico: isset($validated['medical_report']) ? [
                'template_id' => $validated['medical_report']['template_id'] ?? null,
                'body' => $validated['medical_report']['body'],
            ] : null,
        );
    }
}
```

`UpdateExamRequestDTO` — same fields but all nullable for partial update.

`CreateExamRequestModelDTO`:
```php
final readonly class CreateExamRequestModelDTO
{
    public function __construct(
        public string $nome,
        public ?string $categoria,
        public array $itens,
    ) {}
}
```

`UpdateExamRequestModelDTO` — all nullable.

`CreateMedicalReportTemplateDTO`:
```php
final readonly class CreateMedicalReportTemplateDTO
{
    public function __construct(
        public string $nome,
        public string $corpoTemplate,
    ) {}
}
```

`UpdateMedicalReportTemplateDTO` — all nullable.

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/DTOs/
git commit -m "feat(medical-record): add DTOs for exam requests sub-module"
```

---

## Task 4: Form Requests (Validation)

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Requests/StoreExamRequestRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/UpdateExamRequestRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/StoreExamRequestModelRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/UpdateExamRequestModelRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/StoreMedicalReportTemplateRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/UpdateMedicalReportTemplateRequest.php`

**Step 1: Create form requests**

`StoreExamRequestRequest` rules:
```php
return [
    'model_id' => ['nullable', 'string', 'max:255'],
    'items' => ['required', 'array', 'min:1'],
    'items.*.id' => ['required', 'string', 'max:255'],
    'items.*.name' => ['required', 'string', 'max:500'],
    'items.*.tuss_code' => ['nullable', 'string', 'max:50'],
    'items.*.selected' => ['required', 'boolean'],
    'cid_10' => ['nullable', 'string', 'max:20'],
    'clinical_indication' => ['nullable', 'string', 'max:5000'],
    'medical_report' => ['nullable', 'array'],
    'medical_report.template_id' => ['nullable', 'string', 'max:255'],
    'medical_report.body' => ['required_with:medical_report', 'string', 'max:10000'],
];
```

Portuguese messages for all fields.

`StoreExamRequestModelRequest` rules:
```php
return [
    'name' => ['required', 'string', 'max:255'],
    'category' => ['nullable', 'string', 'max:100'],
    'items' => ['required', 'array', 'min:1'],
    'items.*.id' => ['required', 'string', 'max:255'],
    'items.*.name' => ['required', 'string', 'max:500'],
    'items.*.tuss_code' => ['nullable', 'string', 'max:50'],
];
```

`StoreMedicalReportTemplateRequest` rules:
```php
return [
    'name' => ['required', 'string', 'max:255'],
    'body_template' => ['required', 'string', 'max:10000'],
];
```

Update variants: same rules but all nullable (except `items` array rules when items is present).

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Requests/
git commit -m "feat(medical-record): add form requests for exam requests sub-module"
```

---

## Task 5: Resources (PT→EN mapping)

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Resources/ExamRequestResource.php`
- Create: `app/Modules/MedicalRecord/Http/Resources/ExamRequestModelResource.php`
- Create: `app/Modules/MedicalRecord/Http/Resources/MedicalReportTemplateResource.php`

**Step 1: Create resources**

`ExamRequestResource`:
```php
return [
    'id' => $this->id,
    'medical_record_id' => $this->prontuario_id,
    'model_id' => $this->modelo_id,
    'cid_10' => $this->cid_10,
    'clinical_indication' => $this->indicacao_clinica,
    'items' => $this->itens,
    'medical_report' => $this->relatorio_medico,
    'printed_at' => $this->impresso_em?->toIso8601String(),
    'created_at' => $this->created_at->toIso8601String(),
    'updated_at' => $this->updated_at->toIso8601String(),
];
```

`ExamRequestModelResource`:
```php
return [
    'id' => $this->id,
    'name' => $this->nome,
    'category' => $this->categoria,
    'items' => $this->itens,
    'created_at' => $this->created_at->toIso8601String(),
    'updated_at' => $this->updated_at->toIso8601String(),
];
```

`MedicalReportTemplateResource`:
```php
return [
    'id' => $this->id,
    'name' => $this->nome,
    'body_template' => $this->corpo_template,
    'created_at' => $this->created_at->toIso8601String(),
    'updated_at' => $this->updated_at->toIso8601String(),
];
```

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Resources/
git commit -m "feat(medical-record): add resources for exam requests sub-module"
```

---

## Task 6: Services

**Files:**
- Create: `app/Modules/MedicalRecord/Services/ExamRequestService.php`
- Create: `app/Modules/MedicalRecord/Services/ExamRequestModelService.php`
- Create: `app/Modules/MedicalRecord/Services/MedicalReportTemplateService.php`

**Step 1: Create services**

Follow `PrescriptionTemplateService` pattern.

`ExamRequestService`:
- `listForRecord(int $prontuarioId)`: Get all exam requests for a medical record, ordered by created_at desc.
- `create(int $prontuarioId, CreateExamRequestDTO $dto)`: Create new exam request. Verify prontuário is not finalized (throw DomainException if it is).
- `update(int $requestId, UpdateExamRequestDTO $dto)`: Partial update. Verify prontuário is not finalized.
- `delete(int $requestId)`: Delete. Verify prontuário is not finalized.
- `markAsPrinted(int $requestId)`: Set `impresso_em` to now.
- `findOrFail(int $requestId)`: Find or throw NotFoundHttpException.

`ExamRequestModelService`:
- `listForUser(int $userId, ?string $category)`: List models filtered by category.
- `create(int $userId, CreateExamRequestModelDTO $dto)`: Create.
- `update(int $modelId, UpdateExamRequestModelDTO $dto)`: Partial update.
- `delete(int $modelId)`: Delete.
- `findOrFail(int $modelId)`: Find or throw NotFoundHttpException.

`MedicalReportTemplateService`:
- `listForUser(int $userId)`: List templates.
- `create(int $userId, CreateMedicalReportTemplateDTO $dto)`: Create.
- `update(int $templateId, UpdateMedicalReportTemplateDTO $dto)`: Partial update.
- `delete(int $templateId)`: Delete.
- `findOrFail(int $templateId)`: Find or throw NotFoundHttpException.

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Services/
git commit -m "feat(medical-record): add services for exam requests sub-module"
```

---

## Task 7: Policies

**Files:**
- Create: `app/Modules/MedicalRecord/Policies/ExamRequestPolicy.php`
- Create: `app/Modules/MedicalRecord/Policies/ExamRequestModelPolicy.php`
- Create: `app/Modules/MedicalRecord/Policies/MedicalReportTemplatePolicy.php`
- Modify: `app/Modules/MedicalRecord/Providers/MedicalRecordServiceProvider.php`

**Step 1: Create policies**

`ExamRequestPolicy` — authorize via prontuário ownership:
```php
public function view(User $user, SolicitacaoExame $request): bool
{
    return $user->id === $request->prontuario->user_id;
}

public function update(User $user, SolicitacaoExame $request): bool
{
    return $user->id === $request->prontuario->user_id;
}

public function delete(User $user, SolicitacaoExame $request): bool
{
    return $user->id === $request->prontuario->user_id;
}
```

`ExamRequestModelPolicy` and `MedicalReportTemplatePolicy` — follow `PrescriptionTemplatePolicy` pattern (user_id ownership).

**Step 2: Register policies in ServiceProvider**

Add to `boot()`:
```php
Gate::policy(SolicitacaoExame::class, ExamRequestPolicy::class);
Gate::policy(ModeloSolicitacaoExame::class, ExamRequestModelPolicy::class);
Gate::policy(ModeloRelatorioMedico::class, MedicalReportTemplatePolicy::class);
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Policies/ app/Modules/MedicalRecord/Providers/
git commit -m "feat(medical-record): add policies for exam requests sub-module"
```

---

## Task 8: Controllers + Routes

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Controllers/ExamRequestController.php`
- Create: `app/Modules/MedicalRecord/Http/Controllers/ExamRequestModelController.php`
- Create: `app/Modules/MedicalRecord/Http/Controllers/MedicalReportTemplateController.php`
- Modify: `app/Modules/MedicalRecord/routes.php`

**Step 1: Create controllers**

`ExamRequestController` (nested under medical record):
- `index(Request $request, int $medicalRecordId)`: List exam requests for a medical record.
- `store(StoreExamRequestRequest $request, int $medicalRecordId)`: Create exam request. Verify prontuário exists and belongs to user via policy. Check not finalized.
- `update(UpdateExamRequestRequest $request, int $medicalRecordId, int $id)`: Update exam request.
- `destroy(Request $request, int $medicalRecordId, int $id)`: Delete exam request.
- `print(Request $request, int $medicalRecordId, int $id)`: Mark as printed (POST endpoint).

`ExamRequestModelController` (per-doctor, not nested):
- `index(Request $request)`: List models with optional `category` filter.
- `store(StoreExamRequestModelRequest $request)`: Create model.
- `update(UpdateExamRequestModelRequest $request, int $id)`: Update model.
- `destroy(Request $request, int $id)`: Delete model.

`MedicalReportTemplateController` (per-doctor, not nested):
- `index(Request $request)`: List templates.
- `store(StoreMedicalReportTemplateRequest $request)`: Create template.
- `update(UpdateMedicalReportTemplateRequest $request, int $id)`: Update template.
- `destroy(Request $request, int $id)`: Delete template.

**Step 2: Add routes**

```php
// Exam Requests (nested under medical record)
Route::get('/medical-records/{medicalRecordId}/exam-requests', [ExamRequestController::class, 'index']);
Route::post('/medical-records/{medicalRecordId}/exam-requests', [ExamRequestController::class, 'store']);
Route::put('/medical-records/{medicalRecordId}/exam-requests/{id}', [ExamRequestController::class, 'update']);
Route::delete('/medical-records/{medicalRecordId}/exam-requests/{id}', [ExamRequestController::class, 'destroy']);
Route::post('/medical-records/{medicalRecordId}/exam-requests/{id}/print', [ExamRequestController::class, 'print']);

// Exam Request Models (per-doctor templates)
Route::get('/exam-request-models', [ExamRequestModelController::class, 'index']);
Route::post('/exam-request-models', [ExamRequestModelController::class, 'store']);
Route::put('/exam-request-models/{id}', [ExamRequestModelController::class, 'update']);
Route::delete('/exam-request-models/{id}', [ExamRequestModelController::class, 'destroy']);

// Medical Report Templates (per-doctor)
Route::get('/medical-report-templates', [MedicalReportTemplateController::class, 'index']);
Route::post('/medical-report-templates', [MedicalReportTemplateController::class, 'store']);
Route::put('/medical-report-templates/{id}', [MedicalReportTemplateController::class, 'update']);
Route::delete('/medical-report-templates/{id}', [MedicalReportTemplateController::class, 'destroy']);
```

**Step 3: Verify routes register**

```bash
php artisan route:list --path=exam-request
php artisan route:list --path=medical-report-template
```

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Controllers/ app/Modules/MedicalRecord/routes.php
git commit -m "feat(medical-record): add controllers and routes for exam requests sub-module"
```

---

## Task 9: Seeders

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Seeders/ExamRequestModelSeeder.php`
- Create: `app/Modules/MedicalRecord/Database/Seeders/MedicalReportTemplateSeeder.php`

**Step 1: Create ExamRequestModelSeeder**

Seed 7 default models from frontend `exam-models.ts`. These are system-wide defaults (use `user_id` of the first doctor or null — discuss: since `user_id` is NOT NULL, these need to be seeded per-doctor on first login, OR we add a `is_default` boolean column).

**Design decision:** Add `is_default` boolean DEFAULT false to `modelos_solicitacao_exames` and `modelos_relatorio_medico`. Default models have `user_id = null` (make user_id nullable) and `is_default = true`. When listing, return both user's custom models AND default models. This way seeders work globally.

**Alternative (simpler):** Make `user_id` nullable on both tables. Default models have `user_id = null`. Service returns `where user_id = ? OR user_id IS NULL`.

**Chosen approach:** Make `user_id` nullable + add migration to allow null. Seeder creates global defaults with `user_id = null`. Service lists user's models + global defaults.

Add migration:
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_23_000004_make_user_id_nullable_on_model_tables.php`

```php
// modelos_solicitacao_exames: make user_id nullable
// modelos_relatorio_medico: make user_id nullable
```

Seed data: 7 models with 124 total exams (from frontend `exam-models.ts`), 2 report templates (from `report-templates.ts`).

**Step 2: Create MedicalReportTemplateSeeder**

Seed 2 default report templates:
- "Relatório Padrão" with `{{CID_10}}` placeholder
- "Relatório Pós-Cirúrgico" with `{{CID_10}}` placeholder

**Step 3: Run seeders**

```bash
php artisan db:seed --class="App\Modules\MedicalRecord\Database\Seeders\ExamRequestModelSeeder"
php artisan db:seed --class="App\Modules\MedicalRecord\Database\Seeders\MedicalReportTemplateSeeder"
```

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Database/
git commit -m "feat(medical-record): add seeders with 7 default exam models and 2 report templates"
```

---

## Task 10: Tests — Exam Request CRUD

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/ExamRequest/StoreExamRequestTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ExamRequest/ListExamRequestTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ExamRequest/UpdateExamRequestTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ExamRequest/DeleteExamRequestTest.php`

**Step 1: Write tests**

`StoreExamRequestTest`:
- `it('creates an exam request with items')` — POST with valid items, assert 201, assert DB has record
- `it('creates an exam request with medical report')` — POST with medical_report object, assert stored
- `it('creates an exam request with cid_10 and clinical indication')` — POST with all optional fields
- `it('rejects store on finalized medical record')` — finalized prontuário, assert 409
- `it('rejects store without items')` — empty items array, assert 422
- `it('rejects store by non-owner')` — different user, assert 403
- `it('validates item structure')` — missing id/name/selected, assert 422

`ListExamRequestTest`:
- `it('lists exam requests for a medical record')` — create 3, assert returns 3
- `it('does not list requests from other records')` — isolation check
- `it('rejects list by non-owner')` — assert 403

`UpdateExamRequestTest`:
- `it('updates items on an exam request')` — PUT with new items, assert updated
- `it('updates cid_10 and clinical indication')` — partial update
- `it('rejects update on finalized record')` — assert 409
- `it('rejects update by non-owner')` — assert 403

`DeleteExamRequestTest`:
- `it('deletes an exam request')` — assert 200, assert DB missing
- `it('rejects delete on finalized record')` — assert 409
- `it('rejects delete by non-owner')` — assert 403

**Step 2: Run tests**

```bash
php artisan test --compact app/Modules/MedicalRecord/Tests/Feature/ExamRequest/
```

Expected: all pass.

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/
git commit -m "test(medical-record): add exam request CRUD tests"
```

---

## Task 11: Tests — Exam Request Model CRUD

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/ExamRequest/ExamRequestModelTest.php`

**Step 1: Write tests**

- `it('lists exam request models for user including defaults')` — create custom + assert defaults visible
- `it('filters models by category')` — category query param
- `it('creates a custom exam request model')` — assert 201
- `it('updates a custom model')` — assert 200
- `it('deletes a custom model')` — assert 200
- `it('rejects update on another user model')` — assert 403
- `it('rejects delete on another user model')` — assert 403
- `it('validates required fields')` — missing name/items, assert 422

**Step 2: Run tests**

```bash
php artisan test --compact --filter=ExamRequestModel
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/
git commit -m "test(medical-record): add exam request model CRUD tests"
```

---

## Task 12: Tests — Medical Report Template CRUD

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/ExamRequest/MedicalReportTemplateTest.php`

**Step 1: Write tests**

- `it('lists medical report templates for user including defaults')` — assert defaults + custom visible
- `it('creates a medical report template')` — assert 201
- `it('updates a medical report template')` — assert 200
- `it('deletes a medical report template')` — assert 200
- `it('rejects update on another user template')` — assert 403
- `it('validates required fields')` — missing name/body_template, assert 422

**Step 2: Run tests**

```bash
php artisan test --compact --filter=MedicalReportTemplate
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/
git commit -m "test(medical-record): add medical report template CRUD tests"
```

---

## Task 13: Final Checks

**Step 1: Run all project tests**

```bash
php artisan test --compact
```

Expected: all tests pass (previous 261 + new ~30 = ~291).

**Step 2: Run Pint**

```bash
vendor/bin/pint --dirty
```

**Step 3: Verify routes**

```bash
php artisan route:list --path=exam
php artisan route:list --path=medical-report
```

**Step 4: Final commit if needed**

```bash
vendor/bin/pint --dirty && git add -A && git commit -m "style: apply pint formatting"
```

---

## Summary of Endpoints

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/medical-records/{id}/exam-requests` | List exam requests for a record |
| POST | `/medical-records/{id}/exam-requests` | Create exam request |
| PUT | `/medical-records/{id}/exam-requests/{requestId}` | Update exam request |
| DELETE | `/medical-records/{id}/exam-requests/{requestId}` | Delete exam request |
| POST | `/medical-records/{id}/exam-requests/{requestId}/print` | Mark as printed |
| GET | `/exam-request-models` | List exam request models (user + defaults) |
| POST | `/exam-request-models` | Create custom model |
| PUT | `/exam-request-models/{id}` | Update custom model |
| DELETE | `/exam-request-models/{id}` | Delete custom model |
| GET | `/medical-report-templates` | List report templates (user + defaults) |
| POST | `/medical-report-templates` | Create custom template |
| PUT | `/medical-report-templates/{id}` | Update custom template |
| DELETE | `/medical-report-templates/{id}` | Delete custom template |

**Total: 13 endpoints, 3 tables, 3 models, 3 services, 3 controllers, ~30 tests**
