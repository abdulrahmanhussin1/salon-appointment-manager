<?php

namespace App\Http\Middleware;

use App\Traits\AppHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    use AppHelper;
    public function getRoute()
    {
        return Route::current()->getName();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $type = null;
            $page = null;
            if (str_contains($this->getRoute(), '.')) {
                [$type, $page] = explode('.', $this->getRoute());
            }
            if (
                $this->getRoute() === 'home.index'
                ||  ($page === 'multi_destroy' && self::perUSer($type . '.destroy'))
                || ($page === 'store' && self::perUSer($type . '.create'))
                || ($page === 'export' && self::perUSer($type . '.export'))
                || ($page === 'update' && self::perUSer($type . '.edit'))
                || ($page === 'transferOut' && self::perUSer($type . '.transferOutView'))
                || ($page === 'transferIn' && self::perUSer($type . '.transferInView'))

                || self::perUSer($this->getRoute())
                || in_array($this->getRoute(), ['dashboard', 'sales_invoices.getItem', 'sales_invoices.getRelatedEmployees'])
            ) {
                return $next($request);
            }
            return abort(403);
        }
        return redirect()->route('redirect');
    }
}
