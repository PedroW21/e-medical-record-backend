<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_ecg', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->date('data');
            $table->string('padrao');
            $table->text('texto_personalizado')->nullable();
            $table->timestamps();

            $table->index(['prontuario_id', 'data']);
            $table->index(['paciente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_ecg');
    }
};
