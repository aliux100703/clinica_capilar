<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInquiriesEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment(['local', 'testing']) && ! config('clinic.privacy_policy_approved')) {
            return response()->json(['data' => null, 'message' => 'Los formularios todavía no están habilitados.', 'errors' => (object) []], 503);
        }

        return $next($request);
    }
}
