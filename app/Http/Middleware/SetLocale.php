<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales.
     */
    public const SUPPORTED_LOCALES = ['en', 'id', 'zh', 'ko'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priority: session > user preference > default
        $locale = session('locale');

        // If user is logged in and has locale preference, use it
        if (!$locale && auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
            session(['locale' => $locale]);
        }

        // Validate and set locale
        if ($locale && in_array($locale, self::SUPPORTED_LOCALES)) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Check if locale is supported.
     */
    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }
}
