<?php

namespace App\Notifications;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadFollowUpReminder extends Notification
{
    use Queueable;

    public function __construct(
        public Client $client,
        public int $idleDays,
        public bool $isEscalation = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $type = $this->isEscalation ? 'lead_followup_escalation' : 'lead_followup';

        $message = $this->isEscalation
            ? __('messages.lead_escalation_message', [
                'name' => $this->client->name,
                'days' => $this->idleDays,
            ])
            : __('messages.lead_followup_message', [
                'name' => $this->client->name,
                'days' => $this->idleDays,
            ]);

        return [
            'type'      => $type,
            'title'     => $this->isEscalation
                ? __('messages.lead_escalation_title')
                : __('messages.lead_followup_title'),
            'message'   => $message,
            'client_id' => $this->client->id,
            'idle_days' => $this->idleDays,
        ];
    }
}
