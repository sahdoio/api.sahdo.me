<?php

namespace App\Http\Middleware;

use App\Libs\Butler;
use Closure;

class ButlerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $butler = new Butler($request->all());

        $status = $butler->getStatus();
        if (!isset($status['status']) || $status['status'] !== 'ok') {
            return response()->json($status);
        }

        $request->attributes->add(['butler' => $butler]);
        $request->butler = $butler;

        return $next($request);
    }
}
