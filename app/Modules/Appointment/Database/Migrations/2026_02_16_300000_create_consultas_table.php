<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->date('data');
            $table->time('horario');
            $table->string('tipo', 30);
            $table->string('status', 20);
            $table->text('observacoes')->nullable();
            $table->string('nome_solicitante')->nullable();
            $table->string('telefone_solicitante', 20)->nullable();
            $table->string('email_solicitante')->nullable();
            $table->string('origem', 10);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'data', 'horario']);
            $table->index(['user_id', 'data']);
            $table->index('paciente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};
