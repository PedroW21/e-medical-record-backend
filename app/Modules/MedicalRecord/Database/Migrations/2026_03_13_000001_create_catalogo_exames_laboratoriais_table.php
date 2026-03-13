<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_exames_laboratoriais', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('nome');
            $table->string('categoria');
            $table->string('unidade');
            $table->string('faixa_referencia')->nullable();
            $table->string('tipo_resultado')->default('numeric');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_exames_laboratoriais');
    }
};
