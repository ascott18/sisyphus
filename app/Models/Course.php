<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Course
 *
 * @property int $term_id The id of the term that the course belongs to, referencing the terms table.
 * @property int $user_id The id of user that teaches the course.
 * @property integer $course_id The database primary key for this model.
 * @property boolean $no_book Whether this course has been marked as not needing a book.
 * @property Carbon $no_book_marked The time at which this course was marked as not needing a book.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $orders
 * @property-read \App\Models\User|null $user The user who teaches the course. May be null if the teacher is unknown/tba.
 * @property-read \App\Models\Term $term
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Course responded()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Course visible($user = null)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Course orderable($user = null)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Listing[] $listings
 */
class Course extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'course_id';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['course_id'];

    public function orders()
    {
        return $this->hasMany('App\Models\Order', 'course_id', 'course_id');
    }

    public function listings()
    {
        return $this->hasMany('App\Models\Listing', 'course_id', 'course_id');
    }

    public function books()
    {
        return $this->hasManyThrough('App\Models\Book', 'App\Models\Order');
    }

    public function user()
    {
        return $this->hasOne('App\Models\User', 'user_id', 'user_id');
    }

    public function term()
    {
        return $this->hasOne('App\Models\Term', 'term_id', 'term_id');
    }

    public function scopeResponded($query){
        return $query->where(function($query){
            return $query->where('no_book', '!=', 0)
                ->orWhere(\DB::raw('(SELECT COUNT(*) FROM orders WHERE courses.course_id = orders.order_id)', '>', 0));
        });
    }


    /**
     * Gets a Query that represents all courseIds that are similar in department and number to this course.
     * Does not restrict by term.
     *
     * @return \Illuminate\Database\Query\Builder The query that represents the similar course ids.
     */
    public function getSimilarCourseIdsQuery(){
        $similarCourseIdsQuery = Listing::select('course_id');


        $similarCourseIdsQuery->where(function($q) {
            foreach ($this->listings as $listing) {
                $q->orWhere(['department' => $listing->department, 'number' => $listing->number]);
            }
        });

        return $similarCourseIdsQuery
            ->distinct()
            ->toBase();
    }

    public function scopeVisible($query, User $user = null){
        return $this->scopeHelper($query, $user, 'view-all-courses', 'view-dept-courses');
    }

    public function scopeOrderable($query, User $user = null){
        return $this->scopeHelper($query, $user, 'place-all-orders', 'place-dept-orders');
    }

    private function scopeHelper($query, User $user = null, $allPermission, $deptPermission){
        if ($user == null)
            $user = \Auth::user();

        if ($user->may($allPermission))
        {
            return $query;
        }
        elseif ($user->may($deptPermission))
        {
            return $query->where(function($q) use ($user) {
                $deptSubQuery = $user
                    ->departments()
                    ->select('department')
                    ->toBase();

                return
                  $q->where('courses.user_id', '=', $user->user_id)
                    ->orWhereIn('courses.course_id',
                        Listing::withoutGlobalScope('order')
                            ->select('course_id')
                            ->whereIn('listings.department', $deptSubQuery)
                            ->toBase()
                );
            });
        }
        else {
            return $query = $query->where('courses.user_id', '=', $user->user_id);
        }
    }

}
