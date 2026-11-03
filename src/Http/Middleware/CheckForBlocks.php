<?php namespace AltDesign\AltInbound\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Closure;

use AltDesign\AltInbound\Helpers\Data;

class CheckForBlocks
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {

        $onBlockedPage = $request->is('alt-design/alt-inbound/blocked');
        $blockOnDisk = (int) Data::isListed($request->ip());
        $blacklisting = Data::whiteListing();

        if ($blockOnDisk == $blacklisting && $onBlockedPage) {
            return redirect('/', 302);
        }

        // Blacklist and present logic table means == works to handle case. don't redirect the blocked page.
        if ($blockOnDisk != $blacklisting && !$onBlockedPage) {
            return redirect('/alt-design/alt-inbound/blocked', 302);
        }

        return $next($request);
    }
}
