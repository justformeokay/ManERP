<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Industry-standard positions with 3-4 letter codes.
     */
    private const POSITIONS = [
        ['code' => 'DIR',  'name' => 'Direktur'],
        ['code' => 'MGR',  'name' => 'Manager'],
        ['code' => 'SPV',  'name' => 'Supervisor'],
        ['code' => 'STF',  'name' => 'Staff'],
        ['code' => 'OPR',  'name' => 'Operator'],
        ['code' => 'TECH', 'name' => 'Technician'],
    ];

    public function run(): void
    {
        foreach (self::POSITIONS as $pos) {
            Position::updateOrCreate(
                ['code' => $pos['code']],
                ['name' => $pos['name'], 'is_active' => true]
            );
        }
    }
}
