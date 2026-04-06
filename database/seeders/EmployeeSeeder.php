<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Models\Shift;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    /**
     * Total employees to seed.
     */
    private const TOTAL_EMPLOYEES = 50;

    /**
     * Percentage of employees that get linked User accounts.
     */
    private const USER_LINKAGE_PERCENT = 20;

    /**
     * Indonesian departments commonly found in manufacturing/ERP.
     */
    private const DEPARTMENTS = [
        'Keuangan', 'Akuntansi', 'Produksi', 'Gudang',
        'HRD', 'IT', 'Penjualan', 'Pembelian',
        'QC', 'Logistik', 'Marketing', 'Administrasi',
    ];

    /**
     * Positions per department for realistic data.
     */
    private const POSITIONS = [
        'Keuangan'     => ['Finance Manager', 'Finance Staff', 'Kasir', 'AR/AP Staff'],
        'Akuntansi'    => ['Chief Accountant', 'Accounting Staff', 'Tax Staff', 'Junior Accountant'],
        'Produksi'     => ['Production Manager', 'Supervisor Produksi', 'Operator Mesin', 'Teknisi'],
        'Gudang'       => ['Warehouse Manager', 'Warehouse Staff', 'Forklift Operator', 'Stock Keeper'],
        'HRD'          => ['HR Manager', 'HR Staff', 'Recruitment Officer', 'Payroll Staff'],
        'IT'           => ['IT Manager', 'Developer', 'System Administrator', 'IT Support'],
        'Penjualan'    => ['Sales Manager', 'Sales Executive', 'Account Manager', 'Sales Admin'],
        'Pembelian'    => ['Purchasing Manager', 'Buyer', 'Procurement Staff', 'Purchasing Admin'],
        'QC'           => ['QC Manager', 'QC Inspector', 'QA Staff', 'Lab Analyst'],
        'Logistik'     => ['Logistics Manager', 'Dispatcher', 'Driver', 'Admin Logistik'],
        'Marketing'    => ['Marketing Manager', 'Digital Marketer', 'Content Creator', 'Brand Executive'],
        'Administrasi' => ['GA Manager', 'Admin Staff', 'Resepsionis', 'Office Boy'],
    ];

    /**
     * Indonesian full names (male + female, realistic).
     */
    private const NAMES = [
        'Budi Santoso', 'Siti Nurhasanah', 'Agus Prasetyo', 'Dewi Lestari',
        'Hendra Wijaya', 'Ratna Sari', 'Bambang Susilo', 'Ani Rahmawati',
        'Eko Purwanto', 'Sri Wahyuni', 'Doni Saputra', 'Rina Fitriani',
        'Wahyu Hidayat', 'Lina Marlina', 'Rizky Ramadhan', 'Yuni Astuti',
        'Fajar Nugroho', 'Dian Permatasari', 'Arif Wicaksono', 'Mega Puspita',
        'Teguh Firmansyah', 'Novi Anggraini', 'Rudi Hartono', 'Wulan Sari',
        'Bayu Setiawan', 'Putri Handayani', 'Irfan Maulana', 'Sari Indah',
        'Adi Nugraha', 'Fitri Amelia', 'Yoga Pratama', 'Endang Susilowati',
        'Dimas Aditya', 'Lia Kusuma', 'Rendi Saputra', 'Nur Fadilah',
        'Gilang Permana', 'Intan Maharani', 'Fauzi Rahman', 'Tika Wulandari',
        'Hadi Purnomo', 'Ayu Lestari', 'Joko Widodo', 'Maya Anggraeni',
        'Surya Darma', 'Nita Rosita', 'Andri Firmansyah', 'Wati Sumarni',
        'Prasetyo Budi', 'Kartini Dewi',
    ];

    /**
     * PTKP statuses with realistic distribution weights.
     */
    private const PTKP_DISTRIBUTION = [
        'TK/0' => 25,   // 25% — single, no dependents
        'K/0'  => 20,   // 20% — married, no children
        'K/1'  => 20,   // 20% — married, 1 child
        'K/2'  => 15,   // 15% — married, 2 children
        'TK/1' => 8,    // 8%  — single, 1 dependent
        'TK/2' => 5,    // 5%
        'K/3'  => 5,    // 5%
        'TK/3' => 2,    // 2%
    ];

    /**
     * Salary ranges per seniority tier (in Rupiah, as string for bcmath).
     */
    private const SALARY_TIERS = [
        'junior'  => ['basic_min' => '4500000', 'basic_max' => '7000000', 'allowance_max' => '1500000'],
        'mid'     => ['basic_min' => '7000000', 'basic_max' => '12000000', 'allowance_max' => '3000000'],
        'senior'  => ['basic_min' => '12000000', 'basic_max' => '25000000', 'allowance_max' => '5000000'],
        'manager' => ['basic_min' => '20000000', 'basic_max' => '45000000', 'allowance_max' => '8000000'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure banks exist
        $this->call(BankSeeder::class);

        // Ensure shifts exist, create defaults if none
        $this->ensureShiftsExist();

        $bankIds  = Bank::where('is_active', true)->pluck('id')->toArray();
        $shiftIds = Shift::where('is_active', true)->pluck('id')->toArray();

        // Pre-build weighted PTKP pool
        $ptkpPool = $this->buildWeightedPool(self::PTKP_DISTRIBUTION);

        // Determine which indices get user accounts (20%)
        $linkedIndices = array_rand(
            array_flip(range(0, self::TOTAL_EMPLOYEES - 1)),
            (int) ceil(self::TOTAL_EMPLOYEES * self::USER_LINKAGE_PERCENT / 100)
        );
        if (!is_array($linkedIndices)) {
            $linkedIndices = [$linkedIndices];
        }
        $linkedIndices = array_flip($linkedIndices);

        // Login as system user for audit trail
        $admin = User::where('role', 'admin')->first();

        $created = 0;

        for ($i = 0; $i < self::TOTAL_EMPLOYEES; $i++) {
            $name       = self::NAMES[$i];
            $nik        = $this->generateNik($i);
            $department = self::DEPARTMENTS[$i % count(self::DEPARTMENTS)];
            $position   = $this->pickPosition($department, $i);
            $ptkp       = $ptkpPool[$i % count($ptkpPool)];
            $joinDate   = now()->subDays(rand(30, 730))->format('Y-m-d');
            $bankId     = $bankIds[array_rand($bankIds)];
            $shiftId    = $shiftIds[array_rand($shiftIds)];
            $tier       = $this->determineTier($position);

            // Linked user account (20%)
            $userId = null;
            if (isset($linkedIndices[$i])) {
                $userId = $this->createLinkedUser($name, $i, $admin);
            }

            $employeeData = [
                'name'               => $name,
                'position'           => $position,
                'department'         => $department,
                'join_date'          => $joinDate,
                'status'             => 'active',
                'npwp'               => $this->generateNpwp($i),
                'ptkp_status'        => $ptkp,
                // ter_category is auto-derived by model's booted() hook
                'bpjs_tk_number'     => $this->generateBpjsNumber('TK', $i),
                'bpjs_kes_number'    => $this->generateBpjsNumber('KS', $i),
                'bank_id'            => $bankId,
                'bank_account_number' => $this->generateAccountNumber($i),
                'bank_account_name'  => $name,
                'shift_id'           => $shiftId,
                'user_id'            => $userId,
            ];

            $employee = Employee::updateOrCreate(
                ['nik' => $nik],
                $employeeData
            );

            // Audit log with full 8-field HMAC payload (F-14 / F-15 compliance)
            if ($admin) {
                auth()->setUser($admin);
            }

            AuditLogService::log(
                'employee',
                $employee->wasRecentlyCreated ? 'create' : 'update',
                "Employee #{$employee->id} ({$name}) " . ($employee->wasRecentlyCreated ? 'created' : 'updated') . ' via seeder',
                $employee->wasRecentlyCreated ? null : $employee->getOriginal(),
                $employee->toArray(),
                $employee
            );

            // Create salary structure with bcmath precision
            $this->createSalaryStructure($employee, $tier, $joinDate);

            $created++;
        }

        $this->command->info("✓ {$created} employees seeded with audit logs & salary structures.");
    }

    // ── Generators ───────────────────────────────────────────

    /**
     * Generate deterministic 16-digit NIK (province + city + district + dob + sequence).
     * Must be deterministic for idempotent updateOrCreate.
     */
    private function generateNik(int $index): string
    {
        $seed     = $index + 1;
        $province = str_pad((string) (11 + ($seed % 84)), 2, '0', STR_PAD_LEFT);
        $city     = str_pad((string) (1 + ($seed % 99)), 2, '0', STR_PAD_LEFT);
        $district = str_pad((string) (1 + ($seed % 40)), 2, '0', STR_PAD_LEFT);
        $dobDay   = str_pad((string) (1 + ($seed % 28)), 2, '0', STR_PAD_LEFT);
        $dobMonth = str_pad((string) (1 + ($seed % 12)), 2, '0', STR_PAD_LEFT);
        $dobYear  = str_pad((string) (80 + ($seed % 20)), 2, '0', STR_PAD_LEFT);
        $sequence = str_pad((string) $seed, 4, '0', STR_PAD_LEFT);

        return "{$province}{$city}{$district}{$dobDay}{$dobMonth}{$dobYear}{$sequence}";
    }

    /**
     * Generate 15-digit NPWP (format: XX.XXX.XXX.X-XXX.XXX).
     */
    private function generateNpwp(int $index): string
    {
        $prefix   = str_pad((string) rand(10, 99), 2, '0', STR_PAD_LEFT);
        $mid      = str_pad((string) rand(100, 999), 3, '0', STR_PAD_LEFT);
        $mid2     = str_pad((string) rand(100, 999), 3, '0', STR_PAD_LEFT);
        $check    = (string) rand(1, 9);
        $kpp      = str_pad((string) rand(100, 999), 3, '0', STR_PAD_LEFT);
        $branch   = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);

        return "{$prefix}.{$mid}.{$mid2}.{$check}-{$kpp}.{$branch}";
    }

    /**
     * Generate BPJS number (11-13 digits).
     */
    private function generateBpjsNumber(string $prefix, int $index): string
    {
        $length = rand(11, 13);
        $numericPart = str_pad((string) ($index + 1), $length - 2, '0', STR_PAD_LEFT);

        return $prefix . $numericPart;
    }

    /**
     * Generate bank account number (10-12 digits).
     */
    private function generateAccountNumber(int $index): string
    {
        $length = rand(10, 12);
        $base   = str_pad((string) ($index + 10001), $length, '0', STR_PAD_LEFT);

        return $base;
    }

    // ── Helpers ──────────────────────────────────────────────

    /**
     * Ensure at least 3 shifts exist (Pagi, Siang, Malam).
     */
    private function ensureShiftsExist(): void
    {
        if (Shift::count() > 0) {
            return;
        }

        $defaults = [
            ['name' => 'Pagi',   'start_time' => '07:00', 'end_time' => '15:00', 'is_night_shift' => false, 'night_shift_bonus' => '0.00'],
            ['name' => 'Siang',  'start_time' => '15:00', 'end_time' => '23:00', 'is_night_shift' => false, 'night_shift_bonus' => '0.00'],
            ['name' => 'Malam',  'start_time' => '23:00', 'end_time' => '07:00', 'is_night_shift' => true,  'night_shift_bonus' => '50000.00'],
        ];

        foreach ($defaults as $shift) {
            Shift::create(array_merge($shift, [
                'grace_period' => 15,
                'is_active'    => true,
            ]));
        }

        $this->command->info('  → Default shifts (Pagi/Siang/Malam) created.');
    }

    /**
     * Pick a position for the department, cycling through available options.
     */
    private function pickPosition(string $department, int $index): string
    {
        $positions = self::POSITIONS[$department] ?? ['Staff'];
        return $positions[$index % count($positions)];
    }

    /**
     * Determine salary tier based on position keyword.
     */
    private function determineTier(string $position): string
    {
        $lower = strtolower($position);

        if (str_contains($lower, 'manager') || str_contains($lower, 'chief')) {
            return 'manager';
        }
        if (str_contains($lower, 'supervisor') || str_contains($lower, 'senior') || str_contains($lower, 'lead')) {
            return 'senior';
        }
        if (str_contains($lower, 'staff') || str_contains($lower, 'executive') || str_contains($lower, 'buyer')) {
            return 'mid';
        }

        return 'junior';
    }

    /**
     * Build a weighted pool array from distribution weights.
     */
    private function buildWeightedPool(array $distribution): array
    {
        $pool = [];
        foreach ($distribution as $value => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $value;
            }
        }
        shuffle($pool);

        return $pool;
    }

    /**
     * Create a linked User account for ~20% of employees.
     * Disable the UserObserver auto-employee-creation to avoid duplicates.
     */
    private function createLinkedUser(string $name, int $index, ?User $admin): int
    {
        $email = strtolower(str_replace(' ', '.', $name)) . '.' . ($index + 1) . '@manerp.com';

        $user = User::withoutEvents(function () use ($name, $email) {
            return User::updateOrCreate(
                ['email' => $email],
                [
                    'name'     => $name,
                    'password' => Hash::make('password'),
                    'role'     => User::ROLE_STAFF,
                    'status'   => User::STATUS_ACTIVE,
                    'permissions' => [
                        'hr.view' => true,
                    ],
                ]
            );
        });

        if ($admin) {
            auth()->setUser($admin);
        }

        AuditLogService::log(
            'user',
            $user->wasRecentlyCreated ? 'create' : 'update',
            "Staff user #{$user->id} ({$name}) " . ($user->wasRecentlyCreated ? 'created' : 'updated') . ' via seeder',
            null,
            $user->makeHidden(['password', 'remember_token'])->toArray(),
            $user
        );

        return $user->id;
    }

    /**
     * Create salary structure using bcmath for all monetary values.
     */
    private function createSalaryStructure(Employee $employee, string $tier, string $joinDate): void
    {
        $range = self::SALARY_TIERS[$tier];

        // Generate salary using bcmath
        $basicSalary = $this->bcRandBetween($range['basic_min'], $range['basic_max']);
        // Round to nearest 100,000
        $basicSalary = bcmul(bcdiv($basicSalary, '100000', 0), '100000', 2);

        $fixedAllowance    = $this->bcRandBetween('0', $range['allowance_max']);
        $fixedAllowance    = bcmul(bcdiv($fixedAllowance, '50000', 0), '50000', 2);

        $mealAllowance     = bcmul((string) rand(1, 4), '500000', 2);  // 500k – 2M
        $transportAllowance = bcmul((string) rand(1, 6), '250000', 2); // 250k – 1.5M

        // Overtime rate = basic / 173 hours (standard formula)
        $overtimeRate = bcdiv($basicSalary, '173', 2);

        SalaryStructure::updateOrCreate(
            [
                'employee_id'    => $employee->id,
                'effective_date' => $joinDate,
            ],
            [
                'basic_salary'       => $basicSalary,
                'fixed_allowance'    => $fixedAllowance,
                'meal_allowance'     => $mealAllowance,
                'transport_allowance' => $transportAllowance,
                'overtime_rate'      => $overtimeRate,
                'is_active'          => true,
            ]
        );
    }

    /**
     * Generate a random bcmath value between min and max.
     */
    private function bcRandBetween(string $min, string $max): string
    {
        $minInt = (int) $min;
        $maxInt = (int) $max;

        return (string) rand($minInt, $maxInt);
    }
}
