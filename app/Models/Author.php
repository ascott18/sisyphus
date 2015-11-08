<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int author_id
 * @property int book_id
 * @property string first_name
 * @property string last_name
 */
class Author extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'author_id';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['first_name', 'last_name'];

    public function book()
    {
        return $this->belongsTo('App\Models\Book', 'book_id', 'book_id');
    }
}
