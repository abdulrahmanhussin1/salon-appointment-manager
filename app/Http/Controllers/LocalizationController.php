<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocalizationController extends Controller
{
    /**
     * Switch application locale and redirect back.
     */
    public function switch(string $locale, Request $request): RedirectResponse
    {
        $allowedLocales = ['en', 'ar'];

        if (in_array($locale, $allowedLocales, true)) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
        }

        return redirect()->back();
    }
}
