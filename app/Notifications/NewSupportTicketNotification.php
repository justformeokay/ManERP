<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewSupportTicketNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'support_ticket',
            'title'         => 'New Support Ticket',
            'message'       => "Ticket #{$this->ticket->ticket_number}: {$this->ticket->title} (Priority: {$this->ticket->priority})",
            'ticket_id'     => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'category'      => $this->ticket->category,
            'priority'      => $this->ticket->priority,
        ];
    }
}
