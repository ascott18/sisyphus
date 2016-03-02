<?php

namespace App\Http\Controllers;

use App\Models\Listing;
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
use Illuminate\Validation\ValidationException;
use Redirect;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use SearchHelper;


class OrderController extends Controller
{
    /**
     * The number of months past that we will grab the past books used for a course.
     * Bookstore doesn't want us showing data that is too old so that profs are
     * less inclined to pick old books. Old books are often harder for the bookstore to acquire.
     */
    const PAST_BOOKS_NUM_MONTHS = 24;


    /** GET: /requests/
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function getIndex(Request $request)
    {
        return $this->getCreate($request, null);
    }


    /** GET: /requests/create/{course_id?}
     *
     * @param Request $request
     * @param int|null $course_id
     * @return \Illuminate\View\View
     */
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
                    'listings',
                    'orders.book',
                    'user' => function($query){
                        return $query->select('user_id', 'first_name', 'last_name');
                    }])
                ->get();

            $viewParams['courses'] = $courses;


            $book = [];
            if ($request->input('book_id') != null) {
                $book = Book::with('authors')
                    ->where('book_id', '=', $request->input('book_id'))
                    ->get();
            }
			$viewParams['book'] = $book;
        }
        else {
            $course = Course::findOrFail($course_id);

            $this->authorize('place-order-for-course', $course);

            // Grab all the courses that are similar to the one requested.
            // Similar means same department and course number, and offered during the same term.
            $query = Course::orderable()
                ->whereIn('course_id', $course->getSimilarCourseIdsQuery())
                ->where('term_id', '=', $course->term_id)
                ->orWhere('courses.course_id', '=', $course->course_id)
                ->with([
                    'listings',
                    'orders.book',
                    'user' => function($query){
                        return $query->select('user_id', 'first_name', 'last_name');
                }]);

            $courses = $query->get();

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


    /** POST: /requests/no-book/{order_id}
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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


    /** POST: /requests/delete/{order_id}
     *
     * @param Request $request
     * @param $order_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postDelete(Request $request, $order_id)
    {
        $order = Order::findOrFail($order_id);

        $this->authorize('place-order-for-course', $order->course);

        $order->deleted_by = $request->user()->user_id;
        $order->save();
        $order->delete();

        return Redirect::back();
    }


    /** GET: /requests/past-courses/{course_id}
     *
     * @param $course_id int course id to get books for.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPastCourses($course_id)
    {
        $course = Course::findOrFail($course_id);

        $this->authorize('place-order-for-course', $course);

        // Find any listings that have the same department and number,
        // and then get the course_id of those listings.
        // This gives us any courses that are effectively the same course as this one.
        $similarCourseIdsQuery = $course->getSimilarCourseIdsQuery();

        $courses =
            Course::
            whereIn('course_id', $similarCourseIdsQuery)
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


    /** POST: /requests/submit-order
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postSubmitOrder(Request $request)
    {
        $this->validate($request, [
            'courses' => 'required',
            'courses.*.course_id' => 'required|numeric|exists:courses,course_id',

            'cart' => 'required',
            'cart.*.book' => 'required',
            'cart.*.book.book_id' => 'numeric|exists:books,book_id',
            'cart.*.book.isbn13' => 'regex:/^(?:[0-9]-?){12}[0-9]$/',
            'cart.*.book.title' => 'required_without:cart.*.book.book_id',
            'cart.*.book.publisher' => 'required_without:cart.*.book.book_id',
            'cart.*.book.authors' => 'required_without:cart.*.book.book_id',

            // we cant require authors.0, but we can require authors.0.name
            // (to ensure new books have at least one author).
            'cart.*.book.authors.0.name' => 'required_without:cart.*.book.book_id',
            'cart.*.book.authors.*.name' => 'required_without:cart.*.book.book_id',
            'cart.*.notes' => '',
            'cart.*.required' => 'required|boolean',
        ]);

        $params = $request->all();
        $user_id = $request->user()->user_id;

        // Grab all the courses from the db and check auth before we actually start placing orders.
        // This way, we won't place any orders if any of them might cause the process to fail.
        $courses = [];
        foreach($params['courses'] as $section) {
            $course = Course::with(['orders.book.authors', 'listings'])->findOrFail($section['course_id']);
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
