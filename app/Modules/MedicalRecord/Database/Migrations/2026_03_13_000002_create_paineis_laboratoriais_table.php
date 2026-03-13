<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paineis_laboratoriais', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('nome');
            $table->string('categoria');
            $table->jsonb('subsecoes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paineis_laboratoriais');
    }
};
