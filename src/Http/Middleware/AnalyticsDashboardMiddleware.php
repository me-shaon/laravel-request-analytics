<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MeShaon\RequestAnalytics\Contracts\CanAccessAnalyticsDashboard;

class AnalyticsDashboardMiddleware
{
    /**
     * @return JsonResponse|mixed|never
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user) {
            if (! $user instanceof CanAccessAnalyticsDashboard) {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Unauthorized'], 403)
                    : abort(403);
            }

            if (! $user->canAccessAnalyticsDashboard()) {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Access denied'], 403)
                    : abort(403);
            }
        }

        return $next($request);
    }
}
