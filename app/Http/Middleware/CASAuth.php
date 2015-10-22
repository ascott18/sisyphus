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

        // Andrew 10-21-2015: This is important. This comes from the config as a string,
        // but phpCAS validates it to be an int. It will try to throw an exception that doesn't
        // exist if it is not an int.
        // Because the config is reloaded again later in the process, we have to set the proper value
        // directly in _ENV. See https://github.com/Jasig/phpCAS/issues/162
        $_ENV['CAS_PORT'] = (int)$this->config['cas_port'];

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
        }

        return $next($request);
    }
}
