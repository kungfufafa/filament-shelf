<?php

namespace Database\Seeders;

use App\Models\BusinessEntity;
use Illuminate\Database\Seeder;

class BusinessEntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entities = [
            ['name' => 'MAJU', 'format' => '120920.MT/'],
            ['name' => 'MKLI', 'format' => '1210118.MKLI/'],
            ['name' => 'CV.CS', 'format' => '221218.CS/'],
            ['name' => 'TOP', 'format' => '191415.TOP/'],
            ['name' => 'RISM', 'format' => '1781812.RISM/'],
        ];

        foreach ($entities as $entity) {
            BusinessEntity::updateOrCreate(['name' => $entity['name']], $entity);
        }
    }
}
