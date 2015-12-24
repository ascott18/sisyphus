<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Carbon order_start_date The date on which orders will be open for this term.
 * @property Carbon order_due_date The date by which book orders are due for this term.
 * @property integer year The year for which the term exists.
 * @property integer term_number The term number, which is a key in static::$termNumbers.
 * @property integer term_id The database primary key for this model.
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
        10, 20, 25, 30, 35, 40, 15
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
                        $start = Carbon::create($year - 1, 10, 01);
                        break;
                    case 20: // Spring
                    case 25: // Spring Semester
                        $start = Carbon::create($year, 1, 01);
                        break;
                    case 30: // Summer
                    case 35: // Summer semester
                        $start = Carbon::create($year, 4, 01);
                        break;
                    case 40: // Fall
                    case 15: // Fall semester
                        $start = Carbon::create($year, 7, 01);
                        break;
                }

                // TODO: figure out what these dates should look like, roughly, from the bookstore.

                $term = new static([
                    'order_start_date' => $start->copy(),
                    'order_due_date' => $start->copy()->addMonths(1)->addDays(20),
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
    public function termName()
    {
        $term = $this->term_number;

        return array_key_exists($term, self::$termNumbers) ? self::$termNumbers[$term] : 'BAD_TERM';
    }

    /**
     * Check if this term's ordering period has started (it may be complete or in progress)
     *
     * @return string
     */
    public function haveOrdersStarted()
    {
        return Carbon::now() > $this->order_start_date;
    }

    /**
     * Check if this term's ordering period has ended
     *
     * @return string
     */
    public function haveOrdersConcluded()
    {
        return Carbon::now() > $this->order_due_date;
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
    public function getStatusDisplayString()
    {
        if ($this->areOrdersInProgress())
        {
            return "In Progress - " . $this->order_due_date->diffInDays(Carbon::now()) . " days left";
        }

        if ($this->haveOrdersConcluded())
        {
            return "Concluded";
        }

        $daysUntilStart = $this->order_start_date->diffInDays(Carbon::now());
        if ($daysUntilStart <= 100)
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
            where('order_due_date', '>=', Carbon::now())->
            where('order_start_date', '<=', Carbon::now());
    }


}
