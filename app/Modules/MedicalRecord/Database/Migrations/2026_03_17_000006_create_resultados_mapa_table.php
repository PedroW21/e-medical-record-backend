<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_mapa', function (Blueprint $table): void {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_mapa');
    }
};
