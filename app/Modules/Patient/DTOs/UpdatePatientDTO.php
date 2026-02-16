<?php

declare(strict_types=1);

namespace App\Modules\Patient\DTOs;

use App\Modules\Patient\Enums\BloodType;
use App\Modules\Patient\Enums\Gender;
use App\Modules\Patient\Enums\HabitIntensity;
use App\Modules\Patient\Enums\PatientStatus;
use App\Modules\Patient\Http\Requests\UpdatePatientRequest;
use Illuminate\Support\Carbon;

/**
 * Data Transfer Object for updating an existing patient.
 */
final readonly class UpdatePatientDTO
{
    /**
     * @param  string[]  $allergies
     * @param  string[]  $chronicConditions
     */
    public function __construct(
        public string $name,
        public string $cpf,
        public string $phone,
        public ?string $email,
        public Carbon $birthDate,
        public Gender $gender,
        public ?BloodType $bloodType,
        public ?HabitIntensity $smokingHistory,
        public ?HabitIntensity $alcoholHistory,
        public PatientStatus $status,
        public array $allergies,
        public array $chronicConditions,
        public ?AddressDTO $address,
    ) {}

    public static function fromRequest(UpdatePatientRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            cpf: $validated['cpf'],
            phone: $validated['phone'],
            email: $validated['email'] ?? null,
            birthDate: Carbon::parse($validated['birth_date']),
            gender: Gender::fromFrontend($validated['gender']),
            bloodType: isset($validated['blood_type']) ? BloodType::from($validated['blood_type']) : null,
            smokingHistory: isset($validated['medical_history']['smoking']) ? HabitIntensity::from($validated['medical_history']['smoking']) : null,
            alcoholHistory: isset($validated['medical_history']['alcohol']) ? HabitIntensity::from($validated['medical_history']['alcohol']) : null,
            status: PatientStatus::from($validated['status'] ?? 'active'),
            allergies: $validated['allergies'] ?? [],
            chronicConditions: $validated['chronic_conditions'] ?? [],
            address: isset($validated['address']) ? AddressDTO::fromArray($validated['address']) : null,
        );
    }
}
