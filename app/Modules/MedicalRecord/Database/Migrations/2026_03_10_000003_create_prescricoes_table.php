<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->string('subtipo');
            $table->string('tipo_receita');
            $table->boolean('tipo_receita_override')->default(false);
            $table->jsonb('itens');
            $table->text('observacoes')->nullable();
            $table->timestamp('impresso_em')->nullable();
            $table->timestamps();

            $table->index(['prontuario_id', 'subtipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricoes');
    }
};
