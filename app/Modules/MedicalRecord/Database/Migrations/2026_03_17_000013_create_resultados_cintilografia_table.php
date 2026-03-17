<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_cintilografia', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->date('data');
            $table->string('protocolo')->nullable();
            $table->string('modalidade_estresse')->nullable();
            $table->integer('fc_max')->nullable();
            $table->decimal('pct_fc_max_prevista', 8, 2)->nullable();
            $table->integer('pa_max')->nullable();
            $table->jsonb('sintomas_estresse')->nullable();
            $table->jsonb('alteracoes_ecg_estresse')->nullable();
            $table->string('perfusao_da_estresse')->nullable();
            $table->string('perfusao_da_repouso')->nullable();
            $table->string('perfusao_da_reversibilidade')->nullable();
            $table->string('perfusao_cx_estresse')->nullable();
            $table->string('perfusao_cx_repouso')->nullable();
            $table->string('perfusao_cx_reversibilidade')->nullable();
            $table->string('perfusao_cd_estresse')->nullable();
            $table->string('perfusao_cd_repouso')->nullable();
            $table->string('perfusao_cd_reversibilidade')->nullable();
            $table->integer('sss')->nullable();
            $table->integer('srs')->nullable();
            $table->integer('sds')->nullable();
            $table->boolean('sds_override')->default(false);
            $table->string('classificacao_sds')->nullable();
            $table->boolean('classificacao_sds_override')->default(false);
            $table->decimal('fe_repouso', 8, 2)->nullable();
            $table->decimal('vdf_repouso', 8, 2)->nullable();
            $table->decimal('vsf_repouso', 8, 2)->nullable();
            $table->decimal('fe_estresse', 8, 2)->nullable();
            $table->decimal('vdf_estresse', 8, 2)->nullable();
            $table->decimal('vsf_estresse', 8, 2)->nullable();
            $table->boolean('tid_presente')->nullable();
            $table->decimal('razao_tid', 8, 4)->nullable();
            $table->boolean('tid_override')->default(false);
            $table->jsonb('segmentos')->nullable();
            $table->boolean('captacao_pulmonar_aumentada')->nullable();
            $table->boolean('dilatacao_vd')->nullable();
            $table->text('captacao_extracardiaca')->nullable();
            $table->string('resultado_global')->nullable();
            $table->string('extensao_defeito')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['prontuario_id', 'data']);
            $table->index(['paciente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_cintilografia');
    }
};
