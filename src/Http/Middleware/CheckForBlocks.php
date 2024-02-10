<?php

namespace AltDesign\AltBlocker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Statamic\Facades\Site;
use Symfony\Component\HttpFoundation\Response;
use Statamic\Facades\YAML;
use AltDesign\AltBlocker\Helpers\Data;

class CheckForBlocks
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {

        $uri = $request->getRequestUri();
        $b64 = base64_encode($request->ip());
        $blockOnDisk = (int)\Statamic\Facades\File::exists('content/alt-blocker/blocks/' . $b64 . '.yaml');
        $blacklisting = Data::blacklisting();

        // Blacklist and present logic table means == works to handle case. don't redirect the blocked page.
        if ($blockOnDisk == $blacklisting && ($uri != '/alt-design/alt-blocker/blocked')) {
            return redirect('/alt-design/alt-blocker/blocked', 301);
        }
        return $next($request);
    }
}