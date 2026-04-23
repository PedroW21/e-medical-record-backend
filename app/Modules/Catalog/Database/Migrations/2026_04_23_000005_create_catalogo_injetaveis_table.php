<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_injetaveis', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('farmacia_id');
            $table->string('nome');
            $table->string('dosagem');
            $table->string('volume')->nullable();
            $table->string('via_exclusiva')->nullable();
            $table->text('composicao')->nullable();
            $table->boolean('is_blend')->default(false);
            $table->jsonb('vias_permitidas');
            $table->timestamps();

            $table->foreign('farmacia_id')
                ->references('id')
                ->on('catalogo_farmacias')
                ->cascadeOnDelete();

            $table->index(['farmacia_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_injetaveis');
    }
};
