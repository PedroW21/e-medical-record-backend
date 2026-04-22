<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anexos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->string('tipo_anexo');
            $table->string('nome');
            $table->string('tipo_arquivo');
            $table->string('caminho');
            $table->unsignedBigInteger('tamanho_bytes');
            $table->string('status_processamento')->nullable();
            $table->jsonb('dados_extraidos')->nullable();
            $table->text('erro_processamento')->nullable();
            $table->timestamp('processado_em')->nullable();
            $table->timestamp('confirmado_em')->nullable();
            $table->timestamps();

            $table->index('prontuario_id');
            $table->index('paciente_id');
            $table->index(['prontuario_id', 'tipo_anexo']);
            $table->index('status_processamento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos');
    }
};
