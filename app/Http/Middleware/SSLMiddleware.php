<?php

namespace App\Http\Middleware;

use Closure;

class SSLMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   
        if ($request->server('HTTP_X_FORWARDED_PROTO') == 'http') {
            return redirect()->secure($request->getRequestUri());
        }
        
        return $next($request);
    }
}
