<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condicao_cronica_paciente', function (Blueprint $table): void {
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('condicao_cronica_id')->constrained('condicoes_cronicas')->cascadeOnDelete();

            $table->primary(['paciente_id', 'condicao_cronica_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condicao_cronica_paciente');
    }
};
