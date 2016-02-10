<?php

namespace App\Http\Middleware;

use App\Providers\AuthServiceProvider;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;

class EnsureActionAuthorized
{

    protected $authProvider;

    public function __construct(AuthServiceProvider $authProvider)
    {
        $this->authProvider = $authProvider;
    }


    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     * @throws AuthorizationException
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Don't warn about this if we're responding with an exception,
        // or if the status is 422, which means that validation failed.
        if ($response->status() == 422
            || (isset($response->exception) && $response->exception))
        {
            return $response;
        }

        if (!$this->authProvider->getHasControllerAttemptedAuthorization()){
            $message = "Controller did not attempt any authorization.";

            if (config("app.debug")){
                $message .= "\n\nDeveloper Notes:\n"
                    . 'Make sure to call $this->authorize("some-ability") in this action at least once for all execution paths.
                    Explicitly authorize the "all" ability if the action is truly public.';
            }

            throw new AuthorizationException($message);
        }

        return $response;
    }
}
