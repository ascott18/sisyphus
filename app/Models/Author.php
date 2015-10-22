<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'author_id';



    protected $fillable = ['first_name', 'last_name'];

    public function book()
    {
        return $this->belongsTo('App\Models\Book', 'book_id', 'book_id');
    }
}
