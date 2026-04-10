<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use Auditable;

    protected string $model = 'projects';
    public function index(Request $request)
    {
        $projects = Project::with('client')
            ->search($request->input('search'))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->input('type'), fn($q, $t) => $q->where('type', $t))
            ->when($request->input('client_id'), fn($q, $c) => $q->where('client_id', $c))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $clients = Client::active()->orderBy('name')->get(['id', 'name']);

        return view('projects.index', compact('projects', 'clients'));
    }

    public function create()
    {
        return view('projects.form', [
            'project' => new Project(['status' => 'draft']),
            'clients' => Client::active()->orderBy('name')->get(['id', 'name']),
            'users'   => User::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(ProjectRequest $request)
    {
        $project = Project::create($request->validated());
        $this->logCreate($project);

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load('client', 'manager');

        $purchaseOrders = $project->purchaseOrders()
            ->with('supplier')
            ->latest('order_date')
            ->get();

        $totalPurchased = $purchaseOrders->whereNotIn('status', ['cancelled'])->sum('total');

        return view('projects.show', compact('project', 'purchaseOrders', 'totalPurchased'));
    }

    public function edit(Project $project)
    {
        return view('projects.form', [
            'project' => $project,
            'clients' => Client::active()->orderBy('name')->get(['id', 'name']),
            'users'   => User::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(ProjectRequest $request, Project $project)
    {
        $oldData = $project->getOriginal();
        $project->update($request->validated());
        $this->logUpdate($project, $oldData);

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $this->logDelete($project);
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }
}
