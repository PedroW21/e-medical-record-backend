<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preferencias_notificacao', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo_notificacao');
            $table->string('canal');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'tipo_notificacao', 'canal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferencias_notificacao');
    }
};
