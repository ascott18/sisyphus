<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'order_id';

    public function book()
    {
        return $this->hasOne('App\Models\Book', 'book_id', 'book_id');
    }


    public function course()
    {
        return $this->hasOne('App\Models\Course', 'course_id', 'course_id');
    }
}
