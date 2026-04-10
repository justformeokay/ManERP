<?php

namespace App\Observers;

use App\Models\Client;
use App\Models\Invoice;
use App\Services\AuditLogService;

class InvoiceObserver
{
    /**
     * Handle the Invoice "updated" event.
     *
     * When an invoice transitions to 'paid' and the client is a 'prospect',
     * automatically convert the client to 'customer'.
     */
    public function updated(Invoice $invoice): void
    {
        // Only act when status just changed to 'paid'
        if (! $invoice->wasChanged('status') || $invoice->status !== 'paid') {
            return;
        }

        $client = $invoice->client;

        if (! $client || $client->type !== 'prospect') {
            return;
        }

        $oldData = $client->getOriginal();
        $client->update(['type' => 'customer']);

        // Audit log for automatic conversion
        AuditLogService::log(
            'clients',
            'update',
            "Client #{$client->id} automatically converted from 'prospect' to 'customer' (Invoice #{$invoice->id} paid)",
            $oldData,
            $client->fresh()->toArray(),
            $client,
        );

        // Flash a notification so the next page load shows a toast
        if (session()->isStarted()) {
            session()->flash('success', __('messages.auto_conversion_notification', [
                'name' => $client->name,
            ]));
        }
    }
}
