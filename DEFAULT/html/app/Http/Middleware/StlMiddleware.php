<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StlMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $secret = $request->header('X-Secret-Key');
        $access = $request->header('X-Access-Token');

        if (isset($secret) && $secret == env('API_STL_SECRET_KEY') &&  isset($access) && $access == env('API_STL_ACCESS_TOKEN')) {
            return $next($request);
        }

        return response()->json([
            'data' => '',
            'message' => 'Non Authoritative Information',
            'code'    => 203
        ], 203);
    }
}
