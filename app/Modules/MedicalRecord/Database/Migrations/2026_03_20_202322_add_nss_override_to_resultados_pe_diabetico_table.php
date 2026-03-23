<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultados_pe_diabetico', function (Blueprint $table): void {
            $table->boolean('nss_override')->default(false)->after('nss_score');
        });
    }

    public function down(): void
    {
        Schema::table('resultados_pe_diabetico', function (Blueprint $table): void {
            $table->dropColumn('nss_override');
        });
    }
};
