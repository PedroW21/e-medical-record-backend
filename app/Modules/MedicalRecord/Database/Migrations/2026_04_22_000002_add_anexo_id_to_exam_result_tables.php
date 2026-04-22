<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that get a unique `anexo_id` (one exam row per attachment).
     *
     * @var array<int, string>
     */
    private array $uniqueTables = [
        'resultados_ecg',
        'resultados_rx',
        'resultados_texto_livre',
        'resultados_elastografia_hepatica',
        'resultados_mapa',
        'resultados_dexa',
        'resultados_teste_ergometrico',
        'resultados_ecodoppler_carotidas',
        'resultados_ecocardiograma',
        'resultados_mrpa',
        'resultados_cat',
        'resultados_cintilografia',
        'resultados_pe_diabetico',
    ];

    public function up(): void
    {
        foreach ($this->uniqueTables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table): void {
                $t->foreignId('anexo_id')
                    ->nullable()
                    ->after('prontuario_id')
                    ->constrained('anexos')
                    ->nullOnDelete();

                $t->unique('anexo_id', $table.'_anexo_id_unique');
            });
        }

        Schema::table('valores_laboratoriais', function (Blueprint $t): void {
            $t->foreignId('anexo_id')
                ->nullable()
                ->after('prontuario_id')
                ->constrained('anexos')
                ->nullOnDelete();

            $t->index('anexo_id');
        });
    }

    public function down(): void
    {
        foreach ($this->uniqueTables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table): void {
                $t->dropUnique($table.'_anexo_id_unique');
                $t->dropConstrainedForeignId('anexo_id');
            });
        }

        Schema::table('valores_laboratoriais', function (Blueprint $t): void {
            $t->dropIndex(['anexo_id']);
            $t->dropConstrainedForeignId('anexo_id');
        });
    }
};
