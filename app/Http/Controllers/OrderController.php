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

    protected static function buildFilteredOrderQuery($query, User $user){
        if ($user->may('view-dept-orders'))
        {
            $departments = $user->departments()->lists('department');
            $query = $query->whereIn('department', $departments);
        }
        elseif (!$user->may('view-all-orders'))
        {
            $query = $query->where('placed_by', $user->user_id);
        }

        return $query;
    }

    protected static function buildFilteredCourseQuery($query, User $user){
        if ($user->may('view-dept-courses'))
        {
            $departments = $user->departments()->lists('department');
            $query = $query->whereIn('department', $departments);
        }
        elseif (!$user->may('view-all-courses'))
        {
            $query = $query->where('user_id', $user->user_id);
        }

        return $query;
    }

    /** GET: /orders/list
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function getList(Request $request) {
        $this->authorize("all");

        $user = $request->user();

        $currentTerm = Term::currentTerms()->first();
        $currentTermId = $currentTerm ? $currentTerm->term_id : '';

        $terms = Term::whereIn('term_id', function($query) use ($user) {
            static::buildFilteredCourseQuery($query->from('courses'), $user)->select('term_id');
        })
            ->orWhere('term_id', '=', $currentTermId)
            ->orderBy('term_id', 'DESC')
            ->get();

        return view('orders.list',['terms' => $terms, 'currentTermId' => $currentTermId]);
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
        if($request->input('section')) {
            $searchArray = preg_split("/[\s-]/", $request->input('section'));
            foreach($searchArray as $key => $field) {       // strip leading zeros from search terms
                $searchArray[$key] = ltrim($field, '0');
            }
            if(count($searchArray) == 2) {
                // we need to use an anonymous function so the subquery does not override the book_id limit from parent
                $query = $query->where(function($sQuery) use ($searchArray){
                    return $sQuery->where('department', 'LIKE', '%'.$searchArray[0].'%')
                        ->where('course_number', 'LIKE', '%'.$searchArray[1].'%')
                        ->orWhere('course_number', 'LIKE', '%'.$searchArray[0].'%')
                        ->where('course_section', 'LIKE', '%'.$searchArray[1].'%')
                        ->orWhere('department', 'LIKE', '%'.$searchArray[0].'%')
                        ->where('course_section', 'LIKE', '%'.$searchArray[1].'%');
                });
            } elseif(count($searchArray) == 3) {
                // this does not suffer the same problem but should be in a subquery like it is for proper formatting
                $query->where(function($sQuery) use ($searchArray) {
                    return $sQuery->where('department', 'LIKE', '%'.$searchArray[0].'%')
                        ->where('course_number', 'LIKE', '%'.$searchArray[1].'%')
                        ->where('course_section', 'LIKE', '%'.$searchArray[2].'%');
                });
            } else {
                // we need to use an anonymous function so the subquery does not override the book_id limit from parent
                for($i=0; $i<count($searchArray); $i++) {
                    $query = $query->where(function($sQuery) use ($searchArray, $i) {
                        return $sQuery->where('department', 'LIKE', '%'.$searchArray[$i].'%')
                            ->orWhere('course_number', 'LIKE', '%'.$searchArray[$i].'%')
                            ->orWhere('course_section', 'LIKE', '%'.$searchArray[$i].'%');
                    });
                }
            }
        }

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

        $query = Order::query()->withTrashed(); // yes, even the deleted ones

        $query = $query->join('courses', 'orders.course_id', '=', 'courses.course_id'); // join before to get order department

        $query = static::buildFilteredOrderQuery($query, $request->user()); // filter what the user can see

        $query = $query->join('books', 'orders.book_id', '=', 'books.book_id'); // get the books

        if($request->input('term_id')) { // filter by term
            $query = $query->where('term_id', '=', $request->input('term_id'));
        }

        $query = $this->buildListSearchQuery($request, $query); // build the search query
        $query = $this->buildListSortQuery($request, $query); // build the sort query

        $query->with("course.term"); // easily get the term name

        $orders = $query->paginate(10);

        foreach ($orders as $order) {
            $order->term_name = $order->course->term->displayName();
        }

        return response()->json($orders);
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

        $course->no_book = true;
        $course->no_book_marked = Carbon::now();
        $course->save();

        return ['success' => true];
    }

    public function postDelete(Request $request, $order_id)
    {
        $order = Order::findOrFail($order_id);

        $this->authorize('edit-order', $order);

        $order->deleted_by = $request->user()->user_id;
        $order->save();
        $order->delete();

        return Redirect::back();
    }

    public function postUndelete(Request $request, $order_id)
    {
        $order = Order::withTrashed()->findOrFail($order_id);

        $this->authorize('edit-order', $order);

        $order->deleted_by = null;
        $order->save();
        $order->restore();

        return Redirect::back();
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

        $course = Course::findOrFail($params['course_id']);
        $this->authorize("place-order-for-course", $course);

        //TODO: validate incoming data

        foreach ($params['cart'] as $bookData) {

            $book = $bookData['book'];

            if (isset($book['isNew']) && $book['isNew']) {

                $isbn = $book['isbn13'];

                $db_book = null;

                if ($isbn) {
                    $db_book = Book::where('isbn13', '=', $isbn)->first();
                }

                if (!$db_book){
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

            }
            else {
                $db_book = Book::findOrFail($book['book_id']);
            }

            $user_id = Auth::user()->user_id;
            Order::create([
                'placed_by' => $user_id,
                'course_id' => $params['course_id'],
                'required' => $book['required'],
                'book_id' => $db_book->book_id
            ]);

        }



    }
}