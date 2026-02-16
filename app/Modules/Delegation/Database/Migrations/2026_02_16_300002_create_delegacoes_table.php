<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delegacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medico_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('secretaria_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['medico_id', 'secretaria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegacoes');
    }
};
