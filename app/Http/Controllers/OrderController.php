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


class OrderController extends Controller
{
    /**
     * The number of months past that we will grab the past books used for a course.
     * Bookstore doesn't want us showing data that is too old so that profs are
     * less inclined to pick old books. Old books are often harder for the bookstore to acquire.
     */
    const PAST_BOOKS_NUM_MONTHS = 30;


    /** GET: /requests/
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function getIndex(Request $request)
    {
        return $this->getCreate($request, null);
    }


    /** GET: /requests/create/{$course_id?}?book_id={book_id?}
     *
     * Displays the page for submitting textbook requests.
     *
     * If $course_id is provided, the user will be taken straight to book selection for that course.
     * If book_id is set in the query string, the given book will already be in the cart once the user selects a course.
     *
     * @param Request $request
     * @param int|null $course_id
     * @return \Illuminate\View\View
     */
    public function getCreate(Request $request, $course_id = null)
    {
        $user = $request->user();
        $openTerms = Term::currentTerms()->get();

        // Maintain an array of all the parameters we will pass to our view.
        // These here are the ones that are always the same.
        // There are also some that are set below that depend on what path we take through this code.
        $viewParams = [
            'openTerms' => $openTerms,
            'current_user_id' => $user->user_id,
            'continueUrl' => '/requests'
        ];

        if ($course_id == null){
            // In this path, the user will be presented with all courses that they are able to place orders for.
            $this->authorize('all');

            $openTermIds = $openTerms->pluck('term_id');

            if ($request->user()->may('place-all-orders')){
                // If the user can place orders for everyone, don't return ALL courses here
                // because it would almost certainly crash their browser if we did.
                // This is only going to apply to bookstore staff and administrators.
                // with a compsci rollout, Connie will still only have the place-dept-orders permission.

                // Instead of showing all courses, we will just present the courses that they teach.
                // Which probably isn't any courses, but there isn't really anything else to do here that makes sense.
                $query = $request->user()->courses();
                $viewParams['continueUrl'] = '/courses';
            }
            else{
                // If the user can only place dept orders, or if they can only place their own orders, then get all orderable courses.
                $query = Course::orderable();
            }

            $courses = $query
                ->whereIn('term_id', $openTermIds)
                ->with([
                    'listings',
                    'orders.book',
                    'user' => function($query){
                        // Only select what we need from users for security/information disclosure purposes.
                        return $query->select('user_id', 'first_name', 'last_name');
                    }])
                ->get();

            $viewParams['courses'] = $courses;


            // If a book_id was passed in to the query string of the request,
            // send that along to the view so that it can be added to the cart initially.
            if ($request->input('book_id')) {
                $book = Book::with('authors')->findOrFail($request->input('book_id'));
                $viewParams['book'] = $book;
            }
        }
        else {
            // In this path, the user will be taken straight to a specific course.
            /** @var Course $course */
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
                        // Only select what we need from users for security/information disclosure purposes.
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


    /** POST: /requests/no-book/
     *
     * Mark the given course as not needing a book. If the course has any outstanding orders, they will be deleted.
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
            if (!$order->deleted_by){
                $order->deleted_by = $request->user()->user_id;
                $order->save();
                $order->delete();
            }
        }

        $course->no_book = true;
        $course->no_book_marked = Carbon::now();
        $course->save();

        return ['success' => true];
    }


    /** POST: /requests/delete/{order_id}
     *
     * Deletes the specified order.
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
     * Retrieves the past offerings of the specified course within the last PAST_BOOKS_NUM_MONTHS months.
     *
     * @param $course_id int course id to get books for.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPastCourses($course_id)
    {
        /** @var Course $course */
        $course = Course::findOrFail($course_id);

        $this->authorize('place-order-for-course', $course);

        // Find any listings that have the same department and number,
        // and then get the course_id of those listings.
        // This gives us any courses that are effectively the same course as this one.
        $similarCourseIdsQuery = $course->getSimilarCourseIdsQuery();

        // We need to get the full course models of those similar course_ids,
        // and we also need to restrict by age. The bookstore doesn't want super old
        // past books showing up because old books are hard for them to get.
        $courses =
            Course::whereIn('course_id', $similarCourseIdsQuery)
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
                        // Only select what we need from users for security/information disclosure purposes.
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


        // Save the results of each request so that we can echo feedback to the user.
        $orderResults = [];


        /** @var Course $course */
        foreach($courses as $course) {
            foreach ($params['cart'] as $bookData) {

                $book = $bookData['book'];

                // If the book doesn't have a book_id, it must be a new book.
                if (!isset($book['book_id']) || !$book['book_id']) {

                    // Clean up the ISBN so that we can look for it in the database.
                    $isbn = isset($book['isbn13']) ? $book['isbn13'] : '';
                    $isbn = preg_replace('|[^0-9]|', '', $isbn);
                    $edition = trim(isset($book['edition']) ? $book['edition'] : '');

                    $db_book = null;

                    if ($isbn) {
                        // If the isbn was provided, look for an existing book in the database with the same ISBN.
                        $db_book = Book::where('isbn13', '=', $isbn)->first();
                    }
                    else {
                        // If the isbn was not provided, then the user clicked the button to add a new
                        // book to their cart that does not have an ISBN.
                        // Check for an existing book with the same title, pub, and edition.
                        $db_book = Book::where([
                            'title' => trim($book['title']),
                            'publisher' => trim($book['publisher']),
                            'edition' => $edition,
                        ])->first();
                    }

                    if (!$db_book) {
                        // If the book was not already found in the database,
                        // create a new book.
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
                    // If the book_id was set on the book, it must be an
                    // already-existing book. Go get it.
                    $db_book = Book::findOrFail($book['book_id']);
                }

                // Clean up the notes for the book.
                $notes = isset($bookData['notes']) ? $bookData['notes'] : '';
                $notes = trim($notes);

                // Make a spot in our order results for this book if we haven't already made one.
                $book_id = $db_book->book_id;
                if (!isset($orderResults[$book_id]))
                    $orderResults[$book_id] = [];

                // Check if there are already orders for this course for this book.
                $sameOrders = $course->orders->where('book_id', $book_id);

                if (count($sameOrders)) {
                    // If there are already orders for this book for this course,
                    // echo those back to the user.
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
                else
                {
                    // There are no other orders for this course for this book.
                    // Create a new order in the database.
                    $order = $course->orders()->save(new Order([
                        'notes' => $notes,
                        'placed_by' => $user_id,
                        'required' => $bookData['required'],
                        'book_id' => $book_id
                    ]));

                    // Reload orders so we can detect duplicate orders
                    // in subsequent iterations of the loops we're in.
                    // (This important in case the user picked what ended up being the same book more than once).
                    $course->load('orders');

                    // Record this order as being successful so the user can be aware of what happened.
                    $orderResults[$book_id][] = [
                        'notPlaced' => false,
                        'order' => $order->load('book.authors'),
                        'course' => $course,
                    ];
                }
            }

            // Since we just created an order for this course, it can't possibly have no book.
            // Clear out these fields to keep our data consistent.
            $course->no_book = false;
            $course->no_book_marked = null;
            $course->save();
        }

        return ['success' => true, 'orderResults' => array_values($orderResults)];
    }
}
