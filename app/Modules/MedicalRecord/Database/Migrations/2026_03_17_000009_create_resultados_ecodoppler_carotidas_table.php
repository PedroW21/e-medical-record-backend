<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_ecodoppler_carotidas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->date('data');
            $table->decimal('espessura_intimal_carotida_comum_e', 8, 2)->nullable();
            $table->decimal('grau_estenose_carotida_comum_e', 8, 2)->nullable();
            $table->decimal('espessura_intimal_carotida_comum_d', 8, 2)->nullable();
            $table->decimal('grau_estenose_carotida_comum_d', 8, 2)->nullable();
            $table->decimal('espessura_intimal_carotida_externa_e', 8, 2)->nullable();
            $table->decimal('grau_estenose_carotida_externa_e', 8, 2)->nullable();
            $table->decimal('espessura_intimal_carotida_externa_d', 8, 2)->nullable();
            $table->decimal('grau_estenose_carotida_externa_d', 8, 2)->nullable();
            $table->decimal('espessura_intimal_bulbo_interna_e', 8, 2)->nullable();
            $table->decimal('grau_estenose_bulbo_interna_e', 8, 2)->nullable();
            $table->decimal('espessura_intimal_bulbo_interna_d', 8, 2)->nullable();
            $table->decimal('grau_estenose_bulbo_interna_d', 8, 2)->nullable();
            $table->decimal('espessura_intimal_vertebral_e', 8, 2)->nullable();
            $table->decimal('grau_estenose_vertebral_e', 8, 2)->nullable();
            $table->decimal('espessura_intimal_vertebral_d', 8, 2)->nullable();
            $table->decimal('grau_estenose_vertebral_d', 8, 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['prontuario_id', 'data']);
            $table->index(['paciente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_ecodoppler_carotidas');
    }
};
