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
     * @param $id int course id to get books for.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPastCourses($id)
    {
        $this->authorize("all");

        $course = Course::findOrFail($id);
        $courses = Course::where(['department' => $course->department, 'course_number' => $course->course_number]);
        $books = $courses->with("orders.book.authors")->get();
        return response()->json($books);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postSubmitOrder(Request $request)
    {
        $params = $request->all();

        $course = Course::findOrFail($params['course_id']);
        $this->authorize("place-order-for-course", $course);



        foreach ($params['cart'] as $bookData) {

            $book = $bookData['book'];

            if (isset($book['isNew']) && $book['isNew']) {
                $db_book = Book::create([
                    'title' => $params['bookTitle'],
                    'isbn13' => $params['isbn13'],
                    'publisher' => $params['publisher'],
                ]);

                foreach ($book['authors'] as $author) {
                    $db_book->authors()->save(new Author([
                        'first_name' => $author['name']
                    ]));
                }
            }
            else {
                $db_book = Book::findOrFail($book['book_id']);
            }

            Order::create([
                'course_id' => $params['course_id'],
                'book_id' => $db_book->book_id
            ]);

        }



    }
}