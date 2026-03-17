<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_ecocardiograma', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->date('data');
            $table->string('tipo');
            $table->decimal('raiz_aorta', 8, 2)->nullable();
            $table->decimal('aorta_ascendente', 8, 2)->nullable();
            $table->decimal('arco_aortico', 8, 2)->nullable();
            $table->decimal('ae_mm', 8, 2)->nullable();
            $table->decimal('ae_ml', 8, 2)->nullable();
            $table->decimal('ae_indexado', 8, 2)->nullable();
            $table->decimal('septo', 8, 2)->nullable();
            $table->decimal('dvd', 8, 2)->nullable();
            $table->decimal('ddve', 8, 2)->nullable();
            $table->decimal('dsve', 8, 2)->nullable();
            $table->decimal('pp', 8, 2)->nullable();
            $table->decimal('erp', 8, 4)->nullable();
            $table->decimal('indice_massa_ve', 8, 2)->nullable();
            $table->decimal('fe', 8, 2)->nullable();
            $table->decimal('psap', 8, 2)->nullable();
            $table->decimal('tapse', 8, 2)->nullable();
            $table->decimal('onda_e_mitral', 8, 2)->nullable();
            $table->decimal('onda_a', 8, 2)->nullable();
            $table->decimal('relacao_e_a', 8, 4)->nullable();
            $table->boolean('relacao_e_a_override')->default(false);
            $table->decimal('e_septal', 8, 2)->nullable();
            $table->decimal('e_lateral', 8, 2)->nullable();
            $table->decimal('relacao_e_e', 8, 4)->nullable();
            $table->decimal('s_tricuspide', 8, 2)->nullable();
            $table->jsonb('valva_aortica')->nullable();
            $table->jsonb('valva_mitral')->nullable();
            $table->jsonb('valva_tricuspide')->nullable();
            $table->text('analise_qualitativa')->nullable();
            $table->timestamps();

            $table->index(['prontuario_id', 'data']);
            $table->index(['paciente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_ecocardiograma');
    }
};
