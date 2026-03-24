<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\DTOs\CreateExamRequestModelDTO;
use App\Modules\MedicalRecord\DTOs\UpdateExamRequestModelDTO;
use App\Modules\MedicalRecord\Http\Requests\StoreExamRequestModelRequest;
use App\Modules\MedicalRecord\Http\Requests\UpdateExamRequestModelRequest;
use App\Modules\MedicalRecord\Http\Resources\ExamRequestModelResource;
use App\Modules\MedicalRecord\Services\ExamRequestModelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ExamRequestModelController
{
    public function __construct(
        private readonly ExamRequestModelService $examRequestModelService,
    ) {}

    /**
     * List exam request models for the authenticated user.
     *
     * @authenticated
     *
     * @group Exam Request Models
     *
     * @queryParam category string Filter by category. Example: Rotina
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Rotina anual",
     *       "category": "Rotina",
     *       "items": [{"id": "hemograma", "name": "Hemograma completo", "tuss_code": "40302566"}],
     *       "created_at": "2026-03-10T10:00:00.000000Z",
     *       "updated_at": "2026-03-10T10:00:00.000000Z"
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $category = $request->query('category');

        $models = $this->examRequestModelService->listForUser(
            userId: $request->user()->id,
            category: is_string($category) ? $category : null,
        );

        return ExamRequestModelResource::collection($models);
    }

    /**
     * Create a new exam request model.
     *
     * @authenticated
     *
     * @group Exam Request Models
     *
     * @bodyParam name string required The model name. Example: Rotina anual
     * @bodyParam category string nullable The model category. Example: Rotina
     * @bodyParam items array required List of exam items (min 1). Example: [{"id":"hemograma","name":"Hemograma completo","tuss_code":"40302566"}]
     *
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 1,
     *     "name": "Rotina anual",
     *     "category": "Rotina",
     *     "items": [{"id": "hemograma", "name": "Hemograma completo", "tuss_code": "40302566"}],
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T10:00:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 422 scenario="Validation Error" {"message": "O campo nome é obrigatório.", "errors": {"name": ["O campo nome é obrigatório."]}}
     */
    public function store(StoreExamRequestModelRequest $request): JsonResponse
    {
        $dto = CreateExamRequestModelDTO::fromRequest($request);
        $model = $this->examRequestModelService->create(
            userId: $request->user()->id,
            dto: $dto,
        );

        return (new ExamRequestModelResource($model))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an exam request model.
     *
     * @authenticated
     *
     * @group Exam Request Models
     *
     * @urlParam id int required The model ID. Example: 1
     *
     * @bodyParam name string The model name. Example: Rotina semestral
     * @bodyParam category string nullable The model category. Example: Rotina
     * @bodyParam items array List of exam items. Example: [{"id":"glicemia","name":"Glicemia em jejum","tuss_code":"40302213"}]
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Rotina semestral",
     *     "category": "Rotina",
     *     "items": [{"id": "glicemia", "name": "Glicemia em jejum", "tuss_code": "40302213"}],
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T10:30:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Modelo de solicitação de exame não encontrado."}
     */
    public function update(UpdateExamRequestModelRequest $request, int $id): ExamRequestModelResource
    {
        $model = $this->examRequestModelService->findOrFail($id);

        Gate::authorize('update', $model);

        $dto = UpdateExamRequestModelDTO::fromRequest($request);
        $model = $this->examRequestModelService->update($model, $dto);

        return new ExamRequestModelResource($model);
    }

    /**
     * Delete an exam request model.
     *
     * @authenticated
     *
     * @group Exam Request Models
     *
     * @urlParam id int required The model ID. Example: 1
     *
     * @response 200 scenario="Success" {"message": "Modelo de solicitação de exame excluído com sucesso."}
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Modelo de solicitação de exame não encontrado."}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $model = $this->examRequestModelService->findOrFail($id);

        Gate::authorize('delete', $model);

        $this->examRequestModelService->delete($model);

        return response()->json(['message' => 'Modelo de solicitação de exame excluído com sucesso.']);
    }
}
