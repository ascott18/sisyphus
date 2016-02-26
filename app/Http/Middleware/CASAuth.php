<?php

namespace App\Http\Middleware;

// From https://github.com/subfission/cas/blob/master/src/Subfission/Cas/Middleware/CASAuth.php

use Closure;
use Illuminate\Contracts\Auth\Guard;
use phpCAS;

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
        // TODO: Do we want to check this?
        // If we do check this, we will only auth with CAS when the session expires (config.session.lifetime)
        // If we don't check this, we will auth with CAS every single request. Both have their merits, i guess.
        if ($this->auth->guest())
        {
            $cas = app('cas');

            // Need to manually connect since we're bypassing Xavrsl\Cas\CasManager for our authentication attempt.
            $cas->connection();


            // This will throw an exception if authentication fails,
            // and will immediately redirect to login.ewu.edu if authentication is needed.
            // We call out to phpCas manually because Xavrsl/Cas swallows up exceptions in $cas->authenticate() for some reason.
//          $cas->authenticate();

            if (!$this->isPretending()){
                phpCAS::forceAuthentication();
            }


            // If we get here, the user is authenticated with CAS.
            $net_id = $cas->getCurrentUser();

            $oldUser = $this->auth->user();

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

            if (!$oldUser || $oldUser['net_id'] != $net_id)
            {
                $this->auth->login($user);
            }
        }

        return $next($request);
    }
}
