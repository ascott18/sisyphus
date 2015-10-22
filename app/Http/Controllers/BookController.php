<?php

namespace App\Http\Controllers;

use App\Models\Book;

class BookController extends Controller
{

    /** GET: /books/
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $books = Book::paginate(10);

        return view('books.index', ['books' => $books]);
    }


    /** GET: /books/{id}
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $book = Book::findOrFail($id);

        return view('books.details', ['book' => $book]);
    }
}