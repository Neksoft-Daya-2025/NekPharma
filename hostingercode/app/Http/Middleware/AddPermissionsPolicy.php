<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddPermissionsPolicy
{
    /**
     * Allow unload for same-origin so Pusher/Echo and other libs that use
     * addUnloadListener do not trigger "Permissions policy violation: unload is not allowed".
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('Permissions-Policy', 'unload=(self)', true);
        }

        return $response;
    }
}
