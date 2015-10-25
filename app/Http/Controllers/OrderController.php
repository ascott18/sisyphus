<?php

namespace App\Http\Controllers;

use \Illuminate\Http\Request;
use App\Models\Author;
use App\Models\Book;
use App\Models\User;
use App\Models\Order;
use App\Models\Course;

class OrderController extends Controller
{

    /** GET: /orders/
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $courses = Course::paginate(10);

        return view('orders.index', ['courses' => $courses]);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = User::orderByRaw("RAND()")->first();

        return view('orders.create', ['user' => $user]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $params = $request->all();


        $book = Book::create([
            'title' => $params['bookTitle'],
            'isbn13' => $params['isbn13'],
            'publisher' => $params['publisher'],
        ]);

        // TODO: THIS SUCKS
        $book->authors()->save(new Author([
            'first_name' => $params['author1'],
        ]));


    }
}