<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            BusinessEntitySeeder::class,
            JobTitleSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            AssetLocationSeeder::class,
            AssetSeeder::class,
        ]);
    }
}
