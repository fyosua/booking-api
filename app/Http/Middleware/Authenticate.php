<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // If the request expects JSON, don't redirect, just return null
        return $request->expectsJson() ? null : null;
    }

    /**
     * Handle unauthenticated requests.
     */
    protected function unauthenticated($request, array $guards)
    {
        // Return a JSON response if the request expects JSON
        if ($request->expectsJson()) {
            abort(response()->json(['error' => 'Unauthenticated'], 401));
        }

        // Default behavior (for non-API requests)
        parent::unauthenticated($request, $guards);
    }
}