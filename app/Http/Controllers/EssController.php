<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDataChange;
use App\Models\EmployeeDocument;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EssController extends Controller
{
    // ── DOCUMENT UPLOAD ──────────────────────────────────────────

    public function uploadDocument(Request $request): RedirectResponse
    {
        $this->blockImpersonation($request);

        $employee = $this->getEmployee($request);

        $request->validate([
            'document_type' => ['required', 'string', 'in:' . implode(',', EmployeeDocument::ALLOWED_TYPES)],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $file = $request->file('document_file');
        $type = $request->input('document_type');

        // Store in private isolation path
        $directory = "documents/{$employee->id}";
        $filename  = $type . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path      = $file->storeAs($directory, $filename, 'local');

        // SHA-256 hash for integrity verification
        $fileHash = hash_file('sha256', $file->getRealPath());

        $document = EmployeeDocument::create([
            'employee_id'   => $employee->id,
            'type'          => $type,
            'file_path'     => $path,
            'file_hash'     => $fileHash,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'file_size'     => $file->getSize(),
            'status'        => EmployeeDocument::STATUS_PENDING,
        ]);

        AuditLogService::log(
            'hr',
            'create',
            "Employee {$employee->name} uploaded {$type} document",
            null,
            ['document_id' => $document->id, 'file_hash' => $fileHash, 'type' => $type],
            $document
        );

        return back()
            ->with('status', 'document-uploaded')
            ->with('flash_type', 'success');
    }

    // ── DOCUMENT VIEW (Temporary Signed URL) ────────────────────

    public function viewDocument(Request $request, EmployeeDocument $document): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired signed URL.');
        }

        $user = $request->user();
        $isOwner = $user->employee && $user->employee->id === $document->employee_id;
        $isHrAdmin = $user->isAdmin() || $user->hasPermission('hr.view');

        if (! $isOwner && ! $isHrAdmin) {
            abort(403);
        }

        if (! $document->fileExists()) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name,
            ['Content-Type' => $document->mime_type]
        );
    }

    // ── DOCUMENT DELETE ─────────────────────────────────────────

    public function deleteDocument(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $this->blockImpersonation($request);

        $employee = $this->getEmployee($request);

        if ($document->employee_id !== $employee->id) {
            abort(403);
        }

        Storage::disk('local')->delete($document->file_path);

        AuditLogService::log(
            'hr',
            'delete',
            "Employee {$employee->name} deleted {$document->type} document",
            ['document_id' => $document->id, 'file_hash' => $document->file_hash],
            null,
            $document
        );

        $document->delete();

        return back()
            ->with('status', 'document-deleted')
            ->with('flash_type', 'success');
    }

    // ── DATA CHANGE REQUEST ─────────────────────────────────────

    public function requestDataChange(Request $request): RedirectResponse
    {
        $this->blockImpersonation($request);

        $employee = $this->getEmployee($request);

        $validated = $request->validate([
            'phone'               => ['nullable', 'string', 'max:20'],
            'bank_name'           => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'bank_account_name'   => ['nullable', 'string', 'max:255'],
            'bpjs_tk_number'      => ['nullable', 'string', 'max:30'],
            'bpjs_kes_number'     => ['nullable', 'string', 'max:30'],
        ]);

        // Filter only actually changed fields
        $changes = [];
        $original = [];
        foreach (EmployeeDataChange::EDITABLE_FIELDS as $field) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }
            $currentValue = $field === 'phone'
                ? $request->user()->phone
                : $employee->{$field};

            if ((string) ($validated[$field] ?? '') !== (string) ($currentValue ?? '')) {
                $changes[$field]  = $validated[$field];
                $original[$field] = $currentValue;
            }
        }

        if (empty($changes)) {
            return back()->with('status', 'no-changes');
        }

        // Check for existing pending request
        $existingPending = EmployeeDataChange::where('employee_id', $employee->id)
            ->where('status', EmployeeDataChange::STATUS_PENDING)
            ->exists();

        if ($existingPending) {
            return back()
                ->withErrors(['ess' => __('messages.ess_pending_exists')])
                ->withInput();
        }

        $changeRequest = EmployeeDataChange::create([
            'employee_id'       => $employee->id,
            'requested_by'      => $request->user()->id,
            'requested_changes' => $changes,
            'original_data'     => $original,
            'status'            => EmployeeDataChange::STATUS_PENDING,
        ]);

        AuditLogService::log(
            'hr',
            'create',
            "Employee {$employee->name} requested data changes",
            $original,
            $changes,
            $changeRequest
        );

        return back()
            ->with('status', 'change-requested')
            ->with('flash_type', 'success');
    }

    // ── HR APPROVAL / REJECTION ─────────────────────────────────

    public function approveChange(Request $request, EmployeeDataChange $change): RedirectResponse
    {
        if (! $change->isPending()) {
            return back()->withErrors(['ess' => 'This request has already been processed.']);
        }

        $employee = $change->employee;

        // Apply the changes to employee (and user.phone if included)
        foreach ($change->requested_changes as $field => $value) {
            if ($field === 'phone') {
                $employee->user->update(['phone' => $value]);
            } elseif (in_array($field, EmployeeDataChange::EDITABLE_FIELDS, true)) {
                $employee->{$field} = $value;
            }
        }
        $employee->save();

        $change->update([
            'status'      => EmployeeDataChange::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        AuditLogService::log(
            'hr',
            'update',
            "HR approved data change request #{$change->id} for {$employee->name}",
            $change->original_data,
            $change->requested_changes,
            $change
        );

        return back()
            ->with('status', 'change-approved')
            ->with('flash_type', 'success');
    }

    public function rejectChange(Request $request, EmployeeDataChange $change): RedirectResponse
    {
        if (! $change->isPending()) {
            return back()->withErrors(['ess' => 'This request has already been processed.']);
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $change->update([
            'status'           => EmployeeDataChange::STATUS_REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
            'reviewed_by'      => $request->user()->id,
            'reviewed_at'      => now(),
        ]);

        AuditLogService::log(
            'hr',
            'update',
            "HR rejected data change request #{$change->id} for {$change->employee->name}",
            null,
            ['rejection_reason' => $change->rejection_reason],
            $change
        );

        return back()
            ->with('status', 'change-rejected')
            ->with('flash_type', 'success');
    }

    // ── DOCUMENT VERIFICATION (HR) ──────────────────────────────

    public function verifyDocument(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $request->validate([
            'action' => ['required', 'in:verify,reject'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ]);

        $action = $request->input('action');

        if ($action === 'verify') {
            $document->update([
                'status'      => EmployeeDocument::STATUS_VERIFIED,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);
        } else {
            $document->update([
                'status'           => EmployeeDocument::STATUS_REJECTED,
                'rejection_reason' => $request->input('rejection_reason'),
                'verified_by'      => $request->user()->id,
                'verified_at'      => now(),
            ]);
        }

        AuditLogService::log(
            'hr',
            'update',
            "HR {$action}d document #{$document->id} ({$document->type}) for employee #{$document->employee_id}",
            null,
            ['status' => $document->status, 'file_hash' => $document->file_hash],
            $document
        );

        return back()
            ->with('status', "document-{$action}d")
            ->with('flash_type', 'success');
    }

    // ── GUARDS ──────────────────────────────────────────────────

    private function blockImpersonation(Request $request): void
    {
        if (session('impersonator_id')) {
            abort(403, __('messages.ess_impersonation_blocked'));
        }
    }

    private function getEmployee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            abort(403, __('messages.ess_no_employee_record'));
        }

        return $employee;
    }
}
