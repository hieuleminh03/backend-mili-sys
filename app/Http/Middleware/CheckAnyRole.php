<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAnyRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated',
                'errors' => ['auth' => ['You must be logged in to access this resource']]
            ], 401);
        }
        
        foreach ($roles as $role) {
            if ($request->user()->hasRole($role)) {
                return $next($request);
            }
        }
        
        return response()->json([
            'message' => 'Access denied',
            'errors' => ['role' => ['You do not have permission to access this resource']]
        ], 403);
    }
    
    /**
     * Perform any needed cleanup after the response has been sent to the browser.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function terminate($request, $response)
    {
        // No specific cleanup needed for role checking
        // This method prevents Laravel from trying to resolve the middleware during termination
    }
} 