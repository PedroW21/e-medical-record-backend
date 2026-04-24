# Attachments Materialization Plan (Phase 6.5)

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Close the provenance loop for attachments. An `anexo_id` column is added to every `resultados_*` table and to `valores_laboratoriais`. Two flows populate it: (A) **manual entry** — existing `POST /medical-records/{id}/exam-results/{type}` and `POST /medical-records/{id}/lab-results` endpoints accept an optional `anexo_id` so the doctor can link a PDF/image as proof; (B) **AI confirm** — a listener on `AttachmentConfirmed` calls the same services and materializes the exam row automatically, carrying the `anexo_id` by construction. Same column, two origins, unified UX.

**Architecture:**
- One nullable `anexo_id` FK per exam-result table (14 `resultados_*` tables + `valores_laboratoriais`).
- Unique index on `anexo_id` in every table **except** `valores_laboratoriais` (a single lab PDF yields N analyte rows).
- Reuse existing `ExamResultService`/`LabResultService` — extend their create/update methods with an optional `anexo_id` argument. No parallel code path.
- `MaterializeConfirmedAttachment` listener (queued) receives the `AttachmentConfirmed` event, maps `AttachmentType → ExamType` (or lab), and delegates to the same service. Idempotent: if an exam row with that `anexo_id` already exists, it updates instead of re-creating (handles re-confirm scenarios).
- Bridge method `AttachmentType::toExamType(): ?ExamType` on the enum. `Documento`/`Outro` return null → no materialization. `Lab` also returns null → routed to lab handler. `Holter`/`Polissonografia` map to `ExamType::FreeText` with a tipo discriminator in the payload.
- Validation: new custom rule `AttachmentLinkable` verifies anexo exists, same prontuario, owned by doctor, not already linked (except lab). Added to existing Form Requests.

**Tech Stack:** Laravel 12, PHP 8.5, PostgreSQL, Sanctum SPA auth, Pest 4, Laravel Queue.

**Design doc:** MemPalace drawer `e-medical-record-backend/decisions` — "Phase 6.5 Scope Expansion — Anexo as Proof on Manual Flow (2026-04-22)"

**Reference patterns:**
- Existing service: `app/Modules/MedicalRecord/Services/ExamResultService.php`, `LabResultService.php`
- Existing controllers: `ExamResultController.php`, `LabResultController.php`
- AttachmentType → ExamType mapping data: see `app/Modules/MedicalRecord/Enums/ExamType.php` (already has `modelClass()`, `label()`, `deletedMessage()`)
- Custom rule template: `app/Modules/MedicalRecord/Rules/*` (if exists) or inline in Form Request

**Out of scope (future PR):** real AI integration replacing the stub; UI work for the anexo badge / linked-source display; migrating the disk to S3.

---

## Task 1: Migrations — add `anexo_id` to all exam-result tables

**Files:**
- Create a single migration file: `app/Modules/MedicalRecord/Database/Migrations/2026_04_22_000002_add_anexo_id_to_exam_result_tables.php`

**Step 1: Create migration**

```bash
php artisan make:migration add_anexo_id_to_exam_result_tables --path=app/Modules/MedicalRecord/Database/Migrations --no-interaction
```

Rename the generated file so the timestamp prefix matches `2026_04_22_000002_`.

**Step 2: Write migration content**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that get a unique `anexo_id` (one exam row per attachment).
     *
     * @var array<int, string>
     */
    private array $uniqueTables = [
        'resultados_ecg',
        'resultados_rx',
        'resultados_texto_livre',
        'resultados_elastografia_hepatica',
        'resultados_mapa',
        'resultados_dexa',
        'resultados_teste_ergometrico',
        'resultados_ecodoppler_carotidas',
        'resultados_ecocardiograma',
        'resultados_mrpa',
        'resultados_cat',
        'resultados_cintilografia',
        'resultados_pe_diabetico',
    ];

    public function up(): void
    {
        foreach ($this->uniqueTables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table): void {
                $t->foreignId('anexo_id')
                    ->nullable()
                    ->after('prontuario_id')
                    ->constrained('anexos')
                    ->nullOnDelete();

                $t->unique('anexo_id', $table.'_anexo_id_unique');
            });
        }

        // Lab analytes: one lab PDF maps to many analyte rows — FK but no unique index.
        Schema::table('valores_laboratoriais', function (Blueprint $t): void {
            $t->foreignId('anexo_id')
                ->nullable()
                ->after('prontuario_id')
                ->constrained('anexos')
                ->nullOnDelete();

            $t->index('anexo_id');
        });
    }

    public function down(): void
    {
        foreach ($this->uniqueTables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table): void {
                $t->dropUnique($table.'_anexo_id_unique');
                $t->dropConstrainedForeignId('anexo_id');
            });
        }

        Schema::table('valores_laboratoriais', function (Blueprint $t): void {
            $t->dropIndex(['anexo_id']);
            $t->dropConstrainedForeignId('anexo_id');
        });
    }
};
```

**Step 3: Run migration**

```bash
php artisan migrate
```

Expected: one migration runs, 14 tables modified (13 unique + 1 non-unique).

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Migrations/2026_04_22_000002_add_anexo_id_to_exam_result_tables.php
git commit -m "feat(medical-record): add anexo_id FK to exam result and lab analyte tables"
```

---

## Task 2: Extend `AttachmentType` enum with `toExamType()` bridge

**Files:**
- Modify: `app/Modules/MedicalRecord/Enums/AttachmentType.php`

**Step 1: Add method to enum**

Append these methods to the existing `AttachmentType` enum, after `isParseable()`:

```php
/**
 * Map this attachment type to the corresponding exam type slug (if any).
 * Returns null for types that do not materialize into a single exam result row:
 * `documento` and `outro` (no AI), and `lab` (goes through the lab analyte path).
 */
public function toExamType(): ?\App\Modules\MedicalRecord\Enums\ExamType
{
    return match ($this) {
        self::Ecg => \App\Modules\MedicalRecord\Enums\ExamType::Ecg,
        self::Rx => \App\Modules\MedicalRecord\Enums\ExamType::Xray,
        self::Eco => \App\Modules\MedicalRecord\Enums\ExamType::Echo,
        self::Mapa => \App\Modules\MedicalRecord\Enums\ExamType::Mapa,
        self::Mrpa => \App\Modules\MedicalRecord\Enums\ExamType::Mrpa,
        self::Dexa => \App\Modules\MedicalRecord\Enums\ExamType::Dexa,
        self::TesteErgometrico => \App\Modules\MedicalRecord\Enums\ExamType::ErgometricTest,
        self::EcodopplerCarotidas => \App\Modules\MedicalRecord\Enums\ExamType::CarotidEcodoppler,
        self::ElastografiaHepatica => \App\Modules\MedicalRecord\Enums\ExamType::HepaticElastography,
        self::Cat => \App\Modules\MedicalRecord\Enums\ExamType::Cat,
        self::Cintilografia => \App\Modules\MedicalRecord\Enums\ExamType::Scintigraphy,
        self::PeDiabetico => \App\Modules\MedicalRecord\Enums\ExamType::DiabeticFoot,
        self::Holter, self::Polissonografia => \App\Modules\MedicalRecord\Enums\ExamType::FreeText,
        self::Lab, self::Documento, self::Outro => null,
    };
}

/**
 * Whether this attachment type routes through the lab-analyte path
 * (one PDF → N `valores_laboratoriais` rows) instead of a single exam row.
 */
public function isLabType(): bool
{
    return $this === self::Lab;
}
```

**Step 2: Test**

Add a small unit test `app/Modules/MedicalRecord/Tests/Feature/AttachmentTypeBridgeTest.php` verifying that:
- Every parseable type other than `Lab` returns a non-null `ExamType`
- `Documento`, `Outro`, `Lab` return null
- `Holter` and `Polissonografia` both return `ExamType::FreeText`
- `isLabType()` returns true only for `Lab`

Run: `php artisan test --compact --filter=AttachmentTypeBridgeTest`

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Enums/AttachmentType.php app/Modules/MedicalRecord/Tests/Feature/AttachmentTypeBridgeTest.php
git commit -m "feat(medical-record): add AttachmentType→ExamType bridge and isLabType helper"
```

---

## Task 3: Update `Resultado*` + `ValorLaboratorial` models — add `anexo_id` to fillable, add `anexo()` relationship

**Files (14 models):**
- `ResultadoEcg`, `ResultadoRx`, `ResultadoTextoLivre`, `ResultadoElastografiaHepatica`, `ResultadoMapa`, `ResultadoDexa`, `ResultadoTesteErgometrico`, `ResultadoEcodopplerCarotidas`, `ResultadoEcocardiograma`, `ResultadoMrpa`, `ResultadoCat`, `ResultadoCintilografia`, `ResultadoPeDiabetico`, `ValorLaboratorial`

**Step 1: Add to each model**

For each of the 14 models:
1. Append `'anexo_id'` to the `$fillable` array.
2. Add `@property int|null $anexo_id` and `@property-read Anexo|null $anexo` to the class PHPDoc.
3. Add relationship method:
   ```php
   /**
    * @return BelongsTo<Anexo, $this>
    */
   public function anexo(): BelongsTo
   {
       return $this->belongsTo(Anexo::class, 'anexo_id');
   }
   ```
4. Add `use App\Modules\MedicalRecord\Models\Anexo;` import only if not already present (same namespace → usually unnecessary).

**Step 2: Add reverse relationship on `Anexo`**

Edit `app/Modules/MedicalRecord/Models/Anexo.php`:
- For the 13 unique-link exam types: define `belongsToMany`-free individual relationships is noisy. Instead expose a single polymorphic-ish helper:

Append to `Anexo`:
```php
/**
 * Resolve the concrete exam-result Eloquent relationship for this attachment.
 * Returns null for non-parseable types (`documento`, `outro`) and for lab
 * (lab has N analytes — use `valoresLaboratoriais()` instead).
 *
 * @return BelongsTo<Model, $this>|null
 */
public function materializedResult(): ?\Illuminate\Database\Eloquent\Relations\HasOne
{
    $examType = $this->tipo_anexo->toExamType();

    if ($examType === null || $this->tipo_anexo->isLabType()) {
        return null;
    }

    /** @var class-string<Model> $modelClass */
    $modelClass = $examType->modelClass();

    return $this->hasOne($modelClass, 'anexo_id');
}

/**
 * @return HasMany<ValorLaboratorial, $this>
 */
public function valoresLaboratoriais(): HasMany
{
    return $this->hasMany(ValorLaboratorial::class, 'anexo_id');
}
```

Add `use Illuminate\Database\Eloquent\Relations\HasMany;` if missing and `use Illuminate\Database\Eloquent\Model;`.

**Step 3: Tests**

Add a short feature test `AttachmentMaterializedRelationTest.php` covering:
- `ValorLaboratorial` can eager-load the anexo
- `ResultadoEcg::factory()->create(['anexo_id' => $anexo->id])` retrieves `->anexo`
- `$anexo->materializedResult()` dynamically returns the ECG relation for an ECG anexo
- Returns null for documento/outro/lab

Run filtered.

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Models/Resultado*.php app/Modules/MedicalRecord/Models/ValorLaboratorial.php app/Modules/MedicalRecord/Models/Anexo.php app/Modules/MedicalRecord/Tests/Feature/AttachmentMaterializedRelationTest.php
git commit -m "feat(medical-record): wire anexo_id on exam-result models with back-relations"
```

---

## Task 4: Custom validation rule `AttachmentLinkable`

**Files:**
- Create: `app/Modules/MedicalRecord/Rules/AttachmentLinkable.php`

**Step 1: Write rule**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Rules;

use App\Modules\MedicalRecord\Models\Anexo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class AttachmentLinkable implements ValidationRule
{
    public function __construct(
        private readonly int $prontuarioId,
        private readonly int $doctorUserId,
        /** When true, multiple exam rows may share the same anexo_id (lab analytes). */
        private readonly bool $allowMultipleLinks = false,
        /** If provided, ignore this exam result id when checking existing links (update path). */
        private readonly ?int $ignoreResultId = null,
        /** FQCN of the Eloquent model owning the exam result (used for uniqueness check). */
        private readonly ?string $resultModelClass = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $anexo = Anexo::query()->with('prontuario')->find((int) $value);

        if ($anexo === null) {
            $fail('O anexo informado não foi encontrado.');

            return;
        }

        if ($anexo->prontuario_id !== $this->prontuarioId) {
            $fail('O anexo informado pertence a outro prontuário.');

            return;
        }

        if ($anexo->prontuario->user_id !== $this->doctorUserId) {
            $fail('O anexo informado não pertence ao médico autenticado.');

            return;
        }

        if ($this->allowMultipleLinks || $this->resultModelClass === null) {
            return;
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $class */
        $class = $this->resultModelClass;

        $query = $class::query()->where('anexo_id', $anexo->id);

        if ($this->ignoreResultId !== null) {
            $query->whereKeyNot($this->ignoreResultId);
        }

        if ($query->exists()) {
            $fail('Este anexo já está vinculado a outro resultado de exame.');
        }
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Rules/AttachmentLinkable.php
git commit -m "feat(medical-record): add AttachmentLinkable custom validation rule"
```

---

## Task 5: Extend `StoreExamResultRequest` / `UpdateExamResultRequest` with `anexo_id` validation

**Files to modify:**
- `app/Modules/MedicalRecord/Http/Requests/StoreExamResultRequest.php`
- `app/Modules/MedicalRecord/Http/Requests/UpdateExamResultRequest.php`

**Step 1: Add rule + message**

Inside `rules()`, merge into the returned array:
```php
'anexo_id' => [
    'nullable',
    'integer',
    new AttachmentLinkable(
        prontuarioId: (int) $this->route('medicalRecordId'),
        doctorUserId: (int) $this->user()->id,
        resultModelClass: $this->resolveExamType()->modelClass(),
        ignoreResultId: $this instanceof UpdateExamResultRequest ? (int) $this->route('id') : null,
    ),
],
```

Adjust `resolveExamType()` — likely already exists in the current request (used for dispatching shape). If not, derive from the route param `{examType}`.

Add to `messages()`:
```php
'anexo_id.integer' => 'O identificador do anexo deve ser um número inteiro.',
```

**Step 2: Same for Update**

Mirror in `UpdateExamResultRequest`. Pass `ignoreResultId` so the current record does not fail the uniqueness check when re-saving without changing `anexo_id`.

**Step 3: Run existing exam-result tests**

```bash
php artisan test --compact --filter=ExamResult
```

Expected: still green (the new rule is opt-in via presence of `anexo_id`).

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Requests/StoreExamResultRequest.php app/Modules/MedicalRecord/Http/Requests/UpdateExamResultRequest.php
git commit -m "feat(medical-record): accept optional anexo_id on exam-result store/update"
```

---

## Task 6: Extend `StoreLabResultRequest` / `UpdateLabResultRequest` with `anexo_id` validation

**Files:**
- `app/Modules/MedicalRecord/Http/Requests/StoreLabResultRequest.php`
- `app/Modules/MedicalRecord/Http/Requests/UpdateLabResultRequest.php`

**Step 1: Add rule**

```php
'anexo_id' => [
    'nullable',
    'integer',
    new AttachmentLinkable(
        prontuarioId: (int) $this->route('medicalRecordId'),
        doctorUserId: (int) $this->user()->id,
        allowMultipleLinks: true, // many analytes per PDF
    ),
],
```

**Step 2: Run existing lab tests**

```bash
php artisan test --compact --filter=LabResult
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Requests/StoreLabResultRequest.php app/Modules/MedicalRecord/Http/Requests/UpdateLabResultRequest.php
git commit -m "feat(medical-record): accept optional anexo_id on lab-result store/update"
```

---

## Task 7: Extend `ExamResultService` to persist `anexo_id`

**Files to modify:**
- `app/Modules/MedicalRecord/Services/ExamResultService.php`

**Step 1: Extend create/update method signatures**

Find the method that persists the exam row. Add `$anexoId` (nullable int) to the normalized attributes that hit `Model::create([...])` / `Model::fill([...])`. Typically the service takes the validated array already — just pass `anexo_id` through. No behavior change when null.

For the update path, allow null values to *clear* the link (doctor un-links the anexo) — distinguish missing-key vs null-value in the request (use `has('anexo_id')` or rely on the request's validated shape).

**Step 2: Tests**

Update existing store/update tests to cover:
- Store with `anexo_id` → row persisted with the value
- Store without `anexo_id` → row persisted with null
- Update unlinks (`anexo_id: null`) → stored as null
- Update with linked anexo → rejected if anexo already linked to another result (422 via rule)

Extend `app/Modules/MedicalRecord/Tests/Feature/ExamResult/StoreExamResultTest.php` and `UpdateExamResultTest.php` rather than creating new files.

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Services/ExamResultService.php app/Modules/MedicalRecord/Tests/Feature/ExamResult/StoreExamResultTest.php app/Modules/MedicalRecord/Tests/Feature/ExamResult/UpdateExamResultTest.php
git commit -m "feat(medical-record): persist and update anexo_id on exam results"
```

---

## Task 8: Extend `LabResultService` to persist `anexo_id` on analytes

**Files:**
- `app/Modules/MedicalRecord/Services/LabResultService.php`

**Step 1: Propagate `anexo_id` to every `ValorLaboratorial` row created in a lab-result call**

The lab payload groups analytes under panels. A single `anexo_id` (from the request) must be broadcast to all analyte rows created/updated in the same call.

**Step 2: Tests**

Extend `StoreLabResultTest` / `UpdateLabResultTest` with:
- Store with `anexo_id` → every analyte row has the same `anexo_id`
- Store without → all null
- Uniqueness check is skipped (lab allows N rows per anexo)

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Services/LabResultService.php app/Modules/MedicalRecord/Tests/Feature/
git commit -m "feat(medical-record): propagate anexo_id to lab analyte rows"
```

---

## Task 9: Include `anexo_id` in exam-result Resources

**Files (14 resources):**
- All `app/Modules/MedicalRecord/Http/Resources/*Resource.php` for exam results + `LabResultResource.php`

**Step 1: Add field**

Inside each `toArray()`:
```php
'anexo_id' => $this->anexo_id,
```

For lab: the panel envelope already groups analytes — add `anexo_id` at the envelope level (value is the `anexo_id` of any analyte in the set — all share the same value per request). If the envelope doesn't exist as an object (just loose analytes), add `anexo_id` to each analyte row.

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Resources/
git commit -m "feat(medical-record): expose anexo_id in exam-result and lab-result resources"
```

---

## Task 10: `MaterializeConfirmedAttachment` listener

**Files:**
- Create: `app/Modules/MedicalRecord/Listeners/MaterializeConfirmedAttachment.php`

**Step 1: Write listener**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Listeners;

use App\Modules\MedicalRecord\Enums\AttachmentType;
use App\Modules\MedicalRecord\Events\AttachmentConfirmed;
use App\Modules\MedicalRecord\Services\ExamResultService;
use App\Modules\MedicalRecord\Services\LabResultService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class MaterializeConfirmedAttachment implements ShouldQueue
{
    public int $tries = 1;

    public function __construct(
        private readonly ExamResultService $examResultService,
        private readonly LabResultService $labResultService,
    ) {}

    public function handle(AttachmentConfirmed $event): void
    {
        $attachment = $event->attachment;

        if ($attachment->tipo_anexo === AttachmentType::Documento
            || $attachment->tipo_anexo === AttachmentType::Outro) {
            return;
        }

        $examData = $attachment->dados_extraidos ?? [];

        if ($examData === []) {
            return;
        }

        if ($attachment->tipo_anexo->isLabType()) {
            $this->labResultService->materializeFromAttachment(
                prontuarioId: $attachment->prontuario_id,
                anexoId: $attachment->id,
                payload: $examData,
            );

            return;
        }

        $examType = $attachment->tipo_anexo->toExamType();
        if ($examType === null) {
            return;
        }

        $this->examResultService->materializeFromAttachment(
            prontuarioId: $attachment->prontuario_id,
            examType: $examType,
            anexoId: $attachment->id,
            payload: $examData,
        );
    }
}
```

**Step 2: Add service methods**

- `ExamResultService::materializeFromAttachment(int $prontuarioId, ExamType $examType, int $anexoId, array $payload): Model` — idempotent: if exam row with `anexo_id` already exists, update it; else create. Normalize payload to DB shape (reuse the existing transformer used by the manual path).
- `LabResultService::materializeFromAttachment(int $prontuarioId, int $anexoId, array $payload): Collection` — delete existing analyte rows for this `anexo_id` then recreate (simplest consistent behavior; lab is plural by nature).

**Step 3: Register listener**

In `app/Modules/MedicalRecord/Providers/MedicalRecordServiceProvider.php`, inside `boot()`, add:

```php
Event::listen(AttachmentConfirmed::class, MaterializeConfirmedAttachment::class);
```

Imports: `use Illuminate\Support\Facades\Event; use App\Modules\MedicalRecord\Events\AttachmentConfirmed; use App\Modules\MedicalRecord\Listeners\MaterializeConfirmedAttachment;`

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Listeners/MaterializeConfirmedAttachment.php app/Modules/MedicalRecord/Services/ExamResultService.php app/Modules/MedicalRecord/Services/LabResultService.php app/Modules/MedicalRecord/Providers/MedicalRecordServiceProvider.php
git commit -m "feat(medical-record): listener materializes confirmed attachment into exam-result rows"
```

---

## Task 11: Feature tests — `MaterializeConfirmedAttachment`

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/Attachment/MaterializeConfirmedAttachmentTest.php`

**Step 1: Scenarios**

- `it('creates an ECG exam result row when an ECG attachment is confirmed')`
- `it('creates an Echo exam result row for eco attachment')`
- `it('creates a FreeText row for holter with tipo holter')`
- `it('creates a FreeText row for polissonografia with tipo polissonografia')`
- `it('creates N ValorLaboratorial rows for a lab attachment')`
- `it('is a no-op for documento attachments')`
- `it('is a no-op for outro attachments')`
- `it('is idempotent — re-confirming the same attachment updates the existing exam row instead of creating a duplicate')`
- `it('re-confirming lab replaces all analyte rows linked to that anexo_id')`

Run:

```bash
php artisan test --compact --filter=MaterializeConfirmedAttachmentTest
```

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/Attachment/MaterializeConfirmedAttachmentTest.php
git commit -m "test(medical-record): cover MaterializeConfirmedAttachment listener flows"
```

---

## Task 12: Feature tests — manual flow linking an anexo

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/Attachment/LinkAttachmentManuallyTest.php`

**Step 1: Scenarios**

- `it('stores an ECG exam result with a linked anexo_id')`
- `it('rejects anexo_id belonging to another prontuario')` — 422
- `it('rejects anexo_id owned by another doctor')` — 422
- `it('rejects anexo_id already linked to another ECG result')` — 422
- `it('allows null anexo_id on store and update')`
- `it('allows update to unlink the anexo (anexo_id: null)')`
- `it('allows the same anexo_id across multiple lab analyte rows (same store request)')`
- `it('rejects reusing a lab anexo_id for a different lab panel in a different request')` — (depends on policy; validate whether lab allows many requests with same anexo. If so — remove this case.)

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/Attachment/LinkAttachmentManuallyTest.php
git commit -m "test(medical-record): cover manual linking of anexo to exam and lab results"
```

---

## Task 13: Extend existing `AttachmentResource` — include `materialized_result` pointer

**Files:**
- Modify: `app/Modules/MedicalRecord/Http/Resources/AttachmentResource.php`

**Step 1: Add fields**

When the anexo has a materialized result, expose a compact reference so the frontend can render a "linked exam" badge:

```php
'materialized' => $this->whenLoaded('materializedResult', function (): ?array {
    $result = $this->materializedResult;

    return $result === null ? null : [
        'id' => $result->id,
        'exam_type' => $this->tipo_anexo->toExamType()?->value,
    ];
}),
'lab_analytes_count' => $this->tipo_anexo->isLabType()
    ? $this->valoresLaboratoriais()->count()
    : null,
```

Eager-load `materializedResult` in `AttachmentService::findOrFail()` and `listForProntuario()` so N+1 is avoided.

**Step 2: Update relevant tests**

Adjust `ShowAttachmentTest`, `ListAttachmentsTest` to assert the new fields.

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Resources/AttachmentResource.php app/Modules/MedicalRecord/Services/AttachmentService.php app/Modules/MedicalRecord/Tests/Feature/Attachment/
git commit -m "feat(medical-record): surface materialized result reference on attachment resource"
```

---

## Task 14: Full test suite + Pint + Scribe annotations refresh

**Step 1: Pint**

```bash
vendor/bin/pint --dirty
```

**Step 2: Suite**

```bash
php artisan test --compact
```

Expected: previous count (346) plus the new tests; all green.

**Step 3: Re-annotate `ExamResultController`, `LabResultController` if their `@bodyParam` blocks need the new `anexo_id` field**

Add `@bodyParam anexo_id int optional Link this exam result to a previously uploaded attachment. Example: 301` where relevant. Also expand `@response 422` to show a sample error message for `anexo_id` failures.

**Step 4: Commit**

```bash
git add -A
git commit -m "chore(medical-record): pint formatting + refreshed anexo_id scribe docs" --allow-empty
```

---

## Task 15: Serena checkpoint + open PR

**Step 1: Update checkpoint memory**

Update `.serena/memories/session-checkpoint.md`:

```markdown
# Session Checkpoint

## Completed
- [x] Phase 6 (Attachments) — merged PR #6
- [x] Phase 6.5 (Attachments materialization) — branch feature/attachments-materialization
  - anexo_id FK on every resultados_* + valores_laboratoriais
  - Unique on resultados_*, non-unique on lab
  - Manual flow accepts optional anexo_id via AttachmentLinkable rule
  - AI flow: MaterializeConfirmedAttachment listener → ExamResultService / LabResultService
  - Idempotent re-confirm
  - Tests: listener flows + manual linking

## Next Phases
- [ ] Phase 7: Metrics / Evolution API
- [ ] Phase 8: Catalog Seeders
```

**Step 2: PR**

```bash
git push -u origin feature/attachments-materialization
gh pr create --title "feat(medical-record): materialização de anexos confirmados e vínculo manual" --body "$(cat <<'EOF'
## Descrição

Fecha o loop de anexos iniciado na PR #6. Agora tanto o fluxo manual de inserção de resultados de exame quanto o fluxo AI (confirm) gravam os dados nas tabelas `resultados_*` / `valores_laboratoriais`, e os dois gravam no novo campo `anexo_id` quando houver um anexo de origem.

## Mudanças

- Nova migration adicionando `anexo_id` FK (nullable) em 13 tabelas de resultado + `valores_laboratoriais`, com unique index nas 13 e índice simples em lab.
- `AttachmentType::toExamType()` mapeia cada tipo parseável para o `ExamType` equivalente; `lab` vai pela rota de analitos; `documento`/`outro` não materializam.
- Novo `MaterializeConfirmedAttachment` (listener `ShouldQueue`) escuta `AttachmentConfirmed` e delega para `ExamResultService::materializeFromAttachment()` ou `LabResultService::materializeFromAttachment()`. Re-confirm é idempotente.
- Form Requests de exam-results e lab-results passam a aceitar `anexo_id` opcional, validado pela nova regra `AttachmentLinkable` (mesma consulta, mesmo médico, não duplicado — exceto lab).
- Resources expõem `anexo_id` e, em `AttachmentResource`, o ponteiro `materialized` pro resultado linkado.

## Fora do escopo

- Integração real de IA (o stub do Phase 6 continua o mesmo)
- UI para exibir o badge "baseado em anexo" (frontend)
- Migração para S3

## Testes

Novos testes cobrindo listener por tipo, re-confirm idempotente, manual com anexo válido/inválido, cross-prontuário, cross-doctor, duplicidade, unlink. Todo o suite pré-existente continua verde.
EOF
)" --base main
```

---

## Execution Notes

- **Worktree:** use `superpowers:using-git-worktrees` at start. Branch `feature/attachments-materialization`.
- **Pest auto-applies** RefreshDatabase + TestCase to `app/Modules/*/Tests/Feature`. Don't re-declare.
- **Existing services** already encapsulate exam normalization — reuse them. Do NOT duplicate transformers inside the listener.
- **Frontend coordination:** the extended `anexo_id` field on exam-result + lab-result payloads, plus the new `materialized` / `lab_analytes_count` fields on attachment resource, need a frontend PR to surface in the UI.
- **Scribe** still not installed; annotations prepped anyway to match existing controller convention.
