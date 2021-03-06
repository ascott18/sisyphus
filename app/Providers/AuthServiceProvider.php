<?php

namespace App\Providers;

use App\Models\Book;
use App\Models\Course;
use App\Models\Message;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
                    if (View::doneRendering()){
                        $this->isDebuggingUnauthorizedAction = true;
                    }
                    $runningBefore = false;
                    // return true;
                }
                $runningBefore = false;
            }
        });

        // Use sparingly!
        $gate->define('all', function (User $user) {
            return true;
        });


        $courseFilter = function (User $user, Course $course, $allPermission, $deptPermission) {
            if ($user->may($allPermission)) {
                return true;
            }

            if ($user->may($deptPermission) &&
                $user->departments->whereIn('department', Arr::pluck($course->listings, 'department'))->count()){
                return true;
            }

            if ($user->user_id == $course->user_id){
                return true;
            }

            return false;
        };

        $gate->define('place-order-for-course', function (User $user, Course $course) use ($courseFilter) {
            if (!$user->can('view-course', $course))
                return false;

            if (!$course->term->areOrdersInProgress() && !$user->may('order-outside-period'))
                return false;

            return $courseFilter($user, $course, 'place-all-orders', 'place-dept-orders');
        });

        $gate->define('view-course', function (User $user, Course $course) use ($courseFilter) {
            return $courseFilter($user, $course, 'view-all-courses', 'view-dept-courses');
        });


        $gate->define('view-course-list', function (User $user) {
            return true; // $user->may('view-course-list');
        });

        $gate->define('modify-course', function (User $user, Course $course) {
            return $user->may('edit-courses') && $user->can('view-course', $course);
        });

        $gate->define('modify-courses', function (User $user) {
            return $user->may('edit-courses');
        });


        $gate->define('edit-books', function (User $user) {
            return $user->may('edit-books');
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

        $gate->define('touch-message', function (User $user, Message $message) {
            return $message->owner_user_id == $user->user_id;
        });

        $gate->define('send-messages', function (User $user) {
            return $user->may('send-all-messages')
            || $user->may('send-dept-messages');
        });


        $gate->define('view-ticket', function (User $user, Ticket $ticket) {
            if ($ticket->user_id == $user->user_id)
                true;

            // Taking the easy way out...
            return Ticket::visible()->where('ticket_id', '=', $ticket->ticket_id)->count();
        });

        $gate->define('view-dashboard', function (User $user) {
            return $user->may('view-dashboard');
        });

        $gate->define('make-reports', function (User $user) {
           return $user->may("make-reports");
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
