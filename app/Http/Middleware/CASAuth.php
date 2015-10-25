<?php

namespace App\Http\Middleware;

// From https://github.com/subfission/cas/blob/master/src/Subfission/Cas/Middleware/CASAuth.php

use Closure;
use Illuminate\Contracts\Auth\Guard;

class CASAuth {

    protected $config;
    protected $auth;
    protected $session;

    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
        $this->config = config('cas');

        if (config('app.debug') && !$this->config['cas_hostname'])
        {
            throw new \Exception("You're missing the CAS config in your .env file. Grab it from .env.example!");
        }

        $this->session = app('session');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->auth->guest())
        {
            if ($request->ajax())
            {
                return response('Unauthorized.', 401);
            }
            $cas = app('cas');
            $cas->authenticate();

            $net_id = $cas->getCurrentUser();

            $user = \App\Models\User::where(['net_id' => $net_id])->first();

            if (is_null($user))
            {
                $user = new \App\Models\User();
                $user->net_id = $net_id;
                $user->save();
            }

            $this->session->put('user_id', $user->user_id);
            $this->session->put('net_id', $net_id);
        }

        return $next($request);
    }
}
