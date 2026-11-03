<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $frameSources = 'frame-src https://www.youtube-nocookie.com https://player.vimeo.com';
        $existingPolicy = $response->headers->get('Content-Security-Policy');

        if ($existingPolicy !== null && trim($existingPolicy) !== '') {
            $directives = array_filter(
                array_map('trim', explode(';', $existingPolicy)),
                fn (string $directive): bool => ! str_starts_with(strtolower($directive), 'frame-src '),
            );
            $frameSources = implode('; ', [...$directives, $frameSources]);
        }

        $response->headers->set('Content-Security-Policy', $frameSources);

        return $response;
    }
}
