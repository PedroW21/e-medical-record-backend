<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum ExamType: string
{
    case Ecg = 'ecg';
    case Xray = 'xray';
    case FreeText = 'free-text';
    case Temperature = 'temperature';
    case HepaticElastography = 'hepatic-elastography';
    case Mapa = 'mapa';
    case Dexa = 'dexa';
    case ErgometricTest = 'ergometric-test';
    case CarotidEcodoppler = 'carotid-ecodoppler';
    case Echo = 'echo';
    case Mrpa = 'mrpa';
    case Cat = 'cat';
    case Scintigraphy = 'scintigraphy';
    case DiabeticFoot = 'diabetic-foot';

    /** Returns the fully qualified class name of the Eloquent model for this exam type. */
    public function modelClass(): string
    {
        return match ($this) {
            self::Ecg => \App\Modules\MedicalRecord\Models\ResultadoEcg::class,
            self::Xray => \App\Modules\MedicalRecord\Models\ResultadoRx::class,
            self::FreeText => \App\Modules\MedicalRecord\Models\ResultadoTextoLivre::class,
            self::Temperature => \App\Modules\MedicalRecord\Models\RegistroTemperatura::class,
            self::HepaticElastography => \App\Modules\MedicalRecord\Models\ResultadoElastografiaHepatica::class,
            self::Mapa => \App\Modules\MedicalRecord\Models\ResultadoMapa::class,
            self::Dexa => \App\Modules\MedicalRecord\Models\ResultadoDexa::class,
            self::ErgometricTest => \App\Modules\MedicalRecord\Models\ResultadoTesteErgometrico::class,
            self::CarotidEcodoppler => \App\Modules\MedicalRecord\Models\ResultadoEcodopplerCarotidas::class,
            self::Echo => \App\Modules\MedicalRecord\Models\ResultadoEcocardiograma::class,
            self::Mrpa => \App\Modules\MedicalRecord\Models\ResultadoMrpa::class,
            self::Cat => \App\Modules\MedicalRecord\Models\ResultadoCat::class,
            self::Scintigraphy => \App\Modules\MedicalRecord\Models\ResultadoCintilografia::class,
            self::DiabeticFoot => \App\Modules\MedicalRecord\Models\ResultadoPeDiabetico::class,
        };
    }

    /** Returns the fully qualified class name of the API resource for this exam type. */
    public function resourceClass(): string
    {
        return match ($this) {
            self::Ecg => \App\Modules\MedicalRecord\Http\Resources\EcgResultResource::class,
            self::Xray => \App\Modules\MedicalRecord\Http\Resources\XrayResultResource::class,
            self::FreeText => \App\Modules\MedicalRecord\Http\Resources\FreeTextResultResource::class,
            self::Temperature => \App\Modules\MedicalRecord\Http\Resources\TemperatureResultResource::class,
            self::HepaticElastography => \App\Modules\MedicalRecord\Http\Resources\HepaticElastographyResultResource::class,
            self::Mapa => \App\Modules\MedicalRecord\Http\Resources\MapaResultResource::class,
            self::Dexa => \App\Modules\MedicalRecord\Http\Resources\DexaResultResource::class,
            self::ErgometricTest => \App\Modules\MedicalRecord\Http\Resources\ErgometricTestResultResource::class,
            self::CarotidEcodoppler => \App\Modules\MedicalRecord\Http\Resources\CarotidEcodopplerResultResource::class,
            self::Echo => \App\Modules\MedicalRecord\Http\Resources\EchoResultResource::class,
            self::Mrpa => \App\Modules\MedicalRecord\Http\Resources\MrpaResultResource::class,
            self::Cat => \App\Modules\MedicalRecord\Http\Resources\CatResultResource::class,
            self::Scintigraphy => \App\Modules\MedicalRecord\Http\Resources\ScintigraphyResultResource::class,
            self::DiabeticFoot => \App\Modules\MedicalRecord\Http\Resources\DiabeticFootResultResource::class,
        };
    }

    /** Returns the Portuguese label for this exam type. */
    public function label(): string
    {
        return match ($this) {
            self::Ecg => 'Eletrocardiograma (ECG)',
            self::Xray => 'Radiografia (RX)',
            self::FreeText => 'Exame em texto livre',
            self::Temperature => 'Registro de temperatura',
            self::HepaticElastography => 'Elastografia hepática',
            self::Mapa => 'Monitorização Ambulatorial da Pressão Arterial (MAPA)',
            self::Dexa => 'Densitometria óssea (DEXA)',
            self::ErgometricTest => 'Teste ergométrico',
            self::CarotidEcodoppler => 'Ecodoppler de carótidas e vertebrais',
            self::Echo => 'Ecocardiograma',
            self::Mrpa => 'Monitorização Residencial da Pressão Arterial (MRPA)',
            self::Cat => 'Cineangiocoronariografia (CAT)',
            self::Scintigraphy => 'Cintilografia de perfusão miocárdica',
            self::DiabeticFoot => 'Triagem do pé diabético',
        };
    }

    /** Returns a Portuguese deletion success message for this exam type. */
    public function deletedMessage(): string
    {
        return match ($this) {
            self::Ecg => 'Resultado de ECG excluído com sucesso.',
            self::Xray => 'Resultado de radiografia excluído com sucesso.',
            self::FreeText => 'Resultado de exame em texto livre excluído com sucesso.',
            self::Temperature => 'Registro de temperatura excluído com sucesso.',
            self::HepaticElastography => 'Resultado de elastografia hepática excluído com sucesso.',
            self::Mapa => 'Resultado de MAPA excluído com sucesso.',
            self::Dexa => 'Resultado de DEXA excluído com sucesso.',
            self::ErgometricTest => 'Resultado de teste ergométrico excluído com sucesso.',
            self::CarotidEcodoppler => 'Resultado de ecodoppler de carótidas excluído com sucesso.',
            self::Echo => 'Resultado de ecocardiograma excluído com sucesso.',
            self::Mrpa => 'Resultado de MRPA excluído com sucesso.',
            self::Cat => 'Resultado de cineangiocoronariografia excluído com sucesso.',
            self::Scintigraphy => 'Resultado de cintilografia excluído com sucesso.',
            self::DiabeticFoot => 'Resultado de triagem do pé diabético excluído com sucesso.',
        };
    }
}
