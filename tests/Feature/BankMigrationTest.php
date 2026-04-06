<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\BankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BankMigrationTest extends TestCase
{
    use RefreshDatabase;

    // ── T1: Seeder populates all 37 bank records ─────────────

    public function test_bank_seeder_populates_all_records(): void
    {
        $this->assertDatabaseCount('banks', 0);

        $this->seed(BankSeeder::class);

        $this->assertDatabaseCount('banks', 37);

        // Verify idempotency — running again should not duplicate
        $this->seed(BankSeeder::class);
        $this->assertDatabaseCount('banks', 37);

        // Spot-check a few banks
        $this->assertDatabaseHas('banks', ['code' => '014', 'name' => 'PT. BANK CENTRAL ASIA, TBK - (BCA)']);
        $this->assertDatabaseHas('banks', ['code' => '002', 'name' => 'PT. BANK RAKYAT INDONESIA (PERSERO), TBK (BRI)']);
        $this->assertDatabaseHas('banks', ['code' => '008', 'name' => 'PT. BANK MANDIRI (PERSERO), TBK']);
    }

    // ── T2: Employee requires valid bank_id ──────────────────

    public function test_employee_requires_valid_bank_id(): void
    {
        $admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->seed(BankSeeder::class);

        // Invalid bank_id should fail validation
        $response = $this->actingAs($admin)->post(route('hr.employees.store'), [
            'nik'         => 'EMP-TEST-001',
            'name'        => 'Test Employee',
            'join_date'   => '2024-01-01',
            'ptkp_status' => 'TK/0',
            'status'      => 'active',
            'bank_id'     => 99999,
        ]);

        $response->assertSessionHasErrors('bank_id');

        // Valid bank_id should pass the bank_id validation
        $validBank = Bank::first();
        $response = $this->actingAs($admin)->post(route('hr.employees.store'), [
            'nik'         => 'EMP-TEST-002',
            'name'        => 'Test Employee 2',
            'join_date'   => '2024-01-01',
            'ptkp_status' => 'TK/0',
            'status'      => 'active',
            'bank_id'     => $validBank->id,
        ]);

        $response->assertSessionDoesntHaveErrors('bank_id');
        $this->assertDatabaseHas('employees', [
            'nik'     => 'EMP-TEST-002',
            'bank_id' => $validBank->id,
        ]);
    }

    // ── T3: Bank list API is accessible ──────────────────────

    public function test_bank_list_api_is_accessible(): void
    {
        $this->seed(BankSeeder::class);

        $user = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.banks.index'));

        $response->assertOk()
            ->assertJsonCount(37, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'code'],
                ],
            ]);

        // Verify inactive banks are excluded
        Bank::where('code', '014')->update(['is_active' => false]);

        $response = $this->getJson(route('api.banks.index'));
        $response->assertOk()
            ->assertJsonCount(36, 'data');
    }
}
