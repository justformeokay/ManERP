<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Employee;
use App\Models\EmployeeDataChange;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;
    private Employee $employee;
    private User $hrUser;
    private Employee $hrEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->employeeUser = User::factory()->create([
            'role'   => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'phone'  => '08111111111',
            'permissions' => [],
        ]);

        // UserObserver auto-creates an Employee; update it with test-specific fields
        $this->employee = $this->employeeUser->employee;

        // Seed banks for bank_id reference
        $this->seed(\Database\Seeders\BankSeeder::class);
        $bcaBank = Bank::where('code', '014')->first();

        $this->employee->update([
            'bank_id'             => $bcaBank->id,
            'bank_account_number' => '1234567890',
            'bank_account_name'   => 'Test Employee',
            'bpjs_tk_number'      => 'TK1234567890',
            'bpjs_kes_number'     => 'KS1234567890',
        ]);

        $this->hrUser = User::factory()->create([
            'role'        => User::ROLE_STAFF,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => ['hr.view', 'hr.edit'],
        ]);

        // Use the observer-created employee for HR user too
        $this->hrEmployee = $this->hrUser->employee;
    }

    // ── DOCUMENT UPLOAD TESTS ───────────────────────────────────

    public function test_employee_can_upload_document(): void
    {
        $file = UploadedFile::fake()->create('ktp.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'document-uploaded');

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $this->employee->id,
            'type'        => 'ktp',
            'status'      => 'pending',
        ]);
    }

    public function test_upload_stores_sha256_file_hash(): void
    {
        $file = UploadedFile::fake()->create('npwp.pdf', 512, 'application/pdf');

        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'npwp',
                'document_file' => $file,
            ]);

        $document = EmployeeDocument::where('employee_id', $this->employee->id)
            ->where('type', 'npwp')
            ->first();

        $this->assertNotNull($document);
        $this->assertNotNull($document->file_hash);
        $this->assertEquals(64, strlen($document->file_hash)); // SHA-256 length
    }

    public function test_upload_creates_audit_log_with_file_hash(): void
    {
        $file = UploadedFile::fake()->create('ijazah.png', 800, 'image/png');

        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ijazah',
                'document_file' => $file,
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->employeeUser->id,
            'module'  => 'hr',
            'action'  => 'create',
        ]);
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('virus.exe', 512, 'application/x-msdownload');

        $response = $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $response->assertSessionHasErrors('document_file');
        $this->assertDatabaseMissing('employee_documents', [
            'employee_id' => $this->employee->id,
        ]);
    }

    public function test_upload_rejects_oversized_file(): void
    {
        $file = UploadedFile::fake()->create('big.pdf', 3000, 'application/pdf');

        $response = $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $response->assertSessionHasErrors('document_file');
    }

    public function test_upload_rejects_invalid_document_type(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 512, 'application/pdf');

        $response = $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'passport',
                'document_file' => $file,
            ]);

        $response->assertSessionHasErrors('document_type');
    }

    // ── DOCUMENT PRIVATE STORAGE TESTS ──────────────────────────

    public function test_document_is_not_publicly_accessible(): void
    {
        $file = UploadedFile::fake()->create('ktp.pdf', 512, 'application/pdf');

        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $document = EmployeeDocument::where('employee_id', $this->employee->id)->first();

        // Direct GET to the route without a valid signature should be 403
        $response = $this->actingAs($this->employeeUser)
            ->get(route('profile.ess.document.view', $document));

        $response->assertForbidden();
    }

    public function test_signed_url_grants_access_to_owner(): void
    {
        $file = UploadedFile::fake()->create('ktp.pdf', 512, 'application/pdf');

        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $document = EmployeeDocument::where('employee_id', $this->employee->id)->first();
        $signedUrl = $document->getTemporaryUrl();

        $response = $this->actingAs($this->employeeUser)->get($signedUrl);
        $response->assertOk();
    }

    public function test_other_employee_cannot_view_document_via_signed_url(): void
    {
        $file = UploadedFile::fake()->create('ktp.pdf', 512, 'application/pdf');

        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $document = EmployeeDocument::where('employee_id', $this->employee->id)->first();
        $signedUrl = $document->getTemporaryUrl();

        // Another staff user without HR access
        $otherUser = User::factory()->create([
            'role' => 'staff', 'status' => 'active', 'permissions' => [],
        ]);
        // Observer auto-creates employee for $otherUser

        $response = $this->actingAs($otherUser)->get($signedUrl);
        $response->assertForbidden();
    }

    public function test_hr_can_view_any_document_via_signed_url(): void
    {
        $file = UploadedFile::fake()->create('npwp.pdf', 512, 'application/pdf');

        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'npwp',
                'document_file' => $file,
            ]);

        $document = EmployeeDocument::where('employee_id', $this->employee->id)->first();
        $signedUrl = $document->getTemporaryUrl();

        $response = $this->actingAs($this->hrUser)->get($signedUrl);
        $response->assertOk();
    }

    // ── DOCUMENT DELETE TESTS ───────────────────────────────────

    public function test_employee_can_delete_own_document(): void
    {
        $file = UploadedFile::fake()->create('ktp.pdf', 512, 'application/pdf');

        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $document = EmployeeDocument::where('employee_id', $this->employee->id)->first();

        $response = $this->actingAs($this->employeeUser)
            ->delete(route('profile.ess.document.delete', $document));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'document-deleted');
        $this->assertDatabaseMissing('employee_documents', ['id' => $document->id]);
    }

    public function test_employee_cannot_delete_others_document(): void
    {
        $file = UploadedFile::fake()->create('ktp.pdf', 512, 'application/pdf');

        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $document = EmployeeDocument::where('employee_id', $this->employee->id)->first();

        $otherUser = User::factory()->create([
            'role' => 'staff', 'status' => 'active', 'permissions' => [],
        ]);
        // Observer auto-creates employee for $otherUser

        $response = $this->actingAs($otherUser)
            ->delete(route('profile.ess.document.delete', $document));

        $response->assertForbidden();
        $this->assertDatabaseHas('employee_documents', ['id' => $document->id]);
    }

    // ── IMPERSONATION BLOCKING ──────────────────────────────────

    public function test_impersonation_blocks_document_upload(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->create('ktp.pdf', 512, 'application/pdf');

        $response = $this->actingAs($this->employeeUser)
            ->withSession(['impersonator_id' => $admin->id])
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $response->assertForbidden();
    }

    public function test_impersonation_blocks_data_change_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($this->employeeUser)
            ->withSession(['impersonator_id' => $admin->id])
            ->post(route('profile.ess.data-change'), [
                'phone' => '08999999999',
            ]);

        $response->assertForbidden();
    }

    public function test_impersonation_blocks_document_delete(): void
    {
        $file = UploadedFile::fake()->create('ktp.pdf', 512, 'application/pdf');

        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $document = EmployeeDocument::where('employee_id', $this->employee->id)->first();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($this->employeeUser)
            ->withSession(['impersonator_id' => $admin->id])
            ->delete(route('profile.ess.document.delete', $document));

        $response->assertForbidden();
    }

    // ── DATA CHANGE REQUEST TESTS ───────────────────────────────

    public function test_sensitive_data_change_requires_hr_approval(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.data-change'), [
                'phone'               => '08999999999',
                'bank_account_number' => '9876543210',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'change-requested');

        // Employee table MUST NOT be updated yet
        $this->employee->refresh();
        $this->assertEquals('1234567890', $this->employee->bank_account_number);

        $this->employeeUser->refresh();
        $this->assertEquals('08111111111', $this->employeeUser->phone);

        // Pending change should exist
        $this->assertDatabaseHas('employee_data_changes', [
            'employee_id' => $this->employee->id,
            'status'      => 'pending',
        ]);
    }

    public function test_data_change_only_records_actual_changes(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.data-change'), [
                'phone'   => $this->employeeUser->phone,      // same value
                'bank_id' => $this->employee->bank_id,         // same value
            ]);

        $response->assertSessionHas('status', 'no-changes');
        $this->assertDatabaseMissing('employee_data_changes', [
            'employee_id' => $this->employee->id,
        ]);
    }

    public function test_duplicate_pending_request_is_blocked(): void
    {
        // Create first pending request
        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.data-change'), [
                'phone' => '08999999999',
            ]);

        // Attempt second one
        $mandiriBank = Bank::where('code', '008')->first();
        $response = $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.data-change'), [
                'bank_id' => $mandiriBank->id,
            ]);

        $response->assertSessionHasErrors('ess');

        // Only one pending request
        $this->assertEquals(1, EmployeeDataChange::where('employee_id', $this->employee->id)
            ->where('status', 'pending')->count());
    }

    public function test_bank_account_number_must_be_numeric(): void
    {
        $response = $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.data-change'), [
                'bank_account_number' => 'abc123',
            ]);

        $response->assertSessionHasErrors('bank_account_number');
    }

    public function test_data_change_creates_audit_log(): void
    {
        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.data-change'), [
                'phone' => '08999999999',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->employeeUser->id,
            'module'  => 'hr',
            'action'  => 'create',
        ]);
    }

    // ── HR APPROVAL TESTS ───────────────────────────────────────

    public function test_hr_can_approve_data_change(): void
    {
        $mandiriBank = Bank::where('code', '008')->first();
        $bcaBank = Bank::where('code', '014')->first();

        // Create pending change
        EmployeeDataChange::create([
            'employee_id'       => $this->employee->id,
            'requested_by'      => $this->employeeUser->id,
            'requested_changes' => ['phone' => '08999999999', 'bank_id' => $mandiriBank->id],
            'original_data'     => ['phone' => '08111111111', 'bank_id' => $bcaBank->id],
            'status'            => 'pending',
        ]);

        $change = EmployeeDataChange::where('employee_id', $this->employee->id)->first();

        $response = $this->actingAs($this->hrUser)
            ->post(route('ess-admin.change.approve', $change));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'change-approved');

        // Values should now be applied
        $this->employeeUser->refresh();
        $this->assertEquals('08999999999', $this->employeeUser->phone);

        $this->employee->refresh();
        $this->assertEquals($mandiriBank->id, $this->employee->bank_id);

        $change->refresh();
        $this->assertEquals('approved', $change->status);
        $this->assertEquals($this->hrUser->id, $change->reviewed_by);
    }

    public function test_hr_can_reject_data_change(): void
    {
        $change = EmployeeDataChange::create([
            'employee_id'       => $this->employee->id,
            'requested_by'      => $this->employeeUser->id,
            'requested_changes' => ['phone' => '08999999999'],
            'original_data'     => ['phone' => '08111111111'],
            'status'            => 'pending',
        ]);

        $response = $this->actingAs($this->hrUser)
            ->post(route('ess-admin.change.reject', $change), [
                'rejection_reason' => 'Invalid phone number format',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'change-rejected');

        $change->refresh();
        $this->assertEquals('rejected', $change->status);
        $this->assertEquals('Invalid phone number format', $change->rejection_reason);

        // Original value must remain
        $this->employeeUser->refresh();
        $this->assertEquals('08111111111', $this->employeeUser->phone);
    }

    public function test_reject_requires_reason(): void
    {
        $change = EmployeeDataChange::create([
            'employee_id'       => $this->employee->id,
            'requested_by'      => $this->employeeUser->id,
            'requested_changes' => ['phone' => '08999999999'],
            'original_data'     => ['phone' => '08111111111'],
            'status'            => 'pending',
        ]);

        $response = $this->actingAs($this->hrUser)
            ->post(route('ess-admin.change.reject', $change), []);

        $response->assertSessionHasErrors('rejection_reason');
    }

    public function test_staff_without_hr_permission_cannot_approve(): void
    {
        $change = EmployeeDataChange::create([
            'employee_id'       => $this->employee->id,
            'requested_by'      => $this->employeeUser->id,
            'requested_changes' => ['phone' => '08999999999'],
            'original_data'     => ['phone' => '08111111111'],
            'status'            => 'pending',
        ]);

        $response = $this->actingAs($this->employeeUser)
            ->post(route('ess-admin.change.approve', $change));

        $response->assertForbidden();
    }

    // ── DOCUMENT VERIFICATION (HR) ──────────────────────────────

    public function test_hr_can_verify_document(): void
    {
        $file = UploadedFile::fake()->create('ktp.pdf', 512, 'application/pdf');

        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $document = EmployeeDocument::where('employee_id', $this->employee->id)->first();

        $response = $this->actingAs($this->hrUser)
            ->post(route('ess-admin.document.verify', $document), [
                'action' => 'verify',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'document-verifyd');

        $document->refresh();
        $this->assertEquals('verified', $document->status);
        $this->assertEquals($this->hrUser->id, $document->verified_by);
    }

    public function test_hr_can_reject_document(): void
    {
        $file = UploadedFile::fake()->create('ktp.pdf', 512, 'application/pdf');

        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $document = EmployeeDocument::where('employee_id', $this->employee->id)->first();

        $response = $this->actingAs($this->hrUser)
            ->post(route('ess-admin.document.verify', $document), [
                'action'           => 'reject',
                'rejection_reason' => 'Blurry image',
            ]);

        $response->assertRedirect();

        $document->refresh();
        $this->assertEquals('rejected', $document->status);
        $this->assertEquals('Blurry image', $document->rejection_reason);
    }

    // ── USER WITHOUT EMPLOYEE RECORD ────────────────────────────

    public function test_user_without_employee_record_cannot_upload(): void
    {
        $userNoEmployee = User::factory()->create([
            'role' => 'staff', 'status' => 'active', 'permissions' => [],
        ]);
        // Remove auto-created employee to simulate orphan user
        Employee::where('user_id', $userNoEmployee->id)->forceDelete();
        $userNoEmployee->unsetRelation('employee');

        $file = UploadedFile::fake()->create('ktp.pdf', 512, 'application/pdf');

        $response = $this->actingAs($userNoEmployee)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $response->assertForbidden();
    }

    public function test_user_without_employee_record_cannot_request_change(): void
    {
        $userNoEmployee = User::factory()->create([
            'role' => 'staff', 'status' => 'active', 'permissions' => [],
        ]);
        // Remove auto-created employee to simulate orphan user
        Employee::where('user_id', $userNoEmployee->id)->forceDelete();
        $userNoEmployee->unsetRelation('employee');

        $response = $this->actingAs($userNoEmployee)
            ->post(route('profile.ess.data-change'), [
                'phone' => '08999999999',
            ]);

        $response->assertForbidden();
    }

    // ── AUDIT CHECKSUM INTEGRITY ────────────────────────────────

    public function test_audit_log_checksum_is_valid_for_ess_actions(): void
    {
        $file = UploadedFile::fake()->create('ktp.pdf', 512, 'application/pdf');

        $this->actingAs($this->employeeUser)
            ->post(route('profile.ess.document.upload'), [
                'document_type' => 'ktp',
                'document_file' => $file,
            ]);

        $log = \App\Models\ActivityLog::where('user_id', $this->employeeUser->id)
            ->where('module', 'hr')
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->checksum);
        $this->assertTrue(\App\Services\AuditLogService::verifyChecksum($log));
    }
}
