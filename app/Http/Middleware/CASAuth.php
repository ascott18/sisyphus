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


    /**
     * Checks whether or not CAS_PRETEND_USER is set in the .env file.
     * xavrsl/cas provides this functionality, but this method is not public for some reason,
     * so we roll our own.
     *
     * @return bool
     */
    public static function isPretending()
    {
        return !empty(config("cas.cas_pretend_user"));
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
        // Checks whether there is an active session associated with the current request.
        if ($this->auth->guest())
        {
            // Retrieve the instance of CasManager from the Laravel service container.
            // This comes from CasServiceProvider->register()
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

            // If the user isn't found, then they almost certainly have no need to access this system.
            // Throw an exception to present them with an appropriate message, which will halt the request.
            if (is_null($user))
            {
                throw new AccessDeniedHttpException("User '$net_id' was not found in this system.\n Please contact an administrator if you need to be added.");
            }

            // Log the user in with Laravel, starting a session for them.
            $this->auth->login($user);
        }

        return $next($request);
    }
}
