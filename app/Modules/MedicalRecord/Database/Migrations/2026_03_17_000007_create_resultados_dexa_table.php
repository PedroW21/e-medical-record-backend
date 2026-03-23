<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_dexa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->date('data');
            $table->decimal('peso_total', 8, 2)->nullable();
            $table->decimal('dmo', 8, 4)->nullable();
            $table->decimal('t_score', 8, 2)->nullable();
            $table->decimal('gordura_corporal_pct', 8, 2)->nullable();
            $table->decimal('gordura_total', 12, 2)->nullable();
            $table->decimal('imc', 8, 2)->nullable();
            $table->decimal('gordura_visceral', 12, 2)->nullable();
            $table->decimal('gordura_visceral_pct', 8, 2)->nullable();
            $table->decimal('massa_magra', 12, 2)->nullable();
            $table->decimal('massa_magra_pct', 8, 2)->nullable();
            $table->decimal('fmi', 8, 2)->nullable();
            $table->decimal('ffmi', 8, 2)->nullable();
            $table->decimal('rsmi', 8, 2)->nullable();
            $table->decimal('tmr', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['prontuario_id', 'data']);
            $table->index(['paciente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_dexa');
    }
};
