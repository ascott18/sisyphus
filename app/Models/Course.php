<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'course_id';


    public function orders()
    {
        return $this->hasMany('App\Models\Order', 'course_id', 'course_id');
    }
}
