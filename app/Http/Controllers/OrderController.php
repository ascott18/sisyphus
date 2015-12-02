<?php

namespace App\Http\Controllers;

use \Illuminate\Http\Request;
use App\Models\Author;
use App\Models\Book;
use App\Models\User;
use App\Models\Order;
use App\Models\Course;
use League\Flysystem\NotSupportedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrderController extends Controller
{

    /** GET: /orders/
     *
     * @return \Illuminate\View\View
     */
    public function getIndex()
    {
        $this->authorize("all");

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
        // TODO: authorize this correctly based on the course passed in.
        $this->authorize("all");

        $course = Course::findOrFail($course_id);

        $this->authorize('place-order', $course);

        $user = User::find($course->user_id)->first();

        return view('orders.create', ['user' => $user]);
    }

    public function postNoBook(Request $request)
    {
        // TODO: authorize this correctly based on the course passed in.
        $this->authorize("all");

        $course_id=$request->get("course_id");
        $course = Course::findOrFail($course_id);

        $this->authorize('place-order', $course);

        $course->no_book=true;
        $course->save();
    }

    public function getReadCourses()
    {
        // TODO: get the courses of the currently logged in user.
        // (or all the courses that the user is able to see if the user is more privileged.
        $this->authorize("all");

        $courses = User::findOrFail(77)->courses()->with("orders.book")->getResults();
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
        // TODO: pretty sure this action isn't even used.
        throw new AccessDeniedHttpException("This action is obsolete. Update it, or remove it.");

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