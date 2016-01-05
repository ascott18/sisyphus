<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * @property integer book_id The book_id of the book that this order was placed for.
 * @property integer course_id The course_id of the course that this order was placed for.
 * @property integer placed_by The user_id of the user who placed this order.
 * @property integer|null deleted_by integer The user_id of the user who deleted this order.
 * @property Carbon|null deleted_at integer The time at which this order was deleted.
 */
class Order extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

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
    protected $fillable = ['course_id', 'placed_by', 'book_id', 'required'];

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

    public function deletedBy()
    {
        return $this->hasOne('App\Models\User', 'user_id', 'deleted_by');
    }
}
