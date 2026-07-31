<?php

namespace Database\Seeders;

use App\Models\AssetLocation;
use Illuminate\Database\Seeder;

class AssetLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Kantor Pusat',
                'address' => 'Jl. Jendral Sudirman No. 123, Jakarta',
                'description' => 'Kantor pusat perusahaan',
            ],
            [
                'name' => 'Gudang Utama',
                'address' => 'Jl. Industri Raya No. 5, Bekasi',
                'description' => 'Gudang penyimpanan barang',
            ],
            [
                'name' => 'Cabang Bandung',
                'address' => 'Jl. Braga No. 90, Bandung',
                'description' => 'Kantor cabang di Bandung',
            ],
        ];

        foreach ($locations as $location) {
            AssetLocation::updateOrCreate(['name' => $location['name']], $location);
        }
    }
}
