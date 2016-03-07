<?php

namespace App\Http\Middleware;

// From https://github.com/subfission/cas/blob/master/src/Subfission/Cas/Middleware/CASAuth.php

use Closure;
use Illuminate\Contracts\Auth\Guard;
use phpCAS;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

    public static function isPretending()
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
        if ($this->auth->guest())
        {
            $cas = app('cas');

            // Need to manually connect since we're bypassing Xavrsl\Cas\CasManager for our authentication attempt.
            $cas->connection();


            // This will throw an exception if authentication fails,
            // and will immediately redirect to login.ewu.edu if authentication is needed.
            // We call out to phpCas manually because Xavrsl/Cas swallows up exceptions in $cas->authenticate() for some reason.
            // $cas->authenticate();

            if (!$this->isPretending()){
                phpCAS::forceAuthentication();
            }


            // If we get here, the user is authenticated with CAS.
            $net_id = $cas->getCurrentUser();

            $user = \App\Models\User::where(['net_id' => $net_id])->first();

            if (is_null($user))
            {
                throw new AccessDeniedHttpException("User '$net_id' was not found in this system.\n Please contact an administrator if you believe this to be in error.");
            }

            $this->auth->login($user);
        }

        return $next($request);
    }
}
