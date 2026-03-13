<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valores_laboratoriais', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->string('catalogo_exame_id')->nullable();
            $table->string('nome_avulso')->nullable();
            $table->date('data_coleta');
            $table->string('valor');
            $table->decimal('valor_numerico', 12, 4)->nullable();
            $table->string('unidade');
            $table->string('faixa_referencia')->nullable();
            $table->string('painel_id')->nullable();
            $table->timestamps();

            $table->foreign('catalogo_exame_id')->references('id')->on('catalogo_exames_laboratoriais')->nullOnDelete();
            $table->foreign('painel_id')->references('id')->on('paineis_laboratoriais')->nullOnDelete();

            $table->index(['paciente_id', 'catalogo_exame_id', 'data_coleta'], 'idx_valores_lab_evolucao');
            $table->index(['prontuario_id', 'data_coleta'], 'idx_valores_lab_prontuario');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE valores_laboratoriais ADD CONSTRAINT chk_exame_ou_avulso CHECK (catalogo_exame_id IS NOT NULL OR nome_avulso IS NOT NULL)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('valores_laboratoriais');
    }
};
