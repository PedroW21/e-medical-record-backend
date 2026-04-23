<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\InjectableProtocolRoute;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_injetaveis_protocolos', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('farmacia_id');
            $table->string('categoria_terapeutica_id');
            $table->string('nome');
            $table->string('via');
            $table->text('notas_aplicacao')->nullable();
            $table->timestamps();

            $table->foreign('farmacia_id')
                ->references('id')
                ->on('catalogo_farmacias')
                ->cascadeOnDelete();

            $table->foreign('categoria_terapeutica_id')
                ->references('id')
                ->on('catalogo_categorias_terapeuticas')
                ->cascadeOnDelete();

            $table->index(['farmacia_id', 'via']);
            $table->index('categoria_terapeutica_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            $allowed = implode("','", InjectableProtocolRoute::values());
            DB::statement("ALTER TABLE catalogo_injetaveis_protocolos ADD CONSTRAINT chk_protocolo_via CHECK (via IN ('{$allowed}'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_injetaveis_protocolos');
    }
};
