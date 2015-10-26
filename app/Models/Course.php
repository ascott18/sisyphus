<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string department
 * @property int course_number
 * @property int course_section
 */
class Course extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'course_id';


    public function displayIdentifier()
    {
        return $this->department . ' ' . $this->course_number . '-' . str_pad($this->course_section, 2, '0', STR_PAD_LEFT);
    }

    public function orders()
    {
        return $this->hasMany('App\Models\Order', 'course_id', 'course_id');
    }

    public function user()
    {
        return $this->hasMany('App\Models\User', 'user_id', 'user_id');
    }
}
