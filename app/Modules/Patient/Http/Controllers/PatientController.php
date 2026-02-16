<?php

declare(strict_types=1);

namespace App\Modules\Patient\Http\Controllers;

use App\Modules\Patient\Actions\CreatePatientAction;
use App\Modules\Patient\Actions\DeletePatientAction;
use App\Modules\Patient\Actions\UpdatePatientAction;
use App\Modules\Patient\DTOs\CreatePatientDTO;
use App\Modules\Patient\DTOs\UpdatePatientDTO;
use App\Modules\Patient\Http\Requests\ListPatientRequest;
use App\Modules\Patient\Http\Requests\StorePatientRequest;
use App\Modules\Patient\Http\Requests\UpdatePatientRequest;
use App\Modules\Patient\Http\Resources\PatientListResource;
use App\Modules\Patient\Http\Resources\PatientResource;
use App\Modules\Patient\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class PatientController
{
    public function __construct(
        private readonly PatientService $patientService,
        private readonly CreatePatientAction $createAction,
        private readonly UpdatePatientAction $updateAction,
        private readonly DeletePatientAction $deleteAction,
    ) {}

    public function index(ListPatientRequest $request): AnonymousResourceCollection
    {
        $patients = $this->patientService->listForUser(
            userId: $request->user()->id,
            filters: $request->validated(),
        );

        return PatientListResource::collection($patients);
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        $dto = CreatePatientDTO::fromRequest($request);

        $patient = $this->createAction->execute(
            userId: $request->user()->id,
            dto: $dto,
        );

        return (new PatientResource($patient))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $id): PatientResource
    {
        $patient = $this->patientService->findForUser(
            userId: $request->user()->id,
            patientId: $id,
        );

        Gate::authorize('view', $patient);

        return new PatientResource($patient);
    }

    public function update(UpdatePatientRequest $request, int $id): PatientResource
    {
        $patient = $this->patientService->findForUser(
            userId: $request->user()->id,
            patientId: $id,
        );

        Gate::authorize('update', $patient);

        $dto = UpdatePatientDTO::fromRequest($request);
        $patient = $this->updateAction->execute($patient, $dto);

        return new PatientResource($patient);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $patient = $this->patientService->findForUser(
            userId: $request->user()->id,
            patientId: $id,
        );

        Gate::authorize('delete', $patient);

        $this->deleteAction->execute($patient);

        return response()->json(['message' => 'Paciente excluído com sucesso.']);
    }
}
