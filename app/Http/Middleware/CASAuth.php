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

    private function isPretending()
    {
        if (!empty(config("cas.cas_pretend_user")))
        {
            return true;
        }
        return false;
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
        $cas = app('cas');

        // This will throw an exception if authentication fails,
        // and will immediately redirect to login.ewu.edu if authentication is needed.
        $cas->authenticate();


        // If we get here, the user is authenticated with CAS.

        $net_id = $cas->getCurrentUser();

        $user = \App\Models\User::where(['net_id' => $net_id])->first();

        if (is_null($user))
        {
            if (!$this->isPretending())
            {
                $attributes = $cas->getAttributes();
            }
            else
            {
                $attributes = [
                    'Ewuid' => "00123456",
                ];
            }

            $user = new \App\Models\User();
            $user->net_id = $net_id;
            $user->ewu_id = $attributes['Ewuid'];
            $user->save();
        }

        // Second parameter is to prevent persistent logins.
        // We want to auth with CAS every time, in case the user has logged out.
        $this->auth->login($user, false);

        $this->session->put('user_id', $user->user_id);
        $this->session->put('net_id', $net_id);

        return $next($request);
    }
}
