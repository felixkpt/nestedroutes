<?php

namespace Felixkpt\Nestedroutes\Http\Middleware;

use App\Models\TemporaryToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class TemporaryTokenValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $temporary_token = $request->token;

        if (!$temporary_token && !auth()->user()) {
            abort(400, 'Token missing.'); // Token is required

        }

        $record = TemporaryToken::where('token', $temporary_token)->first();

        if ((!$record || $this->hasExpired($record)) && !auth()->user()) {
            abort(401, 'Unauthorized.'); // Token not found or has expired
        }

        // Token is valid; proceed with the request
        return $next($request);
    }

    protected function hasExpired($record)
    {
        $expirationTime = Carbon::parse($record->expires_at)
            ->addMinutes(config('temporary_token.expiration_minutes', 1));

        return Carbon::now()->gt($expirationTime);
    }
}
