<?php

namespace App\Http\Controllers;

use App\Models\Term;
use Auth;
use Carbon\Carbon;
use \Illuminate\Http\Request;
use App\Models\Author;
use App\Models\Book;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Response;
use App\Models\Course;
use Redirect;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderController extends Controller
{

    /** GET: /requests/
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function getIndex(Request $request)
    {
        $this->authorize('all');

        $openTerms = Term::currentTerms()->get();
        foreach ($openTerms as $term) {
            $term['term_name'] = $term->termName();
        }

        $openTermIds = $openTerms->pluck('term_id');

        $courses = $request->user()->courses()
            ->whereIn('term_id', $openTermIds)
            ->with([
                'orders.book',
                'user' => function($query){
                    return $query->select('first_name', 'last_name');
                }])
            ->get();

        return view('orders.index', ['openTerms' => $openTerms, 'courses' => $courses]);
    }

    public function getCreate($course_id)
    {
        $course = Course::findOrFail($course_id);

        $this->authorize('place-order-for-course', $course);

        // Grab all the courses that are similar to the one requested.
        // Similar means same department and course number, and offered during the same term.
        $courses = Course::orderable()
            ->where('department', '=', $course->department)
            ->where('course_number', '=', $course->course_number)
            ->where('term_id', '=', $course->term_id)
            ->with([
                'orders.book',
                'user' => function($query){
                    return $query->select('user_id', 'first_name', 'last_name');
            }])
            ->get();

        $openTerms = Term::currentTerms()->get();

        return view('orders.index', ['openTerms' => $openTerms, 'courses' => $courses, 'course' => $course]);
    }

    public function postNoBook(Request $request)
    {
        $course_id = $request->get("course_id");
        $course = Course::findOrFail($course_id);

        $this->authorize('place-order-for-course', $course);

        foreach ($course->orders as $order) {
            $order->deleted_by = $request->user()->user_id;
            $order->save();
            $order->delete();
        }

        $course->no_book = true;
        $course->no_book_marked = Carbon::now();
        $course->save();

        return ['success' => true];
    }

    public function postDelete(Request $request, $order_id)
    {
        $order = Order::findOrFail($order_id);

        $this->authorize('place-order-for-course', $order->course);

        $order->deleted_by = $request->user()->user_id;
        $order->save();
        $order->delete();

        return Redirect::back();
    }


    /**
     * @param $id int course id to get books for.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPastCourses($id)
    {
        $this->authorize("all");

        $course = Course::findOrFail($id);
        $courses =
            Course::
            where(['department' => $course->department, 'course_number' => $course->course_number])
            ->where('course_id', '!=', $id)
            ->with([
                    "term",
                    "orders.book.authors",
                    'orders.placedBy'=>function($query){
                        return $query->select('user_id', 'first_name', 'last_name');
                    }])
            ->get();

        foreach ($courses as $course) {
            $course->term->term_name = $course->term->termName();
        }

        return response()->json($courses);
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
        foreach($params['courses'] as $section) {
            $course = Course::findOrFail($section['course_id']);
            $this->authorize("place-order-for-course", $course);

            foreach ($params['cart'] as $bookData) {

                $book = $bookData['book'];

                if (isset($book['isNew']) && $book['isNew']) {

                    $isbn = $book['isbn13'];

                    $db_book = null;

                    if ($isbn) {
                        $db_book = Book::where('isbn13', '=', $isbn)->first();
                    }

                    if (!$db_book) {
                        $db_book = Book::create([
                            'title' => $book['title'],
                            'isbn13' => $isbn,
                            'publisher' => $book['publisher'],
                        ]);

                        foreach ($book['authors'] as $author) {
                            $db_book->authors()->save(new Author([
                                'name' => $author['name']
                            ]));
                        }
                    }

                } else {
                    $db_book = Book::findOrFail($book['book_id']);
                }

                $user_id = Auth::user()->user_id;
                Order::create([
                    'placed_by' => $user_id,
                    'course_id' => $course['course_id'],
                    'required' => $book['required'],
                    'book_id' => $db_book->book_id
                ]);
            }

            $course->no_book = false;
            $course->no_book_marked = null;
            $course->save();
        }
    }
}