<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\MagistralType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_magistral_categorias', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('tipo');
            $table->string('rotulo');
            $table->string('icone')->nullable();
            $table->timestamps();

            $table->index('tipo');
        });

        if (DB::getDriverName() === 'pgsql') {
            $allowed = implode("','", MagistralType::values());
            DB::statement("ALTER TABLE catalogo_magistral_categorias ADD CONSTRAINT chk_magistral_categoria_tipo CHECK (tipo IN ('{$allowed}'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_magistral_categorias');
    }
};
