<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->string('cpf', 14);
            $table->string('telefone', 20);
            $table->string('email')->nullable();
            $table->date('data_nascimento');
            $table->string('sexo', 10);
            $table->string('tipo_sanguineo', 5)->nullable();
            $table->string('historico_tabagismo', 20)->nullable();
            $table->string('historico_alcool', 20)->nullable();
            $table->string('status', 10)->default('active');
            $table->timestamp('ultima_consulta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'cpf']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
