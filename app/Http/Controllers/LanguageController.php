<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Switch the application locale.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        // Validate locale
        if (!SetLocale::isSupported($locale)) {
            return back()->with('error', __('messages.invalid_language'));
        }

        // Save to session
        session(['locale' => $locale]);

        // If user is logged in, save preference to database
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }

        return back()->with('success', __('messages.language_changed'));
    }
}
