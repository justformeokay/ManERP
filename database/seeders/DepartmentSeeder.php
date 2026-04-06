<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Industry-standard departments with 3-4 letter codes.
     */
    private const DEPARTMENTS = [
        ['code' => 'PROD', 'name' => 'Produksi'],
        ['code' => 'PPIC', 'name' => 'PPIC'],
        ['code' => 'QUAL', 'name' => 'Quality Control'],
        ['code' => 'MAIN', 'name' => 'Maintenance'],
        ['code' => 'WHSE', 'name' => 'Gudang'],
        ['code' => 'HRGA', 'name' => 'HR & GA'],
        ['code' => 'FINA', 'name' => 'Finance'],
        ['code' => 'PURC', 'name' => 'Purchasing'],
        ['code' => 'SALE', 'name' => 'Sales'],
        ['code' => 'ITSY', 'name' => 'IT System'],
    ];

    public function run(): void
    {
        foreach (self::DEPARTMENTS as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name'], 'is_active' => true]
            );
        }
    }
}
