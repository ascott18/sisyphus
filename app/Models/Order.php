<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed book_id integer The book_id of the book that this order was placed for.
 * @property mixed course_id integer The course_id of the course that this order was placed for.
 * @property mixed placed_by integer The user_id of the user who placed this order.
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


    public function placedBy()
    {
        return $this->hasOne('App\Models\User', 'user_id', 'placed_by');
    }
}
