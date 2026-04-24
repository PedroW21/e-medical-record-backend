# Core Medical Record + Anthropometry — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement CRUD for the core medical record (prontuário) with anthropometry, JSONB sections (physical exam, problem list, risk scores, conduct), immutability enforcement, and follow-up continuity.

**Architecture:** The `MedicalRecord` module already exists with prescription sub-module. We extend it with: (1) a new migration adding JSONB columns to `prontuarios` + creating `medidas_antropometricas`, (2) custom cast classes for each JSONB field returning typed DTOs, (3) `MedicalRecordController` with create/show/list/update/finalize endpoints, (4) immutability via model `saving` event + DB trigger, (5) follow-up continuity copying selective data from the previous record.

**Tech Stack:** Laravel 12, PHP 8.5, PostgreSQL, Pest 4, Sanctum auth

**Frontend types reference:** `/home/pedro-verner/projects/carrera-verner/medical-record/e-medical-record-frontend/src/modules/medical-records/types/`

---

## Task 1: Migration — Add JSONB columns to `prontuarios` + immutability trigger

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_12_000001_add_jsonb_columns_to_prontuarios_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_12_000002_add_immutability_trigger_to_prontuarios.php`

**Step 1: Create migration for JSONB columns**

```bash
php artisan make:migration add_jsonb_columns_to_prontuarios_table --path=app/Modules/MedicalRecord/Database/Migrations --no-interaction
```

Migration content:

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
        Schema::table('prontuarios', function (Blueprint $table): void {
            $table->jsonb('exame_fisico')->nullable()->after('baseado_em_prontuario_id');
            $table->jsonb('lista_problemas')->nullable()->after('exame_fisico');
            $table->jsonb('escores_risco')->nullable()->after('lista_problemas');
            $table->jsonb('conduta')->nullable()->after('escores_risco');

            // Additional indices from design doc
            $table->index(['user_id', 'paciente_id', 'created_at']);
            $table->index(['status', 'paciente_id']);
        });
    }

    public function down(): void
    {
        Schema::table('prontuarios', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'paciente_id', 'created_at']);
            $table->dropIndex(['status', 'paciente_id']);
            $table->dropColumn(['exame_fisico', 'lista_problemas', 'escores_risco', 'conduta']);
        });
    }
};
```

**Step 2: Create migration for immutability trigger**

```bash
php artisan make:migration add_immutability_trigger_to_prontuarios --path=app/Modules/MedicalRecord/Database/Migrations --no-interaction
```

Migration content:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION prevent_finalized_prontuario_update()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF OLD.finalizado_em IS NOT NULL THEN
                    RAISE EXCEPTION 'Não é possível modificar um prontuário finalizado.';
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_prevent_finalized_prontuario_update
            BEFORE UPDATE ON prontuarios
            FOR EACH ROW
            EXECUTE FUNCTION prevent_finalized_prontuario_update();
        ");
    }

    public function down(): void
    {
        DB::unprepared("
            DROP TRIGGER IF EXISTS trg_prevent_finalized_prontuario_update ON prontuarios;
            DROP FUNCTION IF EXISTS prevent_finalized_prontuario_update();
        ");
    }
};
```

**Step 3: Run migrations**

```bash
php artisan migrate
```

Expected: Both migrations run successfully.

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Migrations/2026_03_12_000001_add_jsonb_columns_to_prontuarios_table.php app/Modules/MedicalRecord/Database/Migrations/2026_03_12_000002_add_immutability_trigger_to_prontuarios.php
git commit -m "feat(medical-record): add JSONB columns and immutability trigger to prontuarios"
```

---

## Task 2: Migration — Create `medidas_antropometricas` table

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_12_000003_create_medidas_antropometricas_table.php`

**Step 1: Create migration**

```bash
php artisan make:migration create_medidas_antropometricas_table --path=app/Modules/MedicalRecord/Database/Migrations --no-interaction
```

Migration content — columns mapped from frontend `AnthropometryTabData`:

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
        Schema::create('medidas_antropometricas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes');

            // Vital signs
            $table->decimal('peso', 6, 2)->nullable();           // kg
            $table->decimal('altura', 5, 2)->nullable();         // cm
            $table->decimal('imc', 5, 2)->nullable();            // kg/m²
            $table->string('classificacao_imc')->nullable();     // underweight|normal|overweight|obesity_1|obesity_2|obesity_3
            $table->integer('fc')->nullable();                   // bpm
            $table->decimal('spo2', 5, 2)->nullable();           // %
            $table->decimal('temperatura', 4, 2)->nullable();    // °C

            // Blood pressure: 3 positions x 2 arms (sitting/standing/supine x right/left)
            $table->integer('pa_sentado_d_pas')->nullable();
            $table->integer('pa_sentado_d_pad')->nullable();
            $table->integer('pa_sentado_e_pas')->nullable();
            $table->integer('pa_sentado_e_pad')->nullable();
            $table->integer('pa_em_pe_d_pas')->nullable();
            $table->integer('pa_em_pe_d_pad')->nullable();
            $table->integer('pa_em_pe_e_pas')->nullable();
            $table->integer('pa_em_pe_e_pad')->nullable();
            $table->integer('pa_deitado_d_pas')->nullable();
            $table->integer('pa_deitado_d_pad')->nullable();
            $table->integer('pa_deitado_e_pas')->nullable();
            $table->integer('pa_deitado_e_pad')->nullable();

            // Circumferences (cm)
            $table->decimal('circunferencia_pescoco', 5, 2)->nullable();
            $table->decimal('circunferencia_cintura', 5, 2)->nullable();
            $table->decimal('circunferencia_quadril', 5, 2)->nullable();
            $table->decimal('circunferencia_abdominal', 5, 2)->nullable();
            $table->decimal('circunferencia_braco_d', 5, 2)->nullable();
            $table->decimal('circunferencia_braco_e', 5, 2)->nullable();
            $table->decimal('circunferencia_coxa_d', 5, 2)->nullable();
            $table->decimal('circunferencia_coxa_e', 5, 2)->nullable();
            $table->decimal('circunferencia_panturrilha_d', 5, 2)->nullable();
            $table->decimal('circunferencia_panturrilha_e', 5, 2)->nullable();
            $table->decimal('relacao_cintura_quadril', 5, 4)->nullable();   // waist_hip_ratio
            $table->decimal('relacao_cintura_altura', 5, 4)->nullable();    // waist_height_ratio (RCA)

            // Skinfolds (mm) — Jackson-Pollock 7-site
            $table->decimal('dobra_tricipital', 5, 2)->nullable();
            $table->decimal('dobra_subescapular', 5, 2)->nullable();
            $table->decimal('dobra_suprailica', 5, 2)->nullable();
            $table->decimal('dobra_abdominal', 5, 2)->nullable();
            $table->decimal('dobra_peitoral', 5, 2)->nullable();
            $table->decimal('dobra_coxa', 5, 2)->nullable();
            $table->decimal('dobra_axilar_media', 5, 2)->nullable();        // midaxillary

            // Airway assessment (pre-anesthetic)
            $table->decimal('abertura_bucal', 4, 2)->nullable();            // cm
            $table->decimal('distancia_tireomentual', 4, 2)->nullable();    // cm
            $table->decimal('distancia_mentoesternal', 4, 2)->nullable();   // cm
            $table->string('deslocamento_mandibular')->nullable();          // good|reduced

            $table->timestamps();

            // Indices
            $table->index(['paciente_id', 'created_at']);
            $table->index(['prontuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medidas_antropometricas');
    }
};
```

**Key mapping decisions (frontend → DB):**
- `blood_pressure.right_arm.sitting.systolic` → `pa_sentado_d_pas`
- `blood_pressure.right_arm.sitting.diastolic` → `pa_sentado_d_pad`
- `blood_pressure.heart_rate` → `fc`
- `blood_pressure.oxygen_sat` → `spo2`
- `blood_pressure.temperature` → `temperatura`
- `measures.weight` → `peso`
- `measures.height` → `altura`
- `measures.bmi` → `imc`
- `measures.bmi_classification` → `classificacao_imc`
- `measures.abdominal_circumference` → `circunferencia_abdominal`
- `measures.hip_circumference` → `circunferencia_quadril`
- `measures.waist_hip_ratio` → `relacao_cintura_quadril`
- `measures.waist_height_ratio` → `relacao_cintura_altura`
- `measures.cervical_circumference` → `circunferencia_pescoco`
- `measures.calf_measurement_left` → `circunferencia_panturrilha_e`
- `measures.calf_measurement_right` → `circunferencia_panturrilha_d`
- `measures.mouth_opening` → `abertura_bucal`
- `measures.thyromental_distance` → `distancia_tireomentual`
- `measures.mentosternal_distance` → `distancia_mentoesternal`
- `measures.mandible_displacement` → `deslocamento_mandibular`
- `skinfolds.triceps` → `dobra_tricipital`
- `skinfolds.subscapular` → `dobra_subescapular`
- `skinfolds.suprailiac` → `dobra_suprailica`
- `skinfolds.abdominal` → `dobra_abdominal`
- `skinfolds.pectoral` → `dobra_peitoral`
- `skinfolds.medial_thigh` → `dobra_coxa`
- `skinfolds.midaxillary` → `dobra_axilar_media`

**Note:** Design doc had `dobra_bicipital` and `dobra_panturrilha` but frontend doesn't have them. Frontend has `midaxillary` instead of `bicipital`. Follow frontend.

**Step 2: Run migration**

```bash
php artisan migrate
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Migrations/2026_03_12_000003_create_medidas_antropometricas_table.php
git commit -m "feat(medical-record): create medidas_antropometricas table"
```

---

## Task 3: JSONB DTOs — Typed data structures for the 4 JSONB fields

**Files:**
- Create: `app/Modules/MedicalRecord/DTOs/PhysicalExamData.php`
- Create: `app/Modules/MedicalRecord/DTOs/ProblemListData.php`
- Create: `app/Modules/MedicalRecord/DTOs/RiskScoresData.php`
- Create: `app/Modules/MedicalRecord/DTOs/ConductData.php`

Each DTO is a `final readonly class` that mirrors the frontend TypeScript types exactly, with a `fromArray()` factory and a `toArray()` method for serialization.

**Step 1: Create PhysicalExamData DTO**

File: `app/Modules/MedicalRecord/DTOs/PhysicalExamData.php`

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class PhysicalExamData
{
    /**
     * @param array{
     *     is_normal: bool,
     *     rhythm?: string|null,
     *     heart_sounds?: string|null,
     *     murmur?: string|null,
     *     observations?: string|null
     * } $cardiac
     * @param array{
     *     is_normal: bool,
     *     vesicular_murmur?: string|null,
     *     adventitious_sounds?: string|null,
     *     observations?: string|null
     * } $respiratory
     * @param array{
     *     varicose_veins: bool,
     *     edema: bool,
     *     lymphedema: bool,
     *     ulcer: bool,
     *     asymmetry: bool,
     *     sensitivity_alteration: bool,
     *     motricity_alteration: bool,
     *     observations?: string|null
     * } $lowerLimbs
     * @param array{
     *     status: string,
     *     prosthesis_location?: string[]|null,
     *     diseases?: string[]|null,
     *     observations?: string|null
     * } $dentition
     * @param array{
     *     status: string,
     *     observations?: string|null
     * } $gums
     */
    public function __construct(
        public array $cardiac,
        public array $respiratory,
        public array $lowerLimbs,
        public array $dentition,
        public array $gums,
        public ?int $ceap = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cardiac: $data['cardiac'],
            respiratory: $data['respiratory'],
            lowerLimbs: $data['lower_limbs'],
            dentition: $data['dentition'],
            gums: $data['gums'],
            ceap: isset($data['ceap']) ? (int) $data['ceap'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cardiac' => $this->cardiac,
            'respiratory' => $this->respiratory,
            'lower_limbs' => $this->lowerLimbs,
            'dentition' => $this->dentition,
            'gums' => $this->gums,
            'ceap' => $this->ceap,
        ];
    }
}
```

**Step 2: Create ProblemListData DTO**

File: `app/Modules/MedicalRecord/DTOs/ProblemListData.php`

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class ProblemListData
{
    /**
     * @param array<int, array{
     *     problem_id: string,
     *     label: string,
     *     category: string,
     *     is_custom: bool,
     *     selected_variation?: string|null
     * }> $selectedProblems
     * @param string[] $customProblems
     */
    public function __construct(
        public array $selectedProblems,
        public array $customProblems,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            selectedProblems: $data['selected_problems'] ?? [],
            customProblems: $data['custom_problems'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'selected_problems' => $this->selectedProblems,
            'custom_problems' => $this->customProblems,
        ];
    }
}
```

**Step 3: Create RiskScoresData DTO**

File: `app/Modules/MedicalRecord/DTOs/RiskScoresData.php`

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class RiskScoresData
{
    /**
     * @param array{
     *     rcri: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, criteria_met?: string[]|null, score_points?: int|null},
     *     acp_detsky: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, criteria_met?: string[]|null, score_points?: int|null},
     *     aub_has2: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, criteria_met?: string[]|null, score_points?: int|null},
     *     asa: string,
     *     nyha: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, criteria_met?: string[]|null, score_points?: int|null},
     *     met: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, criteria_met?: string[]|null, score_points?: int|null}
     * } $cardiovascular
     * @param array{
     *     respiratory_failure_risk: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, score_points?: int|null},
     *     pneumonia_risk: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, score_points?: int|null},
     *     ariscat: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, score_points?: int|null},
     *     stop_bang: array{calculated_value?: string|null, manual_override?: string|null, final_value: string, score_points?: int|null},
     *     stop_bang_criteria?: array{snoring: bool, tired: bool, observed_apnea: bool, high_pressure: bool, bmi_over_35: bool, age_over_50: bool, neck_over_40: bool, male_gender: bool}|null
     * } $pulmonary
     * @param array{
     *     ckd_epi: array{method: string, creatinine?: float|null, cystatin_c?: float|null, gfr?: float|null, gfr_stage?: string|null, albuminuria?: float|null, albuminuria_category?: string|null, kdigo_risk?: string|null}
     * } $renal
     */
    public function __construct(
        public ?string $primaryDisease,
        public ?string $plannedSurgery,
        public array $cardiovascular,
        public array $pulmonary,
        public array $renal,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            primaryDisease: $data['primary_disease'] ?? null,
            plannedSurgery: $data['planned_surgery'] ?? null,
            cardiovascular: $data['cardiovascular'],
            pulmonary: $data['pulmonary'],
            renal: $data['renal'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'primary_disease' => $this->primaryDisease,
            'planned_surgery' => $this->plannedSurgery,
            'cardiovascular' => $this->cardiovascular,
            'pulmonary' => $this->pulmonary,
            'renal' => $this->renal,
        ];
    }
}
```

**Step 4: Create ConductData DTO**

File: `app/Modules/MedicalRecord/DTOs/ConductData.php`

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

final readonly class ConductData
{
    /**
     * @param array<int, array{
     *     type: string,
     *     label: string,
     *     default_text: string,
     *     custom_text?: string|null
     * }> $diets
     * @param array{
     *     default_text: string,
     *     custom_text?: string|null
     * } $physicalActivity
     */
    public function __construct(
        public bool $sleepHygiene,
        public string $sleepDefaultText,
        public ?string $sleepObservations,
        public array $diets,
        public array $physicalActivity,
        public bool $xenobioticsRestriction,
        public string $xenobioticsDefaultText,
        public ?string $xenobioticsObservations,
        public bool $medicationCompliance,
        public string $medicationDefaultText,
        public ?string $medicationObservations,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sleepHygiene: (bool) $data['sleep_hygiene'],
            sleepDefaultText: $data['sleep_default_text'],
            sleepObservations: $data['sleep_observations'] ?? null,
            diets: $data['diets'] ?? [],
            physicalActivity: $data['physical_activity'],
            xenobioticsRestriction: (bool) $data['xenobiotics_restriction'],
            xenobioticsDefaultText: $data['xenobiotics_default_text'],
            xenobioticsObservations: $data['xenobiotics_observations'] ?? null,
            medicationCompliance: (bool) $data['medication_compliance'],
            medicationDefaultText: $data['medication_default_text'],
            medicationObservations: $data['medication_observations'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sleep_hygiene' => $this->sleepHygiene,
            'sleep_default_text' => $this->sleepDefaultText,
            'sleep_observations' => $this->sleepObservations,
            'diets' => $this->diets,
            'physical_activity' => $this->physicalActivity,
            'xenobiotics_restriction' => $this->xenobioticsRestriction,
            'xenobiotics_default_text' => $this->xenobioticsDefaultText,
            'xenobiotics_observations' => $this->xenobioticsObservations,
            'medication_compliance' => $this->medicationCompliance,
            'medication_default_text' => $this->medicationDefaultText,
            'medication_observations' => $this->medicationObservations,
        ];
    }
}
```

**Step 5: Commit**

```bash
git add app/Modules/MedicalRecord/DTOs/PhysicalExamData.php app/Modules/MedicalRecord/DTOs/ProblemListData.php app/Modules/MedicalRecord/DTOs/RiskScoresData.php app/Modules/MedicalRecord/DTOs/ConductData.php
git commit -m "feat(medical-record): add typed DTOs for JSONB sections"
```

---

## Task 4: Custom Eloquent Casts for JSONB fields

**Files:**
- Create: `app/Modules/MedicalRecord/Casts/PhysicalExamCast.php`
- Create: `app/Modules/MedicalRecord/Casts/ProblemListCast.php`
- Create: `app/Modules/MedicalRecord/Casts/RiskScoresCast.php`
- Create: `app/Modules/MedicalRecord/Casts/ConductCast.php`

Each cast implements `CastsAttributes`, converting between JSON string (DB) ↔ DTO (PHP).

**Step 1: Create all 4 cast classes**

Pattern (same for all 4, just swap DTO class):

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Casts;

use App\Modules\MedicalRecord\DTOs\PhysicalExamData;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<PhysicalExamData, PhysicalExamData|array<string, mixed>>
 */
final class PhysicalExamCast implements CastsAttributes
{
    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PhysicalExamData
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);

        return PhysicalExamData::fromArray($data);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param PhysicalExamData|array<string, mixed>|null $value
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PhysicalExamData) {
            return json_encode($value->toArray());
        }

        return json_encode($value);
    }
}
```

Create analogous classes for `ProblemListCast` (uses `ProblemListData`), `RiskScoresCast` (uses `RiskScoresData`), `ConductCast` (uses `ConductData`).

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Casts/
git commit -m "feat(medical-record): add custom Eloquent casts for JSONB fields"
```

---

## Task 5: Model — MedidaAntropometrica + update Prontuario

**Files:**
- Create: `app/Modules/MedicalRecord/Models/MedidaAntropometrica.php`
- Modify: `app/Modules/MedicalRecord/Models/Prontuario.php`

**Step 1: Create MedidaAntropometrica model**

File: `app/Modules/MedicalRecord/Models/MedidaAntropometrica.php`

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
 * @property float|null $peso
 * @property float|null $altura
 * @property float|null $imc
 * @property string|null $classificacao_imc
 * @property int|null $fc
 * @property float|null $spo2
 * @property float|null $temperatura
 * @property int|null $pa_sentado_d_pas
 * @property int|null $pa_sentado_d_pad
 * @property int|null $pa_sentado_e_pas
 * @property int|null $pa_sentado_e_pad
 * @property int|null $pa_em_pe_d_pas
 * @property int|null $pa_em_pe_d_pad
 * @property int|null $pa_em_pe_e_pas
 * @property int|null $pa_em_pe_e_pad
 * @property int|null $pa_deitado_d_pas
 * @property int|null $pa_deitado_d_pad
 * @property int|null $pa_deitado_e_pas
 * @property int|null $pa_deitado_e_pad
 * @property float|null $circunferencia_pescoco
 * @property float|null $circunferencia_cintura
 * @property float|null $circunferencia_quadril
 * @property float|null $circunferencia_abdominal
 * @property float|null $circunferencia_braco_d
 * @property float|null $circunferencia_braco_e
 * @property float|null $circunferencia_coxa_d
 * @property float|null $circunferencia_coxa_e
 * @property float|null $circunferencia_panturrilha_d
 * @property float|null $circunferencia_panturrilha_e
 * @property float|null $relacao_cintura_quadril
 * @property float|null $relacao_cintura_altura
 * @property float|null $dobra_tricipital
 * @property float|null $dobra_subescapular
 * @property float|null $dobra_suprailica
 * @property float|null $dobra_abdominal
 * @property float|null $dobra_peitoral
 * @property float|null $dobra_coxa
 * @property float|null $dobra_axilar_media
 * @property float|null $abertura_bucal
 * @property float|null $distancia_tireomentual
 * @property float|null $distancia_mentoesternal
 * @property string|null $deslocamento_mandibular
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Prontuario $prontuario
 * @property-read Paciente $paciente
 */
class MedidaAntropometrica extends Model
{
    use HasFactory;

    protected $table = 'medidas_antropometricas';

    protected $fillable = [
        'prontuario_id',
        'paciente_id',
        'peso',
        'altura',
        'imc',
        'classificacao_imc',
        'fc',
        'spo2',
        'temperatura',
        'pa_sentado_d_pas',
        'pa_sentado_d_pad',
        'pa_sentado_e_pas',
        'pa_sentado_e_pad',
        'pa_em_pe_d_pas',
        'pa_em_pe_d_pad',
        'pa_em_pe_e_pas',
        'pa_em_pe_e_pad',
        'pa_deitado_d_pas',
        'pa_deitado_d_pad',
        'pa_deitado_e_pas',
        'pa_deitado_e_pad',
        'circunferencia_pescoco',
        'circunferencia_cintura',
        'circunferencia_quadril',
        'circunferencia_abdominal',
        'circunferencia_braco_d',
        'circunferencia_braco_e',
        'circunferencia_coxa_d',
        'circunferencia_coxa_e',
        'circunferencia_panturrilha_d',
        'circunferencia_panturrilha_e',
        'relacao_cintura_quadril',
        'relacao_cintura_altura',
        'dobra_tricipital',
        'dobra_subescapular',
        'dobra_suprailica',
        'dobra_abdominal',
        'dobra_peitoral',
        'dobra_coxa',
        'dobra_axilar_media',
        'abertura_bucal',
        'distancia_tireomentual',
        'distancia_mentoesternal',
        'deslocamento_mandibular',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'peso' => 'decimal:2',
            'altura' => 'decimal:2',
            'imc' => 'decimal:2',
            'spo2' => 'decimal:2',
            'temperatura' => 'decimal:2',
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

    protected static function newFactory(): \App\Modules\MedicalRecord\Database\Factories\AnthropometryFactory
    {
        return \App\Modules\MedicalRecord\Database\Factories\AnthropometryFactory::new();
    }
}
```

**Step 2: Update Prontuario model**

Add to `Prontuario.php`:
- JSONB fields to `$fillable`
- Custom casts for JSONB fields
- `medidaAntropometrica()` HasOne relationship
- Immutability enforcement in `saving` event via `booted()` method

```php
// Add to fillable:
'exame_fisico',
'lista_problemas',
'escores_risco',
'conduta',

// Add to casts():
'exame_fisico' => PhysicalExamCast::class,
'lista_problemas' => ProblemListCast::class,
'escores_risco' => RiskScoresCast::class,
'conduta' => ConductCast::class,

// Add relationship:
/**
 * @return HasOne<MedidaAntropometrica, $this>
 */
public function medidaAntropometrica(): HasOne
{
    return $this->hasOne(MedidaAntropometrica::class);
}

// Add immutability enforcement:
protected static function booted(): void
{
    static::saving(function (Prontuario $prontuario): void {
        if (! $prontuario->isDirty('finalizado_em') && $prontuario->getOriginal('finalizado_em') !== null) {
            throw new \Illuminate\Validation\ValidationException(
                validator: validator([], []),
                response: response()->json([
                    'message' => 'Não é possível modificar um prontuário finalizado.',
                ], 403)
            );
        }
    });
}
```

**Important:** The `saving` event allows setting `finalizado_em` (the finalization act itself) but blocks any other update once the record is finalized. The DB trigger is the second defense layer.

**Step 3: Update PHPDoc on Prontuario to include new properties**

Add to the class docblock:
```php
 * @property PhysicalExamData|null $exame_fisico
 * @property ProblemListData|null $lista_problemas
 * @property RiskScoresData|null $escores_risco
 * @property ConductData|null $conduta
 * @property-read MedidaAntropometrica|null $medidaAntropometrica
```

**Step 4: Commit**

```bash
git add app/Modules/MedicalRecord/Models/MedidaAntropometrica.php app/Modules/MedicalRecord/Models/Prontuario.php
git commit -m "feat(medical-record): add MedidaAntropometrica model and update Prontuario with JSONB casts"
```

---

## Task 6: Factory — AnthropometryFactory + update MedicalRecordFactory

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Factories/AnthropometryFactory.php`
- Modify: `app/Modules/MedicalRecord/Database/Factories/MedicalRecordFactory.php`

**Step 1: Create AnthropometryFactory**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\MedidaAntropometrica;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedidaAntropometrica>
 */
final class AnthropometryFactory extends Factory
{
    protected $model = MedidaAntropometrica::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attributes) => Prontuario::find($attributes['prontuario_id'])->paciente_id,
            'peso' => fake()->randomFloat(2, 40, 150),
            'altura' => fake()->randomFloat(2, 140, 200),
            'imc' => fake()->randomFloat(2, 15, 45),
            'classificacao_imc' => fake()->randomElement(['underweight', 'normal', 'overweight', 'obesity_1', 'obesity_2', 'obesity_3']),
            'fc' => fake()->numberBetween(50, 120),
            'spo2' => fake()->randomFloat(2, 90, 100),
            'temperatura' => fake()->randomFloat(2, 35, 39),
            'pa_sentado_d_pas' => fake()->numberBetween(90, 180),
            'pa_sentado_d_pad' => fake()->numberBetween(60, 110),
        ];
    }

    public function withFullVitals(): static
    {
        return $this->state(fn (array $attributes) => [
            'pa_sentado_e_pas' => fake()->numberBetween(90, 180),
            'pa_sentado_e_pad' => fake()->numberBetween(60, 110),
            'pa_em_pe_d_pas' => fake()->numberBetween(90, 180),
            'pa_em_pe_d_pad' => fake()->numberBetween(60, 110),
            'circunferencia_abdominal' => fake()->randomFloat(2, 60, 130),
            'circunferencia_quadril' => fake()->randomFloat(2, 70, 140),
            'circunferencia_pescoco' => fake()->randomFloat(2, 28, 50),
        ]);
    }

    public function withSkinfolds(): static
    {
        return $this->state(fn (array $attributes) => [
            'dobra_tricipital' => fake()->randomFloat(2, 5, 35),
            'dobra_subescapular' => fake()->randomFloat(2, 5, 35),
            'dobra_suprailica' => fake()->randomFloat(2, 5, 35),
            'dobra_abdominal' => fake()->randomFloat(2, 5, 40),
            'dobra_peitoral' => fake()->randomFloat(2, 3, 25),
            'dobra_coxa' => fake()->randomFloat(2, 5, 40),
            'dobra_axilar_media' => fake()->randomFloat(2, 5, 30),
        ]);
    }

    public function withAirwayAssessment(): static
    {
        return $this->state(fn (array $attributes) => [
            'abertura_bucal' => fake()->randomFloat(2, 2, 5),
            'distancia_tireomentual' => fake()->randomFloat(2, 4, 9),
            'distancia_mentoesternal' => fake()->randomFloat(2, 8, 16),
            'deslocamento_mandibular' => fake()->randomElement(['good', 'reduced']),
        ]);
    }
}
```

**Step 2: Update MedicalRecordFactory with JSONB states**

Add these states to `MedicalRecordFactory`:

```php
public function preAnesthetic(): static
{
    return $this->state(fn (array $attributes) => [
        'tipo' => MedicalRecordType::PreAnesthetic,
    ]);
}

public function withPhysicalExam(): static
{
    return $this->state(fn (array $attributes) => [
        'exame_fisico' => [
            'cardiac' => ['is_normal' => true],
            'respiratory' => ['is_normal' => true],
            'lower_limbs' => [
                'varicose_veins' => false,
                'edema' => false,
                'lymphedema' => false,
                'ulcer' => false,
                'asymmetry' => false,
                'sensitivity_alteration' => false,
                'motricity_alteration' => false,
            ],
            'dentition' => ['status' => 'regular'],
            'gums' => ['status' => 'regular'],
        ],
    ]);
}

public function withProblemList(): static
{
    return $this->state(fn (array $attributes) => [
        'lista_problemas' => [
            'selected_problems' => [
                [
                    'problem_id' => 'has',
                    'label' => 'Hipertensão Arterial Sistêmica',
                    'category' => 'metabolic',
                    'is_custom' => false,
                ],
            ],
            'custom_problems' => [],
        ],
    ]);
}

public function withConduct(): static
{
    return $this->state(fn (array $attributes) => [
        'conduta' => [
            'sleep_hygiene' => true,
            'sleep_default_text' => 'Manter higiene do sono adequada.',
            'sleep_observations' => null,
            'diets' => [],
            'physical_activity' => [
                'default_text' => 'Atividade física regular conforme orientação.',
            ],
            'xenobiotics_restriction' => false,
            'xenobiotics_default_text' => 'Evitar tabagismo e etilismo.',
            'xenobiotics_observations' => null,
            'medication_compliance' => true,
            'medication_default_text' => 'Manter adesão medicamentosa.',
            'medication_observations' => null,
        ],
    ]);
}
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Factories/AnthropometryFactory.php app/Modules/MedicalRecord/Database/Factories/MedicalRecordFactory.php
git commit -m "feat(medical-record): add AnthropometryFactory and JSONB factory states"
```

---

## Task 7: DTOs — CreateMedicalRecordDTO + UpdateMedicalRecordDTO

**Files:**
- Create: `app/Modules/MedicalRecord/DTOs/CreateMedicalRecordDTO.php`
- Create: `app/Modules/MedicalRecord/DTOs/UpdateMedicalRecordDTO.php`

**Step 1: Create CreateMedicalRecordDTO**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Enums\MedicalRecordType;
use App\Modules\MedicalRecord\Http\Requests\StoreMedicalRecordRequest;

final readonly class CreateMedicalRecordDTO
{
    /**
     * @param array<string, mixed>|null $anthropometry
     * @param array<string, mixed>|null $physicalExam
     * @param array<string, mixed>|null $problemList
     * @param array<string, mixed>|null $riskScores
     * @param array<string, mixed>|null $conduct
     */
    public function __construct(
        public int $patientId,
        public MedicalRecordType $type,
        public ?array $anthropometry = null,
        public ?array $physicalExam = null,
        public ?array $problemList = null,
        public ?array $riskScores = null,
        public ?array $conduct = null,
        public ?int $basedOnRecordId = null,
    ) {}

    public static function fromRequest(StoreMedicalRecordRequest $request): self
    {
        return new self(
            patientId: (int) $request->validated('patient_id'),
            type: MedicalRecordType::from($request->validated('type')),
            anthropometry: $request->validated('anthropometry'),
            physicalExam: $request->validated('physical_exam'),
            problemList: $request->validated('problem_list'),
            riskScores: $request->validated('risk_scores'),
            conduct: $request->validated('conduct'),
            basedOnRecordId: $request->validated('based_on_record_id') ? (int) $request->validated('based_on_record_id') : null,
        );
    }
}
```

**Step 2: Create UpdateMedicalRecordDTO**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\DTOs;

use App\Modules\MedicalRecord\Http\Requests\UpdateMedicalRecordRequest;

final readonly class UpdateMedicalRecordDTO
{
    /**
     * @param array<string, mixed>|null $anthropometry
     * @param array<string, mixed>|null $physicalExam
     * @param array<string, mixed>|null $problemList
     * @param array<string, mixed>|null $riskScores
     * @param array<string, mixed>|null $conduct
     */
    public function __construct(
        public ?array $anthropometry = null,
        public ?array $physicalExam = null,
        public ?array $problemList = null,
        public ?array $riskScores = null,
        public ?array $conduct = null,
    ) {}

    public static function fromRequest(UpdateMedicalRecordRequest $request): self
    {
        return new self(
            anthropometry: $request->validated('anthropometry'),
            physicalExam: $request->validated('physical_exam'),
            problemList: $request->validated('problem_list'),
            riskScores: $request->validated('risk_scores'),
            conduct: $request->validated('conduct'),
        );
    }
}
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/DTOs/CreateMedicalRecordDTO.php app/Modules/MedicalRecord/DTOs/UpdateMedicalRecordDTO.php
git commit -m "feat(medical-record): add CreateMedicalRecordDTO and UpdateMedicalRecordDTO"
```

---

## Task 8: Form Requests — StoreMedicalRecordRequest + UpdateMedicalRecordRequest

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Requests/StoreMedicalRecordRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/UpdateMedicalRecordRequest.php`

**Step 1: Create StoreMedicalRecordRequest**

This is the most complex Request — it validates all JSONB shapes. Follow existing pattern from `StorePrescriptionRequest` (authorize returns true, Gate in controller).

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\MedicalRecordType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMedicalRecordRequest extends FormRequest
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
            'patient_id' => ['required', 'integer', 'exists:pacientes,id'],
            'type' => ['required', 'string', Rule::in(array_column(MedicalRecordType::cases(), 'value'))],
            'based_on_record_id' => ['nullable', 'integer', 'exists:prontuarios,id'],

            // Anthropometry (flat object with nested blood_pressure, measures, skinfolds)
            'anthropometry' => ['nullable', 'array'],
            'anthropometry.blood_pressure' => ['nullable', 'array'],
            'anthropometry.blood_pressure.right_arm' => ['nullable', 'array'],
            'anthropometry.blood_pressure.right_arm.standing' => ['nullable', 'array'],
            'anthropometry.blood_pressure.right_arm.standing.systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'anthropometry.blood_pressure.right_arm.standing.diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'anthropometry.blood_pressure.right_arm.sitting' => ['nullable', 'array'],
            'anthropometry.blood_pressure.right_arm.sitting.systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'anthropometry.blood_pressure.right_arm.sitting.diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'anthropometry.blood_pressure.right_arm.supine' => ['nullable', 'array'],
            'anthropometry.blood_pressure.right_arm.supine.systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'anthropometry.blood_pressure.right_arm.supine.diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'anthropometry.blood_pressure.left_arm' => ['nullable', 'array'],
            'anthropometry.blood_pressure.left_arm.standing' => ['nullable', 'array'],
            'anthropometry.blood_pressure.left_arm.standing.systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'anthropometry.blood_pressure.left_arm.standing.diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'anthropometry.blood_pressure.left_arm.sitting' => ['nullable', 'array'],
            'anthropometry.blood_pressure.left_arm.sitting.systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'anthropometry.blood_pressure.left_arm.sitting.diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'anthropometry.blood_pressure.left_arm.supine' => ['nullable', 'array'],
            'anthropometry.blood_pressure.left_arm.supine.systolic' => ['nullable', 'integer', 'min:40', 'max:300'],
            'anthropometry.blood_pressure.left_arm.supine.diastolic' => ['nullable', 'integer', 'min:20', 'max:200'],
            'anthropometry.blood_pressure.heart_rate' => ['nullable', 'integer', 'min:20', 'max:250'],
            'anthropometry.blood_pressure.oxygen_sat' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'anthropometry.blood_pressure.temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'anthropometry.measures' => ['nullable', 'array'],
            'anthropometry.measures.weight' => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'anthropometry.measures.height' => ['nullable', 'numeric', 'min:30', 'max:280'],
            'anthropometry.measures.bmi' => ['nullable', 'numeric', 'min:5', 'max:100'],
            'anthropometry.measures.bmi_classification' => ['nullable', 'string', Rule::in(['underweight', 'normal', 'overweight', 'obesity_1', 'obesity_2', 'obesity_3'])],
            'anthropometry.measures.abdominal_circumference' => ['nullable', 'numeric', 'min:30', 'max:200'],
            'anthropometry.measures.hip_circumference' => ['nullable', 'numeric', 'min:30', 'max:200'],
            'anthropometry.measures.waist_hip_ratio' => ['nullable', 'numeric', 'min:0', 'max:3'],
            'anthropometry.measures.waist_height_ratio' => ['nullable', 'numeric', 'min:0', 'max:3'],
            'anthropometry.measures.cervical_circumference' => ['nullable', 'numeric', 'min:20', 'max:70'],
            'anthropometry.measures.calf_measurement_left' => ['nullable', 'numeric', 'min:15', 'max:70'],
            'anthropometry.measures.calf_measurement_right' => ['nullable', 'numeric', 'min:15', 'max:70'],
            'anthropometry.measures.mouth_opening' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'anthropometry.measures.thyromental_distance' => ['nullable', 'numeric', 'min:0', 'max:15'],
            'anthropometry.measures.mentosternal_distance' => ['nullable', 'numeric', 'min:0', 'max:25'],
            'anthropometry.measures.mandible_displacement' => ['nullable', 'string', Rule::in(['good', 'reduced'])],
            'anthropometry.skinfolds' => ['nullable', 'array'],
            'anthropometry.skinfolds.triceps' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'anthropometry.skinfolds.subscapular' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'anthropometry.skinfolds.suprailiac' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'anthropometry.skinfolds.abdominal' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'anthropometry.skinfolds.pectoral' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'anthropometry.skinfolds.medial_thigh' => ['nullable', 'numeric', 'min:1', 'max:80'],
            'anthropometry.skinfolds.midaxillary' => ['nullable', 'numeric', 'min:1', 'max:80'],

            // Physical exam (JSONB)
            'physical_exam' => ['nullable', 'array'],
            'physical_exam.cardiac' => ['required_with:physical_exam', 'array'],
            'physical_exam.cardiac.is_normal' => ['required_with:physical_exam.cardiac', 'boolean'],
            'physical_exam.cardiac.rhythm' => ['nullable', 'string', 'max:500'],
            'physical_exam.cardiac.heart_sounds' => ['nullable', 'string', 'max:500'],
            'physical_exam.cardiac.murmur' => ['nullable', 'string', 'max:500'],
            'physical_exam.cardiac.observations' => ['nullable', 'string', 'max:2000'],
            'physical_exam.respiratory' => ['required_with:physical_exam', 'array'],
            'physical_exam.respiratory.is_normal' => ['required_with:physical_exam.respiratory', 'boolean'],
            'physical_exam.respiratory.vesicular_murmur' => ['nullable', 'string', 'max:500'],
            'physical_exam.respiratory.adventitious_sounds' => ['nullable', 'string', 'max:500'],
            'physical_exam.respiratory.observations' => ['nullable', 'string', 'max:2000'],
            'physical_exam.lower_limbs' => ['required_with:physical_exam', 'array'],
            'physical_exam.lower_limbs.varicose_veins' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.edema' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.lymphedema' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.ulcer' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.asymmetry' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.sensitivity_alteration' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.motricity_alteration' => ['required_with:physical_exam.lower_limbs', 'boolean'],
            'physical_exam.lower_limbs.observations' => ['nullable', 'string', 'max:2000'],
            'physical_exam.ceap' => ['nullable', 'integer', 'min:0', 'max:6'],
            'physical_exam.dentition' => ['required_with:physical_exam', 'array'],
            'physical_exam.dentition.status' => ['required_with:physical_exam.dentition', 'string', Rule::in(['regular', 'prosthesis', 'altered'])],
            'physical_exam.dentition.prosthesis_location' => ['nullable', 'array'],
            'physical_exam.dentition.prosthesis_location.*' => ['string', Rule::in(['superior', 'inferior'])],
            'physical_exam.dentition.diseases' => ['nullable', 'array'],
            'physical_exam.dentition.diseases.*' => ['string', Rule::in(['gingivitis', 'periodontitis', 'tartar'])],
            'physical_exam.dentition.observations' => ['nullable', 'string', 'max:2000'],
            'physical_exam.gums' => ['required_with:physical_exam', 'array'],
            'physical_exam.gums.status' => ['required_with:physical_exam.gums', 'string', Rule::in(['regular', 'altered'])],
            'physical_exam.gums.observations' => ['nullable', 'string', 'max:2000'],

            // Problem list (JSONB)
            'problem_list' => ['nullable', 'array'],
            'problem_list.selected_problems' => ['nullable', 'array'],
            'problem_list.selected_problems.*.problem_id' => ['required', 'string', 'max:100'],
            'problem_list.selected_problems.*.label' => ['required', 'string', 'max:255'],
            'problem_list.selected_problems.*.category' => ['required', 'string', Rule::in(['inflammatory', 'hematologic', 'metabolic', 'gastrointestinal', 'endocrine', 'renal', 'musculoskeletal'])],
            'problem_list.selected_problems.*.is_custom' => ['required', 'boolean'],
            'problem_list.selected_problems.*.selected_variation' => ['nullable', 'string', 'max:100'],
            'problem_list.custom_problems' => ['nullable', 'array'],
            'problem_list.custom_problems.*' => ['string', 'max:500'],

            // Risk scores (JSONB — only for pre_anesthetic)
            'risk_scores' => ['nullable', 'array', Rule::requiredIf(fn () => $this->input('type') === 'pre_anesthetic')],
            'risk_scores.primary_disease' => ['nullable', 'string', 'max:500'],
            'risk_scores.planned_surgery' => ['nullable', 'string', 'max:500'],
            'risk_scores.cardiovascular' => ['required_with:risk_scores', 'array'],
            'risk_scores.pulmonary' => ['required_with:risk_scores', 'array'],
            'risk_scores.renal' => ['required_with:risk_scores', 'array'],

            // Conduct (JSONB)
            'conduct' => ['nullable', 'array'],
            'conduct.sleep_hygiene' => ['required_with:conduct', 'boolean'],
            'conduct.sleep_default_text' => ['required_with:conduct', 'string', 'max:2000'],
            'conduct.sleep_observations' => ['nullable', 'string', 'max:2000'],
            'conduct.diets' => ['nullable', 'array'],
            'conduct.diets.*.type' => ['required', 'string', Rule::in(['dash', 'mediterranean', 'low_carb', 'high_fat', 'intermittent_fasting', 'carnivore', 'paleolithic', 'antihistamine', 'other'])],
            'conduct.diets.*.label' => ['required', 'string', 'max:255'],
            'conduct.diets.*.default_text' => ['required', 'string', 'max:2000'],
            'conduct.diets.*.custom_text' => ['nullable', 'string', 'max:2000'],
            'conduct.physical_activity' => ['required_with:conduct', 'array'],
            'conduct.physical_activity.default_text' => ['required', 'string', 'max:2000'],
            'conduct.physical_activity.custom_text' => ['nullable', 'string', 'max:2000'],
            'conduct.xenobiotics_restriction' => ['required_with:conduct', 'boolean'],
            'conduct.xenobiotics_default_text' => ['required_with:conduct', 'string', 'max:2000'],
            'conduct.xenobiotics_observations' => ['nullable', 'string', 'max:2000'],
            'conduct.medication_compliance' => ['required_with:conduct', 'boolean'],
            'conduct.medication_default_text' => ['required_with:conduct', 'string', 'max:2000'],
            'conduct.medication_observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'O campo paciente é obrigatório.',
            'patient_id.exists' => 'Paciente não encontrado.',
            'type.required' => 'O tipo de consulta é obrigatório.',
            'type.in' => 'Tipo de consulta inválido.',
            'based_on_record_id.exists' => 'Prontuário base não encontrado.',
            'risk_scores.required_if' => 'Os escores de risco são obrigatórios para avaliação pré-anestésica.',
            'anthropometry.blood_pressure.right_arm.sitting.systolic.min' => 'A pressão sistólica deve ser no mínimo 40 mmHg.',
            'anthropometry.blood_pressure.right_arm.sitting.systolic.max' => 'A pressão sistólica deve ser no máximo 300 mmHg.',
            'anthropometry.measures.weight.min' => 'O peso deve ser no mínimo 0,5 kg.',
            'anthropometry.measures.height.min' => 'A altura deve ser no mínimo 30 cm.',
            'physical_exam.cardiac.is_normal.required_with' => 'O campo de ausculta cardíaca normal é obrigatório.',
            'physical_exam.respiratory.is_normal.required_with' => 'O campo de ausculta respiratória normal é obrigatório.',
            'physical_exam.dentition.status.required_with' => 'O status da dentição é obrigatório.',
            'physical_exam.gums.status.required_with' => 'O status da gengiva é obrigatório.',
            'conduct.sleep_hygiene.required_with' => 'O campo de higiene do sono é obrigatório na conduta.',
            'conduct.medication_compliance.required_with' => 'O campo de adesão medicamentosa é obrigatório na conduta.',
        ];
    }
}
```

**Step 2: Create UpdateMedicalRecordRequest**

Same rules as Store but all top-level fields are optional (no `patient_id` or `type` — those can't change). Use `sometimes` instead of `required_with` for conditional presence.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMedicalRecordRequest extends FormRequest
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
        // Same nested rules as StoreMedicalRecordRequest for anthropometry, physical_exam,
        // problem_list, risk_scores, conduct — all top-level fields are 'sometimes' instead of required.
        // Extract shared rules into a private method or trait if needed.
        return [
            'anthropometry' => ['sometimes', 'nullable', 'array'],
            // ... same nested anthropometry rules as Store ...

            'physical_exam' => ['sometimes', 'nullable', 'array'],
            // ... same nested physical_exam rules as Store ...

            'problem_list' => ['sometimes', 'nullable', 'array'],
            // ... same nested problem_list rules as Store ...

            'risk_scores' => ['sometimes', 'nullable', 'array'],
            // ... same nested risk_scores rules as Store ...

            'conduct' => ['sometimes', 'nullable', 'array'],
            // ... same nested conduct rules as Store ...
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        // Same messages as StoreMedicalRecordRequest
        return [];
    }
}
```

**Implementation note:** To avoid duplication, extract the shared nested validation rules into a trait `App\Modules\MedicalRecord\Http\Requests\Concerns\MedicalRecordValidationRules` with methods like `anthropometryRules()`, `physicalExamRules()`, etc. Both Store and Update requests use this trait.

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Requests/StoreMedicalRecordRequest.php app/Modules/MedicalRecord/Http/Requests/UpdateMedicalRecordRequest.php app/Modules/MedicalRecord/Http/Requests/Concerns/
git commit -m "feat(medical-record): add Store and Update form requests with JSONB validation"
```

---

## Task 9: Service — MedicalRecordService

**Files:**
- Create: `app/Modules/MedicalRecord/Services/MedicalRecordService.php`

**Key responsibilities:**
- `listForPatient(int $userId, int $patientId, array $filters)` — paginated list scoped by user ownership
- `findForUser(int $userId, int $id)` — single record with eager loads
- `create(int $userId, CreateMedicalRecordDTO $dto)` — create prontuário + anthropometry (if present)
- `update(int $id, UpdateMedicalRecordDTO $dto)` — update JSONB fields + anthropometry
- `finalize(int $id)` — set `status=finalized`, `finalizado_em=now()`
- `copyFromPrevious(int $basedOnRecordId)` — load data from a previous record for follow-up continuity

**Anthropometry mapping logic lives in the service** — it converts the frontend nested shape (`blood_pressure.right_arm.sitting.systolic`) into flat DB columns (`pa_sentado_d_pas`).

**Step 1: Create MedicalRecordService**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\CreateMedicalRecordDTO;
use App\Modules\MedicalRecord\DTOs\UpdateMedicalRecordDTO;
use App\Modules\MedicalRecord\Enums\MedicalRecordStatus;
use App\Modules\MedicalRecord\Models\MedidaAntropometrica;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MedicalRecordService
{
    /**
     * List medical records for a specific patient, scoped by the authenticated doctor.
     *
     * @param array{status?: string|null, per_page?: int|null} $filters
     * @return LengthAwarePaginator<Prontuario>
     */
    public function listForPatient(int $userId, int $patientId, array $filters = []): LengthAwarePaginator
    {
        $this->assertPatientBelongsToUser($userId, $patientId);

        $query = Prontuario::query()
            ->where('paciente_id', $patientId)
            ->where('user_id', $userId)
            ->with('medidaAntropometrica')
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    /**
     * Find a single medical record by ID, scoped by user ownership.
     */
    public function findForUser(int $userId, int $id): Prontuario
    {
        $prontuario = Prontuario::query()
            ->with(['medidaAntropometrica', 'prescricoes', 'paciente'])
            ->find($id);

        if (! $prontuario || $prontuario->user_id !== $userId) {
            throw new NotFoundHttpException('Prontuário não encontrado.');
        }

        return $prontuario;
    }

    /**
     * Create a new medical record with optional anthropometry data.
     */
    public function create(int $userId, CreateMedicalRecordDTO $dto): Prontuario
    {
        $this->assertPatientBelongsToUser($userId, $dto->patientId);

        return DB::transaction(function () use ($userId, $dto): Prontuario {
            $prontuario = Prontuario::query()->create([
                'paciente_id' => $dto->patientId,
                'user_id' => $userId,
                'tipo' => $dto->type->value,
                'status' => MedicalRecordStatus::Draft->value,
                'baseado_em_prontuario_id' => $dto->basedOnRecordId,
                'exame_fisico' => $dto->physicalExam,
                'lista_problemas' => $dto->problemList,
                'escores_risco' => $dto->riskScores,
                'conduta' => $dto->conduct,
            ]);

            if ($dto->anthropometry !== null) {
                $this->saveAnthropometry($prontuario, $dto->anthropometry);
            }

            return $prontuario->load('medidaAntropometrica');
        });
    }

    /**
     * Update a draft medical record.
     */
    public function update(int $userId, int $id, UpdateMedicalRecordDTO $dto): Prontuario
    {
        $prontuario = $this->findForUser($userId, $id);

        if (! $prontuario->isDraft()) {
            throw new \DomainException('Não é possível modificar um prontuário finalizado.');
        }

        return DB::transaction(function () use ($prontuario, $dto): Prontuario {
            $updateData = array_filter([
                'exame_fisico' => $dto->physicalExam,
                'lista_problemas' => $dto->problemList,
                'escores_risco' => $dto->riskScores,
                'conduta' => $dto->conduct,
            ], fn (mixed $value): bool => $value !== null);

            if (! empty($updateData)) {
                $prontuario->update($updateData);
            }

            if ($dto->anthropometry !== null) {
                $this->saveAnthropometry($prontuario, $dto->anthropometry);
            }

            return $prontuario->fresh(['medidaAntropometrica']);
        });
    }

    /**
     * Finalize a draft medical record, making it immutable.
     */
    public function finalize(int $userId, int $id): Prontuario
    {
        $prontuario = $this->findForUser($userId, $id);

        if (! $prontuario->isDraft()) {
            throw new \DomainException('Este prontuário já foi finalizado.');
        }

        $prontuario->update([
            'status' => MedicalRecordStatus::Finalized->value,
            'finalizado_em' => now(),
        ]);

        return $prontuario->fresh(['medidaAntropometrica']);
    }

    /**
     * Save or update anthropometry data for a medical record.
     * Converts frontend nested shape into flat DB columns.
     *
     * @param array<string, mixed> $data Frontend anthropometry payload
     */
    private function saveAnthropometry(Prontuario $prontuario, array $data): void
    {
        $bp = $data['blood_pressure'] ?? [];
        $measures = $data['measures'] ?? [];
        $skinfolds = $data['skinfolds'] ?? [];

        $columns = [
            'prontuario_id' => $prontuario->id,
            'paciente_id' => $prontuario->paciente_id,

            // Vital signs from blood_pressure
            'fc' => $bp['heart_rate'] ?? null,
            'spo2' => $bp['oxygen_sat'] ?? null,
            'temperatura' => $bp['temperature'] ?? null,

            // BP: right arm
            'pa_sentado_d_pas' => $bp['right_arm']['sitting']['systolic'] ?? null,
            'pa_sentado_d_pad' => $bp['right_arm']['sitting']['diastolic'] ?? null,
            'pa_em_pe_d_pas' => $bp['right_arm']['standing']['systolic'] ?? null,
            'pa_em_pe_d_pad' => $bp['right_arm']['standing']['diastolic'] ?? null,
            'pa_deitado_d_pas' => $bp['right_arm']['supine']['systolic'] ?? null,
            'pa_deitado_d_pad' => $bp['right_arm']['supine']['diastolic'] ?? null,

            // BP: left arm
            'pa_sentado_e_pas' => $bp['left_arm']['sitting']['systolic'] ?? null,
            'pa_sentado_e_pad' => $bp['left_arm']['sitting']['diastolic'] ?? null,
            'pa_em_pe_e_pas' => $bp['left_arm']['standing']['systolic'] ?? null,
            'pa_em_pe_e_pad' => $bp['left_arm']['standing']['diastolic'] ?? null,
            'pa_deitado_e_pas' => $bp['left_arm']['supine']['systolic'] ?? null,
            'pa_deitado_e_pad' => $bp['left_arm']['supine']['diastolic'] ?? null,

            // Measures
            'peso' => $measures['weight'] ?? null,
            'altura' => $measures['height'] ?? null,
            'imc' => $measures['bmi'] ?? null,
            'classificacao_imc' => $measures['bmi_classification'] ?? null,
            'circunferencia_abdominal' => $measures['abdominal_circumference'] ?? null,
            'circunferencia_quadril' => $measures['hip_circumference'] ?? null,
            'relacao_cintura_quadril' => $measures['waist_hip_ratio'] ?? null,
            'relacao_cintura_altura' => $measures['waist_height_ratio'] ?? null,
            'circunferencia_pescoco' => $measures['cervical_circumference'] ?? null,
            'circunferencia_cintura' => $measures['waist_circumference'] ?? null,
            'circunferencia_panturrilha_e' => $measures['calf_measurement_left'] ?? null,
            'circunferencia_panturrilha_d' => $measures['calf_measurement_right'] ?? null,
            'abertura_bucal' => $measures['mouth_opening'] ?? null,
            'distancia_tireomentual' => $measures['thyromental_distance'] ?? null,
            'distancia_mentoesternal' => $measures['mentosternal_distance'] ?? null,
            'deslocamento_mandibular' => $measures['mandible_displacement'] ?? null,

            // Skinfolds
            'dobra_tricipital' => $skinfolds['triceps'] ?? null,
            'dobra_subescapular' => $skinfolds['subscapular'] ?? null,
            'dobra_suprailica' => $skinfolds['suprailiac'] ?? null,
            'dobra_abdominal' => $skinfolds['abdominal'] ?? null,
            'dobra_peitoral' => $skinfolds['pectoral'] ?? null,
            'dobra_coxa' => $skinfolds['medial_thigh'] ?? null,
            'dobra_axilar_media' => $skinfolds['midaxillary'] ?? null,
        ];

        $prontuario->medidaAntropometrica()->updateOrCreate(
            ['prontuario_id' => $prontuario->id],
            $columns,
        );
    }

    /**
     * Assert that the patient belongs to the authenticated user.
     */
    private function assertPatientBelongsToUser(int $userId, int $patientId): void
    {
        $exists = Paciente::query()
            ->where('id', $patientId)
            ->where('user_id', $userId)
            ->exists();

        if (! $exists) {
            throw new NotFoundHttpException('Paciente não encontrado.');
        }
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Services/MedicalRecordService.php
git commit -m "feat(medical-record): add MedicalRecordService with CRUD and anthropometry mapping"
```

---

## Task 10: Resource — MedicalRecordResource + AnthropometryResource

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Resources/MedicalRecordResource.php`
- Create: `app/Modules/MedicalRecord/Http/Resources/AnthropometryResource.php`

**Step 1: Create AnthropometryResource**

Maps flat DB columns back into the frontend nested shape.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use App\Modules\MedicalRecord\Models\MedidaAntropometrica;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MedidaAntropometrica
 */
final class AnthropometryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'blood_pressure' => [
                'right_arm' => [
                    'sitting' => $this->bpReading($this->pa_sentado_d_pas, $this->pa_sentado_d_pad),
                    'standing' => $this->bpReading($this->pa_em_pe_d_pas, $this->pa_em_pe_d_pad),
                    'supine' => $this->bpReading($this->pa_deitado_d_pas, $this->pa_deitado_d_pad),
                ],
                'left_arm' => [
                    'sitting' => $this->bpReading($this->pa_sentado_e_pas, $this->pa_sentado_e_pad),
                    'standing' => $this->bpReading($this->pa_em_pe_e_pas, $this->pa_em_pe_e_pad),
                    'supine' => $this->bpReading($this->pa_deitado_e_pas, $this->pa_deitado_e_pad),
                ],
                'heart_rate' => $this->fc,
                'oxygen_sat' => $this->spo2,
                'temperature' => $this->temperatura,
            ],
            'measures' => [
                'weight' => $this->peso,
                'height' => $this->altura,
                'bmi' => $this->imc,
                'bmi_classification' => $this->classificacao_imc,
                'abdominal_circumference' => $this->circunferencia_abdominal,
                'hip_circumference' => $this->circunferencia_quadril,
                'waist_hip_ratio' => $this->relacao_cintura_quadril,
                'waist_height_ratio' => $this->relacao_cintura_altura,
                'cervical_circumference' => $this->circunferencia_pescoco,
                'calf_measurement_left' => $this->circunferencia_panturrilha_e,
                'calf_measurement_right' => $this->circunferencia_panturrilha_d,
                'mouth_opening' => $this->abertura_bucal,
                'thyromental_distance' => $this->distancia_tireomentual,
                'mentosternal_distance' => $this->distancia_mentoesternal,
                'mandible_displacement' => $this->deslocamento_mandibular,
            ],
            'skinfolds' => [
                'triceps' => $this->dobra_tricipital,
                'subscapular' => $this->dobra_subescapular,
                'suprailiac' => $this->dobra_suprailica,
                'abdominal' => $this->dobra_abdominal,
                'pectoral' => $this->dobra_peitoral,
                'medial_thigh' => $this->dobra_coxa,
                'midaxillary' => $this->dobra_axilar_media,
            ],
        ];
    }

    /**
     * @return array{systolic: int|null, diastolic: int|null}|null
     */
    private function bpReading(?int $systolic, ?int $diastolic): ?array
    {
        if ($systolic === null && $diastolic === null) {
            return null;
        }

        return [
            'systolic' => $systolic,
            'diastolic' => $diastolic,
        ];
    }
}
```

**Step 2: Create MedicalRecordResource**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Prontuario
 */
final class MedicalRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->paciente_id,
            'doctor_id' => $this->user_id,
            'type' => $this->tipo->value,
            'status' => $this->status->value,
            'based_on_record_id' => $this->baseado_em_prontuario_id,
            'anthropometry' => $this->whenLoaded('medidaAntropometrica', fn () => $this->medidaAntropometrica
                ? new AnthropometryResource($this->medidaAntropometrica)
                : null
            ),
            'physical_exam' => $this->exame_fisico?->toArray(),
            'problem_list' => $this->lista_problemas?->toArray(),
            'risk_scores' => $this->escores_risco?->toArray(),
            'conduct' => $this->conduta?->toArray(),
            'finalized_at' => $this->finalizado_em?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Resources/MedicalRecordResource.php app/Modules/MedicalRecord/Http/Resources/AnthropometryResource.php
git commit -m "feat(medical-record): add MedicalRecordResource and AnthropometryResource"
```

---

## Task 11: Policy — Update MedicalRecordPolicy

**Files:**
- Modify: `app/Modules/MedicalRecord/Policies/MedicalRecordPolicy.php`

**Step 1: Add missing policy methods**

Add `create`, `delete`, and `finalize` methods:

```php
public function create(User $user): bool
{
    return true; // Any authenticated doctor can create records
}

public function delete(User $user, Prontuario $prontuario): bool
{
    return $user->id === $prontuario->user_id && $prontuario->isDraft();
}

public function finalize(User $user, Prontuario $prontuario): bool
{
    return $user->id === $prontuario->user_id && $prontuario->isDraft();
}
```

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Policies/MedicalRecordPolicy.php
git commit -m "feat(medical-record): add create, delete, and finalize policy methods"
```

---

## Task 12: Controller — MedicalRecordController

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Controllers/MedicalRecordController.php`

**Endpoints:**
- `GET /patients/{patientId}/medical-records` — list records for a patient
- `POST /medical-records` — create a new record
- `GET /medical-records/{id}` — show a single record
- `PUT /medical-records/{id}` — update a draft record
- `POST /medical-records/{id}/finalize` — finalize a record
- `DELETE /medical-records/{id}` — delete a draft record

**Step 1: Create controller**

Follow existing `PrescriptionController` pattern exactly: final class, constructor injection, Gate::authorize, Scribe annotations, Portuguese error messages.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\DTOs\CreateMedicalRecordDTO;
use App\Modules\MedicalRecord\DTOs\UpdateMedicalRecordDTO;
use App\Modules\MedicalRecord\Http\Requests\StoreMedicalRecordRequest;
use App\Modules\MedicalRecord\Http\Requests\UpdateMedicalRecordRequest;
use App\Modules\MedicalRecord\Http\Resources\MedicalRecordResource;
use App\Modules\MedicalRecord\Services\MedicalRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class MedicalRecordController
{
    public function __construct(
        private readonly MedicalRecordService $medicalRecordService,
    ) {}

    // Scribe: @authenticated @group Medical Records
    // index(int $patientId, Request $request): AnonymousResourceCollection
    // store(StoreMedicalRecordRequest $request): MedicalRecordResource (201)
    // show(int $id): MedicalRecordResource
    // update(int $id, UpdateMedicalRecordRequest $request): MedicalRecordResource
    // finalize(int $id): MedicalRecordResource
    // destroy(int $id): JsonResponse (204)

    // Each method follows the pattern:
    // 1. Fetch resource (if needed)
    // 2. Gate::authorize()
    // 3. Call service
    // 4. Return resource/response
}
```

Full Scribe documentation must be added for all 6 endpoints with `@response` blocks for 200/201/401/403/404/422 scenarios.

**Step 2: Add routes**

Add to `app/Modules/MedicalRecord/routes.php`:

```php
use App\Modules\MedicalRecord\Http\Controllers\MedicalRecordController;

// Medical Records
Route::get('/patients/{patientId}/medical-records', [MedicalRecordController::class, 'index']);
Route::post('/medical-records', [MedicalRecordController::class, 'store']);
Route::get('/medical-records/{id}', [MedicalRecordController::class, 'show']);
Route::put('/medical-records/{id}', [MedicalRecordController::class, 'update']);
Route::post('/medical-records/{id}/finalize', [MedicalRecordController::class, 'finalize']);
Route::delete('/medical-records/{id}', [MedicalRecordController::class, 'destroy']);
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Http/Controllers/MedicalRecordController.php app/Modules/MedicalRecord/routes.php
git commit -m "feat(medical-record): add MedicalRecordController with CRUD and finalize endpoints"
```

---

## Task 13: Feature Tests — Create medical record

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/CreateMedicalRecordTest.php`

**Test scenarios (Pest):**

```php
it('creates a medical record with anthropometry data')
it('creates a medical record with physical exam JSONB')
it('creates a medical record with problem list JSONB')
it('creates a medical record with conduct JSONB')
it('creates a pre-anesthetic record with risk scores')
it('requires risk_scores when type is pre_anesthetic')
it('creates a follow-up record based on a previous record')
it('rejects creation for a patient that belongs to another doctor')
it('rejects creation with invalid type')
it('returns 401 for unauthenticated user')
```

**Step 1: Write tests**

Each test creates a doctor + patient via factories, sends a POST with the full payload matching the frontend types, and asserts the response shape and DB state.

**Step 2: Run tests**

```bash
php artisan test app/Modules/MedicalRecord/Tests/Feature/CreateMedicalRecordTest.php --compact
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/CreateMedicalRecordTest.php
git commit -m "test(medical-record): add feature tests for creating medical records"
```

---

## Task 14: Feature Tests — Update, finalize, delete, list, show

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/UpdateMedicalRecordTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/FinalizeMedicalRecordTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/DeleteMedicalRecordTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ListMedicalRecordTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ShowMedicalRecordTest.php`

**Test scenarios:**

**UpdateMedicalRecordTest:**
```
it('updates physical exam JSONB on a draft record')
it('updates anthropometry data')
it('rejects update on a finalized record')
it('rejects update by another doctor')
it('returns 401 for unauthenticated user')
```

**FinalizeMedicalRecordTest:**
```
it('finalizes a draft record')
it('rejects finalizing an already finalized record')
it('rejects finalization by another doctor')
it('prevents any update after finalization via Eloquent')
it('returns 401 for unauthenticated user')
```

**DeleteMedicalRecordTest:**
```
it('deletes a draft record')
it('rejects deletion of a finalized record')
it('rejects deletion by another doctor')
it('returns 401 for unauthenticated user')
```

**ListMedicalRecordTest:**
```
it('lists medical records for a patient')
it('filters by status')
it('paginates results')
it('does not list records from another doctor')
it('returns 401 for unauthenticated user')
```

**ShowMedicalRecordTest:**
```
it('shows a medical record with all sections')
it('returns anthropometry in nested format')
it('returns 404 for another doctor record')
it('returns 401 for unauthenticated user')
```

**Step 1: Write all test files**

**Step 2: Run all tests**

```bash
php artisan test app/Modules/MedicalRecord/Tests/Feature/ --compact
```

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Tests/Feature/
git commit -m "test(medical-record): add feature tests for update, finalize, delete, list, and show"
```

---

## Task 15: Scribe documentation + format + final verification

**Files:**
- Modify: `app/Modules/MedicalRecord/Http/Controllers/MedicalRecordController.php` (add full Scribe annotations)

**Step 1: Add complete Scribe PHPDoc annotations**

Every method in `MedicalRecordController` must have:
- `@authenticated`
- `@group Medical Records`
- `@urlParam` / `@bodyParam` / `@queryParam` as applicable
- `@response 200/201/401/403/404/422` with realistic example payloads

**Step 2: Regenerate docs**

```bash
php artisan scribe:generate
```

**Step 3: Run Pint**

```bash
vendor/bin/pint --dirty
```

**Step 4: Run full test suite**

```bash
php artisan test --compact
```

Expected: All tests pass.

**Step 5: Commit**

```bash
git add -A
git commit -m "docs(medical-record): add Scribe documentation for medical record endpoints"
```

---

## Summary

| Task | Description | Files |
|------|-------------|-------|
| 1 | Migration: JSONB columns + immutability trigger | 2 migrations |
| 2 | Migration: medidas_antropometricas table | 1 migration |
| 3 | JSONB DTOs (PhysicalExam, ProblemList, RiskScores, Conduct) | 4 DTOs |
| 4 | Custom Eloquent casts for JSONB fields | 4 casts |
| 5 | MedidaAntropometrica model + update Prontuario | 2 models |
| 6 | AnthropometryFactory + update MedicalRecordFactory | 2 factories |
| 7 | CreateMedicalRecordDTO + UpdateMedicalRecordDTO | 2 DTOs |
| 8 | StoreMedicalRecordRequest + UpdateMedicalRecordRequest | 2+ requests |
| 9 | MedicalRecordService | 1 service |
| 10 | MedicalRecordResource + AnthropometryResource | 2 resources |
| 11 | Update MedicalRecordPolicy | 1 policy |
| 12 | MedicalRecordController + routes | 1 controller + routes |
| 13 | Tests: create medical record | 1 test file |
| 14 | Tests: update, finalize, delete, list, show | 5 test files |
| 15 | Scribe docs + pint + final verification | docs |

**Total: ~25 new files, 3 modified files, 6 endpoints, ~25 test scenarios**
