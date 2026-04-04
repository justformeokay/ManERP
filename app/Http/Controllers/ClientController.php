<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use Auditable;

    protected string $model = 'clients';
    public function index(Request $request)
    {
        $clients = Client::query()
            ->search($request->input('search'))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->input('type'), fn($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.form', [
            'client' => new Client(['status' => 'active', 'type' => 'customer']),
        ]);
    }

    public function store(ClientRequest $request)
    {
        $client = Client::create($request->validated());
        $this->logCreate($client);

        return redirect()->route('clients.show', $client)->with('success', 'Client created successfully.');
    }

    public function show(Client $client, ClientService $clientService)
    {
        $exposure = $clientService->calculateTotalExposure($client->id);
        $overdue = $clientService->checkOverdueInvoices($client->id);
        $creditLimit = (float) $client->credit_limit;
        $usagePercent = ($creditLimit > 0) ? min(100, round(((float) $exposure / $creditLimit) * 100, 1)) : 0;

        return view('clients.show', compact('client', 'exposure', 'overdue', 'usagePercent'));
    }

    public function edit(Client $client)
    {
        return view('clients.form', compact('client'));
    }

    public function update(ClientRequest $request, Client $client)
    {
        $oldData = $client->getOriginal();
        $client->update($request->validated());
        $this->logUpdate($client, $oldData);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $this->logDelete($client);
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }
}
