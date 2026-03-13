<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Services;

use App\Modules\MedicalRecord\DTOs\CreateMedicalRecordDTO;
use App\Modules\MedicalRecord\DTOs\UpdateMedicalRecordDTO;
use App\Modules\MedicalRecord\Enums\MedicalRecordStatus;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MedicalRecordService
{
    /**
     * List medical records for a specific patient, scoped by the authenticated doctor.
     *
     * @param  array{status?: string|null, per_page?: int|null}  $filters
     * @return LengthAwarePaginator<Prontuario>
     */
    public function listForPatient(int $userId, int $patientId, array $filters = []): LengthAwarePaginator
    {
        $this->assertPatientBelongsToUser($userId, $patientId);

        $query = Prontuario::query()
            ->where('paciente_id', $patientId)
            ->where('user_id', $userId)
            ->with('medidaAntropometrica')
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    /**
     * Find a single medical record by ID, scoped by user ownership.
     */
    public function findForUser(int $userId, int $id): Prontuario
    {
        $prontuario = Prontuario::query()
            ->with(['medidaAntropometrica', 'prescricoes', 'paciente'])
            ->find($id);

        if (! $prontuario || $prontuario->user_id !== $userId) {
            throw new NotFoundHttpException('Prontuário não encontrado.');
        }

        return $prontuario;
    }

    /**
     * Create a new medical record with optional anthropometry data.
     */
    public function create(int $userId, CreateMedicalRecordDTO $dto): Prontuario
    {
        $this->assertPatientBelongsToUser($userId, $dto->patientId);

        return DB::transaction(function () use ($userId, $dto): Prontuario {
            $prontuario = Prontuario::query()->create([
                'paciente_id' => $dto->patientId,
                'user_id' => $userId,
                'tipo' => $dto->type->value,
                'status' => MedicalRecordStatus::Draft->value,
                'baseado_em_prontuario_id' => $dto->basedOnRecordId,
                'exame_fisico' => $dto->physicalExam,
                'lista_problemas' => $dto->problemList,
                'escores_risco' => $dto->riskScores,
                'conduta' => $dto->conduct,
            ]);

            if ($dto->anthropometry !== null) {
                $this->saveAnthropometry($prontuario, $dto->anthropometry);
            }

            return $prontuario->load('medidaAntropometrica');
        });
    }

    /**
     * Update a draft medical record.
     */
    public function update(int $userId, int $id, UpdateMedicalRecordDTO $dto): Prontuario
    {
        $prontuario = $this->findForUser($userId, $id);

        if (! $prontuario->isDraft()) {
            throw new \DomainException('Não é possível modificar um prontuário finalizado.');
        }

        return DB::transaction(function () use ($prontuario, $dto): Prontuario {
            $updateData = array_filter([
                'exame_fisico' => $dto->physicalExam,
                'lista_problemas' => $dto->problemList,
                'escores_risco' => $dto->riskScores,
                'conduta' => $dto->conduct,
            ], fn (mixed $value): bool => $value !== null);

            if (! empty($updateData)) {
                $prontuario->update($updateData);
            }

            if ($dto->anthropometry !== null) {
                $this->saveAnthropometry($prontuario, $dto->anthropometry);
            }

            return $prontuario->fresh(['medidaAntropometrica']);
        });
    }

    /**
     * Finalize a draft medical record, making it immutable.
     */
    public function finalize(int $userId, int $id): Prontuario
    {
        $prontuario = $this->findForUser($userId, $id);

        if (! $prontuario->isDraft()) {
            throw new \DomainException('Este prontuário já foi finalizado.');
        }

        $prontuario->update([
            'status' => MedicalRecordStatus::Finalized->value,
            'finalizado_em' => now(),
        ]);

        return $prontuario->fresh(['medidaAntropometrica']);
    }

    /**
     * Delete a draft medical record.
     */
    public function delete(int $userId, int $id): void
    {
        $prontuario = $this->findForUser($userId, $id);

        if (! $prontuario->isDraft()) {
            throw new \DomainException('Não é possível excluir um prontuário finalizado.');
        }

        $prontuario->delete();
    }

    /**
     * Save or update anthropometry data for a medical record.
     * Converts frontend nested shape into flat DB columns.
     *
     * @param  array<string, mixed>  $data  Frontend anthropometry payload
     */
    private function saveAnthropometry(Prontuario $prontuario, array $data): void
    {
        $bp = $data['blood_pressure'] ?? [];
        $measures = $data['measures'] ?? [];
        $skinfolds = $data['skinfolds'] ?? [];

        $columns = [
            'prontuario_id' => $prontuario->id,
            'paciente_id' => $prontuario->paciente_id,

            // Vital signs from blood_pressure
            'fc' => $bp['heart_rate'] ?? null,
            'spo2' => $bp['oxygen_sat'] ?? null,
            'temperatura' => $bp['temperature'] ?? null,

            // BP: right arm
            'pa_sentado_d_pas' => $bp['right_arm']['sitting']['systolic'] ?? null,
            'pa_sentado_d_pad' => $bp['right_arm']['sitting']['diastolic'] ?? null,
            'pa_em_pe_d_pas' => $bp['right_arm']['standing']['systolic'] ?? null,
            'pa_em_pe_d_pad' => $bp['right_arm']['standing']['diastolic'] ?? null,
            'pa_deitado_d_pas' => $bp['right_arm']['supine']['systolic'] ?? null,
            'pa_deitado_d_pad' => $bp['right_arm']['supine']['diastolic'] ?? null,

            // BP: left arm
            'pa_sentado_e_pas' => $bp['left_arm']['sitting']['systolic'] ?? null,
            'pa_sentado_e_pad' => $bp['left_arm']['sitting']['diastolic'] ?? null,
            'pa_em_pe_e_pas' => $bp['left_arm']['standing']['systolic'] ?? null,
            'pa_em_pe_e_pad' => $bp['left_arm']['standing']['diastolic'] ?? null,
            'pa_deitado_e_pas' => $bp['left_arm']['supine']['systolic'] ?? null,
            'pa_deitado_e_pad' => $bp['left_arm']['supine']['diastolic'] ?? null,

            // Measures
            'peso' => $measures['weight'] ?? null,
            'altura' => $measures['height'] ?? null,
            'imc' => $measures['bmi'] ?? null,
            'classificacao_imc' => $measures['bmi_classification'] ?? null,
            'circunferencia_abdominal' => $measures['abdominal_circumference'] ?? null,
            'circunferencia_quadril' => $measures['hip_circumference'] ?? null,
            'relacao_cintura_quadril' => $measures['waist_hip_ratio'] ?? null,
            'relacao_cintura_altura' => $measures['waist_height_ratio'] ?? null,
            'circunferencia_pescoco' => $measures['cervical_circumference'] ?? null,
            'circunferencia_cintura' => $measures['waist_circumference'] ?? null,
            'circunferencia_panturrilha_e' => $measures['calf_measurement_left'] ?? null,
            'circunferencia_panturrilha_d' => $measures['calf_measurement_right'] ?? null,
            'abertura_bucal' => $measures['mouth_opening'] ?? null,
            'distancia_tireomentual' => $measures['thyromental_distance'] ?? null,
            'distancia_mentoesternal' => $measures['mentosternal_distance'] ?? null,
            'deslocamento_mandibular' => $measures['mandible_displacement'] ?? null,

            // Skinfolds
            'dobra_tricipital' => $skinfolds['triceps'] ?? null,
            'dobra_subescapular' => $skinfolds['subscapular'] ?? null,
            'dobra_suprailica' => $skinfolds['suprailiac'] ?? null,
            'dobra_abdominal' => $skinfolds['abdominal'] ?? null,
            'dobra_peitoral' => $skinfolds['pectoral'] ?? null,
            'dobra_coxa' => $skinfolds['medial_thigh'] ?? null,
            'dobra_axilar_media' => $skinfolds['midaxillary'] ?? null,
        ];

        $prontuario->medidaAntropometrica()->updateOrCreate(
            ['prontuario_id' => $prontuario->id],
            $columns,
        );
    }

    /**
     * Assert that the patient belongs to the authenticated user.
     *
     * @throws NotFoundHttpException
     */
    private function assertPatientBelongsToUser(int $userId, int $patientId): void
    {
        $exists = Paciente::query()
            ->where('id', $patientId)
            ->where('user_id', $userId)
            ->exists();

        if (! $exists) {
            throw new NotFoundHttpException('Paciente não encontrado.');
        }
    }
}
