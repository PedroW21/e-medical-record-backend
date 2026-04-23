<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_injetaveis_protocolo_componentes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('protocolo_id');
            $table->unsignedInteger('ordem');
            $table->string('nome_farmaco');
            $table->string('dosagem');
            $table->unsignedInteger('quantidade_ampolas');
            $table->string('via')->nullable();
            $table->timestamps();

            $table->foreign('protocolo_id')
                ->references('id')
                ->on('catalogo_injetaveis_protocolos')
                ->cascadeOnDelete();

            $table->unique(['protocolo_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_injetaveis_protocolo_componentes');
    }
};
