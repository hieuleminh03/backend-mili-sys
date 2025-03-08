<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        // Log the authorization header for debugging
        Log::debug('Auth header: ' . $request->header('Authorization'));

        // Check if authorization header exists
        if (!$request->header('Authorization')) {
            Log::warning('JWT: No Authorization header present');
            return response()->json(['message' => 'Unauthenticated. Token not provided.'], 401);
        }

        try {
            // Check token format
            $token = str_replace('Bearer ', '', $request->header('Authorization'));
            if (empty($token)) {
                Log::warning('JWT: Empty token after Bearer prefix removal');
                return response()->json(['message' => 'Unauthenticated. Empty token.'], 401); 
            }

            // Try to authenticate the user
            $user = JWTAuth::setToken($token)->authenticate();
            
            if (!$user) {
                Log::warning('JWT: User not found for token');
                return response()->json(['message' => 'User not found'], 404);
            }

            // Set the authenticated user in the auth guard
            auth()->setUser($user);
            
            // Log successful authentication
            Log::info('JWT: Authentication successful for user #' . $user->id);
            
        } catch (TokenExpiredException $e) {
            Log::warning('JWT: Token expired');
            return response()->json(['message' => 'Token expired'], 401);
        } catch (TokenInvalidException $e) {
            Log::warning('JWT: Token invalid', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Token invalid'], 401);
        } catch (JWTException $e) {
            Log::warning('JWT: ' . $e->getMessage());
            return response()->json(['message' => 'Unauthenticated. ' . $e->getMessage()], 401);
        } catch (\Exception $e) {
            Log::error('JWT: Unexpected error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Unauthenticated. Server error.'], 500);
        }

        return $next($request);
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
        // Clean up after the request
        try {
            // Invalidate token if it was a logout request
            if ($request->is('api/logout')) {
                JWTAuth::invalidate(JWTAuth::getToken());
            }
        } catch (JWTException $e) {
            // Just log or ignore exception during termination
            Log::notice('JWT termination exception: ' . $e->getMessage());
        }
    }
}