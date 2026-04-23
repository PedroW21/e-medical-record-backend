<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Appointment\Database\Seeders\AppointmentSeeder;
use App\Modules\Auth\Database\Seeders\UserSeeder;
use App\Modules\Catalog\Database\Seeders\CatalogDatabaseSeeder;
use App\Modules\Delegation\Database\Seeders\DelegationSeeder;
use App\Modules\MedicalRecord\Database\Seeders\LabPanelSeeder;
use App\Modules\Patient\Database\Seeders\AllergySeeder;
use App\Modules\Patient\Database\Seeders\ChronicConditionSeeder;
use App\Modules\Patient\Database\Seeders\PatientSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AllergySeeder::class,
            ChronicConditionSeeder::class,
            PatientSeeder::class,
            DelegationSeeder::class,
            AppointmentSeeder::class,
            CatalogDatabaseSeeder::class,
            LabPanelSeeder::class,
        ]);
    }
}
