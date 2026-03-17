<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_mrpa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->date('data');
            $table->integer('dias_monitorados');
            $table->string('membro');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['prontuario_id', 'data']);
            $table->index(['paciente_id', 'data']);
        });

        Schema::create('medicoes_mrpa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resultado_mrpa_id')->constrained('resultados_mrpa')->cascadeOnDelete();
            $table->date('data');
            $table->time('hora');
            $table->string('periodo');
            $table->integer('pas');
            $table->integer('pad');
            $table->timestamps();

            $table->index(['resultado_mrpa_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicoes_mrpa');
        Schema::dropIfExists('resultados_mrpa');
    }
};
