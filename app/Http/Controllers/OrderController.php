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
        $user = $request->user();
        $openTerms = Term::currentTerms()->get();

        $viewParams = [
            'openTerms' => $openTerms,
            'current_user_id' => $user->user_id,
            'continueUrl' => '/requests'
        ];

        if ($course_id == null){
            $this->authorize('all');

            $openTermIds = $openTerms->pluck('term_id');

            // If the user can place orders for everyone, don't return courses for everything here
            // because it would almost certainly crash their browser if we did.
            if ($request->user()->may('place-all-orders')){
                $query = $request->user()->courses();
                $viewParams['continueUrl'] = '/courses';
            }
            else{
                $query = Course::orderable();
            }

            $courses = $query
                ->whereIn('term_id', $openTermIds)
                ->with([
                    'orders.book',
                    'user' => function($query){
                        return $query->select('user_id', 'first_name', 'last_name');
                    }])
                ->get();

            $viewParams['courses'] = $courses;

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
                        return $query->select('user_id', 'first_name', 'last_name');
                }])
                ->get();

            // Make sure that the requested course ends up in the list.
            // If we're debugging unauthorized actions, because of the way Course::orderable() works,
            // it is possible that the requested course might have passed the authorize check
            // but didn't end up the query.

            $viewParams['continueUrl'] = '/courses';
            $viewParams['courses'] = $courses;
            $viewParams['course'] = $course;
        }

        return view('orders.create', $viewParams);
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
    private function buildListSearchQuery($request, $query) {
        if($request->input('title'))
            $query = $query->where('title', 'LIKE', '%'.$request->input('title').'%');

        if($request->input('section'))
            Searchhelper::sectionSearchQuery($query, $request->input('section'));

        if($request->input('course_name'))
            $query = $query->where('course_name', 'LIKE', '%'.$request->input('course_name').'%');

        return $query;
    }

    /**
     * Build the sort query for the book detail list
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildListSortQuery($request, $query) {
        if($request->input('sort'))
            if($request->input('sort') == "section") { // special case because of joined tables
                if ($request->input('dir')) {
                    $query = $query->orderBy("department", "desc")
                        ->orderBy("course_number", "desc")
                        ->orderBy("course_section", "desc");
                } else {
                    $query = $query->orderBy("department")
                        ->orderBy("course_number")
                        ->orderBy("course_section");
                }
            } else if($request->input('sort') == "created_at") { // created at needed a special case due to ambiguity
                if ($request->input('dir'))
                    $query = $query->orderBy('orders.created_at', "desc");
                else
                    $query = $query->orderBy('orders.created_at');
            } else {
                if ($request->input('dir'))
                    $query = $query->orderBy($request->input('sort'), "desc");
                else
                    $query = $query->orderBy($request->input('sort'));
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
        $this->authorize("all"); // TODO: fix authorize permission

        $query = Course::visible($request->user());

        $query = $query->join('orders', 'courses.course_id', '=', 'orders.course_id'); // join before to get order department
        $query = $query->join('books', 'orders.book_id', '=', 'books.book_id'); // get the books

        if($request->input('term_id')) { // filter by term
            $query = $query->where('term_id', '=', $request->input('term_id'));
        }

        $query = $this->buildListSearchQuery($request, $query); // build the search query
        $query = $this->buildListSortQuery($request, $query); // build the sort query

        $query->with("term"); // easily get the term name

        $orders = $query->paginate(10);

        return response()->json($orders);
    }

    public function postNoBook(Request $request)
    {
        $course_id = $request->input("course_id");
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
//            ->where('course_id', '!=', $id)
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
//            'cart.*.book.isbn13' => 'required_without:book_id',
            'cart.*.book.title' => 'required_without:book_id',
            'cart.*.book.publisher' => 'required_without:book_id',
            'cart.*.book.authors' => 'required_without:book_id',
            'cart.*.book.authors[0]' => 'required_without:book_id',
            'cart.*.book.authors.*.name' => 'required',
            'cart.*.notes' => '',
            'cart.*.required' => 'required',
        ]);

        $params = $request->all();
        $user_id = $request->user()->user_id;

        // Grab all the courses from the db and check auth before we actually start placing orders.
        // This way, we won't place any orders if any of them might cause the process to fail.
        $courses = [];
        foreach($params['courses'] as $section) {
            $course = Course::with('orders.book.authors')->findOrFail($section['course_id']);
            $this->authorize('place-order-for-course', $course);
            $courses[] = $course;
        }

        $orderResults = [];


        foreach($courses as $course) {
            foreach ($params['cart'] as $bookData) {

                $book = $bookData['book'];

                if (!isset($book['book_id']) || !$book['book_id']) {

                    $isbn = isset($book['isbn13']) ? $book['isbn13'] : '';
                    $isbn = preg_replace('|[^0-9]|', '', $isbn);
                    $edition = trim(isset($book['edition']) ? $book['edition'] : '');

                    $db_book = null;

                    if ($isbn) {
                        $db_book = Book::where('isbn13', '=', $isbn)->first();
                    }
                    else {
                        $db_book = Book::where([
                            'title' => trim($book['title']),
                            'publisher' => trim($book['publisher']),
                            'edition' => $edition,
                        ])->first();
                    }

                    if (!$db_book) {
                        $db_book = Book::create([
                            'title' => trim($book['title']),
                            'isbn13' => $isbn,
                            'publisher' => trim($book['publisher']),
                            'edition' => $edition,
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

                $notes = isset($bookData['notes']) ? $bookData['notes'] : '';
                $notes = trim($notes);

                $book_id = $db_book->book_id;
                if (!isset($orderResults[$book_id]))
                    $orderResults[$book_id] = [];

                $existingOrders = $course->orders;
                $sameOrders = $existingOrders->where('book_id', $book_id);

                if (count($sameOrders)) {
                    foreach ($sameOrders as $sameOrder) {
                        $orderResults[$book_id][] = [
                            'notPlaced' => true,
                            'order' => $sameOrder,
                            'course' => $course,
                            'newOrder' => [
                                'required' => $bookData['required'],
                                'notes' => $notes
                            ]
                        ];
                    }
                }
                else{
                    $order = Order::create([
                        'notes' => $notes,
                        'placed_by' => $user_id,
                        'course_id' => $course['course_id'],
                        'required' => $bookData['required'],
                        'book_id' => $book_id
                    ]);

                    $orderResults[$book_id][] = [
                        'notPlaced' => false,
                        'order' => $order->load('book.authors'),
                        'course' => $course,
                    ];
                }
            }

            $course->no_book = false;
            $course->no_book_marked = null;
            $course->save();
        }

        return ['success' => true, 'orderResults' => array_values($orderResults)];
    }
}
