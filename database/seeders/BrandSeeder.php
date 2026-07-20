<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            'Apple', 'Samsung', 'Sony', 'Lenovo', 'HP', 'Dell', 'Asus', 'Acer', 'Xiaomi',
            'IKEA', 'Informa', 'Chitose',
            'Toyota', 'Honda', 'Daihatsu',
            'Sharp', 'Panasonic',
        ];

        foreach ($brands as $name) {
            Brand::firstOrCreate(['name' => $name]);
        }
    }
}
