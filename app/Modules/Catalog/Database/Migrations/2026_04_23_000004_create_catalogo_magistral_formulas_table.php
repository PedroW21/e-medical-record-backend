<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_magistral_formulas', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('categoria_id');
            $table->string('nome');
            $table->jsonb('componentes');
            $table->text('excipiente')->nullable();
            $table->text('posologia')->nullable();
            $table->text('instrucoes')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->foreign('categoria_id')
                ->references('id')
                ->on('catalogo_magistral_categorias')
                ->cascadeOnDelete();

            $table->index('categoria_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_magistral_formulas');
    }
};
