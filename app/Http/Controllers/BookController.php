<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{

    /** GET: /books/
     *
     * @return \Illuminate\View\View
     */
    public function getIndex()
    {
        $books = Book::paginate(10);

        return view('books.index', ['books' => $books]);
    }

    /** GET: /books/search?search={search}
     * Searches the book list
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */

    public function getSearch(Request $request)
    {
        $searchTerm = $request->input('search');
        $books = Book::where('title', 'LIKE', '%'. $searchTerm .'%')->paginate(10);

        return view('books.search', ['books' => $books, 'searchTerm' => $searchTerm]);
    }

    /** GET: /books/{id}
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function getShow($id)
    {
        $book = Book::findOrFail($id);

        return view('books.details', ['book' => $book]);
    }
}