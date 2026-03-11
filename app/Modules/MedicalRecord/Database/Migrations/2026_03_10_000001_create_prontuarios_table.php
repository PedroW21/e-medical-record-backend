<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prontuarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes');
            $table->foreignId('user_id')->constrained('users');
            $table->string('tipo');
            $table->string('status')->default('draft');
            $table->timestamp('finalizado_em')->nullable();
            $table->foreignId('baseado_em_prontuario_id')->nullable()->constrained('prontuarios');
            $table->timestamps();

            $table->index(['paciente_id', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prontuarios');
    }
};
