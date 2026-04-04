<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TUGAS 4: Audit Integrity & HMAC Validation
 *
 * Proves HMAC checksum detects direct DB tampering,
 * verifies audit log immutability, and validates
 * field-level change tracking.
 */
class AuditIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->actingAs($this->admin);
    }

    // ─── HMAC INTEGRITY ──────────────────────────────────────────

    public function test_valid_audit_log_passes_integrity_check(): void
    {
        $log = AuditLogService::log(
            'inventory',
            'create',
            'Created product RM-001',
            null,
            ['sku' => 'RM-001', 'name' => 'Test Product', 'avg_cost' => 0],
        );

        $this->assertNotNull($log->checksum, 'Log must have a checksum');
        $this->assertTrue(
            AuditLogService::verifyChecksum($log),
            'Valid (untampered) log must pass integrity check'
        );
        $this->assertNotEmpty($log->module);
    }

    public function test_tampered_description_fails_integrity_check(): void
    {
        $log = AuditLogService::log(
            'sales',
            'confirm',
            'Confirmed sales order SO-001',
            ['status' => 'draft'],
            ['status' => 'confirmed'],
        );

        $this->assertTrue(AuditLogService::verifyChecksum($log), 'Pre-tamper check passes');

        // Tamper with the description directly in DB
        DB::table('activity_logs')
            ->where('id', $log->id)
            ->update(['description' => 'TAMPERED: Confirmed sales order SO-999']);

        $log->refresh();
        $this->assertEquals('TAMPERED: Confirmed sales order SO-999', $log->description);
        $this->assertFalse(
            AuditLogService::verifyChecksum($log),
            'Tampered description must FAIL integrity check'
        );
    }

    public function test_tampered_module_fails_integrity_check(): void
    {
        $log = AuditLogService::log(
            'accounting',
            'post',
            'Posted journal entry JE-001',
            ['status' => 'draft'],
            ['status' => 'posted'],
        );

        $this->assertTrue(AuditLogService::verifyChecksum($log));

        // Tamper module
        DB::table('activity_logs')
            ->where('id', $log->id)
            ->update(['module' => 'hacked']);

        $log->refresh();
        $this->assertEquals('hacked', $log->module);
        $this->assertFalse(
            AuditLogService::verifyChecksum($log),
            'Tampered module must FAIL integrity check'
        );
    }

    public function test_tampered_action_fails_integrity_check(): void
    {
        $log = AuditLogService::log(
            'finance',
            'delete',
            'Deleted payment PAY-001',
            ['amount' => 1000000],
            null,
        );

        $this->assertTrue(AuditLogService::verifyChecksum($log));

        DB::table('activity_logs')
            ->where('id', $log->id)
            ->update(['action' => 'create']);

        $log->refresh();
        $this->assertEquals('create', $log->action);
        $this->assertFalse(
            AuditLogService::verifyChecksum($log),
            'Tampered action must FAIL integrity check'
        );
    }

    // ─── IMMUTABILITY ────────────────────────────────────────────

    public function test_audit_log_cannot_be_updated_via_eloquent(): void
    {
        $log = AuditLogService::log(
            'inventory',
            'update',
            'Updated stock',
            ['qty' => 10],
            ['qty' => 20],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $log->update(['description' => 'Hacked description']);
    }

    public function test_audit_log_cannot_be_deleted_via_eloquent(): void
    {
        $log = AuditLogService::log(
            'clients',
            'create',
            'Created client',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $log->delete();
    }

    // ─── FIELD-LEVEL CHANGE TRACKING ─────────────────────────────

    public function test_field_level_changes_are_computed_correctly(): void
    {
        $oldData = [
            'name'   => 'Old Product',
            'status' => 'draft',
            'price'  => 10000,
            'id'     => 1,            // should be excluded
            'updated_at' => '2026-01-01', // should be excluded
        ];
        $newData = [
            'name'   => 'New Product',
            'status' => 'active',
            'price'  => 15000,
            'id'     => 1,
            'updated_at' => '2026-04-01',
        ];

        $changes = AuditLogService::computeChanges($oldData, $newData);

        $this->assertIsArray($changes);
        $this->assertCount(3, $changes, 'Must detect 3 changed fields (name, status, price)');

        $fieldNames = array_column($changes, 'field');
        $this->assertContains('name', $fieldNames);
        $this->assertContains('status', $fieldNames);
        $this->assertContains('price', $fieldNames);
        $this->assertNotContains('id', $fieldNames, 'id must be excluded');
        $this->assertNotContains('updated_at', $fieldNames, 'updated_at must be excluded');
    }

    public function test_no_changes_detected_when_data_is_identical(): void
    {
        $data = ['name' => 'Same', 'status' => 'active', 'price' => 5000];
        $changes = AuditLogService::computeChanges($data, $data);

        $this->assertIsArray($changes);
        $this->assertCount(0, $changes, 'Identical data should produce zero changes');
        $this->assertEmpty($changes);
    }

    // ─── CHECKSUM ON LEGACY RECORDS ──────────────────────────────

    public function test_legacy_record_without_checksum_fails_verification(): void
    {
        // Simulate a legacy log without checksum
        $logId = DB::table('activity_logs')->insertGetId([
            'user_id'     => $this->admin->id,
            'module'      => 'legacy',
            'action'      => 'import',
            'description' => 'Old imported record',
            'ip_address'  => '127.0.0.1',
            'checksum'    => null,
            'created_at'  => now(),
        ]);

        $log = ActivityLog::find($logId);
        $this->assertNull($log->checksum);
        $this->assertFalse(
            AuditLogService::verifyChecksum($log),
            'Record without checksum must fail verification'
        );
    }

    // ─── AUDIT LOG STORES CORRECT METADATA ───────────────────────

    public function test_audit_log_captures_user_and_metadata(): void
    {
        $log = AuditLogService::log(
            'purchasing',
            'create',
            'Created PO-001',
            null,
            ['number' => 'PO-001', 'total' => 5000000],
        );

        $this->assertEquals($this->admin->id, $log->user_id);
        $this->assertEquals('purchasing', $log->module);
        $this->assertEquals('create', $log->action);
        $this->assertEquals('Created PO-001', $log->description);
        $this->assertNotNull($log->ip_address);
        $this->assertNotNull($log->checksum);
        $this->assertNotNull($log->created_at);
    }
}
