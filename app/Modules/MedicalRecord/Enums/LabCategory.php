<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum LabCategory: string
{
    case Hematologia = 'hematologia';
    case Bioquimica = 'bioquimica';
    case Endocrinologia = 'endocrinologia';
    case Hormonal = 'hormonal';
    case Imunologia = 'imunologia';
    case Coprologia = 'coprologia';
    case Microbiologia = 'microbiologia';
    case Liquidos = 'liquidos';
    case MarcadoresTumorais = 'marcadores_tumorais';
    case Outros = 'outros';
    case Urinalise = 'urinalise';
    case Especializado = 'especializado';
}
