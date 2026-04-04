<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Notifications\NewSupportTicketNotification;
use App\Notifications\SupportTicketReplyNotification;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->isAdmin()
            ? SupportTicket::with('user')
            : SupportTicket::where('user_id', $request->user()->id);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $tickets = $query->latest()->paginate(20)->appends($request->query());

        return view('support.index', compact('tickets'));
    }

    public function create()
    {
        return view('support.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'category'    => 'required|in:' . implode(',', SupportTicket::categories()),
            'priority'    => 'required|in:' . implode(',', SupportTicket::priorities()),
            'description' => 'required|string|max:5000',
        ]);

        $ticket = DB::transaction(function () use ($validated, $request) {
            $ticket = SupportTicket::create([
                'ticket_number' => SupportTicket::generateTicketNumber(),
                'user_id'       => $request->user()->id,
                'title'         => $validated['title'],
                'category'      => $validated['category'],
                'priority'      => $validated['priority'],
                'status'        => 'open',
                'description'   => $validated['description'],
            ]);

            AuditLogService::log(
                'support',
                'create',
                "Created support ticket #{$ticket->ticket_number}: {$ticket->title}",
                null,
                $ticket->toArray(),
                $ticket
            );

            return $ticket;
        });

        // Notify all admins
        $admins = User::where('role', User::ROLE_ADMIN)->get();
        foreach ($admins as $admin) {
            $admin->notify(new NewSupportTicketNotification($ticket));
        }

        return redirect()->route('support.show', $ticket)
            ->with('success', __('messages.ticket_created'));
    }

    public function show(SupportTicket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->load(['replies.user', 'user', 'assignee']);

        return view('support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $this->authorize('reply', $ticket);

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $reply = DB::transaction(function () use ($validated, $request, $ticket) {
            $reply = SupportTicketReply::create([
                'support_ticket_id' => $ticket->id,
                'user_id'           => $request->user()->id,
                'message'           => $validated['message'],
                'is_admin_reply'    => $request->user()->isAdmin(),
            ]);

            // Auto-set status to in_progress if admin replies on open ticket
            if ($request->user()->isAdmin() && $ticket->status === 'open') {
                $ticket->update(['status' => 'in_progress', 'assigned_to' => $request->user()->id]);
            }

            AuditLogService::log(
                'support',
                'reply',
                "Replied to ticket #{$ticket->ticket_number}",
                null,
                ['message' => $validated['message']],
                $ticket
            );

            return $reply;
        });

        // Notify the other party
        if ($request->user()->isAdmin()) {
            $ticket->user->notify(new SupportTicketReplyNotification($ticket, $reply));
        } else {
            $admins = User::where('role', User::ROLE_ADMIN)->get();
            foreach ($admins as $admin) {
                $admin->notify(new SupportTicketReplyNotification($ticket, $reply));
            }
        }

        return back()->with('success', __('messages.reply_sent'));
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $this->authorize('updateStatus', $ticket);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', SupportTicket::statuses()),
        ]);

        $oldStatus = $ticket->status;

        DB::transaction(function () use ($validated, $ticket, $oldStatus) {
            $updates = ['status' => $validated['status']];

            if ($validated['status'] === 'resolved') {
                $updates['resolved_at'] = now();
            } elseif ($validated['status'] === 'closed') {
                $updates['closed_at'] = now();
            }

            $ticket->update($updates);

            AuditLogService::log(
                'support',
                'status_change',
                "Changed ticket #{$ticket->ticket_number} status from {$oldStatus} to {$validated['status']}",
                ['status' => $oldStatus],
                ['status' => $validated['status']],
                $ticket
            );
        });

        return back()->with('success', __('messages.ticket_status_updated'));
    }
}
