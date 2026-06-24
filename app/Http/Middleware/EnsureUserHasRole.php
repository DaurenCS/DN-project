<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): mixed
    {
        if ($request->is('*/login')) {
            return $next($request);
        }

        if (!$request->user()) {
            return $next($request);
        }

        if (!$request->user()->hasAnyRole($roles)) {
            abort(403, 'Доступ запрещен: у вас недостаточно прав.');
        }

        return $next($request);
    }
}
