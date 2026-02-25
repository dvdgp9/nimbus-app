<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    /**
     * Handle an incoming request.
     * Redirect to onboarding if user hasn't completed it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !auth()->user()->hasCompletedOnboarding()) {
            // Allow access to onboarding routes and logout
            if ($request->routeIs('onboarding.*') || $request->routeIs('logout')) {
                return $next($request);
            }
            
            // Allow Google OAuth callback during onboarding
            if ($request->routeIs('google.callback') || $request->routeIs('google.redirect')) {
                return $next($request);
            }

            return redirect()->route('onboarding.index');
        }

        return $next($request);
    }
}
