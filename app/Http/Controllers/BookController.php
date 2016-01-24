<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Course;
use Illuminate\Http\Request;

class BookController extends Controller
{

    /** GET: /books/
     *
     * @return \Illuminate\View\View
     */
    public function getIndex()
    {
        $this->authorize("all");

        return view('books.index');
    }

    /**
     * Build the search query for the books controller
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildBookSearchQuery($request, $query) {
        if($request->input('title'))
            $query = $query->where('title', 'LIKE', '%'.$request->input('title').'%');
        if($request->input('author')) {
            $query->join('authors', function ($join) use ($request) {
                $join->on('authors.book_id', '=', 'books.book_id')
                    ->where('authors.name', 'LIKE', '%'.$request->input('author').'%');
            });
        }
        if($request->input('publisher'))
            $query = $query->where('publisher', 'LIKE', '%'.$request->input('publisher').'%');
        if($request->input('isbn13')) {
            $isbn = str_replace("-", "", $request->input('isbn13'));
            $query = $query->where('isbn13', 'LIKE', '%' . $isbn . '%');
        }

        return $query;
    }


    /**
     * Build the sort query for the books controller
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildBookSortQuery($request, $query) {
        if($request->input('sort'))
            if($request->input('dir'))
                $query = $query->orderBy($request->input('sort'), "desc");
            else
                $query = $query->orderBy($request->input('sort'));

        return $query;
    }

    /** GET: /books/book-list?page={}&{sort=}&{dir=}&{title=}&{publisher=}&{isbn=}
     * Searches the book list
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function getBookList(Request $request)
    {
        $this->authorize("all");

        $user = $request->user();
        if ($user->may('view-all-courses')){
            $query = Book::query();
        }
        else if ($user->may('view-dept-books')){
            // Only show books that have been ordered for courses that the user
            // either teaches, or administers as a dept secretary.
            $query = Book::whereIn('books.book_id',
                Course::visible()
                ->join('orders', function ($join) {
                    $join->on('orders.course_id', '=', 'courses.course_id')
                        ->whereNull('orders.deleted_at');
                })
                ->select('orders.book_id')
                ->getQuery());
        }
        else {
            // I am self-join, destroyer of worlds.
            // We do the self join here to get all the courses that are
            // similar to the ones that the current user teaches (same number and dept).
            // This way we get a nice set of relevant books to the user, without bombarding them with
            // every single physics book just because they taught a physics class once five years ago.
            $query = Book::whereIn('books.book_id',
                Course::where('courses.user_id', '=', $user->user_id)
                    ->join('courses as similarCourses', function ($join) {
                        $join->on('courses.department', '=', 'similarCourses.department')
                            ->on('courses.course_number', '=', 'similarCourses.course_number');
                    })
                    ->join('orders', function ($join) {
                        $join->on('orders.course_id', '=', 'similarCourses.course_id')
                            ->whereNull('orders.deleted_at');
                    })
                    ->select('orders.book_id')
                    ->distinct()
                    ->getQuery());
        }

        $query = $this->buildBookSearchQuery($request, $query);
        $query = $this->buildBookSortQuery($request, $query);

        $query = $query->with('authors');

        $books = $query->paginate(10);

        return response()->json($books);
    }

    private function buildDetailSearchQuery($request, $query) {
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
    private function buildDetailSortQuery($request, $query) {
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
            } else {
                if ($request->input('dir'))
                    $query = $query->orderBy($request->input('sort'), "desc");
                else
                    $query = $query->orderBy($request->input('sort'));
            }

        return $query;
    }


    /** GET: /books/book-detail-list?page={}&{sort=}&{dir=}&{section=}&{course_name=}&{ordered_by=}
     * Searches the book list
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function getBookDetailList(Request $request)
    {
        $this->authorize("all");

        $query = \App\Models\Order::query()->with("course.term");

        if($request->input('book_id'))
            $query = $query->where('book_id', '=', $request->input('book_id')); // find the book ID

        $query = $query->join('courses', 'orders.course_id', '=', 'courses.course_id'); // need to join the courses into the dataset

        $query = $this->buildDetailSearchQuery($request, $query); // build the search terms query
        $query = $this->buildDetailSortQuery($request, $query); // build the sort query

        $orders = $query->paginate(10); // get paginated result

        foreach ($orders as $order) {
            $order->course->term['term_name'] = $order->course->term->displayName();
            $order->course['canView'] = $request->user()->can('view-course', $order->course);
        }


        return response()->json($orders);
    }

    /*
    public function getCover(Request $request) {
        $googleResponse = json_decode(file_get_contents("https://www.googleapis.com/books/v1/volumes?q=isbn:".$request->input('isbn')));

        $coverImage = file_get_contents($googleResponse->items[0]->volumeInfo->imageLinks->thumbnail);

        return response()->json(array (
            "image" => base64_encode($coverImage)
            )
        );

    }
    */

    /** GET: /books/details/{id}
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function getDetails($id)
    {
        $this->authorize("all");

        $book = Book::findOrFail($id);

        return view('books.details', ['book' => $book]);
    }


    public function getEdit($id)
    {

        $book = Book::findOrFail($id);
        $this->authorize("edit-book",$book);

        return view('books.edit', ['book' => $book]);
    }

    /** GET: /books/book-by-isbn13/{id}
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function getBookByIsbn(Request $request)
    {
        $this->authorize("all");

        $isbn13 = $request->input('isbn13');

        $book = Book::where('isbn13', '=', $isbn13)->with("authors")->get();

        return response()->json($book);
    }

    public function postEdit(Request $request) {
        $this->validate($request, [
            'book' => 'required|array',
            'book.book_id' => 'required|integer',
            'book.title' => 'required|string',
            'book.publisher' => 'required|string',
            'authors' => 'required',
            'authors.*' => 'string'
        ]);

        $book = $request->get('book');
        $authors = $request->get('authors');

        $db_book = Book::findOrFail($book['book_id']);
        $this->authorize("edit-book", $db_book);

        $db_book->update($book);

        $db_book->authors()->delete();

        foreach ($authors as $author) {
            $db_book->authors()->create(['name' => $author]);
        }

        return redirect('books/details/' . $db_book->book_id);
    }

}