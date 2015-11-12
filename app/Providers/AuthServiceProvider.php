<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function boot(GateContract $gate)
    {
        $this->registerPolicies($gate);

        $gate->define('view-order', function (User $user, Order $order) {
            if ($user->may('view-all-orders')) {
                return true;
            }

            if ($user->may('view-dept-orders') &&
                $user->departments()->where('department', '=', $order->course()->department)->count()){
                return true;
            }

            if ($user->user_id == $order->course()->user_id){
                return true;
            }

            return false;
        });
    }
}
