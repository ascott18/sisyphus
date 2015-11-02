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

        return "Not yet started";
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


}
