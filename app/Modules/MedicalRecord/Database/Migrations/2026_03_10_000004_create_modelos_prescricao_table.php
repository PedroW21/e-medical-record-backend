<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelos_prescricao', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nome');
            $table->jsonb('tags')->nullable();
            $table->string('subtipo');
            $table->jsonb('itens');
            $table->timestamps();

            $table->index(['user_id', 'subtipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos_prescricao');
    }
};
