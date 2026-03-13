<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\DTOs\CreateMedicalRecordDTO;
use App\Modules\MedicalRecord\DTOs\UpdateMedicalRecordDTO;
use App\Modules\MedicalRecord\Http\Requests\StoreMedicalRecordRequest;
use App\Modules\MedicalRecord\Http\Requests\UpdateMedicalRecordRequest;
use App\Modules\MedicalRecord\Http\Resources\MedicalRecordResource;
use App\Modules\MedicalRecord\Models\Prontuario;
use App\Modules\MedicalRecord\Services\MedicalRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class MedicalRecordController
{
    public function __construct(
        private readonly MedicalRecordService $medicalRecordService,
    ) {}

    /**
     * List all medical records for a patient.
     *
     * Returns a paginated list of medical records for the given patient,
     * ordered by creation date (most recent first).
     *
     * @authenticated
     *
     * @group Medical Records
     *
     * @urlParam patientId int required The patient ID. Example: 1
     *
     * @queryParam status string Filter by status (draft or finalized). Example: draft
     * @queryParam per_page int Items per page (max 100). Example: 15
     * @queryParam page int The page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "patient_id": 1,
     *       "doctor_id": 1,
     *       "type": "first_visit",
     *       "status": "draft",
     *       "based_on_record_id": null,
     *       "anthropometry": {
     *         "measures": {"weight": 78.5, "height": 175, "bmi": 25.6, "bmi_classification": "normal"},
     *         "blood_pressure": {"right_arm": {"sitting": {"systolic": 120, "diastolic": 80}}}
     *       },
     *       "physical_exam": {"cardiac": "Ritmo regular em 2 tempos, sem sopros."},
     *       "problem_list": null,
     *       "risk_scores": null,
     *       "conduct": null,
     *       "finalized_at": null,
     *       "created_at": "2026-03-12T14:30:00.000000Z",
     *       "updated_at": "2026-03-12T14:30:00.000000Z"
     *     }
     *   ],
     *   "links": {"first": "http://localhost/patients/1/medical-records?page=1", "last": "http://localhost/patients/1/medical-records?page=1", "prev": null, "next": null},
     *   "meta": {"current_page": 1, "from": 1, "last_page": 1, "per_page": 15, "to": 1, "total": 1}
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 404 scenario="Not Found" {"message": "Paciente não encontrado."}
     */
    public function index(Request $request, int $patientId): AnonymousResourceCollection
    {
        Gate::authorize('create', Prontuario::class);

        $records = $this->medicalRecordService->listForPatient(
            userId: $request->user()->id,
            patientId: $patientId,
            filters: $request->only(['status', 'per_page', 'page']),
        );

        return MedicalRecordResource::collection($records);
    }

    /**
     * Create a new medical record.
     *
     * Creates a new medical record in draft status for the specified patient.
     * Optionally includes anthropometry data, physical exam findings, problem list,
     * risk scores, and conduct plan.
     *
     * @authenticated
     *
     * @group Medical Records
     *
     * @bodyParam patient_id int required The patient ID. Example: 1
     * @bodyParam type string required Consultation type (first_visit, return, pre_anesthesia, etc.). Example: first_visit
     * @bodyParam based_on_record_id int nullable ID of a previous record to base this one on. Example: null
     * @bodyParam anthropometry object nullable Anthropometry data.
     * @bodyParam anthropometry.measures object nullable Body measures.
     * @bodyParam anthropometry.measures.weight number nullable Patient weight in kg. Example: 78.5
     * @bodyParam anthropometry.measures.height number nullable Patient height in cm. Example: 175
     * @bodyParam anthropometry.measures.bmi number nullable Body mass index. Example: 25.6
     * @bodyParam anthropometry.blood_pressure object nullable Blood pressure readings.
     * @bodyParam anthropometry.blood_pressure.right_arm object nullable Right arm BP.
     * @bodyParam anthropometry.blood_pressure.right_arm.sitting object nullable Sitting BP right arm.
     * @bodyParam anthropometry.blood_pressure.right_arm.sitting.systolic int nullable Systolic pressure. Example: 120
     * @bodyParam anthropometry.blood_pressure.right_arm.sitting.diastolic int nullable Diastolic pressure. Example: 80
     * @bodyParam physical_exam object nullable Physical exam findings as a key-value object. Example: {"cardiac": "Ritmo regular em 2 tempos, sem sopros."}
     * @bodyParam problem_list object nullable Patient problem list. Example: null
     * @bodyParam risk_scores object nullable Risk scores (required for pre-anesthesia type). Example: null
     * @bodyParam conduct object nullable Clinical conduct plan. Example: null
     *
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 1,
     *     "patient_id": 1,
     *     "doctor_id": 1,
     *     "type": "first_visit",
     *     "status": "draft",
     *     "based_on_record_id": null,
     *     "anthropometry": {
     *       "measures": {"weight": 78.5, "height": 175, "bmi": 25.6, "bmi_classification": "normal"},
     *       "blood_pressure": {"right_arm": {"sitting": {"systolic": 120, "diastolic": 80}}}
     *     },
     *     "physical_exam": {"cardiac": "Ritmo regular em 2 tempos, sem sopros."},
     *     "problem_list": null,
     *     "risk_scores": null,
     *     "conduct": null,
     *     "finalized_at": null,
     *     "created_at": "2026-03-12T14:30:00.000000Z",
     *     "updated_at": "2026-03-12T14:30:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Paciente não encontrado."}
     * @response 422 scenario="Validation Error" {"message": "O tipo de consulta é obrigatório.", "errors": {"type": ["O tipo de consulta é obrigatório."]}}
     */
    public function store(StoreMedicalRecordRequest $request): JsonResponse
    {
        Gate::authorize('create', Prontuario::class);

        $prontuario = $this->medicalRecordService->create(
            userId: $request->user()->id,
            dto: CreateMedicalRecordDTO::fromRequest($request),
        );

        return (new MedicalRecordResource($prontuario))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Retrieve a single medical record.
     *
     * Returns the full details of a medical record, including anthropometry data,
     * prescriptions, and the associated patient.
     *
     * @authenticated
     *
     * @group Medical Records
     *
     * @urlParam id int required The medical record ID. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "patient_id": 1,
     *     "doctor_id": 1,
     *     "type": "first_visit",
     *     "status": "draft",
     *     "based_on_record_id": null,
     *     "anthropometry": {
     *       "measures": {"weight": 78.5, "height": 175, "bmi": 25.6, "bmi_classification": "normal"},
     *       "blood_pressure": {"right_arm": {"sitting": {"systolic": 120, "diastolic": 80}}}
     *     },
     *     "physical_exam": {"cardiac": "Ritmo regular em 2 tempos, sem sopros."},
     *     "problem_list": null,
     *     "risk_scores": null,
     *     "conduct": null,
     *     "finalized_at": null,
     *     "created_at": "2026-03-12T14:30:00.000000Z",
     *     "updated_at": "2026-03-12T14:30:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     */
    public function show(Request $request, int $id): MedicalRecordResource
    {
        $prontuario = $this->medicalRecordService->findForUser(
            userId: $request->user()->id,
            id: $id,
        );

        Gate::authorize('view', $prontuario);

        return new MedicalRecordResource($prontuario);
    }

    /**
     * Update a draft medical record.
     *
     * Updates an existing medical record that is still in draft status.
     * Finalized records cannot be modified.
     *
     * @authenticated
     *
     * @group Medical Records
     *
     * @urlParam id int required The medical record ID. Example: 1
     *
     * @bodyParam anthropometry object nullable Updated anthropometry data.
     * @bodyParam anthropometry.measures object nullable Body measures.
     * @bodyParam anthropometry.measures.weight number nullable Patient weight in kg. Example: 80.0
     * @bodyParam anthropometry.blood_pressure object nullable Blood pressure readings.
     * @bodyParam physical_exam object nullable Updated physical exam findings. Example: {"cardiac": "Ritmo regular em 2 tempos, sem sopros.", "pulmonar": "Murmúrio vesicular presente bilateralmente."}
     * @bodyParam problem_list object nullable Updated patient problem list. Example: null
     * @bodyParam risk_scores object nullable Updated risk scores. Example: null
     * @bodyParam conduct object nullable Updated clinical conduct plan. Example: null
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "patient_id": 1,
     *     "doctor_id": 1,
     *     "type": "first_visit",
     *     "status": "draft",
     *     "based_on_record_id": null,
     *     "anthropometry": {
     *       "measures": {"weight": 80.0, "height": 175, "bmi": 26.1, "bmi_classification": "overweight"},
     *       "blood_pressure": {"right_arm": {"sitting": {"systolic": 118, "diastolic": 78}}}
     *     },
     *     "physical_exam": {"cardiac": "Ritmo regular em 2 tempos, sem sopros.", "pulmonar": "Murmúrio vesicular presente bilateralmente."},
     *     "problem_list": null,
     *     "risk_scores": null,
     *     "conduct": null,
     *     "finalized_at": null,
     *     "created_at": "2026-03-12T14:30:00.000000Z",
     *     "updated_at": "2026-03-12T15:00:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível modificar um prontuário finalizado."}
     * @response 422 scenario="Validation Error" {"message": "O campo peso deve ser um número.", "errors": {"anthropometry.measures.weight": ["O campo peso deve ser um número."]}}
     */
    public function update(UpdateMedicalRecordRequest $request, int $id): MedicalRecordResource
    {
        $prontuario = $this->medicalRecordService->findForUser(
            userId: $request->user()->id,
            id: $id,
        );

        Gate::authorize('update', $prontuario);

        $prontuario = $this->medicalRecordService->update(
            userId: $request->user()->id,
            id: $id,
            dto: UpdateMedicalRecordDTO::fromRequest($request),
        );

        return new MedicalRecordResource($prontuario);
    }

    /**
     * Finalize a medical record.
     *
     * Marks a draft medical record as finalized, making it immutable.
     * Finalized records cannot be edited or deleted.
     *
     * @authenticated
     *
     * @group Medical Records
     *
     * @urlParam id int required The medical record ID. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "patient_id": 1,
     *     "doctor_id": 1,
     *     "type": "first_visit",
     *     "status": "finalized",
     *     "based_on_record_id": null,
     *     "anthropometry": {
     *       "measures": {"weight": 78.5, "height": 175, "bmi": 25.6, "bmi_classification": "normal"},
     *       "blood_pressure": {"right_arm": {"sitting": {"systolic": 120, "diastolic": 80}}}
     *     },
     *     "physical_exam": {"cardiac": "Ritmo regular em 2 tempos, sem sopros."},
     *     "problem_list": null,
     *     "risk_scores": null,
     *     "conduct": null,
     *     "finalized_at": "2026-03-12T16:00:00.000000Z",
     *     "created_at": "2026-03-12T14:30:00.000000Z",
     *     "updated_at": "2026-03-12T16:00:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Este prontuário já foi finalizado."}
     */
    public function finalize(Request $request, int $id): MedicalRecordResource
    {
        $prontuario = $this->medicalRecordService->findForUser(
            userId: $request->user()->id,
            id: $id,
        );

        Gate::authorize('finalize', $prontuario);

        $prontuario = $this->medicalRecordService->finalize(
            userId: $request->user()->id,
            id: $id,
        );

        return new MedicalRecordResource($prontuario);
    }

    /**
     * Delete a draft medical record.
     *
     * Permanently deletes a medical record that is still in draft status.
     * Finalized records cannot be deleted.
     *
     * @authenticated
     *
     * @group Medical Records
     *
     * @urlParam id int required The medical record ID. Example: 1
     *
     * @response 200 scenario="Success" {"message": "Prontuário excluído com sucesso."}
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Prontuário não encontrado."}
     * @response 409 scenario="Conflict" {"message": "Não é possível excluir um prontuário finalizado."}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $prontuario = $this->medicalRecordService->findForUser(
            userId: $request->user()->id,
            id: $id,
        );

        Gate::authorize('delete', $prontuario);

        $this->medicalRecordService->delete(
            userId: $request->user()->id,
            id: $id,
        );

        return response()->json(['message' => 'Prontuário excluído com sucesso.']);
    }
}
