<?php

namespace App\Http\Controllers;

use App\Models\Term;
use \Illuminate\Http\Request;
use App\Models\Author;
use App\Models\Book;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Response;
use App\Models\Course;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderController extends Controller
{

    /** GET: /orders/
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function getIndex(Request $request)
    {
        $targetUser = $request->user();

        if ($request->user_id){
            $targetUser = User::findOrFail($request->user_id);
        }

        $this->authorize('place-order-for-user', $targetUser);

        $openTerms = Term::currentTerms()->get();

        return view('orders.index', ['user_id' => $targetUser->user_id, 'openTerms' => $openTerms]);
    }


    /** GET /orders/create/1
     * Show the form for creating a new resource.
     *
     * @param int $course_id The courseId to create an order for.
     * @return \Illuminate\Http\Response
     */
    public function getCreate($course_id)
    {
        // TODO: pretty sure this action isn't even used.
        throw new AccessDeniedHttpException("This action is obsolete, maybe?. Remove this throw if it is actually used.");

        // TODO: authorize this correctly based on the course passed in.
        $this->authorize("all");

        $course = Course::findOrFail($course_id);

        $this->authorize('place-order', $course);

        $user = User::find($course->user_id)->first();

        return view('orders.create', ['user' => $user]);
    }

    public function postNoBook(Request $request)
    {
        $course_id=$request->get("course_id");
        $course = Course::findOrFail($course_id);

        $this->authorize('place-order-for-course', $course);

        if (count($course->orders)){
            return response()->json([
                'success' => false,
                'message' => "This course already has orders placed for it."],
                Response::HTTP_BAD_REQUEST);
        }

        $course->no_book=true;
        $course->save();

        return ['success' => true];
    }

    public function getReadCourses(Request $request)
    {
        $user = $targetUser = $request->user();

        if ($request->user_id){
            $targetUser = User::findOrFail($request->user_id);
        }

        $this->authorize('place-order-for-user', $targetUser);

        $courses = $targetUser->currentCourses()->with("orders.book")->get();
        $retCourses = [];

        foreach ($courses as $course) {
            if ($user->can('place-order-for-course', $course))
                $retCourses[] = $course;
        }

        return response()->json($retCourses);
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
                    'title' => $params['title'],
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
                'required' => $params['required'],
                'book_id' => $db_book->book_id
            ]);

        }



    }
}