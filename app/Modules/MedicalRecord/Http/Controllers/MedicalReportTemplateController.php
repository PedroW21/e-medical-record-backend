<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Controllers;

use App\Modules\MedicalRecord\DTOs\CreateMedicalReportTemplateDTO;
use App\Modules\MedicalRecord\DTOs\UpdateMedicalReportTemplateDTO;
use App\Modules\MedicalRecord\Http\Requests\StoreMedicalReportTemplateRequest;
use App\Modules\MedicalRecord\Http\Requests\UpdateMedicalReportTemplateRequest;
use App\Modules\MedicalRecord\Http\Resources\MedicalReportTemplateResource;
use App\Modules\MedicalRecord\Services\MedicalReportTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class MedicalReportTemplateController
{
    public function __construct(
        private readonly MedicalReportTemplateService $medicalReportTemplateService,
    ) {}

    /**
     * List medical report templates for the authenticated user.
     *
     * @authenticated
     *
     * @group Medical Report Templates
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Atestado padrão",
     *       "body_template": "Atesto para os devidos fins que o(a) paciente {{NOME_PACIENTE}}, portador(a) do diagnóstico {{CID_10}}, encontra-se sob meus cuidados médicos.",
     *       "created_at": "2026-03-10T10:00:00.000000Z",
     *       "updated_at": "2026-03-10T10:00:00.000000Z"
     *     }
     *   ]
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $templates = $this->medicalReportTemplateService->listForUser(
            userId: $request->user()->id,
        );

        return MedicalReportTemplateResource::collection($templates);
    }

    /**
     * Create a new medical report template.
     *
     * @authenticated
     *
     * @group Medical Report Templates
     *
     * @bodyParam name string required The template name. Example: Atestado padrão
     * @bodyParam body_template string required The template body with placeholders. Example: Atesto para os devidos fins que o(a) paciente {{NOME_PACIENTE}}, portador(a) do diagnóstico {{CID_10}}, encontra-se sob meus cuidados médicos.
     *
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 1,
     *     "name": "Atestado padrão",
     *     "body_template": "Atesto para os devidos fins que o(a) paciente {{NOME_PACIENTE}}, portador(a) do diagnóstico {{CID_10}}, encontra-se sob meus cuidados médicos.",
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T10:00:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 422 scenario="Validation Error" {"message": "O campo nome é obrigatório.", "errors": {"name": ["O campo nome é obrigatório."]}}
     */
    public function store(StoreMedicalReportTemplateRequest $request): JsonResponse
    {
        $dto = CreateMedicalReportTemplateDTO::fromRequest($request);
        $template = $this->medicalReportTemplateService->create(
            userId: $request->user()->id,
            dto: $dto,
        );

        return (new MedicalReportTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a medical report template.
     *
     * @authenticated
     *
     * @group Medical Report Templates
     *
     * @urlParam id int required The template ID. Example: 1
     *
     * @bodyParam name string The template name. Example: Atestado atualizado
     * @bodyParam body_template string The template body. Example: Declaramos que o(a) paciente {{NOME_PACIENTE}} esteve sob nossos cuidados.
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "name": "Atestado atualizado",
     *     "body_template": "Declaramos que o(a) paciente {{NOME_PACIENTE}} esteve sob nossos cuidados.",
     *     "created_at": "2026-03-10T10:00:00.000000Z",
     *     "updated_at": "2026-03-10T10:30:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Modelo de relatório médico não encontrado."}
     */
    public function update(UpdateMedicalReportTemplateRequest $request, int $id): MedicalReportTemplateResource
    {
        $template = $this->medicalReportTemplateService->findOrFail($id);

        Gate::authorize('update', $template);

        $dto = UpdateMedicalReportTemplateDTO::fromRequest($request);
        $template = $this->medicalReportTemplateService->update($template, $dto);

        return new MedicalReportTemplateResource($template);
    }

    /**
     * Delete a medical report template.
     *
     * @authenticated
     *
     * @group Medical Report Templates
     *
     * @urlParam id int required The template ID. Example: 1
     *
     * @response 200 scenario="Success" {"message": "Modelo de relatório médico excluído com sucesso."}
     * @response 401 scenario="Unauthenticated" {"message": "Token inválido."}
     * @response 403 scenario="Forbidden" {"message": "Esta ação não é autorizada."}
     * @response 404 scenario="Not Found" {"message": "Modelo de relatório médico não encontrado."}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $template = $this->medicalReportTemplateService->findOrFail($id);

        Gate::authorize('delete', $template);

        $this->medicalReportTemplateService->delete($template);

        return response()->json(['message' => 'Modelo de relatório médico excluído com sucesso.']);
    }
}
