<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            $message = 'Tu cuenta esta pausada. Contacta al administrador.';

            if ($user->active_starts_at && $user->active_starts_at->isFuture()) {
                $message = 'Tu cuenta aun no esta activa. Fecha de inicio: ' . $user->active_starts_at->format('d/m/Y') . '.';
            }

            if ($user->active_ends_at && $user->active_ends_at->isPast() && ! $user->active_ends_at->isToday()) {
                $message = 'Tu cuenta vencio el ' . $user->active_ends_at->format('d/m/Y') . '. Contacta al administrador.';
            }

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors([
                'email' => $message,
            ]);
        }

        return $next($request);
    }
}
