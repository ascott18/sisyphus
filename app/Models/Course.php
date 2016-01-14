<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string department The department code for the course, e.g. "CSCD".
 * @property string course_name The name of the course, e.g. "Programming Principles I"
 * @property int course_number The number of the course, e.g. 210.
 * @property int course_section The section of the course, e.g. 01.
 * @property int term_id The id of the term that the course belongs to, referencing the terms table.
 * @property int user_id The id of user that teaches the course.
 * @property integer course_id The database primary key for this model.
 * @property boolean no_book Whether this course has been marked as not needing a book.
 * @property Carbon no_book_marked The time at which this course was marked as not needing a book.
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


    public function displayIdentifier()
    {
        return $this->department . ' ' . $this->course_number . '-' . str_pad($this->course_section, 2, '0', STR_PAD_LEFT);
    }

    public function canPlaceOrder()
    {
        $currentTermsIds = Term::currentTerms()->select('term_id')->get()->values();

        return $currentTermsIds->contains($this->term_id);
    }

    public function orders()
    {
        return $this->hasMany('App\Models\Order', 'course_id', 'course_id');
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

    public function scopeVisible($query, User $user = null){
        if ($user == null)
            $user = \Auth::user();

        if ($user->may('view-dept-courses'))
        {
            $departments = $user->departments()->lists('department');
            $query = $query->where(function($query) use ($departments, $user) {
                $query = $query->whereIn('department', $departments);
                return $query = $query->orWhere('user_id', $user->user_id);
            });
        }
        elseif (!$user->may('view-all-courses'))
        {
            $query = $query->where('user_id', $user->user_id);
        }

        return $query;
    }
}
