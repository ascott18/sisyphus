<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Term
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Course[] $courses
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Term currentOrPast()
 * @property integer $term_id The database primary key for this model.
 * @property integer $term_number The term number, which is a key in static::$termNumbers.
 * @property \Carbon\Carbon $order_start_date The date on which orders will be open for this term.
 * @property \Carbon\Carbon $order_due_date The date by which book orders are due for this term.
 * @property integer $year The year for which the term exists.
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read string $term_name
 * @property-read string $display_name
 * @property-read mixed $status
 */
class Term extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'term_id';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'order_start_date', 'order_due_date'];


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['order_start_date', 'order_due_date'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['term_name', 'display_name', 'status'];

    /**
     * A mapping of term numbers to their English name.
     *
     * @var array
     */
    public static $termNumbers = [
        10 => 'Winter',
        15 => 'Fall Semester',
        20 => 'Spring',
        25 => 'Spring Semester',
        30 => 'Summer',
        35 => 'Summer Semester',
        40 => 'Fall'
    ];

    /**
     * The logical date ordering of the term numbers.
     *
     * @var array
     */
    public static $termOrdering = [
        10, 25, 20, 35, 30, 15, 40
    ];


    /**
     * Creates terms in the database for the given year.
     *
     * @param $year int The year to create terms in the database for.
     */
    public static function createTermsForYear($year)
    {
        foreach (static::$termOrdering as $term_number) {
            if (static::where([
                'term_number' => $term_number,
                'year' => $year])->count() == 0)
            {

                switch ($term_number){
                    case 10: // Winter
                        $end = Carbon::create($year - 1, 12, 4);
                        break;
                    case 25: // Spring Semester
                        $end = Carbon::create($year - 1, 12, 4);
                        break;
                    case 20: // Spring
                        $end = Carbon::create($year, 2, 28);
                        break;
                    case 35: // Summer semester
                        $end = Carbon::create($year, 4, 8);
                        break;
                    case 30: // Summer
                        $end = Carbon::create($year, 5, 20);
                        break;
                    case 15: // Fall semester
                        $end = Carbon::create($year, 7, 21);
                        break;
                    case 40: // Fall
                        $end = Carbon::create($year, 8, 21);
                        break;
                }


                $term = new static([
                    'order_start_date' => $end->copy()->addMonths(-2)->addDays(-15),
                    'order_due_date' => $end->copy(),
                ]);

                // Add these manually - they shouldn't be mass assignable.
                $term->term_number = $term_number;
                $term->year = $year;

                $term->save();
            }
        }
    }


    /**
     * Gets the name of the term_number of this model.
     *
     * @return string
     */
    public function getTermNameAttribute()
    {
        $term = $this->term_number;

        return array_key_exists($term, self::$termNumbers) ? self::$termNumbers[$term] : 'BAD_TERM';
    }

    /**
     * @deprecated Use $term->term_name instead.
     */
    public function termName()
    {
        return $this->term_name;
    }


    /**
     * Gets the name of the term_number of this model.
     *
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        return $this->term_name . ' ' . $this->year;
    }

    /**
     * @deprecated Use $term->display_name instead.
     */
    public function displayName()
    {
        return $this->display_name;
    }

    /**
     * Check if this term's ordering period has started (it may be complete or in progress)
     *
     * @return string
     */
    public function haveOrdersStarted()
    {
        return Carbon::today() >= $this->order_start_date;
    }

    /**
     * Check if this term's ordering period has ended
     *
     * @return string
     */
    public function haveOrdersConcluded()
    {
        return Carbon::today() > $this->order_due_date;
    }

    /**
     * Check if this term's ordering period is in progress
     *
     * @return string
     */
    public function areOrdersInProgress()
    {
        return $this->haveOrdersStarted() && !$this->haveOrdersConcluded();
    }

    /**
     * Get the representation of the status of the term's ordering period as a human-readable string.
     *
     * @return string
     */
    public function getStatusAttribute()
    {
        if ($this->areOrdersInProgress())
        {
            return "In Progress - " . $this->order_due_date->diffInDays(Carbon::today()) . " days left";
        }

        if ($this->haveOrdersConcluded())
        {
            return "Concluded";
        }

        $daysUntilStart = $this->order_start_date->diffInDays(Carbon::today());
        if ($daysUntilStart <= 200)
        {
            return "Starts in $daysUntilStart days";
        }

        return "Will start eventually";
    }


    /**
     * Gets the courses belonging to the term.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function courses()
    {
        return $this->hasMany('App\Models\Course', 'term_id', 'term_id');
    }

    /**
     * Gets the terms for which orders are currently in progress.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function currentTerms()
    {
        return static::
        where('order_due_date', '>=', Carbon::today())->
        where('order_start_date', '<=', Carbon::today());
    }


    /**
     * Gets the terms which ended within the given number of days.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function recentTerms($days)
    {
        return static::
        where('order_due_date', '>=', Carbon::today()->subDays($days))->
        where('order_due_date', '<=', Carbon::today());
    }


    /**
     * Gets the terms which started on or before today
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function scopeCurrentOrPast($query)
    {
        return $query->where('order_start_date', '<=', Carbon::today());
    }


}
