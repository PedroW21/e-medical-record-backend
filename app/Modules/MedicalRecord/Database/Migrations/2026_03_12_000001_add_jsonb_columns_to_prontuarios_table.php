<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prontuarios', function (Blueprint $table): void {
            $table->jsonb('exame_fisico')->nullable()->after('baseado_em_prontuario_id');
            $table->jsonb('lista_problemas')->nullable()->after('exame_fisico');
            $table->jsonb('escores_risco')->nullable()->after('lista_problemas');
            $table->jsonb('conduta')->nullable()->after('escores_risco');

            $table->index(['user_id', 'paciente_id', 'created_at']);
            $table->index(['status', 'paciente_id']);
        });
    }

    public function down(): void
    {
        Schema::table('prontuarios', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'paciente_id', 'created_at']);
            $table->dropIndex(['status', 'paciente_id']);
            $table->dropColumn(['exame_fisico', 'lista_problemas', 'escores_risco', 'conduta']);
        });
    }
};
