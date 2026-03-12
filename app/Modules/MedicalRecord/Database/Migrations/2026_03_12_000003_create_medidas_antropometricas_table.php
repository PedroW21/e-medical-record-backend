<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medidas_antropometricas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prontuario_id')->constrained('prontuarios')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes');

            // Vital signs
            $table->decimal('peso', 6, 2)->nullable();
            $table->decimal('altura', 5, 2)->nullable();
            $table->decimal('imc', 5, 2)->nullable();
            $table->string('classificacao_imc')->nullable();
            $table->integer('fc')->nullable();
            $table->decimal('spo2', 5, 2)->nullable();
            $table->decimal('temperatura', 4, 2)->nullable();

            // Blood pressure: 3 positions x 2 arms
            $table->integer('pa_sentado_d_pas')->nullable();
            $table->integer('pa_sentado_d_pad')->nullable();
            $table->integer('pa_sentado_e_pas')->nullable();
            $table->integer('pa_sentado_e_pad')->nullable();
            $table->integer('pa_em_pe_d_pas')->nullable();
            $table->integer('pa_em_pe_d_pad')->nullable();
            $table->integer('pa_em_pe_e_pas')->nullable();
            $table->integer('pa_em_pe_e_pad')->nullable();
            $table->integer('pa_deitado_d_pas')->nullable();
            $table->integer('pa_deitado_d_pad')->nullable();
            $table->integer('pa_deitado_e_pas')->nullable();
            $table->integer('pa_deitado_e_pad')->nullable();

            // Circumferences (cm)
            $table->decimal('circunferencia_pescoco', 5, 2)->nullable();
            $table->decimal('circunferencia_cintura', 5, 2)->nullable();
            $table->decimal('circunferencia_quadril', 5, 2)->nullable();
            $table->decimal('circunferencia_abdominal', 5, 2)->nullable();
            $table->decimal('circunferencia_braco_d', 5, 2)->nullable();
            $table->decimal('circunferencia_braco_e', 5, 2)->nullable();
            $table->decimal('circunferencia_coxa_d', 5, 2)->nullable();
            $table->decimal('circunferencia_coxa_e', 5, 2)->nullable();
            $table->decimal('circunferencia_panturrilha_d', 5, 2)->nullable();
            $table->decimal('circunferencia_panturrilha_e', 5, 2)->nullable();
            $table->decimal('relacao_cintura_quadril', 5, 4)->nullable();
            $table->decimal('relacao_cintura_altura', 5, 4)->nullable();

            // Skinfolds (mm)
            $table->decimal('dobra_tricipital', 5, 2)->nullable();
            $table->decimal('dobra_subescapular', 5, 2)->nullable();
            $table->decimal('dobra_suprailica', 5, 2)->nullable();
            $table->decimal('dobra_abdominal', 5, 2)->nullable();
            $table->decimal('dobra_peitoral', 5, 2)->nullable();
            $table->decimal('dobra_coxa', 5, 2)->nullable();
            $table->decimal('dobra_axilar_media', 5, 2)->nullable();

            // Airway assessment
            $table->decimal('abertura_bucal', 4, 2)->nullable();
            $table->decimal('distancia_tireomentual', 4, 2)->nullable();
            $table->decimal('distancia_mentoesternal', 4, 2)->nullable();
            $table->string('deslocamento_mandibular')->nullable();

            $table->timestamps();

            $table->index(['paciente_id', 'created_at']);
            $table->index(['prontuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medidas_antropometricas');
    }
};
