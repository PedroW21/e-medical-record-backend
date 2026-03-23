<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_cat', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->date('data');
            $table->jsonb('cd')->nullable();
            $table->jsonb('ce')->nullable();
            $table->jsonb('da')->nullable();
            $table->jsonb('cx')->nullable();
            $table->jsonb('d1')->nullable();
            $table->jsonb('d2')->nullable();
            $table->jsonb('mge')->nullable();
            $table->jsonb('mgd')->nullable();
            $table->jsonb('dp')->nullable();
            $table->jsonb('stents')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['prontuario_id', 'data']);
            $table->index(['paciente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_cat');
    }
};
