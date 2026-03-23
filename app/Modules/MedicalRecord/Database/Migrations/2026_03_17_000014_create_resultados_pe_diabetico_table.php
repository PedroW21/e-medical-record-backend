<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_pe_diabetico', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->date('data');
            $table->jsonb('anamnese')->nullable();
            $table->jsonb('sintomas_neuropaticos')->nullable();
            $table->jsonb('inspecao_visual')->nullable();
            $table->jsonb('deformidades')->nullable();
            $table->jsonb('neurologico')->nullable();
            $table->jsonb('vascular')->nullable();
            $table->jsonb('termometria')->nullable();
            $table->integer('nss_score')->nullable();
            $table->integer('nds_score')->nullable();
            $table->boolean('nds_override')->default(false);
            $table->decimal('itb_direito', 8, 4)->nullable();
            $table->decimal('itb_esquerdo', 8, 4)->nullable();
            $table->boolean('itb_direito_override')->default(false);
            $table->boolean('itb_esquerdo_override')->default(false);
            $table->decimal('tbi_direito', 8, 4)->nullable();
            $table->decimal('tbi_esquerdo', 8, 4)->nullable();
            $table->boolean('tbi_direito_override')->default(false);
            $table->boolean('tbi_esquerdo_override')->default(false);
            $table->string('categoria_iwgdf')->nullable();
            $table->boolean('categoria_iwgdf_override')->default(false);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['prontuario_id', 'data']);
            $table->index(['paciente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_pe_diabetico');
    }
};
