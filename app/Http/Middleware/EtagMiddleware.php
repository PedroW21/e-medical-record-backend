<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds HTTP cache validators to successful GET responses so that clients can
 * revalidate cached payloads via `If-None-Match` and receive a cheap
 * `304 Not Modified` when nothing changed.
 *
 * The ETag is a weak validator built from the md5 hash of the response body.
 * `Cache-Control: private, must-revalidate` forces the browser to revalidate
 * with the server before serving any locally cached copy.
 */
final class EtagMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return $response;
        }

        $etag = 'W/"'.md5((string) $response->getContent()).'"';

        $response->headers->set('ETag', $etag);
        $response->headers->set('Cache-Control', 'private, must-revalidate');

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'private, must-revalidate');
        }

        return $response;
    }
}
