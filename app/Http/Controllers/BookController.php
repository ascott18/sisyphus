<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Providers\SearchServiceProvider;
use Cache;
use App\Models\Book;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SearchHelper;
use GoogleBooks;

class BookController extends Controller
{
    /** GET: /books/
     *
     * @return \Illuminate\View\View
     */
    public function getIndex()
    {
        $this->authorize('all');

        return view('books.index');
    }


    /**
     * Build the search query for the books controller
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildBookSearchQuery($tableState, $query) {
        if (isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject; // initialize predicate object
        else {
            return $query;
        }

        if (isset($predicateObject->title) && $predicateObject->title != '')
            $query = $query->where('title', 'LIKE', '%'.$predicateObject->title.'%');

        if (isset($predicateObject->author) && $predicateObject->author != '') {
            $query = $query->where('authors.name', 'LIKE', '%'.$predicateObject->author.'%');
        }

        if (isset($predicateObject->publisher) && $predicateObject->publisher != '')
            $query = $query->where('publisher', 'LIKE', '%'.$predicateObject->publisher.'%');

        if (isset($predicateObject->isbn13) && $predicateObject->isbn13 != '') {
            $isbn = str_replace('-', '', $predicateObject->isbn13);
            $query = $query->where('isbn13', 'LIKE', '%' . $isbn . '%');
        }

        return $query;
    }


    /**
     * Build the sort query for the books controller
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildBookSortQuery($tableState, $query) {
        if (isset($tableState->sort->predicate)) {
            $sorts = [
                'title' => [
                    'title', '',
                ],
                'author' => [
                    'authors.name', '',
                ],
                'publisher' => [
                    'publisher', '',
                ],
                'isbn13' => [
                    'isbn13', '',
                ]
            ];

            SearchHelper::buildSortQuery($query, $tableState->sort, $sorts);

        }
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
        $this->authorize('all');

        $tableState = json_decode($request->input('table_state'));

        $user = $request->user();
        if ($user->may('view-all-courses')){
            $query = Book::query();
        }
        else if ($user->may('view-dept-courses')){
            // Only show books that have been ordered for courses that the user
            // either teaches, or administers as a dept secretary.
            $query = Book::whereIn('books.book_id',
                Course::visible()
                ->join('orders', function ($join) {
                    $join->on('orders.course_id', '=', 'courses.course_id')
                        ->whereNull('orders.deleted_at');
                })
                ->select('orders.book_id')
                ->toBase());
        }
        else {
            // I am self-join, destroyer of worlds.
            // We do the self join here to get all the courses that are
            // similar to the ones that the current user teaches (same number and dept).
            // This way we get a nice set of relevant books to the user, without bombarding them with
            // every single physics book just because they taught a physics class once five years ago.
            $query = Book::whereIn('books.book_id',
                Course::where('courses.user_id', '=', $user->user_id)
                    ->join('listings', 'courses.course_id', '=', 'listings.course_id')
                    ->join('listings as similarListings', function ($join) {
                        $join->on('listings.department', '=', 'similarListings.department')
                            ->on('listings.number', '=', 'similarListings.number');
                    })
                    ->join('orders', function ($join) {
                        $join->on('orders.course_id', '=', 'similarListings.course_id')
                            ->whereNull('orders.deleted_at');
                    })
                    ->select('orders.book_id')
                    ->distinct()
                    ->toBase()
            );
        }

        $query->select('books.*')
            ->distinct();

        if ((isset($tableState->sort->predicate) && $tableState->sort->predicate == "author")
            || isset($tableState->search->predicateObject->author) ) { // only join when we actually need it

            $query->join('authors','books.book_id', '=', 'authors.book_id');
        }

        $query = $this->buildBookSearchQuery($tableState, $query);
        $query = $this->buildBookSortQuery($tableState, $query);

        $query = $query->with('authors');

        $books = SearchServiceProvider::paginate($query, 10);

        return response()->json($books);
    }


    /**
     * Build the search query for the book detail list
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildBookDetailSearchQuery($tableState, $query) {
        if (isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject;
        else
            return $query;

        if (isset($predicateObject->section) && $predicateObject->section != '')
        SearchHelper::sectionSearchQuery($query, $predicateObject->section); // use search helper for section search
        if (isset($predicateObject->name) && $predicateObject->name != '')
            $query = $query->where('name', 'LIKE', '%'.$predicateObject->name.'%');
        

        return $query;
    }


    /**
     * Build the sort query for the book detail list
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildBookDetailSortQuery($tableState, $query) {
        if (isset($tableState->sort->predicate)) {
            $sorts = [
                'term' => [
                    'order_id', 'desc',
                ],
                'section' => [
                    'department', '',
                    'number', '',
                    'section', '',
                ],
                'name' => [
                    'name', '',
                ]
            ];

            SearchHelper::buildSortQuery($query, $tableState->sort, $sorts);
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
        $this->authorize('all');

        $tableState = json_decode($request->input('table_state'));

        $query = Order::query()
            ->select('orders.*')
            ->distinct()
            ->with(['course.term', 'course.listings']);

        if ($request->input('book_id')) {
            $query = $query->where('book_id', '=', str_replace('"', '', $request->input('book_id'))); // find the book ID
        }

        $query = $query->join('listings', 'orders.course_id', '=', 'listings.course_id');

        $query = $this->buildBookDetailSearchQuery($tableState, $query);
        $query = $this->buildBookDetailSortQuery($tableState, $query);

        // Use our custom paginator that can handle the distinct clause properly.
        $orders = SearchServiceProvider::paginate($query, 10);

        foreach ($orders as $order) {
            $order->course['canView'] = $request->user()->can('view-course', $order->course);
        }

        return response()->json($orders);
    }


    /** GET: /books/cover?isbn={isbn}
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCover(Request $request) {
        $this->authorize('all');

        if (!preg_match('/[0-9\-]{13,20}/', $request->input('isbn'))){
            return response(
                file_get_contents(public_path('images/badRequest.jpg')),
                Response::HTTP_BAD_REQUEST,
                ['Content-Type' => 'image/jpeg']
            );
        }

        return GoogleBooks::getCoverThumbnail($request->input('isbn'));
    }


    /** GET: /books/details/{book_id}
     *
     * @param int $book_id
     * @return \Illuminate\View\View
     */
    public function getDetails($book_id)
    {
        $this->authorize('all');

        $book = Book::findOrFail($book_id);

        return view('books.details', ['book' => $book]);
    }


    /** GET: /books/edit/{book_id}
     *
     * @param int $book_id
     * @return \Illuminate\View\View
     */
    public function getEdit($book_id)
    {
        $book = Book::findOrFail($book_id);
        $this->authorize('edit-book', $book);

        return view('books.edit', ['book' => $book]);
    }


    /** GET: /books/book-by-isbn13?isbn13={isbn13}
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function getBookByIsbn(Request $request)
    {
        $this->authorize('all');

        $isbn13 = $request->input('isbn13');

        $book = Book::where('isbn13', '=', $isbn13)->with('authors')->get();

        return $book;
    }


    /** POST: /books/edit
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function postEdit(Request $request) {
        $this->validate($request, [
            'book' => 'required|array',
            'book.book_id' => 'required|integer',
            'book.title' => 'required|string',
            'book.publisher' => 'required|string',
            'authors' => 'required',
            'authors.*' => 'string'
        ]);

        $book = $request->input('book');
        $authors = $request->input('authors');

        $db_book = Book::findOrFail($book['book_id']);
        $this->authorize('edit-book', $db_book);

        $db_book->update($book);

        $db_book->authors()->delete();

        foreach ($authors as $author) {
            $db_book->authors()->create(['name' => $author]);
        }

        return redirect('books/details/' . $db_book->book_id);
    }

}