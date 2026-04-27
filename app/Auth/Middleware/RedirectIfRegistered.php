<?php

declare(strict_types=1);

namespace App\Auth\Middleware;

use App\User\Models\UserModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wpuszcza anonimowych i ghostów (email NULL).
 * Zarejestrowanych redirectuje na home — nie ma po co lądować na /register lub /login.
 */
class RedirectIfRegistered
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof UserModel && ! $user->isGuest()) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
