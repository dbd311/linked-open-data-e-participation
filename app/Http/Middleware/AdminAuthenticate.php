<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Session;

use Closure;

/* * *
 * Check the admin rights before running services in cpanel
 * @author: Duy Dinh <dinhbaduy@gmail.com>
 * @date 05 August 2016
 */

class AdminAuthenticate extends Authenticate {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        if (Session::get('user')) {
            if (substr(Session::get('user')->role->value, sizeof(Session::get('user')->role->value) - 6) == 'admin') {
                return $next($request);
            }
        } else {
            return response('Unauthorized.', 401);
        }
    }

}
