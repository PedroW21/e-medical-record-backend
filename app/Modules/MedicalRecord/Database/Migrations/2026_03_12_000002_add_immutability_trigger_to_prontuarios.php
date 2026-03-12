<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared("
            CREATE OR REPLACE FUNCTION prevent_finalized_prontuario_update()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF OLD.finalizado_em IS NOT NULL THEN
                    RAISE EXCEPTION 'Não é possível modificar um prontuário finalizado.';
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_prevent_finalized_prontuario_update
            BEFORE UPDATE ON prontuarios
            FOR EACH ROW
            EXECUTE FUNCTION prevent_finalized_prontuario_update();
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared("
            DROP TRIGGER IF EXISTS trg_prevent_finalized_prontuario_update ON prontuarios;
            DROP FUNCTION IF EXISTS prevent_finalized_prontuario_update();
        ");
    }
};
