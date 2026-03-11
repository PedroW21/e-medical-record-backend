<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicamentos', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('principio_ativo');
            $table->string('apresentacao')->nullable();
            $table->string('fabricante')->nullable();
            $table->string('codigo_anvisa')->nullable();
            $table->string('lista_anvisa')->nullable();
            $table->boolean('controlado')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('nome');
            $table->index('principio_ativo');
            $table->index('lista_anvisa');
            $table->index('ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicamentos');
    }
};
