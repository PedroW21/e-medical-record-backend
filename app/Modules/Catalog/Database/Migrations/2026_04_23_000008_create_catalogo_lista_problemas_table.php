<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\ProblemCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_lista_problemas', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('categoria');
            $table->string('rotulo');
            $table->jsonb('variacao')->nullable();
            $table->timestamps();

            $table->index('categoria');
        });

        if (DB::getDriverName() === 'pgsql') {
            $allowed = implode("','", ProblemCategory::values());
            DB::statement("ALTER TABLE catalogo_lista_problemas ADD CONSTRAINT chk_problema_categoria CHECK (categoria IN ('{$allowed}'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_lista_problemas');
    }
};
