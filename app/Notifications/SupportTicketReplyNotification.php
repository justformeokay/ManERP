<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupportTicketReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
        public SupportTicketReply $reply,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'support_ticket_reply',
            'title'         => 'New Reply on Ticket',
            'message'       => "New reply on #{$this->ticket->ticket_number}: {$this->ticket->title} by {$this->reply->user->name}",
            'ticket_id'     => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'reply_by'      => $this->reply->user->name,
        ];
    }
}
