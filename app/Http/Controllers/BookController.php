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

    /** GET: /books/book-list?page={}&{sort=}&{dir=}&{title=}&{publisher=}&{isbn=}
     * Searches the book list
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */

    public function getBookList(Request $request)
    {

        $query = Book::query();

        if($request->input('sort'))
            if($request->input('dir'))
                $query = $query->orderBy($request->input('sort'), "desc");
            else
                $query = $query->orderBy($request->input('sort'));

        if($request->input('title'))
            $query = $query->where('title', 'LIKE', '%'.$request->input('title').'%');
        if($request->input('publisher'))
            $query = $query->where('publisher', 'LIKE', '%'.$request->input('publisher').'%');
        if($request->input('isbn13'))
            $query = $query->where('isbn13', 'LIKE', '%'.$request->input('isbn13').'%');


        $books = $query->paginate(10);

        foreach($books as $book) {
            $book->bAuth = $book->authors;
        }


        return response()->json($books);
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