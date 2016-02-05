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
    public function course()
    {
        return $this->hasOne('App\Models\Course', 'course_id', 'course_id');
    }

}
