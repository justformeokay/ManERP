<?php

namespace App\Console\Commands;

use App\Mail\LeadFollowUpReminderMail;
use App\Mail\StaleLeadsDigest;
use App\Models\Client;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\LeadFollowUpReminder;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendLeadFollowUpReminders extends Command
{
    protected $signature = 'leads:send-reminders {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send follow-up reminders for stale leads that have been idle beyond the grace period';

    public function handle(): int
    {
        $graceDays = (int) Setting::get('lead_followup_days', 7);
        $escalationDays = (int) Setting::get('lead_escalation_days', 14);
        $cutoff = Carbon::now()->subDays($graceDays);
        $escalationCutoff = Carbon::now()->subDays($escalationDays);

        // Find active leads idle beyond the grace period
        $staleLeads = Client::where('type', 'lead')
            ->where('status', 'active')
            ->where('updated_at', '<=', $cutoff)
            ->get();

        if ($staleLeads->isEmpty()) {
            $this->info('No stale leads found.');
            return self::SUCCESS;
        }

        // Get all sales users (staff with clients.view permission, or admins)
        $salesUsers = User::where('status', User::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->where('role', User::ROLE_ADMIN)
                  ->orWhereJsonContains('permissions', 'clients.view');
            })
            ->get();

        // Get sales managers for escalation
        $managers = User::where('status', User::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->where('role', User::ROLE_ADMIN)
                  ->orWhere('role', 'sales_manager');
            })
            ->get();

        $sentCount = 0;
        $escalatedCount = 0;
        $emailEnabled = Setting::get('lead_followup_email_enabled', '0') === '1';

        /** @var Client $lead */
        foreach ($staleLeads as $lead) {
            $idleDays = (int) $lead->updated_at->diffInDays(now());
            $isEscalation = $lead->updated_at <= $escalationCutoff;

            if ($this->option('dry-run')) {
                $label = $isEscalation ? 'ESCALATION' : 'REMINDER';
                $this->line("[{$label}] {$lead->name} — idle {$idleDays} days (updated: {$lead->updated_at->toDateString()})");
                continue;
            }

            // Send standard reminder to all sales users
            foreach ($salesUsers as $user) {
                $user->notify(new LeadFollowUpReminder($lead, $idleDays, false));

                // Queue individual email per sales user per lead
                if ($emailEnabled && $user->email) {
                    Mail::to($user->email)->queue(
                        new LeadFollowUpReminderMail($lead, $user->name, $idleDays, false)
                    );
                }
            }
            $sentCount++;

            // Escalation: send to managers if beyond escalation threshold
            if ($isEscalation) {
                foreach ($managers as $manager) {
                    // Avoid duplicate if manager already received the standard reminder
                    if ($salesUsers->contains('id', $manager->id)) {
                        continue;
                    }
                    $manager->notify(new LeadFollowUpReminder($lead, $idleDays, true));

                    if ($emailEnabled && $manager->email) {
                        Mail::to($manager->email)->queue(
                            new LeadFollowUpReminderMail($lead, $manager->name, $idleDays, true)
                        );
                    }
                }
                $escalatedCount++;
            }

            // Mark email reminder sent on the lead for Kanban urgency indicator
            if ($emailEnabled) {
                Client::where('id', $lead->id)->update(['reminder_email_sent_at' => now()]);
            }

            // Audit log
            AuditLogService::log(
                'clients',
                'reminder',
                "Auto follow-up reminder sent for Lead #{$lead->id} ({$lead->name}) — idle {$idleDays} days"
                    . ($isEscalation ? ' [ESCALATED to managers]' : ''),
                null,
                ['client_id' => $lead->id, 'idle_days' => $idleDays, 'escalated' => $isEscalation],
                $lead,
            );
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run complete. {$staleLeads->count()} stale lead(s) found.");
        } else {
            // Optional: send digest email summary if enabled
            if ($emailEnabled) {
                foreach ($salesUsers as $user) {
                    if ($user->email) {
                        Mail::to($user->email)->queue(new StaleLeadsDigest($staleLeads, $user->name));
                    }
                }
            }

            $this->info("Sent reminders for {$sentCount} lead(s). Escalated: {$escalatedCount}.");
        }

        return self::SUCCESS;
    }
}
