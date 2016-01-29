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
use SearchHelper;


class OrderController extends Controller
{
    const PAST_BOOKS_NUM_MONTHS = 24;

    /** GET: /requests/
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function getIndex(Request $request)
    {
        if ($request->user()->may('place-all-orders'))
            return $this->getList($request);
        else
            return $this->getCreate($request, null);
    }

    public function getCreate(Request $request, $course_id = null)
    {
        if ($course_id == null){
            $this->authorize('all');

            $openTerms = Term::currentTerms()->get();

            $openTermIds = $openTerms->pluck('term_id');

            // If the user can place orders for everyone, don't return courses for everything here
            // because it would almost certainly crash their browser if we did.
            if ($request->user()->may('place-all-orders'))
                $query = $request->user()->courses();
            else
                $query = Course::orderable();

            $courses = $query
                ->whereIn('term_id', $openTermIds)
                ->with([
                    'orders.book',
                    'user' => function($query){
                        return $query->select('first_name', 'last_name');
                    }])
                ->get();

            return view('orders.create', ['openTerms' => $openTerms, 'courses' => $courses]);
        }
        else {
            $course = Course::findOrFail($course_id);

            $this->authorize('place-order-for-course', $course);

            // Grab all the courses that are similar to the one requested.
            // Similar means same department and course number, and offered during the same term.
            $courses = Course::orderable()
                ->where('department', '=', $course->department)
                ->where('course_number', '=', $course->course_number)
                ->where('term_id', '=', $course->term_id)
                ->orWhere('course_id', '=', $course->course_id)
                ->with([
                    'orders.book',
                    'user' => function($query){
                        return $query->select('user_id', 'last_name');
                }])
                ->get();

            $openTerms = Term::currentTerms()->get();

            // Make sure that the requested course ends up in the list.
            // If we're debugging unauthorized actions, because of the way Course::orderable() works,
            // it is possible that the requested course might have passed the authorize check
            // but didn't end up the query.


            return view('orders.create', ['openTerms' => $openTerms, 'courses' => $courses, 'course' => $course]);
        }
    }

    /** GET: /orders/list
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function getList(Request $request) {
        $this->authorize("all");

        $user = $request->user();

        $currentTermIds = Term::currentTerms()->pluck('term_id');
        $userTermIds = Course::visible($user)->distinct()->pluck('term_id');

        $allRelevantTermIds = $currentTermIds->merge($userTermIds)->unique();

        $terms = Term::whereIn('term_id', $allRelevantTermIds)
            ->orderBy('term_id', 'DESC')
            ->get();

        return view('orders.list',['terms' => $terms]);
    }

    /**
     * Build the search query for the books controller
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildListSearchQuery($tableState, $query) {
        $predicateObject = [];
        if(isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject; // initialize predicate object

        if(isset($predicateObject->title))
            $query = $query->where('title', 'LIKE', '%'.$predicateObject->title.'%');
        if(isset($predicateObject->section))
            Searchhelper::sectionSearchQuery($query, $predicateObject->section);
        if(isset($predicateObject->course_name))
            $query = $query->where('course_name', 'LIKE', '%'.$predicateObject->course_name.'%');

        return $query;
    }

    /**
     * Build the sort query for the book detail list
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildListSortQuery($tableState, $query) {
        if(isset($tableState->sort->predicate)) {
            $sort = $tableState->sort;
            if ($sort->predicate == "section") { // special case because of joined tables
                if ($sort->reverse == 1) {
                    $query = $query->orderBy("department", "desc")
                        ->orderBy("course_number", "desc")
                        ->orderBy("course_section", "desc");
                } else {
                    $query = $query->orderBy("department")
                        ->orderBy("course_number")
                        ->orderBy("course_section");
                }
            } else if ($sort->predicate == "created_at") { // created at needed a special case due to ambiguity
                if ($sort->reverse == 1)
                    $query = $query->orderBy('orders.created_at', "desc");
                else
                    $query = $query->orderBy('orders.created_at');
            } else {
                if ($sort->reverse == 1)
                    $query = $query->orderBy($sort->predicate, "desc");
                else
                    $query = $query->orderBy($sort->predicate);
            }
        }

        return $query;
    }

    /** GET: /orders/order-list?page={}&{sort=}&{dir=}
     * searches the order list
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderList(Request $request)
    {
        $tableState = json_decode($request->input('table_state'));

        $this->authorize("all"); // TODO: fix authorize permission

        $query = Course::visible($request->user());

        $query = $query->join('orders', 'courses.course_id', '=', 'orders.course_id'); // join before to get order department
        $query = $query->join('books', 'orders.book_id', '=', 'books.book_id'); // get the books

        if(isset($tableState->term_id) && $tableState->term_id != "")
            $query = $query->where('term_id', '=', $tableState->term_id);

        $query = $this->buildListSearchQuery($tableState, $query); // build the search query
        $query = $this->buildListSortQuery($tableState, $query); // build the sort query

        $query->with("term"); // easily get the term name

        $orders = $query->paginate(10);

        return response()->json($orders);
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
        $course = Course::findOrFail($id);

        $this->authorize('place-order-for-course', $course);

        $courses =
            Course::
            where(['department' => $course->department, 'course_number' => $course->course_number])
            ->where('course_id', '!=', $id)
            ->where('created_at', '>', Carbon::today()->subMonths(static::PAST_BOOKS_NUM_MONTHS))
            ->with([
                    "term" => function ($query){
                        return $query->select('term_id', 'term_number', 'year');
                    },
                    "orders" => function ($query){
                        return $query
                            ->select('order_id', 'book_id', 'course_id')
                            ->with('book.authors');
                    },
                    'user' => function($query){
                        return $query->select('user_id', 'last_name');
                    }])
            ->get();

        return $courses;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postSubmitOrder(Request $request)
    {
        $this->validate($request, [
            'courses' => 'required',
            'courses.*.course_id' => 'required|numeric|exists:courses,course_id',
            'cart' => 'required',
            'cart.*.book' => 'required',
            'cart.*.book.book_id' => 'required_unless:isNew,true',
            'cart.*.book.isbn13' => 'required_if:isNew,true',
            'cart.*.book.title' => 'required_if:isNew,true',
            'cart.*.book.publisher' => 'required_if:isNew,true',
            'cart.*.book.authors' => 'required_if:isNew,true',
            'cart.*.book.authors*.name' => 'required',
            'cart.*.notes' => '',
            'cart.*.required' => 'required',
        ]);

        $params = $request->all();
        $user_id = $request->user()->user_id;

        // Grab all the courses from the db and check auth before we actually start placing orders.
        // This way, we won't place any orders if any of them might cause the process to fail.
        $courses = [];
        foreach($params['courses'] as $section) {
            $course = Course::findOrFail($section['course_id']);
            $this->authorize('place-order-for-course', $course);
            $courses[] = $course;
        }

        foreach($courses as $course) {
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
                            'title' => trim($book['title']),
                            'isbn13' => trim($isbn),
                            'publisher' => trim($book['publisher']),
                        ]);

                        foreach ($book['authors'] as $author) {
                            $db_book->authors()->save(new Author([
                                'name' => trim($author['name'])
                            ]));
                        }
                    }

                } else {
                    $db_book = Book::findOrFail($book['book_id']);
                }

                Order::create([
                    'notes' => isset($bookData['notes']) ? $bookData['notes'] : '',
                    'placed_by' => $user_id,
                    'course_id' => $course['course_id'],
                    'required' => $bookData['required'],
                    'book_id' => $db_book->book_id
                ]);
            }

            $course->no_book = false;
            $course->no_book_marked = null;
            $course->save();
        }
    }
}