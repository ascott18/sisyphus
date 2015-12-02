<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed book_id
 * @property mixed course_id
 */
class Order extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'order_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['course_id', 'book_id'];

    public function book()
    {
        return $this->hasOne('App\Models\Book', 'book_id', 'book_id');
    }


    public function course()
    {
        return $this->hasOne('App\Models\Course', 'course_id', 'course_id');
    }
}
