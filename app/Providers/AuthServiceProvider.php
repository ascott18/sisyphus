<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Request;
use View;

class AuthServiceProvider extends ServiceProvider
{

    protected $controllerAuthCount = 0;
    protected $isDebuggingUnauthorizedAction = false;

    /**
     * Gets whether or not an attempt has been made to authorize the
     * current request before the view has started rendering.
     *
     * @return bool Whether or not an authorization attempt has been made.
     */
    public function getHasControllerAttemptedAuthorization(){
        return $this->controllerAuthCount > 0;
    }

    /**
     * Gets whether or not we are in debug mode and have bypassed an auth
     * check as a result of being in debug mode.
     *
     * @return bool Whether or not an authorization attempt has been bypassed.
     */
    public function getIsDebuggingUnauthorizedAction(){
        return $this->isDebuggingUnauthorizedAction;
    }

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

        $gate->before(function ($user, $ability, $data = null) {
            // We do a recursive role check by calling denies($ability), so
            // prevent us from doing the global check here if we're already doing it now.
            global $runningBefore;

            // See comments below for why we check doneRendering()
            if (View::doneRendering()){
                $this->controllerAuthCount++;
            }

            if (config("app.debug") && !$runningBefore){
                $runningBefore = true;
                if (Gate::forUser($user)->denies($ability, $data) ) {
                    // View::doneRendering() returns true if we are done rendering the view (duh),
                    // OR if we have not yet started rendering the view (which is actually what we want to check here)

                    // The reason for this is that there is role checking for the menu items, but we don't want
                    // to display this message if the only thing being checked is those items in master.blade.php.

                    // In other words, if we haven't started rendering yet, then we must be still running through the controller,
                    // so it is safe to display this message at that point
                    // (the user is unauthorized to do something in the controller if we reach this point).
                    if (!Request::ajax() && View::doneRendering()){
                        $this->isDebuggingUnauthorizedAction = true;
                    }
                    $runningBefore = false;
                     return true;
                }
                $runningBefore = false;
            }
        });

        // Use sparingly!
        $gate->define('all', function (User $user) {
            return true;
        });


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

        $gate->define('edit-order', function (User $user, Order $order) {
            if ($user->may('edit-all-orders')) {
                return true;
            }

            return false;
        });

        $gate->define('place-order-for-course', function (User $user, Course $course) {
            if ($user->may('place-all-orders')) {
                return true;
            }

            if ($user->may('place-dept-orders') &&
                $user->departments()->where('department', '=', $course->department)->count()){
                return true;
            }

            if ($user->user_id == $course->user_id){
                return true;
            }

            return false;
        });

        $gate->define('view-course', function (User $user, Course $course) {
            if ($user->may('view-all-courses')) {
                return true;
            }

            if ($user->may('view-dept-courses') &&
                $user->departments()->where('department', '=', $course->department)->count()){
                return true;
            }

            if ($user->user_id == $course->user_id){
                return true;
            }

            return false;
        });


        $gate->define('view-course-list', function (User $user) {
            return $user->may('view-course-list');
        });


        $gate->define('place-order-for-user', function (User $user, User $targetUser) {
            if ($user->may('place-all-orders')) {
                return true;
            }

            // TODO: restrict these courses to those of the current term.
            if ($user->may('place-dept-orders')) {
                $departments = $user->departments()->lists('department');
                foreach ($targetUser->currentCourses as $course) {
                    if ($departments->contains($course->department)) {
                        return true;
                    }
                }
            }

            if ($user->user_id == $targetUser->user_id){
                return true;
            }

            return false;
        });


        $gate->define('edit-book', function (User $user, Book $book) {
            if ($user->may('edit-books')) {
                return true;
            }

            return false;
        });





        $gate->define('manage-users', function (User $user) {
            return $user->may('manage-users');
        });

        $gate->define('manage-roles', function (User $user) {
            return $user->may('manage-roles');
        });

        $gate->define('view-terms', function (User $user) {
            return $user->may('view-terms');
        });

        $gate->define('edit-terms', function (User $user) {
            return $user->may('edit-terms');
        });

        $gate->define('send-messages', function (User $user) {
            return $user->may('send-messages-to-all')
            || $user->may('send-messages-to-department');
        });

        $gate->define('touch-message', function (User $user, Message $message) {
            return $message->owner_user_id == $user->user_id;
        });

        $gate->define('send-message-to-user', function (User $user, User $recipient) {
            if ($user->may('send-messages-to-all'))
                return true;

            $userDepartments = $user->departments();

            // TODO: test if this works
            if ($user->may('send-messages-to-department') &&
                $recipient->courses()->whereIn('department', $userDepartments)->first() != null){
                return true;
            }

            return false;
        });
    }

    public function register()
    {
        parent::register();

        $this->app->singleton(AuthServiceProvider::class, function ($app) {
            return $this;
        });
    }
}
