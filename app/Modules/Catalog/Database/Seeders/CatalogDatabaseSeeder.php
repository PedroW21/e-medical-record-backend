<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;

final class CatalogDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PharmacySeeder::class,
            TherapeuticCategorySeeder::class,
            MagistralCatalogSeeder::class,
            InjectableCatalogSeeder::class,
            InjectableProtocolSeeder::class,
            ProblemListSeeder::class,
        ]);
    }
}
