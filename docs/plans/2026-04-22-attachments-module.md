# Attachments Module Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement the Attachments sub-module inside MedicalRecord — upload of files (PDF/JPG/JPEG/PNG/GIF) bound to a prontuário, status tracking (pending/processing/completed/failed/confirmed), stub AI parsing via queued job, per-anexo confirmation endpoint emitting domain events, Reverb broadcasting for realtime status updates, and full test coverage.

**Architecture:** Attachments always bind to a `prontuario_id` (NOT NULL). The prontuário is expected to already exist as `draft` (created when the doctor opens the consultation — existing behavior). Uploads go to local disk via `Storage::disk('local')`, scoped under `anexos/{prontuario_id}/{uuid}.{ext}`. The stub `ParseAttachmentJob` (ShouldQueue) mocks a plausible `dados_extraidos` payload per `tipo_anexo` and updates status to `completed` (or `failed` for a deterministic test path). Domain events (`AttachmentUploaded`, `AttachmentParseCompleted`, `AttachmentParseFailed`, `AttachmentConfirmed`) are dispatched and broadcast on a per-prontuário private Reverb channel for the frontend to reactively update status. The confirm endpoint (Option A — dedicated merge) is scoped to this PR as an event-emitting "accept" step; materializing `Resultado*` rows per exam type from the payload lives in a follow-up PR.

**Tech Stack:** Laravel 12, PHP 8.5, PostgreSQL, Sanctum SPA auth, Pest 4, Laravel Reverb 1, Laravel Queue.

**Design doc:** Serena memory `medical-record-module/10-attachments-design`

**Reference patterns:**
- Controller/Service/DTO: `app/Modules/MedicalRecord/Http/Controllers/PrescriptionTemplateController.php`, `app/Modules/MedicalRecord/Services/PrescriptionTemplateService.php`
- Policy: `app/Modules/MedicalRecord/Policies/PrescriptionTemplatePolicy.php`
- Event pattern: `app/Modules/Appointment/Events/PublicAppointmentRequested.php`
- Model + relationships: `app/Modules/MedicalRecord/Models/Prontuario.php`, `app/Modules/MedicalRecord/Models/SolicitacaoExame.php`
- Resource PT→EN mapping: `app/Modules/MedicalRecord/Http/Resources/PrescriptionResource.php`

**Out of scope (future PR):** materializing `dados_extraidos` into the corresponding `Resultado*` tables (per-tipo dispatcher), real AI integration, S3 driver swap, image thumbnailing.

---

## Task 1: Enums (TipoAnexo, StatusProcessamento, TipoArquivo)

**Files:**
- Create: `app/Modules/MedicalRecord/Enums/TipoAnexo.php`
- Create: `app/Modules/MedicalRecord/Enums/StatusProcessamento.php`
- Create: `app/Modules/MedicalRecord/Enums/TipoArquivo.php`

**Step 1: Create enum classes via artisan**

```bash
php artisan make:enum Modules/MedicalRecord/Enums/TipoAnexo --string --no-interaction
php artisan make:enum Modules/MedicalRecord/Enums/StatusProcessamento --string --no-interaction
php artisan make:enum Modules/MedicalRecord/Enums/TipoArquivo --string --no-interaction
```

**Step 2: Write `TipoAnexo`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum TipoAnexo: string
{
    case Lab = 'lab';
    case Ecg = 'ecg';
    case Rx = 'rx';
    case Eco = 'eco';
    case Mapa = 'mapa';
    case Mrpa = 'mrpa';
    case Dexa = 'dexa';
    case TesteErgometrico = 'teste_ergometrico';
    case EcodopplerCarotidas = 'ecodoppler_carotidas';
    case ElastografiaHepatica = 'elastografia_hepatica';
    case Cat = 'cat';
    case Cintilografia = 'cintilografia';
    case PeDiabetico = 'pe_diabetico';
    case Holter = 'holter';
    case Polissonografia = 'polissonografia';
    case Documento = 'documento';
    case Outro = 'outro';

    /**
     * Whether this attachment type is eligible for AI parsing.
     */
    public function isParseable(): bool
    {
        return ! in_array($this, [self::Documento, self::Outro], true);
    }
}
```

**Step 3: Write `StatusProcessamento`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum StatusProcessamento: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Confirmed = 'confirmed';
}
```

**Step 4: Write `TipoArquivo`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum TipoArquivo: string
{
    case Pdf = 'pdf';
    case Jpg = 'jpg';
    case Jpeg = 'jpeg';
    case Png = 'png';
    case Gif = 'gif';

    public static function fromExtension(string $extension): self
    {
        return self::from(strtolower($extension));
    }

    public function isImage(): bool
    {
        return in_array($this, [self::Jpg, self::Jpeg, self::Png, self::Gif], true);
    }
}
```

**Step 5: Commit**

```bash
git add app/Modules/MedicalRecord/Enums/TipoAnexo.php app/Modules/MedicalRecord/Enums/StatusProcessamento.php app/Modules/MedicalRecord/Enums/TipoArquivo.php
git commit -m "feat(medical-record): add TipoAnexo, StatusProcessamento, TipoArquivo enums for attachments"
```

---

## Task 2: Migration — `anexos` table

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_04_22_000001_create_anexos_table.php`

**Step 1: Create migration**

```bash
php artisan make:migration create_anexos_table --path=app/Modules/MedicalRecord/Database/Migrations --no-interaction
```

Rename the generated file so the timestamp prefix matches `2026_04_22_000001_`.

**Step 2: Write migration content**

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
        Schema::create('anexos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->string('tipo_anexo');
            $table->string('nome');
            $table->string('tipo_arquivo');
            $table->string('caminho');
            $table->unsignedBigInteger('tamanho_bytes');
            $table->string('status_processamento')->nullable();
            $table->jsonb('dados_extraidos')->nullable();
            $table->text('erro_processamento')->nullable();
            $table->timestamp('processado_em')->nullable();
            $table->timestamp('confirmado_em')->nullable();
            $table->timestamps();

            $table->index('prontuario_id');
            $table->index('paciente_id');
            $table->index(['prontuario_id', 'tipo_anexo']);
            $table->index('status_processamento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos');
    }
};
```

**Step 3: Run migration**

```bash
php artisan migrate
```

Expected: `anexos` table created.

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Migrations/2026_04_22_000001_create_anexos_table.php
git commit -m "feat(medical-record): add anexos migration with prontuario FK and processing status fields"
```

---

## Task 3: `Anexo` model + factory + Prontuario relationship

**Files:**
- Create: `app/Modules/MedicalRecord/Models/Anexo.php`
- Create: `app/Modules/MedicalRecord/Database/Factories/AttachmentFactory.php`
- Modify: `app/Modules/MedicalRecord/Models/Prontuario.php` (add `anexos()` relationship + PHPDoc)

**Step 1: Create model**

```bash
php artisan make:model Modules/MedicalRecord/Models/Anexo --no-interaction
```

**Step 2: Write `Anexo` model content**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\MedicalRecord\Database\Factories\AttachmentFactory;
use App\Modules\MedicalRecord\Enums\StatusProcessamento;
use App\Modules\MedicalRecord\Enums\TipoAnexo;
use App\Modules\MedicalRecord\Enums\TipoArquivo;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $prontuario_id
 * @property int $paciente_id
 * @property TipoAnexo $tipo_anexo
 * @property string $nome
 * @property TipoArquivo $tipo_arquivo
 * @property string $caminho
 * @property int $tamanho_bytes
 * @property StatusProcessamento|null $status_processamento
 * @property array<string, mixed>|null $dados_extraidos
 * @property string|null $erro_processamento
 * @property \Illuminate\Support\Carbon|null $processado_em
 * @property \Illuminate\Support\Carbon|null $confirmado_em
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 */
class Anexo extends Model
{
    use HasFactory;

    protected $table = 'anexos';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'tipo_anexo',
        'nome',
        'tipo_arquivo',
        'caminho',
        'tamanho_bytes',
        'status_processamento',
        'dados_extraidos',
        'erro_processamento',
        'processado_em',
        'confirmado_em',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_anexo' => TipoAnexo::class,
            'tipo_arquivo' => TipoArquivo::class,
            'status_processamento' => StatusProcessamento::class,
            'dados_extraidos' => 'array',
            'tamanho_bytes' => 'integer',
            'processado_em' => 'datetime',
            'confirmado_em' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Prontuario, $this>
     */
    public function prontuario(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class, 'prontuario_id');
    }

    /**
     * @return BelongsTo<Paciente, $this>
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function isParseable(): bool
    {
        return $this->tipo_anexo->isParseable();
    }

    protected static function newFactory(): Factory
    {
        return AttachmentFactory::new();
    }
}
```

**Step 3: Create factory**

```bash
php artisan make:factory Modules/MedicalRecord/Database/Factories/AttachmentFactory --model=Modules/MedicalRecord/Models/Anexo --no-interaction
```

**Step 4: Write factory content**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Enums\StatusProcessamento;
use App\Modules\MedicalRecord\Enums\TipoAnexo;
use App\Modules\MedicalRecord\Enums\TipoArquivo;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Anexo>
 */
class AttachmentFactory extends Factory
{
    protected $model = Anexo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prontuario = Prontuario::factory()->create();
        $tipoAnexo = fake()->randomElement(TipoAnexo::cases());
        $tipoArquivo = fake()->randomElement(TipoArquivo::cases());

        return [
            'prontuario_id' => $prontuario->id,
            'paciente_id' => $prontuario->paciente_id,
            'tipo_anexo' => $tipoAnexo,
            'nome' => fake()->words(2, true).'.'.$tipoArquivo->value,
            'tipo_arquivo' => $tipoArquivo,
            'caminho' => 'anexos/'.$prontuario->id.'/'.fake()->uuid().'.'.$tipoArquivo->value,
            'tamanho_bytes' => fake()->numberBetween(1024, 5_000_000),
            'status_processamento' => $tipoAnexo->isParseable() ? StatusProcessamento::Pending : null,
        ];
    }

    public function parseable(): static
    {
        return $this->state(fn () => [
            'tipo_anexo' => TipoAnexo::Ecg,
            'status_processamento' => StatusProcessamento::Pending,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status_processamento' => StatusProcessamento::Completed,
            'dados_extraidos' => ['date' => now()->toDateString(), 'pattern' => 'normal'],
            'processado_em' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status_processamento' => StatusProcessamento::Failed,
            'erro_processamento' => 'Stub failure for test',
            'processado_em' => now(),
        ]);
    }

    public function confirmed(): static
    {
        return $this->completed()->state(fn () => [
            'status_processamento' => StatusProcessamento::Confirmed,
            'confirmado_em' => now(),
        ]);
    }
}
```

**Step 5: Add relationship to `Prontuario`**

Edit `app/Modules/MedicalRecord/Models/Prontuario.php`:
- Add `@property-read \Illuminate\Database\Eloquent\Collection<int, Anexo> $anexos` in the PHPDoc block alongside other `@property-read` lines.
- Add method:

```php
/**
 * @return HasMany<Anexo, $this>
 */
public function anexos(): HasMany
{
    return $this->hasMany(Anexo::class, 'prontuario_id');
}
```

**Step 6: Commit**

```bash
git add app/Modules/MedicalRecord/Models/Anexo.php app/Modules/MedicalRecord/Database/Factories/AttachmentFactory.php app/Modules/MedicalRecord/Models/Prontuario.php
git commit -m "feat(medical-record): add Anexo model, factory, and Prontuario relationship"
```

---

## Task 4: Storage disk config

**Files:**
- Modify: `config/filesystems.php` (add `anexos` disk rooted under `storage/app/anexos`)
- Modify: `.env.example` (document `ANEXOS_DISK`)

**Step 1: Inspect existing disks**

```bash
grep -n "'disks'" config/filesystems.php
```

**Step 2: Add `anexos` disk**

In `config/filesystems.php`, inside the `'disks'` array, add:

```php
'anexos' => [
    'driver' => 'local',
    'root' => storage_path('app/anexos'),
    'throw' => false,
],
```

**Step 3: Add env var**

In `.env.example`:

```
ANEXOS_DISK=anexos
```

And expose it via `config/filesystems.php` default resolution only if needed. Hardcoding the disk name `anexos` in code is acceptable for MVP — no env var required. Skip `.env.example` change if keeping hardcoded.

**Step 4: Commit**

```bash
git add config/filesystems.php
git commit -m "chore(config): add anexos local disk"
```

---

## Task 5: DTOs

**Files:**
- Create: `app/Modules/MedicalRecord/DTOs/UploadAttachmentDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/ConfirmAttachmentDTO.php`

**Step 1: Write `UploadAttachmentDTO`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Enums\TipoAnexo;
use Illuminate\Http\UploadedFile;

final readonly class UploadAttachmentDTO
{
    public function __construct(
        public int $prontuarioId,
        public TipoAnexo $tipoAnexo,
        public UploadedFile $file,
        public ?string $nome = null,
    ) {}
}
```

**Step 2: Write `ConfirmAttachmentDTO`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class ConfirmAttachmentDTO
{
    /**
     * @param array<string, mixed> $examData The edited/confirmed payload the doctor reviewed.
     */
    public function __construct(
        public int $attachmentId,
        public array $examData,
    ) {}
}
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/DTOs/UploadAttachmentDTO.php app/Modules/MedicalRecord/DTOs/ConfirmAttachmentDTO.php
git commit -m "feat(medical-record): add Upload and Confirm attachment DTOs"
```

---

## Task 6: Form Requests

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Requests/UploadAttachmentRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/ConfirmAttachmentRequest.php`

**Step 1: Create requests**

```bash
php artisan make:request Modules/MedicalRecord/Http/Requests/UploadAttachmentRequest --no-interaction
php artisan make:request Modules/MedicalRecord/Http/Requests/ConfirmAttachmentRequest --no-interaction
```

**Step 2: Write `UploadAttachmentRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\TipoAnexo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadAttachmentRequest extends FormRequest
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
            'tipo_anexo' => ['required', Rule::enum(TipoAnexo::class)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,gif', 'max:10240'],
            'nome' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_anexo.required' => 'O campo tipo de anexo é obrigatório.',
            'tipo_anexo.enum' => 'O tipo de anexo informado é inválido.',
            'file.required' => 'O arquivo é obrigatório.',
            'file.file' => 'O arquivo enviado é inválido.',
            'file.mimes' => 'O arquivo deve ser PDF, JPG, JPEG, PNG ou GIF.',
            'file.max' => 'O arquivo não pode exceder 10 MB.',
            'nome.max' => 'O nome não pode exceder 255 caracteres.',
        ];
    }
}
```

**Step 3: Write `ConfirmAttachmentRequest`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmAttachmentRequest extends FormRequest
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
            'exam_data' => ['required', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'exam_data.required' => 'Os dados do exame são obrigatórios.',
            'exam_data.array' => 'Os dados do exame devem ser um objeto válido.',
        ];
    }
}
```

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Requests/UploadAttachmentRequest.php app/Modules/MedicalRecord/Http/Requests/ConfirmAttachmentRequest.php
git commit -m "feat(medical-record): add Upload and Confirm attachment form requests"
```

---

## Task 7: AttachmentResource

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Resources/AttachmentResource.php`

**Step 1: Create resource**

```bash
php artisan make:resource Modules/MedicalRecord/Http/Resources/AttachmentResource --no-interaction
```

**Step 2: Write resource content**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use App\Modules\MedicalRecord\Models\Anexo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @mixin Anexo
 */
class AttachmentResource extends JsonResource
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
            'attachment_type' => $this->tipo_anexo->value,
            'name' => $this->nome,
            'file_type' => $this->tipo_arquivo->value,
            'file_url' => URL::temporarySignedRoute(
                'attachments.download',
                now()->addMinutes(30),
                ['attachment' => $this->id]
            ),
            'file_size' => $this->tamanho_bytes,
            'processing_status' => $this->status_processamento?->value,
            'extracted_data' => $this->dados_extraidos,
            'processing_error' => $this->erro_processamento,
            'processed_at' => $this->processado_em?->toIso8601String(),
            'confirmed_at' => $this->confirmado_em?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Resources/AttachmentResource.php
git commit -m "feat(medical-record): add AttachmentResource with signed download URL"
```

---

## Task 8: Domain Events

**Files:**
- Create: `app/Modules/MedicalRecord/Events/AttachmentUploaded.php`
- Create: `app/Modules/MedicalRecord/Events/AttachmentParseCompleted.php`
- Create: `app/Modules/MedicalRecord/Events/AttachmentParseFailed.php`
- Create: `app/Modules/MedicalRecord/Events/AttachmentConfirmed.php`

**Step 1: Write `AttachmentUploaded`**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Events;

use App\Modules\MedicalRecord\Models\Anexo;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AttachmentUploaded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Anexo $attachment) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('medical-records.'.$this->attachment->prontuario_id)];
    }

    public function broadcastAs(): string
    {
        return 'attachment.uploaded';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->attachment->id,
            'medical_record_id' => $this->attachment->prontuario_id,
            'attachment_type' => $this->attachment->tipo_anexo->value,
            'processing_status' => $this->attachment->status_processamento?->value,
        ];
    }
}
```

**Step 2: Write `AttachmentParseCompleted`** (same structure; `broadcastAs() = 'attachment.parse_completed'`; include `extracted_data` in payload)

**Step 3: Write `AttachmentParseFailed`** (same structure; `broadcastAs() = 'attachment.parse_failed'`; include `processing_error` in payload)

**Step 4: Write `AttachmentConfirmed`** (same structure; `broadcastAs() = 'attachment.confirmed'`; include `confirmed_at` in payload)

**Step 5: Register broadcasting channel authorization**

Edit `routes/channels.php` (create if absent) and add:

```php
use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('medical-records.{prontuarioId}', function (User $user, int $prontuarioId): bool {
    return Prontuario::query()
        ->whereKey($prontuarioId)
        ->where('user_id', $user->id)
        ->exists();
});
```

If `routes/channels.php` is not auto-loaded, register it in `bootstrap/app.php` via `->withBroadcasting(channels: __DIR__.'/../routes/channels.php')`.

**Step 6: Commit**

```bash
git add app/Modules/MedicalRecord/Events/ routes/channels.php bootstrap/app.php
git commit -m "feat(medical-record): add attachment broadcast events and channel authorization"
```

---

## Task 9: Stub `ParseAttachmentJob`

**Files:**
- Create: `app/Modules/MedicalRecord/Jobs/ParseAttachmentJob.php`

**Step 1: Create job**

```bash
php artisan make:job Modules/MedicalRecord/Jobs/ParseAttachmentJob --no-interaction
```

**Step 2: Write job content**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Jobs;

use App\Modules\MedicalRecord\Enums\StatusProcessamento;
use App\Modules\MedicalRecord\Enums\TipoAnexo;
use App\Modules\MedicalRecord\Events\AttachmentParseCompleted;
use App\Modules\MedicalRecord\Events\AttachmentParseFailed;
use App\Modules\MedicalRecord\Models\Anexo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ParseAttachmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $attachmentId) {}

    public function handle(): void
    {
        $attachment = Anexo::find($this->attachmentId);

        if ($attachment === null) {
            return;
        }

        if (! $attachment->isParseable()) {
            return;
        }

        $attachment->update([
            'status_processamento' => StatusProcessamento::Processing,
        ]);

        try {
            $mock = $this->mockExtractionFor($attachment->tipo_anexo);

            $attachment->update([
                'status_processamento' => StatusProcessamento::Completed,
                'dados_extraidos' => $mock,
                'erro_processamento' => null,
                'processado_em' => now(),
            ]);

            AttachmentParseCompleted::dispatch($attachment->fresh());
        } catch (Throwable $e) {
            $attachment->update([
                'status_processamento' => StatusProcessamento::Failed,
                'erro_processamento' => $e->getMessage(),
                'processado_em' => now(),
            ]);

            AttachmentParseFailed::dispatch($attachment->fresh());
        }
    }

    /**
     * Produce a mocked `dados_extraidos` payload shaped to the attachment type.
     * Replace with real AI integration in a later PR.
     *
     * @return array<string, mixed>
     */
    private function mockExtractionFor(TipoAnexo $tipo): array
    {
        $today = now()->toDateString();

        return match ($tipo) {
            TipoAnexo::Ecg => ['date' => $today, 'pattern' => 'normal'],
            TipoAnexo::Rx => ['date' => $today, 'pattern' => 'normal'],
            TipoAnexo::Eco => ['date' => $today, 'type' => 'transthoracic', 'ef' => 60],
            TipoAnexo::Dexa => ['date' => $today, 'bmd' => 1.1, 't_score' => -0.5],
            TipoAnexo::Lab => ['date' => $today, 'panels' => [], 'loose' => []],
            TipoAnexo::Mapa => ['date' => $today, 'systolic_awake' => 128, 'diastolic_awake' => 82],
            TipoAnexo::Mrpa => ['date' => $today, 'days_monitored' => 7, 'limb' => 'right_arm', 'measurements' => []],
            TipoAnexo::Holter, TipoAnexo::Polissonografia => ['date' => $today, 'text' => 'Stub extraction — replace with AI output.'],
            default => ['date' => $today, 'raw' => 'Stub extraction — replace with AI output.'],
        };
    }
}
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Jobs/ParseAttachmentJob.php
git commit -m "feat(medical-record): add ParseAttachmentJob stub with per-type mock extraction"
```

---

## Task 10: `AttachmentService`

**Files:**
- Create: `app/Modules/MedicalRecord/Services/AttachmentService.php`

**Step 1: Write service content**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\ConfirmAttachmentDTO;
use App\Modules\MedicalRecord\DTOs\UploadAttachmentDTO;
use App\Modules\MedicalRecord\Enums\MedicalRecordStatus;
use App\Modules\MedicalRecord\Enums\StatusProcessamento;
use App\Modules\MedicalRecord\Enums\TipoArquivo;
use App\Modules\MedicalRecord\Events\AttachmentConfirmed;
use App\Modules\MedicalRecord\Events\AttachmentUploaded;
use App\Modules\MedicalRecord\Jobs\ParseAttachmentJob;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class AttachmentService
{
    public function listForProntuario(int $prontuarioId): Collection
    {
        return Anexo::query()
            ->where('prontuario_id', $prontuarioId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findOrFail(int $id): Anexo
    {
        return Anexo::query()->findOrFail($id);
    }

    public function upload(UploadAttachmentDTO $dto): Anexo
    {
        $prontuario = Prontuario::query()->findOrFail($dto->prontuarioId);

        if ($prontuario->status === MedicalRecordStatus::Finalized) {
            throw new DomainException('Não é possível anexar arquivos a um prontuário finalizado.');
        }

        $extension = strtolower($dto->file->getClientOriginalExtension());
        $tipoArquivo = TipoArquivo::fromExtension($extension);

        $filename = Str::uuid()->toString().'.'.$extension;
        $path = 'anexos/'.$prontuario->id.'/'.$filename;

        Storage::disk('anexos')->put($path, file_get_contents($dto->file->getRealPath()));

        $initialStatus = $dto->tipoAnexo->isParseable() ? StatusProcessamento::Pending : null;

        $attachment = Anexo::query()->create([
            'prontuario_id' => $prontuario->id,
            'paciente_id' => $prontuario->paciente_id,
            'tipo_anexo' => $dto->tipoAnexo,
            'nome' => $dto->nome ?? $dto->file->getClientOriginalName(),
            'tipo_arquivo' => $tipoArquivo,
            'caminho' => $path,
            'tamanho_bytes' => $dto->file->getSize(),
            'status_processamento' => $initialStatus,
        ]);

        AttachmentUploaded::dispatch($attachment);

        if ($attachment->isParseable()) {
            ParseAttachmentJob::dispatch($attachment->id);
        }

        return $attachment->fresh();
    }

    public function retryParse(int $id): Anexo
    {
        $attachment = $this->findOrFail($id);

        if (! $attachment->isParseable()) {
            throw new DomainException('Este tipo de anexo não é processável por IA.');
        }

        $attachment->update([
            'status_processamento' => StatusProcessamento::Pending,
            'erro_processamento' => null,
        ]);

        ParseAttachmentJob::dispatch($attachment->id);

        return $attachment->fresh();
    }

    public function confirm(ConfirmAttachmentDTO $dto): Anexo
    {
        return DB::transaction(function () use ($dto) {
            $attachment = $this->findOrFail($dto->attachmentId);

            if (! $attachment->isParseable()) {
                throw new DomainException('Este tipo de anexo não requer confirmação.');
            }

            if ($attachment->status_processamento !== StatusProcessamento::Completed) {
                throw new DomainException('Somente anexos com processamento concluído podem ser confirmados.');
            }

            $attachment->update([
                'dados_extraidos' => $dto->examData,
                'status_processamento' => StatusProcessamento::Confirmed,
                'confirmado_em' => now(),
            ]);

            $fresh = $attachment->fresh();
            AttachmentConfirmed::dispatch($fresh);

            return $fresh;
        });
    }

    public function delete(int $id): void
    {
        $attachment = $this->findOrFail($id);

        if ($attachment->status_processamento === StatusProcessamento::Confirmed) {
            throw new DomainException('Não é possível remover um anexo já confirmado.');
        }

        Storage::disk('anexos')->delete($attachment->caminho);
        $attachment->delete();
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Services/AttachmentService.php
git commit -m "feat(medical-record): add AttachmentService with upload, parse retry, confirm, delete"
```

---

## Task 11: `AttachmentPolicy`

**Files:**
- Create: `app/Modules/MedicalRecord/Policies/AttachmentPolicy.php`

**Step 1: Write policy content**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;

class AttachmentPolicy
{
    public function viewAnyForProntuario(User $user, Prontuario $prontuario): bool
    {
        return $prontuario->user_id === $user->id;
    }

    public function view(User $user, Anexo $attachment): bool
    {
        return $attachment->prontuario->user_id === $user->id;
    }

    public function create(User $user, Prontuario $prontuario): bool
    {
        return $prontuario->user_id === $user->id;
    }

    public function update(User $user, Anexo $attachment): bool
    {
        return $attachment->prontuario->user_id === $user->id;
    }

    public function delete(User $user, Anexo $attachment): bool
    {
        return $attachment->prontuario->user_id === $user->id;
    }
}
```

**Step 2: Register policy**

In `app/Modules/MedicalRecord/Providers/MedicalRecordServiceProvider.php`, inside `boot()`, add:

```php
Gate::policy(Anexo::class, AttachmentPolicy::class);
```

Import: `use App\Modules\MedicalRecord\Models\Anexo; use App\Modules\MedicalRecord\Policies\AttachmentPolicy; use Illuminate\Support\Facades\Gate;`

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Policies/AttachmentPolicy.php app/Modules/MedicalRecord/Providers/MedicalRecordServiceProvider.php
git commit -m "feat(medical-record): add AttachmentPolicy and register in service provider"
```

---

## Task 12: `AttachmentController`

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Controllers/AttachmentController.php`

**Step 1: Write controller content**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MedicalRecord\DTOs\ConfirmAttachmentDTO;
use App\Modules\MedicalRecord\DTOs\UploadAttachmentDTO;
use App\Modules\MedicalRecord\Enums\TipoAnexo;
use App\Modules\MedicalRecord\Http\Requests\ConfirmAttachmentRequest;
use App\Modules\MedicalRecord\Http\Requests\UploadAttachmentRequest;
use App\Modules\MedicalRecord\Http\Resources\AttachmentResource;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $service) {}

    /**
     * List attachments of a medical record.
     *
     * @authenticated
     * @group Attachments
     */
    public function index(Request $request, int $prontuarioId): AnonymousResourceCollection
    {
        $prontuario = Prontuario::query()->findOrFail($prontuarioId);
        $this->authorize('viewAnyForProntuario', [Anexo::class, $prontuario]);

        return AttachmentResource::collection(
            $this->service->listForProntuario($prontuarioId)
        );
    }

    /**
     * Upload a file as an attachment for a medical record.
     *
     * @authenticated
     * @group Attachments
     */
    public function store(UploadAttachmentRequest $request, int $prontuarioId): AttachmentResource
    {
        $prontuario = Prontuario::query()->findOrFail($prontuarioId);
        $this->authorize('create', [Anexo::class, $prontuario]);

        $dto = new UploadAttachmentDTO(
            prontuarioId: $prontuarioId,
            tipoAnexo: TipoAnexo::from($request->validated('tipo_anexo')),
            file: $request->file('file'),
            nome: $request->validated('nome'),
        );

        $attachment = $this->service->upload($dto);

        return new AttachmentResource($attachment);
    }

    /**
     * Show a single attachment with its current processing state.
     *
     * @authenticated
     * @group Attachments
     */
    public function show(int $id): AttachmentResource
    {
        $attachment = $this->service->findOrFail($id);
        $this->authorize('view', $attachment);

        return new AttachmentResource($attachment);
    }

    /**
     * Download the raw file.
     *
     * @authenticated
     * @group Attachments
     */
    public function download(int $id): StreamedResponse
    {
        $attachment = $this->service->findOrFail($id);
        $this->authorize('view', $attachment);

        return Storage::disk('anexos')->download(
            $attachment->caminho,
            $attachment->nome,
        );
    }

    /**
     * Retry AI parsing for a failed or completed attachment.
     *
     * @authenticated
     * @group Attachments
     */
    public function retry(int $id): AttachmentResource
    {
        $attachment = $this->service->findOrFail($id);
        $this->authorize('update', $attachment);

        return new AttachmentResource($this->service->retryParse($id));
    }

    /**
     * Confirm the doctor-reviewed extracted data for an attachment.
     *
     * @authenticated
     * @group Attachments
     */
    public function confirm(ConfirmAttachmentRequest $request, int $id): AttachmentResource
    {
        $attachment = $this->service->findOrFail($id);
        $this->authorize('update', $attachment);

        $dto = new ConfirmAttachmentDTO(
            attachmentId: $id,
            examData: $request->validated('exam_data'),
        );

        return new AttachmentResource($this->service->confirm($dto));
    }

    /**
     * Delete an attachment (not allowed after confirmation).
     *
     * @authenticated
     * @group Attachments
     */
    public function destroy(int $id): JsonResponse
    {
        $attachment = $this->service->findOrFail($id);
        $this->authorize('delete', $attachment);

        $this->service->delete($id);

        return response()->json([], 204);
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Controllers/AttachmentController.php
git commit -m "feat(medical-record): add AttachmentController with index/store/show/download/retry/confirm/destroy"
```

---

## Task 13: Routes

**Files:**
- Modify: `app/Modules/MedicalRecord/routes.php`

**Step 1: Add routes**

Inside the existing `Route::middleware(['auth:sanctum'])->group(function () { ... })` block (or equivalent existing pattern), add:

```php
Route::get('medical-records/{prontuario}/attachments', [AttachmentController::class, 'index']);
Route::post('medical-records/{prontuario}/attachments', [AttachmentController::class, 'store']);
Route::get('attachments/{attachment}', [AttachmentController::class, 'show']);
Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])
    ->name('attachments.download');
Route::post('attachments/{attachment}/retry', [AttachmentController::class, 'retry']);
Route::post('attachments/{attachment}/confirm', [AttachmentController::class, 'confirm']);
Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);
```

Add `use App\Modules\MedicalRecord\Http\Controllers\AttachmentController;` at the top of `routes.php`.

**Step 2: Verify routes**

```bash
php artisan route:list --path=attachments
```

Expected: 6 routes (index via medical-records/{id}/attachments, store, show, download, retry, confirm, destroy).

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/routes.php
git commit -m "feat(medical-record): add attachment routes (list, upload, show, download, retry, confirm, delete)"
```

---

## Task 14: Feature tests — Upload

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/UploadAttachmentTest.php`

**Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Enums\StatusProcessamento;
use App\Modules\MedicalRecord\Enums\TipoAnexo;
use App\Modules\MedicalRecord\Events\AttachmentUploaded;
use App\Modules\MedicalRecord\Jobs\ParseAttachmentJob;
use App\Modules\MedicalRecord\Models\Anexo;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('uploads a pdf attachment for a draft medical record', function () {
    Storage::fake('anexos');
    Event::fake([AttachmentUploaded::class]);
    Queue::fake();

    $user = User::factory()->create();
    $prontuario = Prontuario::factory()->for($user)->create();

    $this->actingAs($user);

    $response = $this->postJson("/api/medical-records/{$prontuario->id}/attachments", [
        'tipo_anexo' => TipoAnexo::Ecg->value,
        'file' => UploadedFile::fake()->create('ecg.pdf', 200, 'application/pdf'),
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.attachment_type', 'ecg');
    $response->assertJsonPath('data.processing_status', StatusProcessamento::Pending->value);

    $attachment = Anexo::query()->firstOrFail();
    Storage::disk('anexos')->assertExists($attachment->caminho);

    Event::assertDispatched(AttachmentUploaded::class);
    Queue::assertPushed(ParseAttachmentJob::class);
});

it('uploads a non-parseable documento without queuing parse job', function () {
    Storage::fake('anexos');
    Queue::fake();

    $user = User::factory()->create();
    $prontuario = Prontuario::factory()->for($user)->create();

    $this->actingAs($user);

    $this->postJson("/api/medical-records/{$prontuario->id}/attachments", [
        'tipo_anexo' => TipoAnexo::Documento->value,
        'file' => UploadedFile::fake()->create('laudo.pdf', 100, 'application/pdf'),
    ])->assertCreated()
      ->assertJsonPath('data.processing_status', null);

    Queue::assertNotPushed(ParseAttachmentJob::class);
});

it('rejects file with disallowed mime type', function () {
    Storage::fake('anexos');

    $user = User::factory()->create();
    $prontuario = Prontuario::factory()->for($user)->create();

    $this->actingAs($user);

    $this->postJson("/api/medical-records/{$prontuario->id}/attachments", [
        'tipo_anexo' => TipoAnexo::Lab->value,
        'file' => UploadedFile::fake()->create('foo.exe', 50, 'application/octet-stream'),
    ])->assertUnprocessable()
      ->assertJsonValidationErrors(['file']);
});

it('denies upload to a medical record owned by another doctor', function () {
    Storage::fake('anexos');

    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $prontuario = Prontuario::factory()->for($owner)->create();

    $this->actingAs($intruder);

    $this->postJson("/api/medical-records/{$prontuario->id}/attachments", [
        'tipo_anexo' => TipoAnexo::Ecg->value,
        'file' => UploadedFile::fake()->create('ecg.pdf', 100, 'application/pdf'),
    ])->assertForbidden();
});

it('rejects upload to a finalized medical record', function () {
    Storage::fake('anexos');

    $user = User::factory()->create();
    $prontuario = Prontuario::factory()->for($user)->finalized()->create();

    $this->actingAs($user);

    $this->postJson("/api/medical-records/{$prontuario->id}/attachments", [
        'tipo_anexo' => TipoAnexo::Ecg->value,
        'file' => UploadedFile::fake()->create('ecg.pdf', 100, 'application/pdf'),
    ])->assertStatus(409);
});
```

**Step 2: Run tests**

```bash
php artisan test --compact --filter=UploadAttachmentTest
```

Expected: ALL PASS (fail first if any behavior missing, then fix until green).

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/UploadAttachmentTest.php
git commit -m "test(medical-record): add upload attachment feature tests"
```

---

## Task 15: Feature tests — List, Show, Download

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/ListAttachmentsTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ShowAttachmentTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/DownloadAttachmentTest.php`

**Step 1: Write `ListAttachmentsTest`**

Covers: lists only attachments of the given prontuário; excludes other prontuários; denies if doctor does not own the prontuário; empty when no attachments.

**Step 2: Write `ShowAttachmentTest`**

Covers: returns single attachment; 404 when missing; 403 when owned by another doctor.

**Step 3: Write `DownloadAttachmentTest`**

Covers: returns file content with correct filename/headers; 403 when not owner; uses signed URL mechanism (generate signed route manually in test).

**Step 4: Run tests**

```bash
php artisan test --compact --filter=Attachment
```

**Step 5: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/ListAttachmentsTest.php app/Modules/MedicalRecord/Tests/Feature/ShowAttachmentTest.php app/Modules/MedicalRecord/Tests/Feature/DownloadAttachmentTest.php
git commit -m "test(medical-record): add list, show, download attachment tests"
```

---

## Task 16: Feature tests — Delete, Retry

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/DeleteAttachmentTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/RetryAttachmentTest.php`

**Step 1: `DeleteAttachmentTest`**

Covers:
- Deletes pending/completed/failed attachments (204), removes file from disk.
- Forbids deletion of `confirmed` attachments (409).
- 403 for non-owner.

**Step 2: `RetryAttachmentTest`**

Covers:
- Resets `failed` attachment to `pending` + pushes job.
- Resets `completed` attachment back to `pending` + pushes job (re-parse).
- Forbids retry for `documento`/`outro` (409).
- 403 for non-owner.

**Step 3: Run tests**

```bash
php artisan test --compact --filter=Attachment
```

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/DeleteAttachmentTest.php app/Modules/MedicalRecord/Tests/Feature/RetryAttachmentTest.php
git commit -m "test(medical-record): add delete and retry attachment tests"
```

---

## Task 17: Feature tests — Confirm

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/ConfirmAttachmentTest.php`

**Step 1: Write tests**

Covers:
- Confirms a `completed` attachment: stores `exam_data` as `dados_extraidos`, sets `status = confirmed`, sets `confirmado_em`, dispatches `AttachmentConfirmed`.
- Forbids confirm when status != completed (409).
- Forbids confirm for `documento`/`outro` (409).
- Requires non-empty `exam_data` (422).
- 403 for non-owner.

Use `Event::fake([AttachmentConfirmed::class])` and assert dispatched.

**Step 2: Run tests**

```bash
php artisan test --compact --filter=ConfirmAttachmentTest
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/ConfirmAttachmentTest.php
git commit -m "test(medical-record): add confirm attachment tests"
```

---

## Task 18: Unit test — `ParseAttachmentJob`

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/ParseAttachmentJobTest.php`

**Step 1: Write tests**

Covers:
- Running the job on a parseable attachment sets `status = completed`, fills `dados_extraidos` with the type-specific mock shape, sets `processado_em`, dispatches `AttachmentParseCompleted`.
- Running the job on a `documento` attachment is a no-op (no status mutation, no event).
- On thrown exception inside `mockExtractionFor` (simulate via a non-existent attachment id), the job returns without error.

Use `(new ParseAttachmentJob($id))->handle();` directly — don't dispatch to queue.

**Step 2: Run tests**

```bash
php artisan test --compact --filter=ParseAttachmentJobTest
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/ParseAttachmentJobTest.php
git commit -m "test(medical-record): add ParseAttachmentJob behavior tests"
```

---

## Task 19: Scribe API docs

**Files:**
- Modify: `app/Modules/MedicalRecord/Http/Controllers/AttachmentController.php` (enrich PHPDoc with `@response` blocks, `@bodyParam`, `@urlParam`).

**Step 1: Annotate every action**

For each action in `AttachmentController`, add:
- `@urlParam` for path params
- `@bodyParam` for request fields (including `multipart/form-data`)
- `@response 200|201|204` success with a realistic JSON payload
- `@response 401` unauthenticated
- `@response 403` forbidden
- `@response 404` not found (for show/download/retry/confirm/destroy)
- `@response 409` domain error (for finalized prontuario, confirmed attachment deletion, non-parseable retry)
- `@response 422` validation (for store/confirm)

All descriptions, group names, and scenario labels in **English** per project language policy. Validation error example payloads use Portuguese messages.

Reference example from an existing controller for tone/shape.

**Step 2: Regenerate docs**

```bash
php artisan scribe:generate
```

Expected: docs regenerated without errors.

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Controllers/AttachmentController.php public/docs resources/views/scribe
git commit -m "docs(medical-record): annotate attachment endpoints for Scribe"
```

---

## Task 20: Code formatting, docs regen, final test run

**Step 1: Pint**

```bash
vendor/bin/pint --dirty
```

**Step 2: Full test suite**

```bash
php artisan test --compact
```

Expected: ALL green. The pre-existing suite should continue passing (the attachments module does not touch existing tables/models except for the `Prontuario::anexos()` relationship addition, which is additive).

**Step 3: Scribe regen (ensure committed state is up to date)**

```bash
php artisan scribe:generate
```

**Step 4: Commit any remaining changes**

```bash
git add -A
git commit -m "chore(medical-record): pint formatting + regenerated docs" --allow-empty
```

If nothing to commit, skip this commit.

---

## Task 21: Update Serena checkpoint

**Step 1: Update session checkpoint**

Use `mcp__serena__write_memory` with name `session-checkpoint.md`:

```markdown
# Session Checkpoint

## Completed
- [x] Phase 6 (Attachments module) — branch feature/attachments-module
  - anexos table + Anexo model + TipoAnexo/StatusProcessamento/TipoArquivo enums
  - Upload (pdf/jpg/jpeg/png/gif, 10MB) → local disk `anexos`
  - Stub ParseAttachmentJob with per-type mock extraction
  - Events: AttachmentUploaded, AttachmentParseCompleted, AttachmentParseFailed, AttachmentConfirmed (Reverb broadcast on medical-records.{id} private channel)
  - Confirm endpoint (Option A — dedicated merge, stores exam_data in dados_extraidos + status=confirmed + event)
  - Policy: doctor must own prontuario
  - Signed download URLs (30min)
  - Tests: upload, list, show, download, retry, confirm, delete, parse job

## Next Phases
- [ ] Phase 6.5: Materialize dados_extraidos → Resultado* rows on confirm (per-tipo dispatcher)
- [ ] Phase 7: Metrics / Evolution API
- [ ] Phase 8: Catalog Seeders
```

**Step 2: (No commit — the checkpoint lives in Serena memories, not the repo.)**

---

## Task 22: Open Pull Request

**Step 1: Push branch and open PR**

```bash
git push -u origin feature/attachments-module
gh pr create --title "feat(medical-record): módulo de anexos com upload, stub de parsing e confirm" --body "$(cat <<'EOF'
## Descrição

Implementa o sub-módulo de Anexos dentro de MedicalRecord, cobrindo:

- Upload de arquivos (PDF, JPG, JPEG, PNG, GIF, até 10MB) sempre vinculados a um prontuário em rascunho
- Armazenamento local no disco `anexos` em `anexos/{prontuario_id}/{uuid}.{ext}`
- Job `ParseAttachmentJob` (stub) que mocka `dados_extraidos` por `tipo_anexo`
- Eventos de domínio com broadcast via Reverb no canal privado `medical-records.{prontuario_id}`:
  - `AttachmentUploaded`, `AttachmentParseCompleted`, `AttachmentParseFailed`, `AttachmentConfirmed`
- Endpoint `confirm` (dedicated merge) que persiste os dados revisados pelo médico e marca o anexo como `confirmed`
- Policies garantindo isolamento por médico dono do prontuário
- URLs de download assinadas (30 minutos)

## Escopo

- `tipo_anexo`: 17 valores definidos em enum (lab, ecg, rx, eco, mapa, mrpa, dexa, teste_ergometrico, ecodoppler_carotidas, elastografia_hepatica, cat, cintilografia, pe_diabetico, holter, polissonografia, documento, outro)
- Status: `pending → processing → completed → confirmed`, com `failed` para falhas
- `documento` e `outro` não passam pela fila de parsing

## Fora do escopo

- Integração real com IA (o job atual é stub)
- Materialização de `dados_extraidos` nas tabelas `Resultado*` (próxima fase)
- Migração do disco local para S3

## Testes

Todos os testes existentes continuam passando. Novos testes cobrem upload (sucesso, tipos não parseáveis, MIME inválido, dono errado, prontuário finalizado), listagem, show, download, retry, confirm e delete, além de testes comportamentais do `ParseAttachmentJob`.
EOF
)"
```

**Step 2: Report the PR URL.**

---

## Execution Notes

- **Worktree:** optional; feel free to execute on a feature branch directly.
- **Test each task in isolation** before committing; keep commits focused.
- **Do not merge** this PR until frontend extends its `Attachment` TypeScript type to include `attachment_type`, `processing_status`, `extracted_data`, `processing_error`, `processed_at`, `confirmed_at`. Coordinate with frontend PR.
- **Reverb local dev:** `php artisan reverb:start` (already part of `composer dev`).
- **Queue worker:** ensure `php artisan queue:work` is running so stub parse executes (also part of `composer dev`).
