<?php

namespace App\Models;

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

    public function course()
    {
        return $this->hasOne('App\Models\Course', 'course_id', 'course_id');
    }

}
