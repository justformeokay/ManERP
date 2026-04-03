<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Employees ────────────────────────────────────────────
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 20)->unique();
            $table->string('name');
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->date('join_date');
            $table->date('resign_date')->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('bpjs_tk_number', 30)->nullable();
            $table->string('bpjs_kes_number', 30)->nullable();
            $table->string('ptkp_status', 10);            // TK/0, K/0, K/1, K/2, K/3
            $table->char('ter_category', 1);               // A, B, or C
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number', 30)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('department');
            $table->index('status');
        });

        // ── Salary Structures ────────────────────────────────────
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('fixed_allowance', 15, 2)->default(0);     // Tunjangan Tetap
            $table->decimal('meal_allowance', 15, 2)->default(0);      // Tunjangan Makan
            $table->decimal('transport_allowance', 15, 2)->default(0); // Tunjangan Transport
            $table->decimal('overtime_rate', 15, 2)->default(0);       // Tarif Lembur per jam
            $table->date('effective_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['employee_id', 'effective_date']);
        });

        // ── Payroll Periods ──────────────────────────────────────
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('month');
            $table->unsignedSmallInteger('year');
            $table->enum('status', ['draft', 'approved', 'posted'])->default('draft');
            $table->decimal('total_gross', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_net', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['month', 'year']);
            $table->index('status');
        });

        // ── Payslips ─────────────────────────────────────────────
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();

            // ─ Earnings ─
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('fixed_allowance', 15, 2)->default(0);
            $table->decimal('meal_allowance', 15, 2)->default(0);
            $table->decimal('transport_allowance', 15, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_amount', 15, 2)->default(0);
            $table->decimal('other_earnings', 15, 2)->default(0);
            $table->decimal('gross_salary', 15, 2)->default(0);

            // ─ BPJS (Perusahaan) ─
            $table->decimal('bpjs_jht_company', 15, 2)->default(0);   // 3.7%
            $table->decimal('bpjs_jkk_company', 15, 2)->default(0);   // 0.24%
            $table->decimal('bpjs_jkm_company', 15, 2)->default(0);   // 0.3%
            $table->decimal('bpjs_jp_company', 15, 2)->default(0);    // 2%
            $table->decimal('bpjs_kes_company', 15, 2)->default(0);   // 4%

            // ─ BPJS (Karyawan) ─
            $table->decimal('bpjs_jht_employee', 15, 2)->default(0);  // 2%
            $table->decimal('bpjs_jp_employee', 15, 2)->default(0);   // 1%
            $table->decimal('bpjs_kes_employee', 15, 2)->default(0);  // 1%

            // ─ PPh 21 ─
            $table->decimal('pph21_amount', 15, 2)->default(0);

            // ─ Other Deductions ─
            $table->decimal('loan_deduction', 15, 2)->default(0);     // Kasbon
            $table->decimal('absence_deduction', 15, 2)->default(0);  // Potongan tidak hadir
            $table->decimal('other_deductions', 15, 2)->default(0);

            // ─ Totals ─
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
            $table->index('employee_id');
        });

        // ── Payslip Items (Detail breakdown) ─────────────────────
        Schema::create('payslip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earning', 'deduction']);
            $table->string('label');                          // e.g. "Gaji Pokok", "BPJS JHT"
            $table->decimal('amount', 15, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['payslip_id', 'type']);
        });

        // ── PPh 21 TER Rate Table ────────────────────────────────
        Schema::create('pph21_ter_rates', function (Blueprint $table) {
            $table->id();
            $table->char('category', 1);                      // A, B, C
            $table->decimal('min_salary', 15, 2);
            $table->decimal('max_salary', 15, 2)->nullable();  // null = unlimited
            $table->decimal('rate', 8, 4);                     // e.g. 0.0025 = 0.25%
            $table->timestamps();

            $table->index(['category', 'min_salary']);
        });

        // ── Seed TER rates (PP 58/2023 / PMK 168/2023) ──────────
        $this->seedTerRates();

        // ── Add tax-related COA accounts for payroll ─────────────
        $this->seedPayrollAccounts();
    }

    /**
     * Seed PPh 21 TER rates based on PMK 168/2023.
     * Rates are monthly effective rates (Tarif Efektif Rata-rata).
     */
    private function seedTerRates(): void
    {
        $now = now();
        $rates = [];

        // ── Category A: PTKP TK/0, TK/1 ──────────────────────
        $catA = [
            [0, 5400000, 0],
            [5400000, 5650000, 0.0025],
            [5650000, 5950000, 0.005],
            [5950000, 6300000, 0.0075],
            [6300000, 6750000, 0.01],
            [6750000, 7500000, 0.0125],
            [7500000, 8550000, 0.015],
            [8550000, 9650000, 0.0175],
            [9650000, 10050000, 0.02],
            [10050000, 10350000, 0.0225],
            [10350000, 10700000, 0.025],
            [10700000, 11050000, 0.03],
            [11050000, 11600000, 0.035],
            [11600000, 12500000, 0.04],
            [12500000, 13750000, 0.05],
            [13750000, 15100000, 0.06],
            [15100000, 16950000, 0.07],
            [16950000, 19750000, 0.08],
            [19750000, 24150000, 0.09],
            [24150000, 26450000, 0.10],
            [26450000, 28000000, 0.11],
            [28000000, 30050000, 0.12],
            [30050000, 32400000, 0.13],
            [32400000, 35400000, 0.14],
            [35400000, 39100000, 0.15],
            [39100000, 43850000, 0.16],
            [43850000, 47800000, 0.17],
            [47800000, 51400000, 0.18],
            [51400000, 56300000, 0.19],
            [56300000, 62200000, 0.20],
            [62200000, 68600000, 0.21],
            [68600000, 77500000, 0.22],
            [77500000, 89000000, 0.23],
            [89000000, 103000000, 0.24],
            [103000000, 125000000, 0.25],
            [125000000, 157000000, 0.26],
            [157000000, 206000000, 0.27],
            [206000000, 337000000, 0.28],
            [337000000, 454000000, 0.29],
            [454000000, 550000000, 0.30],
            [550000000, 695000000, 0.31],
            [695000000, 910000000, 0.32],
            [910000000, 1400000000, 0.33],
            [1400000000, null, 0.34],
        ];

        // ── Category B: PTKP K/0, K/1 ────────────────────────
        $catB = [
            [0, 6200000, 0],
            [6200000, 6500000, 0.0025],
            [6500000, 6850000, 0.005],
            [6850000, 7300000, 0.0075],
            [7300000, 9200000, 0.01],
            [9200000, 10750000, 0.015],
            [10750000, 11250000, 0.02],
            [11250000, 11600000, 0.025],
            [11600000, 12600000, 0.03],
            [12600000, 13600000, 0.04],
            [13600000, 14950000, 0.05],
            [14950000, 16400000, 0.06],
            [16400000, 18450000, 0.07],
            [18450000, 21850000, 0.08],
            [21850000, 26000000, 0.09],
            [26000000, 27700000, 0.10],
            [27700000, 29350000, 0.11],
            [29350000, 31450000, 0.12],
            [31450000, 33950000, 0.13],
            [33950000, 37100000, 0.14],
            [37100000, 41100000, 0.15],
            [41100000, 45800000, 0.16],
            [45800000, 49500000, 0.17],
            [49500000, 53800000, 0.18],
            [53800000, 58500000, 0.19],
            [58500000, 64000000, 0.20],
            [64000000, 71000000, 0.21],
            [71000000, 80000000, 0.22],
            [80000000, 93000000, 0.23],
            [93000000, 109000000, 0.24],
            [109000000, 129000000, 0.25],
            [129000000, 163000000, 0.26],
            [163000000, 211000000, 0.27],
            [211000000, 374000000, 0.28],
            [374000000, 459000000, 0.29],
            [459000000, 555000000, 0.30],
            [555000000, 704000000, 0.31],
            [704000000, 957000000, 0.32],
            [957000000, 1405000000, 0.33],
            [1405000000, null, 0.34],
        ];

        // ── Category C: PTKP K/2, K/3 ────────────────────────
        $catC = [
            [0, 6600000, 0],
            [6600000, 6950000, 0.0025],
            [6950000, 7350000, 0.005],
            [7350000, 7800000, 0.0075],
            [7800000, 8850000, 0.01],
            [8850000, 9800000, 0.0125],
            [9800000, 10950000, 0.015],
            [10950000, 11200000, 0.02],
            [11200000, 12050000, 0.025],
            [12050000, 12950000, 0.03],
            [12950000, 14150000, 0.04],
            [14150000, 15550000, 0.05],
            [15550000, 17050000, 0.06],
            [17050000, 19500000, 0.07],
            [19500000, 22700000, 0.08],
            [22700000, 26600000, 0.09],
            [26600000, 28100000, 0.10],
            [28100000, 30100000, 0.11],
            [30100000, 32600000, 0.12],
            [32600000, 35400000, 0.13],
            [35400000, 38900000, 0.14],
            [38900000, 43000000, 0.15],
            [43000000, 47000000, 0.16],
            [47000000, 51000000, 0.17],
            [51000000, 55800000, 0.18],
            [55800000, 61400000, 0.19],
            [61400000, 68000000, 0.20],
            [68000000, 74500000, 0.21],
            [74500000, 83200000, 0.22],
            [83200000, 95000000, 0.23],
            [95000000, 110000000, 0.24],
            [110000000, 134000000, 0.25],
            [134000000, 169000000, 0.26],
            [169000000, 221000000, 0.27],
            [221000000, 390000000, 0.28],
            [390000000, 463000000, 0.29],
            [463000000, 561000000, 0.30],
            [561000000, 709000000, 0.31],
            [709000000, 965000000, 0.32],
            [965000000, 1419000000, 0.33],
            [1419000000, null, 0.34],
        ];

        foreach (['A' => $catA, 'B' => $catB, 'C' => $catC] as $cat => $brackets) {
            foreach ($brackets as $b) {
                $rates[] = [
                    'category'   => $cat,
                    'min_salary' => $b[0],
                    'max_salary' => $b[1],
                    'rate'       => $b[2],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        \Illuminate\Support\Facades\DB::table('pph21_ter_rates')->insert($rates);
    }

    /**
     * Seed COA accounts needed for payroll auto-journal.
     */
    private function seedPayrollAccounts(): void
    {
        $now = now();
        $accounts = [
            ['code' => '5100', 'name' => 'Beban Gaji & Upah',           'type' => 'expense'],
            ['code' => '5110', 'name' => 'Beban Tunjangan Karyawan',    'type' => 'expense'],
            ['code' => '5120', 'name' => 'Beban BPJS Perusahaan',      'type' => 'expense'],
            ['code' => '2110', 'name' => 'Utang Gaji (Payroll Payable)','type' => 'liability'],
            ['code' => '2120', 'name' => 'Utang PPh 21',               'type' => 'liability'],
            ['code' => '2130', 'name' => 'Utang BPJS',                 'type' => 'liability'],
        ];

        foreach ($accounts as $acct) {
            \Illuminate\Support\Facades\DB::table('chart_of_accounts')->updateOrInsert(
                ['code' => $acct['code']],
                array_merge($acct, [
                    'is_active'   => true,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_items');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('pph21_ter_rates');

        // Remove seeded COA accounts
        \Illuminate\Support\Facades\DB::table('chart_of_accounts')
            ->whereIn('code', ['5100', '5110', '5120', '2110', '2120', '2130'])
            ->delete();
    }
};
