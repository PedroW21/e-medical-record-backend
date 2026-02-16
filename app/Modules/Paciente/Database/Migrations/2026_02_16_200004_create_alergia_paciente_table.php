<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alergia_paciente', function (Blueprint $table): void {
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('alergia_id')->constrained('alergias')->cascadeOnDelete();

            $table->primary(['paciente_id', 'alergia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alergia_paciente');
    }
};
