<?php

namespace App\Http\Controllers;

use \Illuminate\Http\Request;
use App\Models\Author;
use App\Models\Book;
use App\Models\User;
use App\Models\Order;
use App\Models\Course;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrderController extends Controller
{

    /** GET: /orders/
     *
     * @return \Illuminate\View\View
     */
    public function getIndex()
    {
        $courses = User::orderByRaw("RAND()")->first()->courses();

        return view('orders.index', ['courses' => $courses->get()]);
    }


    /** GET /orders/create/1
     * Show the form for creating a new resource.
     *
     * @param int $course_id The courseId to create an order for.
     * @return \Illuminate\Http\Response
     */
    public function getCreate($course_id)
    {
        $course = Course::findOrFail($course_id);

        if ($course->user_id !== session('user_id'))
        {
            throw new AccessDeniedHttpException("You do not teach this course");
        }

        $user = User::find($course->user_id)->first();

        return view('orders.create', ['user' => $user]);
    }

    public function postNoBook(Request $request)
    {
        $course_id=$request->get("course_id");

        $course = Course::findOrFail($course_id);

        if ($course->user_id !== session('user_id'))
        {
            // throw new AccessDeniedHttpException("You do not teach this course");
        }
        $course->no_book=true;
        $course->save();
    }

    public function getReadCourses()
    {
        $courses = User::orderByRaw("RAND()")->first()->courses()->with("orders.book")->getResults();
        return response()->json($courses);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postStore(Request $request)
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