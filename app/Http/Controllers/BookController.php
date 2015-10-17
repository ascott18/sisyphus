<?php

namespace App\Http\Controllers;

use App\Models\Book;

class BookController extends Controller
{

    public function getIndex()
    {
        $books = Book::paginate(10);

        return view('books.index', ['books' => $books]);
    }
}