<?php

namespace App\Http\Controllers;

use App\Models\SystemLicense;
use App\Services\LicenseService;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index()
    {
        $license = LicenseService::current();
        $activeUsers = $license ? $license->activeUserCount() : 0;

        return view('license.index', compact('license', 'activeUsers'));
    }

    public function activate()
    {
        return view('license.activate');
    }

    public function processActivation(Request $request)
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|size:64',
            'company_name' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
        ]);

        $result = LicenseService::activate(
            $validated['serial_number'],
            $validated['company_name'],
            $validated['domain']
        );

        if ($result['success']) {
            return redirect()->route('license.index')
                ->with('success', __('messages.' . $result['message']));
        }

        return back()
            ->withInput()
            ->with('error', __('messages.' . $result['message']));
    }

    public function expired()
    {
        $license = LicenseService::current();
        return view('license.expired', compact('license'));
    }
}
