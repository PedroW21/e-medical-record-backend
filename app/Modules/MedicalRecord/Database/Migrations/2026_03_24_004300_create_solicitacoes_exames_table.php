<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_exames', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->string('modelo_id')->nullable();
            $table->string('cid_10')->nullable();
            $table->text('indicacao_clinica')->nullable();
            $table->jsonb('itens');
            $table->jsonb('relatorio_medico')->nullable();
            $table->timestamp('impresso_em')->nullable();
            $table->timestamps();
            $table->index('prontuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_exames');
    }
};
