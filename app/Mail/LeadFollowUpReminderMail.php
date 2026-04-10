<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadFollowUpReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public string $salesName,
        public int $idleDays,
        public bool $isEscalation = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isEscalation
            ? __('messages.email_lead_escalation_subject', ['name' => $this->client->name])
            : __('messages.email_lead_reminder_subject', ['name' => $this->client->name]);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-followup-reminder',
        );
    }
}
