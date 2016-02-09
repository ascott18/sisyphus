<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Listing
 *
 * @property integer $listing_id
 * @property string $name
 * @property integer $number
 * @property string $department
 * @property integer $section
 * @property integer $course_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Course $course
 */
class Listing extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'listing_id';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['listing_id'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure that our ordering of listings is correct.
        // The 'primary' listing of a course is always going to be the one with the lowest pk.
        // MySQL doesn't guarantee any sort of ordering with plain select statements, so we do
        // this to help ensure that things are always consistent.
        static::addGlobalScope('order', function(Builder $builder) {
            $builder->orderBy('listing_id');
        });
    }

    public function displayIdentifier()
    {
        return $this->department . ' ' . str_pad($this->number, 3, '0', STR_PAD_LEFT) . '-' . str_pad($this->section, 2, '0', STR_PAD_LEFT);
    }

    public function course()
    {
        return $this->hasOne('App\Models\Course', 'course_id', 'course_id');
    }

}
