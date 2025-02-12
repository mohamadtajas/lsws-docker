<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class PreventConcurrentRequests
{
    public function handle($request, Closure $next)
    {
        // Ensure the user is authenticated
        $user = Auth::user();
        if (!$user) {
            if ($request->expectsJson()) {
               return response()->json(['message' => 'Unauthorized'], 401);
            } else {
                abort(404);
            }
        }

        // Generate a unique cache key for this user and route
        $key = 'request-lock:' . $user->id . ':' . md5($request->path());

        // Check if the key exists in the cache (indicating an ongoing request)
        if (Cache::has($key)) {
            if ($request->expectsJson()) {
               return response()->json(['message' => 'Request already in progress, please wait.'], 429);
            } else {
                abort(404);
            }
        }

        // Store the key in the cache with a 50-second expiration
        Cache::put($key, true, now()->addSeconds(50));

        // Proceed with the request
        $response = $next($request);

        // After the request is processed, remove the key from the cache
        // This allows the user to make another request immediately after the response is sent
        Cache::forget($key);

        return $response;
    }
}
