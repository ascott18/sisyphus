<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string publisher The publisher of the book. varchar(255)
 * @property string isbn13 The ISBN13 of the book, without hyphens. varchar(13)
 * @property string title The title of the book. varchar(100)
 */
class Book extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'book_id';


    protected $fillable = array('title', 'isbn13', 'publisher');

    public function authors()
    {
        return $this->hasMany('App\Models\Author', 'book_id', 'book_id');
    }


    public function orders()
    {
        return $this->hasMany('App\Models\Order', 'book_id', 'book_id');
    }


    /**
     * @param $num int The number of books to select
     * @param $offset int The offset to start the query at
     * @return mixed The section of books that was queried for.
     */
    public static function getPaginatedBooks($num, $offset)
    {
        return Book::take($num)->skip($offset)->get();
    }
}
