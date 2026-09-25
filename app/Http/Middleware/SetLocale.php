<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedLocales = ['en', 'ar'];

        if ($request->has('lang') && in_array($request->query('lang'), $allowedLocales, true)) {
            $locale = (string) $request->query('lang');
            session(['locale' => $locale]);
        } else {
            $locale = session('locale', config('app.locale', 'en'));
            if (! in_array($locale, $allowedLocales, true)) {
                $locale = config('app.locale', 'en');
            }
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
