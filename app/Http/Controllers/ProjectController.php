<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::with('client')
            ->search($request->input('search'))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
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
        Project::create($request->validated());

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load('client', 'manager');

        return view('projects.show', compact('project'));
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
        $project->update($request->validated());

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }
}
