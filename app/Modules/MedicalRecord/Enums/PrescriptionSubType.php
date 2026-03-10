<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum PrescriptionSubType: string
{
    case Allopathic = 'allopathic';
    case Magistral = 'magistral';
    case InjectableIm = 'injectable_im';
    case InjectableEv = 'injectable_ev';
    case InjectableCombined = 'injectable_combined';
    case InjectableProtocol = 'injectable_protocol';
    case Glp1 = 'glp1';
    case Steroid = 'steroid';
    case SubcutaneousImplant = 'subcutaneous_implant';
    case Ozonotherapy = 'ozonotherapy';
    case Procedure = 'procedure';

    /**
     * Subtypes that never reference medications catalog.
     *
     * @return list<self>
     */
    public static function nonMedicationTypes(): array
    {
        return [
            self::Procedure,
            self::Ozonotherapy,
            self::SubcutaneousImplant,
        ];
    }
}
