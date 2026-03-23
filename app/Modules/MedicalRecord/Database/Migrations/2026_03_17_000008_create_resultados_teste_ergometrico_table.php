<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_teste_ergometrico', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->date('data');
            $table->string('protocolo')->nullable();
            $table->decimal('pct_fc_max_prevista', 8, 2)->nullable();
            $table->integer('fc_max')->nullable();
            $table->integer('pas_max')->nullable();
            $table->integer('pas_pre')->nullable();
            $table->decimal('vo2_max', 8, 2)->nullable();
            $table->decimal('mvo2_max', 8, 2)->nullable();
            $table->decimal('deficit_cronotropico', 8, 2)->nullable();
            $table->decimal('deficit_funcional_ve', 8, 2)->nullable();
            $table->decimal('debito_cardiaco', 8, 2)->nullable();
            $table->decimal('volume_sistolico', 8, 2)->nullable();
            $table->integer('dp_max')->nullable();
            $table->decimal('met_max', 8, 2)->nullable();
            $table->string('aptidao_cardiorrespiratoria')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['prontuario_id', 'data']);
            $table->index(['paciente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_teste_ergometrico');
    }
};
