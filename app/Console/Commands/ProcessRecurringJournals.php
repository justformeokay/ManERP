<?php

namespace App\Console\Commands;

use App\Models\JournalEntry;
use App\Models\JournalTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessRecurringJournals extends Command
{
    protected $signature = 'journals:process-recurring';
    protected $description = 'Process all due recurring journal templates and create journal entries';

    public function handle(): int
    {
        $templates = JournalTemplate::recurringDue()->get();

        if ($templates->isEmpty()) {
            $this->info('No recurring journals due.');
            return self::SUCCESS;
        }

        $processed = 0;

        foreach ($templates as $template) {
            try {
                DB::transaction(function () use ($template) {
                    $journal = JournalEntry::create([
                        'reference'   => 'REC-' . $template->id . '-' . now()->format('Ymd'),
                        'date'        => now()->toDateString(),
                        'description' => $template->name . ' (Recurring)',
                    ]);

                    foreach ($template->lines as $line) {
                        $journal->items()->create([
                            'account_id' => $line['account_id'],
                            'debit'      => $line['debit'] ?? 0,
                            'credit'     => $line['credit'] ?? 0,
                        ]);
                    }

                    $template->update([
                        'last_run_date' => now(),
                        'next_run_date' => $this->calculateNextRun($template),
                    ]);
                });

                $processed++;
                $this->line("  ✓ {$template->name}");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$template->name}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$processed}/{$templates->count()} recurring journals.");
        return self::SUCCESS;
    }

    private function calculateNextRun(JournalTemplate $template): \Carbon\Carbon
    {
        $base = now();

        return match ($template->frequency) {
            'daily'     => $base->addDay(),
            'weekly'    => $base->addWeek(),
            'monthly'   => $base->addMonth(),
            'quarterly' => $base->addMonths(3),
            'yearly'    => $base->addYear(),
            default     => $base->addMonth(),
        };
    }
}
