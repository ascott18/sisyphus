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