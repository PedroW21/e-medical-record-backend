# Structured Exam Results Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement CRUD for 14 structured exam result types, each stored in its own normalized table, nested under medical records.

**Architecture:** A generic `ExamResultController` + `ExamResultService` + `ExamType` enum registry pattern. Each exam type gets its own migration, model, factory, and resource, but shares controller/service/policy logic via the enum registry. This avoids 14×4=56 near-identical controller methods. MRPA has a parent-child table relationship handled with custom logic in the service.

**Tech Stack:** Laravel 12, PHP 8.5, PostgreSQL, Pest 4, Sanctum

---

## Exam Types Overview

| Slug (route param) | Model | Table | Complexity |
|---|---|---|---|
| `ecg` | `ResultadoEcg` | `resultados_ecg` | Simple (3 cols) |
| `xray` | `ResultadoRx` | `resultados_rx` | Simple (3 cols) |
| `free-text` | `ResultadoTextoLivre` | `resultados_texto_livre` | Simple (3 cols) |
| `temperature` | `RegistroTemperatura` | `registros_temperatura` | Simple (3 cols) |
| `hepatic-elastography` | `ResultadoElastografiaHepatica` | `resultados_elastografia_hepatica` | Simple (4 cols) |
| `mapa` | `ResultadoMapa` | `resultados_mapa` | Medium (13 cols) |
| `dexa` | `ResultadoDexa` | `resultados_dexa` | Medium (14 cols) |
| `ergometric-test` | `ResultadoTesteErgometrico` | `resultados_teste_ergometrico` | Medium (15 cols) |
| `carotid-ecodoppler` | `ResultadoEcodopplerCarotidas` | `resultados_ecodoppler_carotidas` | Medium (17 cols) |
| `echo` | `ResultadoEcocardiograma` | `resultados_ecocardiograma` | Complex (28 cols + 3 JSONB) |
| `mrpa` | `ResultadoMrpa` | `resultados_mrpa` + `medicoes_mrpa` | Complex (parent-child) |
| `cat` | `ResultadoCat` | `resultados_cat` | Complex (9 JSONB + stents) |
| `scintigraphy` | `ResultadoCintilografia` | `resultados_cintilografia` | Complex (35+ cols + JSONB) |
| `diabetic-foot` | `ResultadoPeDiabetico` | `resultados_pe_diabetico` | Complex (scores + JSONB sections) |

All tables share: `id` (bigint PK), `prontuario_id` (FK), `paciente_id` (FK, denormalized), `data` (date), `created_at`, `updated_at`.

---

## Task 1: ExamType Enum

**Files:**
- Create: `app/Modules/MedicalRecord/Enums/ExamType.php`

**Step 1: Create the enum**

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

use App\Modules\MedicalRecord\Models\RegistroTemperatura;
use App\Modules\MedicalRecord\Models\ResultadoCat;
use App\Modules\MedicalRecord\Models\ResultadoCintilografia;
use App\Modules\MedicalRecord\Models\ResultadoDexa;
use App\Modules\MedicalRecord\Models\ResultadoEcg;
use App\Modules\MedicalRecord\Models\ResultadoEcocardiograma;
use App\Modules\MedicalRecord\Models\ResultadoEcodopplerCarotidas;
use App\Modules\MedicalRecord\Models\ResultadoElastografiaHepatica;
use App\Modules\MedicalRecord\Models\ResultadoMapa;
use App\Modules\MedicalRecord\Models\ResultadoMrpa;
use App\Modules\MedicalRecord\Models\ResultadoPeDiabetico;
use App\Modules\MedicalRecord\Models\ResultadoRx;
use App\Modules\MedicalRecord\Models\ResultadoTesteErgometrico;
use App\Modules\MedicalRecord\Models\ResultadoTextoLivre;

enum ExamType: string
{
    case Ecg = 'ecg';
    case Xray = 'xray';
    case FreeText = 'free-text';
    case Temperature = 'temperature';
    case HepaticElastography = 'hepatic-elastography';
    case Mapa = 'mapa';
    case Dexa = 'dexa';
    case ErgometricTest = 'ergometric-test';
    case CarotidEcodoppler = 'carotid-ecodoppler';
    case Echo = 'echo';
    case Mrpa = 'mrpa';
    case Cat = 'cat';
    case Scintigraphy = 'scintigraphy';
    case DiabeticFoot = 'diabetic-foot';

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Ecg => ResultadoEcg::class,
            self::Xray => ResultadoRx::class,
            self::FreeText => ResultadoTextoLivre::class,
            self::Temperature => RegistroTemperatura::class,
            self::HepaticElastography => ResultadoElastografiaHepatica::class,
            self::Mapa => ResultadoMapa::class,
            self::Dexa => ResultadoDexa::class,
            self::ErgometricTest => ResultadoTesteErgometrico::class,
            self::CarotidEcodoppler => ResultadoEcodopplerCarotidas::class,
            self::Echo => ResultadoEcocardiograma::class,
            self::Mrpa => ResultadoMrpa::class,
            self::Cat => ResultadoCat::class,
            self::Scintigraphy => ResultadoCintilografia::class,
            self::DiabeticFoot => ResultadoPeDiabetico::class,
        };
    }

    /**
     * @return class-string<\Illuminate\Http\Resources\Json\JsonResource>
     */
    public function resourceClass(): string
    {
        return match ($this) {
            self::Ecg => \App\Modules\MedicalRecord\Http\Resources\EcgResultResource::class,
            self::Xray => \App\Modules\MedicalRecord\Http\Resources\XrayResultResource::class,
            self::FreeText => \App\Modules\MedicalRecord\Http\Resources\FreeTextResultResource::class,
            self::Temperature => \App\Modules\MedicalRecord\Http\Resources\TemperatureRecordResource::class,
            self::HepaticElastography => \App\Modules\MedicalRecord\Http\Resources\HepaticElastographyResultResource::class,
            self::Mapa => \App\Modules\MedicalRecord\Http\Resources\MapaResultResource::class,
            self::Dexa => \App\Modules\MedicalRecord\Http\Resources\DexaResultResource::class,
            self::ErgometricTest => \App\Modules\MedicalRecord\Http\Resources\ErgometricTestResultResource::class,
            self::CarotidEcodoppler => \App\Modules\MedicalRecord\Http\Resources\CarotidEcodopplerResultResource::class,
            self::Echo => \App\Modules\MedicalRecord\Http\Resources\EchoResultResource::class,
            self::Mrpa => \App\Modules\MedicalRecord\Http\Resources\MrpaResultResource::class,
            self::Cat => \App\Modules\MedicalRecord\Http\Resources\CatResultResource::class,
            self::Scintigraphy => \App\Modules\MedicalRecord\Http\Resources\ScintigraphyResultResource::class,
            self::DiabeticFoot => \App\Modules\MedicalRecord\Http\Resources\DiabeticFootResultResource::class,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Ecg => 'Eletrocardiograma',
            self::Xray => 'Radiografia',
            self::FreeText => 'Resultado de texto livre',
            self::Temperature => 'Registro de temperatura',
            self::HepaticElastography => 'Elastografia hepática',
            self::Mapa => 'MAPA',
            self::Dexa => 'Densitometria (DEXA)',
            self::ErgometricTest => 'Teste ergométrico',
            self::CarotidEcodoppler => 'Ecodoppler de carótidas e vertebrais',
            self::Echo => 'Ecocardiograma',
            self::Mrpa => 'MRPA',
            self::Cat => 'Cateterismo',
            self::Scintigraphy => 'Cintilografia de perfusão miocárdica',
            self::DiabeticFoot => 'Screening de pé diabético',
        };
    }

    public function deletedMessage(): string
    {
        return match ($this) {
            self::Temperature => 'Registro de temperatura excluído com sucesso.',
            default => 'Resultado de exame excluído com sucesso.',
        };
    }
}
```

**Step 2: Commit**

```bash
git add app/Modules/MedicalRecord/Enums/ExamType.php
git commit -m "feat(medical-record): add ExamType enum with model/resource registry"
```

---

## Task 2: Migrations — Simple Exams

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_17_000001_create_resultados_ecg_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_17_000002_create_resultados_rx_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_17_000003_create_resultados_texto_livre_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_17_000004_create_registros_temperatura_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_17_000005_create_resultados_elastografia_hepatica_table.php`

**Step 1: Create migrations using artisan**

Run: `php artisan make:migration create_resultados_ecg_table --path=app/Modules/MedicalRecord/Database/Migrations --no-interaction`

Repeat for each table. Then fill in the columns:

### resultados_ecg
```php
Schema::create('resultados_ecg', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');
    $table->string('padrao'); // normal|right_deviation|left_deviation|altered
    $table->text('texto_personalizado')->nullable();
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

### resultados_rx
```php
Schema::create('resultados_rx', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');
    $table->string('padrao'); // normal|poor_quality|altered
    $table->text('texto_personalizado')->nullable();
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

### resultados_texto_livre
```php
Schema::create('resultados_texto_livre', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');
    $table->string('tipo'); // holter|polysomnography|other
    $table->text('texto');
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

### registros_temperatura
```php
Schema::create('registros_temperatura', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');
    $table->time('hora');
    $table->decimal('valor', 4, 1); // °C, e.g. 36.5
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

### resultados_elastografia_hepatica
```php
Schema::create('resultados_elastografia_hepatica', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');
    $table->decimal('fracao_gordura', 8, 2)->nullable();
    $table->decimal('tsi', 8, 2)->nullable();
    $table->decimal('kpa', 8, 2)->nullable();
    $table->text('observacoes')->nullable();
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

**Step 2: Run migration**

Run: `php artisan migrate`

**Step 3: Commit**

```bash
git add app/Modules/MedicalRecord/Database/Migrations/2026_03_17_00000{1,2,3,4,5}_*.php
git commit -m "feat(medical-record): add migrations for simple exam result tables"
```

---

## Task 3: Migrations — Medium Exams

**Files:**
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_17_000006_create_resultados_mapa_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_17_000007_create_resultados_dexa_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_17_000008_create_resultados_teste_ergometrico_table.php`
- Create: `app/Modules/MedicalRecord/Database/Migrations/2026_03_17_000009_create_resultados_ecodoppler_carotidas_table.php`

### resultados_mapa
```php
Schema::create('resultados_mapa', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');
    $table->decimal('pas_vigilia', 8, 2)->nullable();
    $table->decimal('pad_vigilia', 8, 2)->nullable();
    $table->decimal('pas_sono', 8, 2)->nullable();
    $table->decimal('pad_sono', 8, 2)->nullable();
    $table->decimal('pas_24h', 8, 2)->nullable();
    $table->decimal('pad_24h', 8, 2)->nullable();
    $table->boolean('pas_24h_override')->default(false);
    $table->boolean('pad_24h_override')->default(false);
    $table->decimal('descenso_noturno_pas', 8, 2)->nullable();
    $table->boolean('descenso_noturno_pas_override')->default(false);
    $table->decimal('descenso_noturno_pad', 8, 2)->nullable();
    $table->boolean('descenso_noturno_pad_override')->default(false);
    $table->text('observacoes')->nullable();
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

### resultados_dexa
```php
Schema::create('resultados_dexa', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');
    $table->decimal('peso_total', 8, 2)->nullable(); // kg
    $table->decimal('dmo', 8, 4)->nullable(); // g/cm²
    $table->decimal('t_score', 8, 2)->nullable();
    $table->decimal('gordura_corporal_pct', 8, 2)->nullable(); // %
    $table->decimal('gordura_total', 12, 2)->nullable(); // g
    $table->decimal('imc', 8, 2)->nullable(); // kg/m²
    $table->decimal('gordura_visceral', 12, 2)->nullable(); // g
    $table->decimal('gordura_visceral_pct', 8, 2)->nullable(); // %
    $table->decimal('massa_magra', 12, 2)->nullable(); // g
    $table->decimal('massa_magra_pct', 8, 2)->nullable(); // %
    $table->decimal('fmi', 8, 2)->nullable(); // kg/m²
    $table->decimal('ffmi', 8, 2)->nullable(); // kg/m²
    $table->decimal('rsmi', 8, 2)->nullable(); // kg/m²
    $table->decimal('tmr', 10, 2)->nullable(); // kcal/dia
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

### resultados_teste_ergometrico
```php
Schema::create('resultados_teste_ergometrico', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');
    $table->string('protocolo')->nullable(); // bruce|bruce_modified|ellestad|naughton|balke|ramp
    $table->decimal('pct_fc_max_prevista', 8, 2)->nullable(); // %
    $table->integer('fc_max')->nullable(); // bpm
    $table->integer('pas_max')->nullable(); // mmHg
    $table->integer('pas_pre')->nullable(); // mmHg
    $table->decimal('vo2_max', 8, 2)->nullable(); // ml/kg/min
    $table->decimal('mvo2_max', 8, 2)->nullable(); // ml O2/100g VE/min
    $table->decimal('deficit_cronotropico', 8, 2)->nullable(); // %
    $table->decimal('deficit_funcional_ve', 8, 2)->nullable(); // %
    $table->decimal('debito_cardiaco', 8, 2)->nullable(); // L/min
    $table->decimal('volume_sistolico', 8, 2)->nullable(); // mL/sist
    $table->integer('dp_max')->nullable(); // bpm·mmHg
    $table->decimal('met_max', 8, 2)->nullable();
    $table->string('aptidao_cardiorrespiratoria')->nullable(); // low|moderate|excellent
    $table->text('observacoes')->nullable();
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

### resultados_ecodoppler_carotidas

8 arteries × 2 measurements = 16 numeric columns + observacoes.

```php
Schema::create('resultados_ecodoppler_carotidas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');

    // Carótida comum esquerda/direita
    $table->decimal('espessura_intimal_carotida_comum_e', 8, 2)->nullable();
    $table->decimal('grau_estenose_carotida_comum_e', 8, 2)->nullable();
    $table->decimal('espessura_intimal_carotida_comum_d', 8, 2)->nullable();
    $table->decimal('grau_estenose_carotida_comum_d', 8, 2)->nullable();

    // Carótida externa esquerda/direita
    $table->decimal('espessura_intimal_carotida_externa_e', 8, 2)->nullable();
    $table->decimal('grau_estenose_carotida_externa_e', 8, 2)->nullable();
    $table->decimal('espessura_intimal_carotida_externa_d', 8, 2)->nullable();
    $table->decimal('grau_estenose_carotida_externa_d', 8, 2)->nullable();

    // Bulbo/interna esquerda/direita
    $table->decimal('espessura_intimal_bulbo_interna_e', 8, 2)->nullable();
    $table->decimal('grau_estenose_bulbo_interna_e', 8, 2)->nullable();
    $table->decimal('espessura_intimal_bulbo_interna_d', 8, 2)->nullable();
    $table->decimal('grau_estenose_bulbo_interna_d', 8, 2)->nullable();

    // Vertebral esquerda/direita
    $table->decimal('espessura_intimal_vertebral_e', 8, 2)->nullable();
    $table->decimal('grau_estenose_vertebral_e', 8, 2)->nullable();
    $table->decimal('espessura_intimal_vertebral_d', 8, 2)->nullable();
    $table->decimal('grau_estenose_vertebral_d', 8, 2)->nullable();

    $table->text('observacoes')->nullable();
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

**Step 2: Run migration, commit**

```bash
php artisan migrate
git add app/Modules/MedicalRecord/Database/Migrations/2026_03_17_00000{6,7,8,9}_*.php
git commit -m "feat(medical-record): add migrations for medium exam result tables"
```

---

## Task 4: Migrations — Complex Exams

**Files:**
- Create: `2026_03_17_000010_create_resultados_ecocardiograma_table.php`
- Create: `2026_03_17_000011_create_resultados_mrpa_table.php` (+ `medicoes_mrpa`)
- Create: `2026_03_17_000012_create_resultados_cat_table.php`
- Create: `2026_03_17_000013_create_resultados_cintilografia_table.php`
- Create: `2026_03_17_000014_create_resultados_pe_diabetico_table.php`

### resultados_ecocardiograma
```php
Schema::create('resultados_ecocardiograma', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');
    $table->string('tipo'); // transthoracic|transesophageal
    $table->decimal('raiz_aorta', 8, 2)->nullable(); // mm
    $table->decimal('aorta_ascendente', 8, 2)->nullable(); // mm
    $table->decimal('arco_aortico', 8, 2)->nullable(); // mm
    $table->decimal('ae_mm', 8, 2)->nullable(); // mm
    $table->decimal('ae_ml', 8, 2)->nullable(); // ml
    $table->decimal('ae_indexado', 8, 2)->nullable(); // mL/m²
    $table->decimal('septo', 8, 2)->nullable(); // mm
    $table->decimal('dvd', 8, 2)->nullable(); // mm
    $table->decimal('ddve', 8, 2)->nullable(); // mm
    $table->decimal('dsve', 8, 2)->nullable(); // mm
    $table->decimal('pp', 8, 2)->nullable(); // mm
    $table->decimal('erp', 8, 4)->nullable();
    $table->decimal('indice_massa_ve', 8, 2)->nullable(); // g/m²
    $table->decimal('fe', 8, 2)->nullable(); // %
    $table->decimal('psap', 8, 2)->nullable(); // mmHg
    $table->decimal('tapse', 8, 2)->nullable(); // mm
    $table->decimal('onda_e_mitral', 8, 2)->nullable(); // cm/s
    $table->decimal('onda_a', 8, 2)->nullable(); // cm/s
    $table->decimal('relacao_e_a', 8, 4)->nullable();
    $table->boolean('relacao_e_a_override')->default(false);
    $table->decimal('e_septal', 8, 2)->nullable(); // cm/s
    $table->decimal('e_lateral', 8, 2)->nullable(); // cm/s
    $table->decimal('relacao_e_e', 8, 4)->nullable();
    $table->decimal('s_tricuspide', 8, 2)->nullable(); // cm/s
    $table->jsonb('valva_aortica')->nullable(); // {status, description}
    $table->jsonb('valva_mitral')->nullable();
    $table->jsonb('valva_tricuspide')->nullable();
    $table->text('analise_qualitativa')->nullable();
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

### resultados_mrpa + medicoes_mrpa
```php
// Parent table
Schema::create('resultados_mrpa', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');
    $table->integer('dias_monitorados');
    $table->string('membro'); // right_arm|left_arm
    $table->text('observacoes')->nullable();
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});

// Child table (time-series measurements)
Schema::create('medicoes_mrpa', function (Blueprint $table) {
    $table->id();
    $table->foreignId('resultado_mrpa_id')->constrained('resultados_mrpa')->cascadeOnDelete();
    $table->date('data');
    $table->time('hora');
    $table->string('periodo'); // morning|evening
    $table->integer('pas'); // mmHg
    $table->integer('pad'); // mmHg
    $table->timestamps();

    $table->index(['resultado_mrpa_id', 'data']);
});
```

### resultados_cat
```php
Schema::create('resultados_cat', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');
    // Each artery is JSONB: {status, proximal: {has_obstruction, percentage}, media: {...}, distal: {...}}
    $table->jsonb('cd')->nullable(); // Coronária Direita
    $table->jsonb('ce')->nullable(); // Coronária Esquerda
    $table->jsonb('da')->nullable(); // Descendente Anterior
    $table->jsonb('cx')->nullable(); // Circunflexa
    $table->jsonb('d1')->nullable(); // Primeira Diagonal
    $table->jsonb('d2')->nullable(); // Segunda Diagonal
    $table->jsonb('mge')->nullable(); // Ramo Marginal Esquerdo
    $table->jsonb('mgd')->nullable(); // Ramo Marginal Direito
    $table->jsonb('dp')->nullable(); // Descendente Posterior
    $table->jsonb('stents')->nullable(); // [{artery, status}]
    $table->text('observacoes')->nullable();
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

### resultados_cintilografia
```php
Schema::create('resultados_cintilografia', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');

    // General
    $table->string('protocolo')->nullable(); // one_day_stress_rest|one_day_rest_stress|two_day

    // Stress parameters
    $table->string('modalidade_estresse')->nullable(); // physical|pharmacological|combined
    $table->integer('fc_max')->nullable(); // bpm
    $table->decimal('pct_fc_max_prevista', 8, 2)->nullable(); // %
    $table->integer('pa_max')->nullable(); // mmHg
    $table->jsonb('sintomas_estresse')->nullable(); // array of strings
    $table->jsonb('alteracoes_ecg_estresse')->nullable(); // array of strings

    // Perfusion by territory (DA, CX, CD) — 3 fields each × 3 territories = 9
    $table->string('perfusao_da_estresse')->nullable(); // PerfusionGrade
    $table->string('perfusao_da_repouso')->nullable();
    $table->string('perfusao_da_reversibilidade')->nullable(); // ReversibilityType
    $table->string('perfusao_cx_estresse')->nullable();
    $table->string('perfusao_cx_repouso')->nullable();
    $table->string('perfusao_cx_reversibilidade')->nullable();
    $table->string('perfusao_cd_estresse')->nullable();
    $table->string('perfusao_cd_repouso')->nullable();
    $table->string('perfusao_cd_reversibilidade')->nullable();

    // Semi-quantitative scores
    $table->integer('sss')->nullable();
    $table->integer('srs')->nullable();
    $table->integer('sds')->nullable();
    $table->boolean('sds_override')->default(false);
    $table->string('classificacao_sds')->nullable(); // SdsClassification
    $table->boolean('classificacao_sds_override')->default(false);

    // Ventricular function — Rest
    $table->decimal('fe_repouso', 8, 2)->nullable(); // %
    $table->decimal('vdf_repouso', 8, 2)->nullable(); // ml
    $table->decimal('vsf_repouso', 8, 2)->nullable(); // ml

    // Ventricular function — Stress
    $table->decimal('fe_estresse', 8, 2)->nullable();
    $table->decimal('vdf_estresse', 8, 2)->nullable();
    $table->decimal('vsf_estresse', 8, 2)->nullable();

    // TID
    $table->boolean('tid_presente')->nullable();
    $table->decimal('razao_tid', 8, 4)->nullable();
    $table->boolean('tid_override')->default(false);

    // 17-segment bull's eye (JSONB — consumed as a visual block)
    $table->jsonb('segmentos')->nullable(); // Record<number, {wall_motion, wall_thickening}>

    // Findings
    $table->boolean('captacao_pulmonar_aumentada')->nullable();
    $table->boolean('dilatacao_vd')->nullable();
    $table->text('captacao_extracardiaca')->nullable();
    $table->string('resultado_global')->nullable(); // normal|ischemia|fibrosis|mixed
    $table->string('extensao_defeito')->nullable(); // small|moderate|large
    $table->text('observacoes')->nullable();
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

### resultados_pe_diabetico

Hybrid approach: real columns for numeric scores (chartable), JSONB for detailed assessment sections (consumed as blocks).

```php
Schema::create('resultados_pe_diabetico', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
    $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
    $table->date('data');

    // Sections as JSONB (consumed as blocks by frontend)
    $table->jsonb('anamnese')->nullable(); // DiabeticFootAnamnesis
    $table->jsonb('sintomas_neuropaticos')->nullable(); // NeuropathicSymptoms
    $table->jsonb('inspecao_visual')->nullable(); // skin, nails, ulcer fields
    $table->jsonb('deformidades')->nullable(); // hallux valgus, claw toes, etc.
    $table->jsonb('neurologico')->nullable(); // monofilament, tuning fork, VPT per side
    $table->jsonb('vascular')->nullable(); // pulses, BP, capillary refill per side
    $table->jsonb('termometria')->nullable(); // temperatures per site per side

    // Numeric scores as real columns (for evolution tracking/charting)
    $table->integer('nss_score')->nullable(); // Neuropathic Symptom Score
    $table->integer('nds_score')->nullable(); // Neuropathic Disability Score
    $table->boolean('nds_override')->default(false);
    $table->decimal('itb_direito', 8, 4)->nullable(); // Índice Tornozelo-Braquial
    $table->decimal('itb_esquerdo', 8, 4)->nullable();
    $table->boolean('itb_direito_override')->default(false);
    $table->boolean('itb_esquerdo_override')->default(false);
    $table->decimal('tbi_direito', 8, 4)->nullable(); // Toe Brachial Index
    $table->decimal('tbi_esquerdo', 8, 4)->nullable();
    $table->boolean('tbi_direito_override')->default(false);
    $table->boolean('tbi_esquerdo_override')->default(false);

    // IWGDF classification
    $table->string('categoria_iwgdf')->nullable(); // IwgdfCategory
    $table->boolean('categoria_iwgdf_override')->default(false);

    $table->text('observacoes')->nullable();
    $table->timestamps();

    $table->index(['prontuario_id', 'data']);
    $table->index(['paciente_id', 'data']);
});
```

**Step 2: Run migration, commit**

```bash
php artisan migrate
git add app/Modules/MedicalRecord/Database/Migrations/2026_03_17_0000{10,11,12,13,14}_*.php
git commit -m "feat(medical-record): add migrations for complex exam result tables"
```

---

## Task 5: Models — All 14

**Files:** Create 14 model files + 1 child model in `app/Modules/MedicalRecord/Models/`

All models follow this base pattern:

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Models;

use App\Modules\MedicalRecord\Database\Factories\{FactoryClass};
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class {ModelName} extends Model
{
    use HasFactory;

    protected $table = '{table_name}';

    protected $fillable = [
        'prontuario_id', 'paciente_id', 'data',
        // ... exam-specific columns
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            // ... exam-specific casts
        ];
    }

    /** @return BelongsTo<Prontuario, $this> */
    public function prontuario(): BelongsTo
    {
        return $this->belongsTo(Prontuario::class);
    }

    /** @return BelongsTo<Paciente, $this> */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    protected static function newFactory(): {FactoryClass}
    {
        return {FactoryClass}::new();
    }
}
```

### Model-specific notes:

**ResultadoEcg** — fillable: `padrao`, `texto_personalizado`

**ResultadoRx** — fillable: `padrao`, `texto_personalizado`

**ResultadoTextoLivre** — fillable: `tipo`, `texto`

**RegistroTemperatura** — fillable: `hora`, `valor`. Casts: `'valor' => 'decimal:1'`

**ResultadoElastografiaHepatica** — fillable: `fracao_gordura`, `tsi`, `kpa`, `observacoes`. Casts: decimals

**ResultadoMapa** — fillable: all BP columns + overrides + observacoes. Casts: decimals, booleans

**ResultadoDexa** — fillable: all body composition columns. Casts: decimals

**ResultadoTesteErgometrico** — fillable: protocolo + all test columns. Casts: decimals, integers

**ResultadoEcodopplerCarotidas** — fillable: 16 artery columns + observacoes. Casts: decimals

**ResultadoEcocardiograma** — fillable: tipo + all echo columns + valve JSONBs. Casts: decimals, booleans, JSONB via `'array'`

**ResultadoMrpa** — fillable: `dias_monitorados`, `membro`, `observacoes`. Has `medicoes()` HasMany relationship to `MedicaoMrpa`

**MedicaoMrpa** — Table: `medicoes_mrpa`. fillable: `resultado_mrpa_id`, `data`, `hora`, `periodo`, `pas`, `pad`. BelongsTo `ResultadoMrpa`

**ResultadoCat** — fillable: 9 artery JSONBs + `stents` + `observacoes`. Casts: all artery columns as `'array'`, `'stents' => 'array'`

**ResultadoCintilografia** — fillable: all columns. Casts: decimals, booleans, JSONBs as `'array'`

**ResultadoPeDiabetico** — fillable: JSONB sections + score columns + observacoes. Casts: JSONB sections as `'array'`, decimals, booleans

**Step: Create all models, then commit**

```bash
git add app/Modules/MedicalRecord/Models/Resultado*.php app/Modules/MedicalRecord/Models/RegistroTemperatura.php app/Modules/MedicalRecord/Models/MedicaoMrpa.php
git commit -m "feat(medical-record): add models for all structured exam result types"
```

---

## Task 6: Factories — All Exam Types

**Files:** Create in `app/Modules/MedicalRecord/Database/Factories/`

Each factory follows the same pattern. Reference implementation for ECG:

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Database\Factories;

use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoEcg;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ResultadoEcg> */
final class EcgResultFactory extends Factory
{
    protected $model = ResultadoEcg::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'prontuario_id' => Prontuario::factory(),
            'paciente_id' => fn (array $attrs) => Prontuario::find($attrs['prontuario_id'])->paciente_id,
            'data' => $this->faker->date(),
            'padrao' => $this->faker->randomElement(['normal', 'right_deviation', 'left_deviation', 'altered']),
            'texto_personalizado' => null,
        ];
    }

    public function altered(): static
    {
        return $this->state(fn () => [
            'padrao' => 'altered',
            'texto_personalizado' => $this->faker->sentence(),
        ]);
    }
}
```

Create one factory per model. All follow the same structure with:
- `prontuario_id` → `Prontuario::factory()`
- `paciente_id` derived from prontuario
- `data` → `$this->faker->date()`
- Exam-specific fields with realistic defaults
- Useful states (e.g., `altered()` for ECG, `withMeasurements()` for MRPA)

**MRPA factory special case** — needs `afterCreating()` to create child measurements:

```php
public function withMeasurements(int $count = 6): static
{
    return $this->afterCreating(function (ResultadoMrpa $mrpa) use ($count): void {
        MedicaoMrpa::factory()->count($count)->create([
            'resultado_mrpa_id' => $mrpa->id,
        ]);
    });
}
```

**Commit:**
```bash
git add app/Modules/MedicalRecord/Database/Factories/*Factory.php
git commit -m "feat(medical-record): add factories for all structured exam result types"
```

---

## Task 7: ExamResultService (Generic CRUD)

**Files:**
- Create: `app/Modules/MedicalRecord/Services/ExamResultService.php`

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\Enums\ExamType;
use App\Modules\MedicalRecord\Models\MedicaoMrpa;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Models\ResultadoMrpa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExamResultService
{
    /**
     * Find medical record or throw 404.
     */
    public function findMedicalRecordOrFail(int $medicalRecordId): Prontuario
    {
        $prontuario = Prontuario::query()->find($medicalRecordId);

        if (! $prontuario) {
            throw new NotFoundHttpException('Prontuário não encontrado.');
        }

        return $prontuario;
    }

    /**
     * Find an exam result by ID within a medical record, or throw 404.
     */
    public function findForMedicalRecordOrFail(ExamType $examType, int $id, int $medicalRecordId): Model
    {
        $modelClass = $examType->modelClass();
        $query = $modelClass::query()->with('prontuario');

        if ($examType === ExamType::Mrpa) {
            $query->with('medicoes');
        }

        $result = $query->find($id);

        if (! $result || $result->prontuario_id !== $medicalRecordId) {
            throw new NotFoundHttpException('Resultado de exame não encontrado.');
        }

        return $result;
    }

    /**
     * List all results of a given exam type for a medical record.
     *
     * @return Collection<int, Model>
     */
    public function listByMedicalRecord(ExamType $examType, int $medicalRecordId): Collection
    {
        $modelClass = $examType->modelClass();
        $query = $modelClass::query()
            ->where('prontuario_id', $medicalRecordId)
            ->orderByDesc('data')
            ->orderBy('id');

        if ($examType === ExamType::Mrpa) {
            $query->with('medicoes');
        }

        return $query->get();
    }

    /**
     * Store a new exam result.
     *
     * @param array<string, mixed> $data
     */
    public function store(ExamType $examType, int $medicalRecordId, array $data): Model
    {
        $prontuario = $this->findMedicalRecordOrFail($medicalRecordId);
        $this->ensureDraft($prontuario);

        $data['prontuario_id'] = $prontuario->id;
        $data['paciente_id'] = $prontuario->paciente_id;

        if ($examType === ExamType::Mrpa) {
            return $this->storeMrpa($data);
        }

        $modelClass = $examType->modelClass();

        return $modelClass::query()->create($data);
    }

    /**
     * Update an existing exam result.
     *
     * @param array<string, mixed> $data
     */
    public function update(ExamType $examType, int $id, int $medicalRecordId, array $data): Model
    {
        $result = $this->findForMedicalRecordOrFail($examType, $id, $medicalRecordId);
        $this->ensureDraft($result->prontuario);

        if ($examType === ExamType::Mrpa && isset($data['measurements'])) {
            return $this->updateMrpa($result, $data);
        }

        $result->update($data);

        return $result->fresh();
    }

    /**
     * Delete an exam result.
     */
    public function delete(ExamType $examType, int $id, int $medicalRecordId): void
    {
        $result = $this->findForMedicalRecordOrFail($examType, $id, $medicalRecordId);
        $this->ensureDraft($result->prontuario);
        $result->delete();
    }

    /**
     * MRPA-specific: store parent + child measurements in a transaction.
     */
    private function storeMrpa(array $data): ResultadoMrpa
    {
        return DB::transaction(function () use ($data): ResultadoMrpa {
            $measurements = $data['measurements'] ?? [];
            unset($data['measurements']);

            $mrpa = ResultadoMrpa::query()->create($data);

            foreach ($measurements as $measurement) {
                $mrpa->medicoes()->create($measurement);
            }

            return $mrpa->load('medicoes');
        });
    }

    /**
     * MRPA-specific: update parent + replace child measurements.
     */
    private function updateMrpa(Model $mrpa, array $data): ResultadoMrpa
    {
        return DB::transaction(function () use ($mrpa, $data): ResultadoMrpa {
            $measurements = $data['measurements'];
            unset($data['measurements']);

            $mrpa->update($data);
            $mrpa->medicoes()->delete();

            foreach ($measurements as $measurement) {
                $mrpa->medicoes()->create($measurement);
            }

            return $mrpa->fresh()->load('medicoes');
        });
    }

    private function ensureDraft(Prontuario $prontuario): void
    {
        if (! $prontuario->isDraft()) {
            throw new ConflictHttpException('Não é possível modificar resultados de exame de um prontuário finalizado.');
        }
    }
}
```

**Commit:**
```bash
git add app/Modules/MedicalRecord/Services/ExamResultService.php
git commit -m "feat(medical-record): add generic ExamResultService for structured exams"
```

---

## Task 8: ExamResultPolicy + ServiceProvider Update

**Files:**
- Create: `app/Modules/MedicalRecord/Policies/ExamResultPolicy.php`
- Modify: `app/Modules/MedicalRecord/Providers/MedicalRecordServiceProvider.php`

### ExamResultPolicy

A single policy that works for all exam result models. Uses `Model` type hint since all exam models share the same `prontuario` relationship.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Policies;

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;
use Illuminate\Database\Eloquent\Model;

final class ExamResultPolicy
{
    public function viewAny(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    public function create(User $user, Prontuario $prontuario): bool
    {
        return $user->id === $prontuario->user_id;
    }

    public function update(User $user, Model $examResult): bool
    {
        return $user->id === $examResult->prontuario->user_id;
    }

    public function delete(User $user, Model $examResult): bool
    {
        return $user->id === $examResult->prontuario->user_id;
    }
}
```

### ServiceProvider update

Register the policy for ALL exam result model classes in `boot()`:

```php
// In boot(), after existing policy registrations:
$examResultModels = array_map(
    fn (ExamType $type) => $type->modelClass(),
    ExamType::cases(),
);
foreach ($examResultModels as $modelClass) {
    Gate::policy($modelClass, ExamResultPolicy::class);
}
```

**Commit:**
```bash
git add app/Modules/MedicalRecord/Policies/ExamResultPolicy.php app/Modules/MedicalRecord/Providers/MedicalRecordServiceProvider.php
git commit -m "feat(medical-record): add ExamResultPolicy and register for all exam types"
```

---

## Task 9: Validation Rules Registry

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Requests/StoreExamResultRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/UpdateExamResultRequest.php`
- Create: `app/Modules/MedicalRecord/Http/Requests/ExamResultValidationRules.php` (trait)

The `StoreExamResultRequest` reads `examType` from the route and returns the appropriate rules.

### ExamResultValidationRules trait

This trait provides the full rules map for all 14 exam types. Store rules use `required` where needed; Update rules use `sometimes`.

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\ExamType;

trait ExamResultValidationRules
{
    /**
     * Get validation rules for a specific exam type (store mode).
     *
     * @return array<string, array<int, mixed>>
     */
    protected function storeRulesFor(ExamType $examType): array
    {
        $base = ['date' => ['required', 'date', 'before_or_equal:today']];

        return array_merge($base, match ($examType) {
            ExamType::Ecg => [
                'pattern' => ['required', 'string', 'in:normal,right_deviation,left_deviation,altered'],
                'custom_text' => ['nullable', 'string', 'max:5000'],
            ],
            ExamType::Xray => [
                'pattern' => ['required', 'string', 'in:normal,poor_quality,altered'],
                'custom_text' => ['nullable', 'string', 'max:5000'],
            ],
            ExamType::FreeText => [
                'type' => ['required', 'string', 'in:holter,polysomnography,other'],
                'text' => ['required', 'string', 'max:10000'],
            ],
            ExamType::Temperature => [
                'time' => ['required', 'date_format:H:i'],
                'value' => ['required', 'numeric', 'min:30', 'max:45'],
            ],
            ExamType::HepaticElastography => [
                'fat_fraction' => ['nullable', 'numeric', 'min:0'],
                'tsi' => ['nullable', 'numeric', 'min:0'],
                'kpa' => ['nullable', 'numeric', 'min:0'],
                'observations' => ['nullable', 'string', 'max:5000'],
            ],
            ExamType::Mapa => [
                'systolic_awake' => ['nullable', 'numeric', 'min:0', 'max:300'],
                'diastolic_awake' => ['nullable', 'numeric', 'min:0', 'max:200'],
                'systolic_sleep' => ['nullable', 'numeric', 'min:0', 'max:300'],
                'diastolic_sleep' => ['nullable', 'numeric', 'min:0', 'max:200'],
                'systolic_24h' => ['nullable', 'numeric', 'min:0', 'max:300'],
                'diastolic_24h' => ['nullable', 'numeric', 'min:0', 'max:200'],
                'systolic_24h_override' => ['nullable', 'boolean'],
                'diastolic_24h_override' => ['nullable', 'boolean'],
                'nocturnal_dipping_systolic' => ['nullable', 'numeric'],
                'nocturnal_dipping_systolic_override' => ['nullable', 'boolean'],
                'nocturnal_dipping_diastolic' => ['nullable', 'numeric'],
                'nocturnal_dipping_diastolic_override' => ['nullable', 'boolean'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ],
            ExamType::Dexa => [
                'total_weight' => ['nullable', 'numeric', 'min:0'],
                'bmd' => ['nullable', 'numeric'],
                't_score' => ['nullable', 'numeric'],
                'body_fat_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'total_fat' => ['nullable', 'numeric', 'min:0'],
                'bmi' => ['nullable', 'numeric', 'min:0'],
                'visceral_fat' => ['nullable', 'numeric', 'min:0'],
                'visceral_fat_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'lean_mass' => ['nullable', 'numeric', 'min:0'],
                'lean_mass_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'fmi' => ['nullable', 'numeric', 'min:0'],
                'ffmi' => ['nullable', 'numeric', 'min:0'],
                'rsmi' => ['nullable', 'numeric', 'min:0'],
                'rmr' => ['nullable', 'numeric', 'min:0'],
            ],
            ExamType::ErgometricTest => [
                'protocol' => ['nullable', 'string', 'in:bruce,bruce_modified,ellestad,naughton,balke,ramp'],
                'hr_max_predicted_pct' => ['nullable', 'numeric', 'min:0', 'max:200'],
                'hr_max' => ['nullable', 'integer', 'min:0', 'max:300'],
                'bp_systolic_max' => ['nullable', 'integer', 'min:0', 'max:400'],
                'bp_systolic_pre' => ['nullable', 'integer', 'min:0', 'max:400'],
                'vo2_max' => ['nullable', 'numeric', 'min:0'],
                'mvo2_max' => ['nullable', 'numeric', 'min:0'],
                'chronotropic_deficit' => ['nullable', 'numeric'],
                'lv_functional_deficit' => ['nullable', 'numeric'],
                'cardiac_output' => ['nullable', 'numeric', 'min:0'],
                'stroke_volume' => ['nullable', 'numeric', 'min:0'],
                'dp_max' => ['nullable', 'integer', 'min:0'],
                'met_max' => ['nullable', 'numeric', 'min:0'],
                'cardio_respiratory_fitness' => ['nullable', 'string', 'in:low,moderate,excellent'],
                'observations' => ['nullable', 'string', 'max:5000'],
            ],
            ExamType::CarotidEcodoppler => [
                'common_carotid_left.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'common_carotid_left.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'common_carotid_right.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'common_carotid_right.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'external_carotid_left.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'external_carotid_left.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'external_carotid_right.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'external_carotid_right.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'bulb_internal_left.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'bulb_internal_left.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'bulb_internal_right.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'bulb_internal_right.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'vertebral_left.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'vertebral_left.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'vertebral_right.intimal_thickness' => ['nullable', 'numeric', 'min:0'],
                'vertebral_right.stenosis_degree' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'observations' => ['nullable', 'string', 'max:5000'],
            ],
            ExamType::Echo => [
                'type' => ['required', 'string', 'in:transthoracic,transesophageal'],
                'aorta_root' => ['nullable', 'numeric', 'min:0'],
                'aorta_ascending' => ['nullable', 'numeric', 'min:0'],
                'aortic_arch' => ['nullable', 'numeric', 'min:0'],
                'la_mm' => ['nullable', 'numeric', 'min:0'],
                'la_ml' => ['nullable', 'numeric', 'min:0'],
                'la_indexed' => ['nullable', 'numeric', 'min:0'],
                'septum' => ['nullable', 'numeric', 'min:0'],
                'rvd' => ['nullable', 'numeric', 'min:0'],
                'lvedd' => ['nullable', 'numeric', 'min:0'],
                'lvesd' => ['nullable', 'numeric', 'min:0'],
                'pw' => ['nullable', 'numeric', 'min:0'],
                'rwt' => ['nullable', 'numeric', 'min:0'],
                'lv_mass_index' => ['nullable', 'numeric', 'min:0'],
                'ef' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'pasp' => ['nullable', 'numeric', 'min:0'],
                'tapse' => ['nullable', 'numeric', 'min:0'],
                'e_mitral' => ['nullable', 'numeric', 'min:0'],
                'a_wave' => ['nullable', 'numeric', 'min:0'],
                'e_a_ratio' => ['nullable', 'numeric', 'min:0'],
                'e_a_ratio_override' => ['nullable', 'boolean'],
                'e_septal' => ['nullable', 'numeric', 'min:0'],
                'e_lateral' => ['nullable', 'numeric', 'min:0'],
                'e_e_ratio' => ['nullable', 'numeric', 'min:0'],
                's_tricuspid' => ['nullable', 'numeric', 'min:0'],
                'valve_aortic' => ['nullable', 'array'],
                'valve_aortic.status' => ['nullable', 'string', 'in:regular,alterada'],
                'valve_aortic.description' => ['nullable', 'string', 'max:1000'],
                'valve_mitral' => ['nullable', 'array'],
                'valve_mitral.status' => ['nullable', 'string', 'in:regular,alterada'],
                'valve_mitral.description' => ['nullable', 'string', 'max:1000'],
                'valve_tricuspid' => ['nullable', 'array'],
                'valve_tricuspid.status' => ['nullable', 'string', 'in:regular,alterada'],
                'valve_tricuspid.description' => ['nullable', 'string', 'max:1000'],
                'qualitative_analysis' => ['nullable', 'string', 'max:5000'],
            ],
            ExamType::Mrpa => [
                'days_monitored' => ['required', 'integer', 'min:1', 'max:30'],
                'limb' => ['required', 'string', 'in:right_arm,left_arm'],
                'observations' => ['nullable', 'string', 'max:5000'],
                'measurements' => ['required', 'array', 'min:1'],
                'measurements.*.date' => ['required', 'date'],
                'measurements.*.time' => ['required', 'date_format:H:i'],
                'measurements.*.period' => ['required', 'string', 'in:morning,evening'],
                'measurements.*.systolic' => ['required', 'integer', 'min:0', 'max:400'],
                'measurements.*.diastolic' => ['required', 'integer', 'min:0', 'max:300'],
            ],
            ExamType::Cat => [
                'cd' => ['nullable', 'array'],
                'ce' => ['nullable', 'array'],
                'da' => ['nullable', 'array'],
                'cx' => ['nullable', 'array'],
                'd1' => ['nullable', 'array'],
                'd2' => ['nullable', 'array'],
                'mge' => ['nullable', 'array'],
                'mgd' => ['nullable', 'array'],
                'dp' => ['nullable', 'array'],
                'stents' => ['nullable', 'array'],
                'stents.*.artery' => ['required', 'string', 'in:cd,ce,da,cx,d1,d2,mge,mgd,dp'],
                'stents.*.status' => ['nullable', 'string', 'in:pervio,obstruido'],
                'observations' => ['nullable', 'string', 'max:5000'],
            ],
            ExamType::Scintigraphy => [
                'protocol' => ['nullable', 'string', 'in:one_day_stress_rest,one_day_rest_stress,two_day'],
                'stress_modality' => ['nullable', 'string', 'in:physical,pharmacological,combined'],
                'hr_max' => ['nullable', 'integer', 'min:0', 'max:300'],
                'hr_max_predicted_pct' => ['nullable', 'numeric', 'min:0', 'max:200'],
                'bp_max' => ['nullable', 'integer', 'min:0', 'max:400'],
                'stress_symptoms' => ['nullable', 'array'],
                'stress_symptoms.*' => ['string', 'in:chest_pain,dyspnea,dizziness,none'],
                'stress_ecg_changes' => ['nullable', 'array'],
                'stress_ecg_changes.*' => ['string', 'in:st_depression,st_elevation,arrhythmia,none'],
                'perfusion_da.stress' => ['nullable', 'string', 'in:normal,mild_hypoperfusion,moderate_hypoperfusion,severe_hypoperfusion,absent'],
                'perfusion_da.rest' => ['nullable', 'string', 'in:normal,mild_hypoperfusion,moderate_hypoperfusion,severe_hypoperfusion,absent'],
                'perfusion_da.reversibility' => ['nullable', 'string', 'in:reversible,partially_reversible,fixed,reverse_redistribution'],
                'perfusion_cx.stress' => ['nullable', 'string', 'in:normal,mild_hypoperfusion,moderate_hypoperfusion,severe_hypoperfusion,absent'],
                'perfusion_cx.rest' => ['nullable', 'string', 'in:normal,mild_hypoperfusion,moderate_hypoperfusion,severe_hypoperfusion,absent'],
                'perfusion_cx.reversibility' => ['nullable', 'string', 'in:reversible,partially_reversible,fixed,reverse_redistribution'],
                'perfusion_cd.stress' => ['nullable', 'string', 'in:normal,mild_hypoperfusion,moderate_hypoperfusion,severe_hypoperfusion,absent'],
                'perfusion_cd.rest' => ['nullable', 'string', 'in:normal,mild_hypoperfusion,moderate_hypoperfusion,severe_hypoperfusion,absent'],
                'perfusion_cd.reversibility' => ['nullable', 'string', 'in:reversible,partially_reversible,fixed,reverse_redistribution'],
                'sss' => ['nullable', 'integer', 'min:0'],
                'srs' => ['nullable', 'integer', 'min:0'],
                'sds' => ['nullable', 'integer', 'min:0'],
                'sds_override' => ['nullable', 'boolean'],
                'sds_classification' => ['nullable', 'string', 'in:normal,mild_ischemia,moderate_ischemia,severe_ischemia'],
                'sds_classification_override' => ['nullable', 'boolean'],
                'ef_rest' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'edv_rest' => ['nullable', 'numeric', 'min:0'],
                'esv_rest' => ['nullable', 'numeric', 'min:0'],
                'ef_stress' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'edv_stress' => ['nullable', 'numeric', 'min:0'],
                'esv_stress' => ['nullable', 'numeric', 'min:0'],
                'tid_present' => ['nullable', 'boolean'],
                'tid_ratio' => ['nullable', 'numeric', 'min:0'],
                'tid_override' => ['nullable', 'boolean'],
                'segments' => ['nullable', 'array'],
                'increased_lung_uptake' => ['nullable', 'boolean'],
                'rv_dilation' => ['nullable', 'boolean'],
                'extracardiac_uptake' => ['nullable', 'string', 'max:1000'],
                'global_result' => ['nullable', 'string', 'in:normal,ischemia,fibrosis,mixed'],
                'defect_extent' => ['nullable', 'string', 'in:small,moderate,large'],
                'observations' => ['nullable', 'string', 'max:5000'],
            ],
            ExamType::DiabeticFoot => [
                'anamnesis' => ['nullable', 'array'],
                'neuropathic_symptoms' => ['nullable', 'array'],
                'visual_inspection' => ['nullable', 'array'],
                'deformities' => ['nullable', 'array'],
                'neurological' => ['nullable', 'array'],
                'vascular' => ['nullable', 'array'],
                'thermometry' => ['nullable', 'array'],
                'nss_score' => ['nullable', 'integer', 'min:0'],
                'nds_score' => ['nullable', 'integer', 'min:0'],
                'nds_override' => ['nullable', 'boolean'],
                'itb_right' => ['nullable', 'numeric', 'min:0'],
                'itb_left' => ['nullable', 'numeric', 'min:0'],
                'itb_right_override' => ['nullable', 'boolean'],
                'itb_left_override' => ['nullable', 'boolean'],
                'tbi_right' => ['nullable', 'numeric', 'min:0'],
                'tbi_left' => ['nullable', 'numeric', 'min:0'],
                'tbi_right_override' => ['nullable', 'boolean'],
                'tbi_left_override' => ['nullable', 'boolean'],
                'iwgdf_category' => ['nullable', 'string'],
                'iwgdf_category_override' => ['nullable', 'boolean'],
                'observations' => ['nullable', 'string', 'max:5000'],
            ],
        });
    }

    /**
     * Get validation messages for exam results (Portuguese).
     *
     * @return array<string, string>
     */
    protected function examMessages(): array
    {
        return [
            'date.required' => 'A data do exame é obrigatória.',
            'date.date' => 'A data do exame deve ser uma data válida.',
            'date.before_or_equal' => 'A data do exame não pode ser futura.',
            'pattern.required' => 'O padrão do exame é obrigatório.',
            'pattern.in' => 'O padrão informado é inválido.',
            'type.required' => 'O tipo do exame é obrigatório.',
            'type.in' => 'O tipo informado é inválido.',
            'text.required' => 'O texto do resultado é obrigatório.',
            'time.required' => 'O horário é obrigatório.',
            'time.date_format' => 'O horário deve estar no formato HH:mm.',
            'value.required' => 'O valor é obrigatório.',
            'value.numeric' => 'O valor deve ser numérico.',
            'value.min' => 'O valor informado está abaixo do mínimo permitido.',
            'value.max' => 'O valor informado está acima do máximo permitido.',
            'days_monitored.required' => 'O número de dias monitorados é obrigatório.',
            'limb.required' => 'O membro utilizado é obrigatório.',
            'limb.in' => 'O membro informado é inválido.',
            'measurements.required' => 'As medições são obrigatórias.',
            'measurements.min' => 'É necessário informar ao menos uma medição.',
            'measurements.*.systolic.required' => 'A pressão sistólica é obrigatória.',
            'measurements.*.diastolic.required' => 'A pressão diastólica é obrigatória.',
            'measurements.*.period.required' => 'O período é obrigatório.',
            'measurements.*.period.in' => 'O período informado é inválido.',
        ];
    }
}
```

### StoreExamResultRequest

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;

final class StoreExamResultRequest extends FormRequest
{
    use ExamResultValidationRules;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $examType = ExamType::from($this->route('examType'));

        return $this->storeRulesFor($examType);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->examMessages();
    }
}
```

### UpdateExamResultRequest

Same as Store but all rules become `sometimes` instead of `required` (except date which remains required if provided).

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateExamResultRequest extends FormRequest
{
    use ExamResultValidationRules;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $examType = ExamType::from($this->route('examType'));
        $storeRules = $this->storeRulesFor($examType);

        // Convert 'required' to 'sometimes' for updates
        $updateRules = [];
        foreach ($storeRules as $field => $rules) {
            $updateRules[$field] = array_map(
                fn ($rule) => $rule === 'required' ? 'sometimes' : $rule,
                $rules,
            );
        }

        return $updateRules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->examMessages();
    }
}
```

**Commit:**
```bash
git add app/Modules/MedicalRecord/Http/Requests/StoreExamResultRequest.php \
       app/Modules/MedicalRecord/Http/Requests/UpdateExamResultRequest.php \
       app/Modules/MedicalRecord/Http/Requests/ExamResultValidationRules.php
git commit -m "feat(medical-record): add form requests with validation rules for all exam types"
```

---

## Task 10: Field Mapping — Frontend↔DB

Each resource transforms frontend field names (English camelCase) to DB column names (Portuguese snake_case). The `ExamResultService` and `StoreExamResultRequest` also need this mapping for the store/update operations.

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Resources/ExamResultFieldMap.php` (trait)

This trait provides the bidirectional mapping (API→DB for writes, DB→API for reads) per exam type:

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use App\Modules\MedicalRecord\Enums\ExamType;

trait ExamResultFieldMap
{
    /**
     * Map from API field names to DB column names.
     *
     * @return array<string, string> API field → DB column
     */
    public static function apiToDbMap(ExamType $examType): array
    {
        return match ($examType) {
            ExamType::Ecg => [
                'pattern' => 'padrao',
                'custom_text' => 'texto_personalizado',
            ],
            ExamType::Xray => [
                'pattern' => 'padrao',
                'custom_text' => 'texto_personalizado',
            ],
            ExamType::FreeText => [
                'type' => 'tipo',
                'text' => 'texto',
            ],
            ExamType::Temperature => [
                'time' => 'hora',
                'value' => 'valor',
            ],
            ExamType::HepaticElastography => [
                'fat_fraction' => 'fracao_gordura',
                'tsi' => 'tsi',
                'kpa' => 'kpa',
                'observations' => 'observacoes',
            ],
            ExamType::Mapa => [
                'systolic_awake' => 'pas_vigilia',
                'diastolic_awake' => 'pad_vigilia',
                'systolic_sleep' => 'pas_sono',
                'diastolic_sleep' => 'pad_sono',
                'systolic_24h' => 'pas_24h',
                'diastolic_24h' => 'pad_24h',
                'systolic_24h_override' => 'pas_24h_override',
                'diastolic_24h_override' => 'pad_24h_override',
                'nocturnal_dipping_systolic' => 'descenso_noturno_pas',
                'nocturnal_dipping_systolic_override' => 'descenso_noturno_pas_override',
                'nocturnal_dipping_diastolic' => 'descenso_noturno_pad',
                'nocturnal_dipping_diastolic_override' => 'descenso_noturno_pad_override',
                'notes' => 'observacoes',
            ],
            ExamType::Dexa => [
                'total_weight' => 'peso_total',
                'bmd' => 'dmo',
                't_score' => 't_score',
                'body_fat_pct' => 'gordura_corporal_pct',
                'total_fat' => 'gordura_total',
                'bmi' => 'imc',
                'visceral_fat' => 'gordura_visceral',
                'visceral_fat_pct' => 'gordura_visceral_pct',
                'lean_mass' => 'massa_magra',
                'lean_mass_pct' => 'massa_magra_pct',
                'fmi' => 'fmi',
                'ffmi' => 'ffmi',
                'rsmi' => 'rsmi',
                'rmr' => 'tmr',
            ],
            ExamType::ErgometricTest => [
                'protocol' => 'protocolo',
                'hr_max_predicted_pct' => 'pct_fc_max_prevista',
                'hr_max' => 'fc_max',
                'bp_systolic_max' => 'pas_max',
                'bp_systolic_pre' => 'pas_pre',
                'vo2_max' => 'vo2_max',
                'mvo2_max' => 'mvo2_max',
                'chronotropic_deficit' => 'deficit_cronotropico',
                'lv_functional_deficit' => 'deficit_funcional_ve',
                'cardiac_output' => 'debito_cardiaco',
                'stroke_volume' => 'volume_sistolico',
                'dp_max' => 'dp_max',
                'met_max' => 'met_max',
                'cardio_respiratory_fitness' => 'aptidao_cardiorrespiratoria',
                'observations' => 'observacoes',
            ],
            ExamType::CarotidEcodoppler => [
                'common_carotid_left.intimal_thickness' => 'espessura_intimal_carotida_comum_e',
                'common_carotid_left.stenosis_degree' => 'grau_estenose_carotida_comum_e',
                'common_carotid_right.intimal_thickness' => 'espessura_intimal_carotida_comum_d',
                'common_carotid_right.stenosis_degree' => 'grau_estenose_carotida_comum_d',
                'external_carotid_left.intimal_thickness' => 'espessura_intimal_carotida_externa_e',
                'external_carotid_left.stenosis_degree' => 'grau_estenose_carotida_externa_e',
                'external_carotid_right.intimal_thickness' => 'espessura_intimal_carotida_externa_d',
                'external_carotid_right.stenosis_degree' => 'grau_estenose_carotida_externa_d',
                'bulb_internal_left.intimal_thickness' => 'espessura_intimal_bulbo_interna_e',
                'bulb_internal_left.stenosis_degree' => 'grau_estenose_bulbo_interna_e',
                'bulb_internal_right.intimal_thickness' => 'espessura_intimal_bulbo_interna_d',
                'bulb_internal_right.stenosis_degree' => 'grau_estenose_bulbo_interna_d',
                'vertebral_left.intimal_thickness' => 'espessura_intimal_vertebral_e',
                'vertebral_left.stenosis_degree' => 'grau_estenose_vertebral_e',
                'vertebral_right.intimal_thickness' => 'espessura_intimal_vertebral_d',
                'vertebral_right.stenosis_degree' => 'grau_estenose_vertebral_d',
                'observations' => 'observacoes',
            ],
            ExamType::Echo => [
                'type' => 'tipo',
                'aorta_root' => 'raiz_aorta',
                'aorta_ascending' => 'aorta_ascendente',
                'aortic_arch' => 'arco_aortico',
                'la_mm' => 'ae_mm',
                'la_ml' => 'ae_ml',
                'la_indexed' => 'ae_indexado',
                'septum' => 'septo',
                'rvd' => 'dvd',
                'lvedd' => 'ddve',
                'lvesd' => 'dsve',
                'pw' => 'pp',
                'rwt' => 'erp',
                'lv_mass_index' => 'indice_massa_ve',
                'ef' => 'fe',
                'pasp' => 'psap',
                'tapse' => 'tapse',
                'e_mitral' => 'onda_e_mitral',
                'a_wave' => 'onda_a',
                'e_a_ratio' => 'relacao_e_a',
                'e_a_ratio_override' => 'relacao_e_a_override',
                'e_septal' => 'e_septal',
                'e_lateral' => 'e_lateral',
                'e_e_ratio' => 'relacao_e_e',
                's_tricuspid' => 's_tricuspide',
                'valve_aortic' => 'valva_aortica',
                'valve_mitral' => 'valva_mitral',
                'valve_tricuspid' => 'valva_tricuspide',
                'qualitative_analysis' => 'analise_qualitativa',
            ],
            ExamType::Mrpa => [
                'days_monitored' => 'dias_monitorados',
                'limb' => 'membro',
                'observations' => 'observacoes',
                // 'measurements' handled separately (child table)
            ],
            ExamType::Cat => [
                'cd' => 'cd',
                'ce' => 'ce',
                'da' => 'da',
                'cx' => 'cx',
                'd1' => 'd1',
                'd2' => 'd2',
                'mge' => 'mge',
                'mgd' => 'mgd',
                'dp' => 'dp',
                'stents' => 'stents',
                'observations' => 'observacoes',
            ],
            ExamType::Scintigraphy => [
                'protocol' => 'protocolo',
                'stress_modality' => 'modalidade_estresse',
                'hr_max' => 'fc_max',
                'hr_max_predicted_pct' => 'pct_fc_max_prevista',
                'bp_max' => 'pa_max',
                'stress_symptoms' => 'sintomas_estresse',
                'stress_ecg_changes' => 'alteracoes_ecg_estresse',
                'perfusion_da.stress' => 'perfusao_da_estresse',
                'perfusion_da.rest' => 'perfusao_da_repouso',
                'perfusion_da.reversibility' => 'perfusao_da_reversibilidade',
                'perfusion_cx.stress' => 'perfusao_cx_estresse',
                'perfusion_cx.rest' => 'perfusao_cx_repouso',
                'perfusion_cx.reversibility' => 'perfusao_cx_reversibilidade',
                'perfusion_cd.stress' => 'perfusao_cd_estresse',
                'perfusion_cd.rest' => 'perfusao_cd_repouso',
                'perfusion_cd.reversibility' => 'perfusao_cd_reversibilidade',
                'sss' => 'sss',
                'srs' => 'srs',
                'sds' => 'sds',
                'sds_override' => 'sds_override',
                'sds_classification' => 'classificacao_sds',
                'sds_classification_override' => 'classificacao_sds_override',
                'ef_rest' => 'fe_repouso',
                'edv_rest' => 'vdf_repouso',
                'esv_rest' => 'vsf_repouso',
                'ef_stress' => 'fe_estresse',
                'edv_stress' => 'vdf_estresse',
                'esv_stress' => 'vsf_estresse',
                'tid_present' => 'tid_presente',
                'tid_ratio' => 'razao_tid',
                'tid_override' => 'tid_override',
                'segments' => 'segmentos',
                'increased_lung_uptake' => 'captacao_pulmonar_aumentada',
                'rv_dilation' => 'dilatacao_vd',
                'extracardiac_uptake' => 'captacao_extracardiaca',
                'global_result' => 'resultado_global',
                'defect_extent' => 'extensao_defeito',
                'observations' => 'observacoes',
            ],
            ExamType::DiabeticFoot => [
                'anamnesis' => 'anamnese',
                'neuropathic_symptoms' => 'sintomas_neuropaticos',
                'visual_inspection' => 'inspecao_visual',
                'deformities' => 'deformidades',
                'neurological' => 'neurologico',
                'vascular' => 'vascular',
                'thermometry' => 'termometria',
                'nss_score' => 'nss_score',
                'nds_score' => 'nds_score',
                'nds_override' => 'nds_override',
                'itb_right' => 'itb_direito',
                'itb_left' => 'itb_esquerdo',
                'itb_right_override' => 'itb_direito_override',
                'itb_left_override' => 'itb_esquerdo_override',
                'tbi_right' => 'tbi_direito',
                'tbi_left' => 'tbi_esquerdo',
                'tbi_right_override' => 'tbi_direito_override',
                'tbi_left_override' => 'tbi_esquerdo_override',
                'iwgdf_category' => 'categoria_iwgdf',
                'iwgdf_category_override' => 'categoria_iwgdf_override',
                'observations' => 'observacoes',
            ],
        };
    }

    /**
     * Map from DB column names to API field names.
     *
     * @return array<string, string> DB column → API field
     */
    public static function dbToApiMap(ExamType $examType): array
    {
        return array_flip(self::apiToDbMap($examType));
    }
}
```

**The ExamResultService uses this to transform API data → DB columns before store/update.**
**Each Resource uses the inverse to transform DB columns → API fields.**

Add a method to `ExamResultService`:

```php
use App\Modules\MedicalRecord\Http\Resources\ExamResultFieldMap;

final class ExamResultService
{
    use ExamResultFieldMap;

    /**
     * Transform validated API data to DB column names.
     *
     * @param array<string, mixed> $apiData
     * @return array<string, mixed>
     */
    private function mapApiToDb(ExamType $examType, array $apiData): array
    {
        $map = self::apiToDbMap($examType);
        $dbData = [];

        foreach ($apiData as $apiField => $value) {
            // Handle dot-notation fields (e.g., common_carotid_left.intimal_thickness)
            if (str_contains($apiField, '.')) {
                $dbColumn = $map[$apiField] ?? null;
                if ($dbColumn) {
                    $dbData[$dbColumn] = $value;
                }
                continue;
            }

            $dbColumn = $map[$apiField] ?? $apiField;
            $dbData[$dbColumn] = $value;
        }

        // Handle 'date' → 'data' (common to all types)
        if (isset($dbData['date'])) {
            $dbData['data'] = $dbData['date'];
            unset($dbData['date']);
        }

        return $dbData;
    }
    // ...
}
```

**Update the `store()` and `update()` methods to use `mapApiToDb()` before creating/updating.**

**Commit:**
```bash
git add app/Modules/MedicalRecord/Http/Resources/ExamResultFieldMap.php \
       app/Modules/MedicalRecord/Services/ExamResultService.php
git commit -m "feat(medical-record): add bidirectional field mapping for all exam types"
```

---

## Task 11: Resource Classes

**Files:** Create 14 resource files in `app/Modules/MedicalRecord/Http/Resources/`

Each resource uses the `ExamResultFieldMap` trait's `dbToApiMap()` to transform DB columns to API field names. All resources share the same base structure:

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Resources;

use App\Modules\MedicalRecord\Enums\ExamType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EcgResultResource extends JsonResource
{
    use ExamResultFieldMap;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $base = [
            'id' => $this->id,
            'medical_record_id' => $this->prontuario_id,
            'patient_id' => $this->paciente_id,
            'date' => $this->data->format('Y-m-d'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        $map = self::dbToApiMap(ExamType::Ecg);
        foreach ($map as $dbCol => $apiField) {
            $base[$apiField] = $this->{$dbCol};
        }

        return $base;
    }
}
```

Repeat for all 14 types. Each one just changes the `ExamType::` case.

**Special case — MrpaResultResource** also includes `measurements`:

```php
public function toArray(Request $request): array
{
    $base = [/* standard fields */];

    $map = self::dbToApiMap(ExamType::Mrpa);
    foreach ($map as $dbCol => $apiField) {
        $base[$apiField] = $this->{$dbCol};
    }

    $base['measurements'] = $this->whenLoaded('medicoes', fn () => $this->medicoes->map(fn ($m) => [
        'id' => $m->id,
        'date' => $m->data->format('Y-m-d'),
        'time' => $m->hora,
        'period' => $m->periodo,
        'systolic' => $m->pas,
        'diastolic' => $m->pad,
    ])->all());

    return $base;
}
```

**Commit:**
```bash
git add app/Modules/MedicalRecord/Http/Resources/*Resource.php
git commit -m "feat(medical-record): add resource classes for all exam result types"
```

---

## Task 12: ExamResultController

**Files:**
- Create: `app/Modules/MedicalRecord/Http/Controllers/ExamResultController.php`

```php
<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\Enums\ExamType;
use App\Modules\MedicalRecord\Http\Requests\StoreExamResultRequest;
use App\Modules\MedicalRecord\Http\Requests\UpdateExamResultRequest;
use App\Modules\MedicalRecord\Services\ExamResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Manage structured exam results for a medical record.
 *
 * @authenticated
 * @group Exam Results
 */
final class ExamResultController
{
    public function __construct(
        private readonly ExamResultService $examResultService,
    ) {}

    /**
     * List exam results by type.
     *
     * Retrieves all results of a specific exam type for a medical record.
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam examType string required The exam type slug. Example: ecg
     *
     * @response 200 scenario="Success (ECG)" {"data": [{"id": 1, "medical_record_id": 1, "patient_id": 1, "date": "2026-03-15", "pattern": "normal", "custom_text": null, "created_at": "2026-03-15T10:30:00Z", "updated_at": "2026-03-15T10:30:00Z"}]}
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Acesso negado."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     */
    public function index(Request $request, int $medicalRecordId, string $examType): AnonymousResourceCollection
    {
        $type = ExamType::from($examType);
        $prontuario = $this->examResultService->findMedicalRecordOrFail($medicalRecordId);
        Gate::authorize('viewAny', [$type->modelClass(), $prontuario]);

        $results = $this->examResultService->listByMedicalRecord($type, $medicalRecordId);
        $resourceClass = $type->resourceClass();

        return $resourceClass::collection($results);
    }

    /**
     * Store a new exam result.
     *
     * Creates a new structured exam result for the specified medical record.
     * The medical record must be in draft status.
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam examType string required The exam type slug. Example: ecg
     *
     * @bodyParam date string required The exam date (YYYY-MM-DD). Example: 2026-03-15
     * @bodyParam pattern string The ECG pattern (for ecg type). Example: normal
     *
     * @response 201 scenario="Created" {"data": {"id": 1, "medical_record_id": 1, "patient_id": 1, "date": "2026-03-15", "pattern": "normal", "custom_text": null, "created_at": "2026-03-15T10:30:00Z", "updated_at": "2026-03-15T10:30:00Z"}}
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Acesso negado."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     * @response 409 scenario="Finalized" {"message": "Não é possível modificar resultados de exame de um prontuário finalizado."}
     * @response 422 scenario="Validation Error" {"message": "O padrão do exame é obrigatório.", "errors": {"pattern": ["O padrão do exame é obrigatório."]}}
     */
    public function store(StoreExamResultRequest $request, int $medicalRecordId, string $examType): JsonResponse
    {
        $type = ExamType::from($examType);
        $prontuario = $this->examResultService->findMedicalRecordOrFail($medicalRecordId);
        Gate::authorize('create', [$type->modelClass(), $prontuario]);

        $result = $this->examResultService->store($type, $medicalRecordId, $request->validated());
        $resourceClass = $type->resourceClass();

        return (new $resourceClass($result))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an exam result.
     *
     * Updates an existing structured exam result. The medical record must be in draft status.
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam examType string required The exam type slug. Example: ecg
     * @urlParam id int required The exam result ID. Example: 1
     *
     * @response 200 scenario="Updated" {"data": {"id": 1, "medical_record_id": 1, "patient_id": 1, "date": "2026-03-15", "pattern": "altered", "custom_text": "Alteração no segmento ST", "created_at": "2026-03-15T10:30:00Z", "updated_at": "2026-03-15T11:00:00Z"}}
     * @response 404 scenario="Not Found" {"message": "Resultado de exame não encontrado."}
     * @response 409 scenario="Finalized" {"message": "Não é possível modificar resultados de exame de um prontuário finalizado."}
     */
    public function update(UpdateExamResultRequest $request, int $medicalRecordId, string $examType, int $id): JsonResponse
    {
        $type = ExamType::from($examType);
        $result = $this->examResultService->findForMedicalRecordOrFail($type, $id, $medicalRecordId);
        Gate::authorize('update', $result);

        $updated = $this->examResultService->update($type, $id, $medicalRecordId, $request->validated());
        $resourceClass = $type->resourceClass();

        return response()->json(['data' => new $resourceClass($updated)]);
    }

    /**
     * Delete an exam result.
     *
     * Deletes an exam result. The medical record must be in draft status.
     *
     * @urlParam medicalRecordId int required The medical record ID. Example: 1
     * @urlParam examType string required The exam type slug. Example: ecg
     * @urlParam id int required The exam result ID. Example: 1
     *
     * @response 200 scenario="Deleted" {"message": "Resultado de exame excluído com sucesso."}
     * @response 404 scenario="Not Found" {"message": "Resultado de exame não encontrado."}
     * @response 409 scenario="Finalized" {"message": "Não é possível modificar resultados de exame de um prontuário finalizado."}
     */
    public function destroy(Request $request, int $medicalRecordId, string $examType, int $id): JsonResponse
    {
        $type = ExamType::from($examType);
        $result = $this->examResultService->findForMedicalRecordOrFail($type, $id, $medicalRecordId);
        Gate::authorize('delete', $result);

        $this->examResultService->delete($type, $id, $medicalRecordId);

        return response()->json(['message' => $type->deletedMessage()]);
    }
}
```

**Commit:**
```bash
git add app/Modules/MedicalRecord/Http/Controllers/ExamResultController.php
git commit -m "feat(medical-record): add generic ExamResultController for structured exams"
```

---

## Task 13: Routes

**Files:**
- Modify: `app/Modules/MedicalRecord/routes.php`

Add inside the existing `auth:sanctum` middleware group:

```php
// Structured exam results (generic CRUD for all exam types)
$examTypePattern = implode('|', array_map(fn ($t) => $t->value, ExamType::cases()));

Route::where('examType', $examTypePattern)->group(function (): void {
    Route::get('/medical-records/{medicalRecordId}/exam-results/{examType}', [ExamResultController::class, 'index']);
    Route::post('/medical-records/{medicalRecordId}/exam-results/{examType}', [ExamResultController::class, 'store']);
    Route::put('/medical-records/{medicalRecordId}/exam-results/{examType}/{id}', [ExamResultController::class, 'update']);
    Route::delete('/medical-records/{medicalRecordId}/exam-results/{examType}/{id}', [ExamResultController::class, 'destroy']);
});
```

Add the necessary imports at the top of routes.php:
```php
use App\Modules\MedicalRecord\Enums\ExamType;
use App\Modules\MedicalRecord\Http\Controllers\ExamResultController;
```

**Verify routes:**
```bash
php artisan route:list --path=exam-results
```

**Commit:**
```bash
git add app/Modules/MedicalRecord/routes.php
git commit -m "feat(medical-record): add routes for structured exam results"
```

---

## Task 14: Tests — Simple Exams (ECG, RX, Free Text, Temperature, Elastografia)

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/ExamResult/StoreSimpleExamResultTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ExamResult/ListExamResultTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ExamResult/UpdateExamResultTest.php`
- Create: `app/Modules/MedicalRecord/Tests/Feature/ExamResult/DeleteExamResultTest.php`

Use a shared test approach with datasets for simple exam types.

### StoreSimpleExamResultTest.php

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MedicalRecord\Models\Prontuario;

it('stores an ECG result', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        [
            'date' => '2026-03-15',
            'pattern' => 'normal',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.date', '2026-03-15')
        ->assertJsonPath('data.pattern', 'normal')
        ->assertJsonPath('data.medical_record_id', $prontuario->id);

    $this->assertDatabaseHas('resultados_ecg', [
        'prontuario_id' => $prontuario->id,
        'padrao' => 'normal',
    ]);
});

it('stores an ECG result with altered pattern and custom text', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        [
            'date' => '2026-03-15',
            'pattern' => 'altered',
            'custom_text' => 'Alteração no segmento ST',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.pattern', 'altered')
        ->assertJsonPath('data.custom_text', 'Alteração no segmento ST');
});

it('stores an X-ray result', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/xray",
        ['date' => '2026-03-15', 'pattern' => 'normal']
    );

    $response->assertCreated()
        ->assertJsonPath('data.pattern', 'normal');
});

it('stores a free text result', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/free-text",
        [
            'date' => '2026-03-15',
            'type' => 'holter',
            'text' => 'Ritmo sinusal, sem arritmias significativas em 24h.',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.type', 'holter')
        ->assertJsonPath('data.text', 'Ritmo sinusal, sem arritmias significativas em 24h.');
});

it('stores a temperature record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/temperature",
        ['date' => '2026-03-15', 'time' => '08:30', 'value' => 36.5]
    );

    $response->assertCreated()
        ->assertJsonPath('data.time', '08:30')
        ->assertJsonPath('data.value', 36.5);
});

it('stores a hepatic elastography result', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/hepatic-elastography",
        ['date' => '2026-03-15', 'fat_fraction' => 5.2, 'kpa' => 4.8]
    );

    $response->assertCreated()
        ->assertJsonPath('data.fat_fraction', 5.2)
        ->assertJsonPath('data.kpa', 4.8);
});

// --- Authorization tests ---

it('rejects store by non-owner', function (): void {
    $doctorA = User::factory()->doctor()->create();
    $doctorB = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctorA->id]);

    $response = $this->actingAs($doctorB)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2026-03-15', 'pattern' => 'normal']
    );

    $response->assertForbidden();
});

it('rejects store on finalized record', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->finalized()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2026-03-15', 'pattern' => 'normal']
    );

    $response->assertStatus(409);
});

it('rejects unauthenticated access', function (): void {
    $prontuario = Prontuario::factory()->create();

    $response = $this->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2026-03-15', 'pattern' => 'normal']
    );

    $response->assertUnauthorized();
});

it('rejects invalid exam type', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/invalid-type",
        ['date' => '2026-03-15']
    );

    $response->assertNotFound();
});

it('validates required fields for ECG', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2026-03-15'] // missing pattern
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['pattern']);
});

it('rejects future dates', function (): void {
    $doctor = User::factory()->doctor()->create();
    $prontuario = Prontuario::factory()->create(['user_id' => $doctor->id]);

    $response = $this->actingAs($doctor)->postJson(
        "/api/medical-records/{$prontuario->id}/exam-results/ecg",
        ['date' => '2030-01-01', 'pattern' => 'normal']
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['date']);
});
```

### ListExamResultTest.php, UpdateExamResultTest.php, DeleteExamResultTest.php

Follow the same pattern as the store tests. Key test scenarios:

**List:**
- Lists results for a medical record (ordered by date desc)
- Returns empty array when no results
- Rejects non-owner
- Rejects unauthenticated

**Update:**
- Updates specific fields
- Rejects non-owner
- Rejects finalized record (409)
- Returns 404 for wrong medical record

**Delete:**
- Deletes result
- Rejects non-owner
- Rejects finalized record (409)
- Returns 404 for wrong medical record

**Step: Run tests**

```bash
php artisan test app/Modules/MedicalRecord/Tests/Feature/ExamResult/ --compact
```

**Commit:**
```bash
git add app/Modules/MedicalRecord/Tests/Feature/ExamResult/
git commit -m "test(medical-record): add tests for simple exam result CRUD"
```

---

## Task 15: Tests — Medium Exams (MAPA, DEXA, Ergometric, Ecodoppler)

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/ExamResult/StoreMediumExamResultTest.php`

Test store + field mapping for each medium exam type:

```php
it('stores a MAPA result with BP values and overrides', function (): void {
    // Tests all 13 MAPA fields including overrides
});

it('stores a DEXA result with body composition data', function (): void {
    // Tests all 14 DEXA fields
});

it('stores an ergometric test result', function (): void {
    // Tests protocol + all cardiac function fields
});

it('stores a carotid ecodoppler with bilateral measurements', function (): void {
    // Tests nested artery measurements flattening
});
```

**Commit:**
```bash
git add app/Modules/MedicalRecord/Tests/Feature/ExamResult/StoreMediumExamResultTest.php
git commit -m "test(medical-record): add tests for medium exam result types"
```

---

## Task 16: Tests — Complex Exams (Echo, MRPA, CAT, Scintigraphy, Diabetic Foot)

**Files:**
- Create: `app/Modules/MedicalRecord/Tests/Feature/ExamResult/StoreComplexExamResultTest.php`

Key complex test scenarios:

```php
it('stores an echo result with valve assessments (JSONB)', function (): void {
    // Tests decimal columns + JSONB valve_aortic, valve_mitral, valve_tricuspid
});

it('stores an MRPA result with child measurements', function (): void {
    // Tests parent-child creation in transaction
    // Verifies medicoes_mrpa rows created
    // Verifies measurements returned in response
});

it('updates an MRPA result and replaces measurements', function (): void {
    // Tests child record replacement on update
});

it('deletes an MRPA result and cascades to measurements', function (): void {
    // Tests cascade delete of child records
});

it('stores a CAT result with artery JSONB data', function (): void {
    // Tests JSONB artery structures + stents array
});

it('stores a scintigraphy result with perfusion territories and segments', function (): void {
    // Tests perfusion mapping, scores, 17-segment JSONB
});

it('stores a diabetic foot screening with JSONB sections and numeric scores', function (): void {
    // Tests JSONB sections + real score columns
});
```

**Commit:**
```bash
git add app/Modules/MedicalRecord/Tests/Feature/ExamResult/StoreComplexExamResultTest.php
git commit -m "test(medical-record): add tests for complex exam result types"
```

---

## Task 17: Add Prontuario Relationships

**Files:**
- Modify: `app/Modules/MedicalRecord/Models/Prontuario.php`

Add HasMany relationships for all exam types so they can be eager-loaded:

```php
public function resultadosEcg(): HasMany { return $this->hasMany(ResultadoEcg::class); }
public function resultadosRx(): HasMany { return $this->hasMany(ResultadoRx::class); }
public function resultadosTextoLivre(): HasMany { return $this->hasMany(ResultadoTextoLivre::class); }
public function registrosTemperatura(): HasMany { return $this->hasMany(RegistroTemperatura::class); }
public function resultadosElastografiaHepatica(): HasMany { return $this->hasMany(ResultadoElastografiaHepatica::class); }
public function resultadosMapa(): HasMany { return $this->hasMany(ResultadoMapa::class); }
public function resultadosDexa(): HasMany { return $this->hasMany(ResultadoDexa::class); }
public function resultadosTesteErgometrico(): HasMany { return $this->hasMany(ResultadoTesteErgometrico::class); }
public function resultadosEcodopplerCarotidas(): HasMany { return $this->hasMany(ResultadoEcodopplerCarotidas::class); }
public function resultadosEcocardiograma(): HasMany { return $this->hasMany(ResultadoEcocardiograma::class); }
public function resultadosMrpa(): HasMany { return $this->hasMany(ResultadoMrpa::class); }
public function resultadosCat(): HasMany { return $this->hasMany(ResultadoCat::class); }
public function resultadosCintilografia(): HasMany { return $this->hasMany(ResultadoCintilografia::class); }
public function resultadosPeDiabetico(): HasMany { return $this->hasMany(ResultadoPeDiabetico::class); }
```

**Commit:**
```bash
git add app/Modules/MedicalRecord/Models/Prontuario.php
git commit -m "feat(medical-record): add Prontuario relationships for all exam result types"
```

---

## Task 18: Scribe Documentation + Pint + Final Verification

**Step 1: Run Pint**

```bash
vendor/bin/pint --dirty
```

**Step 2: Run all tests**

```bash
php artisan test app/Modules/MedicalRecord/Tests/ --compact
```

Expected: All tests passing (previous 102 + new exam result tests).

**Step 3: Generate Scribe docs**

```bash
php artisan scribe:generate
```

**Step 4: Final commit**

```bash
git add -A
git commit -m "chore(medical-record): format code and regenerate API docs"
```

---

## Summary: Files to Create/Modify

| Category | Count | Files |
|---|---|---|
| Enum | 1 | `ExamType.php` |
| Migrations | 14 | One per exam table + `medicoes_mrpa` |
| Models | 15 | 14 exam models + `MedicaoMrpa` |
| Factories | 15 | One per model |
| Service | 1 | `ExamResultService.php` |
| Controller | 1 | `ExamResultController.php` |
| Policy | 1 | `ExamResultPolicy.php` |
| Requests | 3 | Store, Update, Validation trait |
| Resources | 14 | One per exam type |
| Field Map | 1 | `ExamResultFieldMap.php` trait |
| Tests | 4-6 | Grouped by complexity |
| Routes | 1 | Modified `routes.php` |
| ServiceProvider | 1 | Modified to register policies |
| Prontuario Model | 1 | Modified with new relationships |
| **Total** | ~60 files | |
