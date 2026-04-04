<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->isAdmin() || $user->id === $ticket->user_id;
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        return ($user->isAdmin() || $user->id === $ticket->user_id) && $ticket->isOpen();
    }

    public function updateStatus(User $user, SupportTicket $ticket): bool
    {
        return $user->isAdmin();
    }
}
