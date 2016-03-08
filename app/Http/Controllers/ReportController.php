<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Models\Course;
use Auth;
use DB;
use App\Models\Order;
use App\Models\Book;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ReportController extends Controller
{
    /** GET: /reports/{$term_id?}
     *
     * Displays a page to generate reports.
     * If $term_id is provided, the term filter will be pre-filled with that term.
     *
     * @param Request $request
     * @param null $term_id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex(Request $request, $term_id = null)
    {
        $this->authorize("make-reports");

        $user = $request->user();

        // Get all of the departments that will be visible to the user.
        // We have to do this because for a bookstore user who can see all courses,
        // they are not assigned to any departments specifically, so we need to look at
        // what actually exists in the database to see what departments they will be able to select.
        $departments = Course::visible($user)
            ->join('listings', 'listings.course_id', '=', 'courses.course_id')
            ->distinct()
            ->pluck('department');

        // Find all the terms that have courses that are visible to the user
        // so that the user is not bombarded with lots of semester terms when they
        // won't have any data in those terms, for example.
        /** @var Builder $terms */
        $terms = Term::whereIn('term_id',
            Course::visible($user)
                ->distinct()
                ->select('term_id')
                ->toBase()
        );

        // If a term_id was passed in, make sure that the term will be present in
        // the dropdown list where the user selects a term.
        if ($term_id){
            // Do a findOrFail so the request will fail if an invalid term_id was passed in.
            Term::findOrFail($term_id);
            $terms = $terms->orWhere('term_id', '=', $term_id);
        }

        $terms = $terms
            ->orderBy('order_start_date', 'DESC')
            ->get();

        // This is the term that will be selected by default.
        $currentTermId = $term_id ? intval($term_id) : $terms->first()->term_id;

        return view('reports.index', [
            'departments' => $departments,
            'terms' => $terms,
            'currentTermId' => $currentTermId
        ]);
    }


    /** POST: /reports/submit-report
     *
     * Receives options for report generation and returns an appropriate set of data.
     *
     * @param Request $request
     * @return array
     */
    public function postSubmitReport(Request $request)
    {
        $this->authorize("make-reports");

        $this->validate($request, [
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'term_id' => 'required|exists:terms,term_id',
            'reportType' => 'required|string|in:orders,courses',
            'departments' => 'required|array',
            'departments.0' => 'required|string',
            'departments.*' => 'string',
            'include.deleted' => 'required|boolean',
            'include.nondeleted' => 'required|boolean',
            'include.submitted' => 'required|boolean',
            'include.notSubmitted' => 'required|boolean',
            'include.noBook' => 'required|boolean',
        ]);

        $params = $request->all();

        $start = $params['startDate'];
        $end = $params['endDate'];
        $departments = $params['departments'];
        $include = $params['include'];
        $term_id = $params['term_id'];

        if ($params['reportType'] == 'orders' && ($include['deleted'] || $include['nondeleted'])){
            // The user has requested an orders (requests) report.

            // Start with all the orders for courses that fall in the
            // term that the user has selected and the departments that the user has selected.
            $query = Order::whereIn('course_id',
                Course::visible()
                    ->join('listings', 'listings.course_id', '=', 'courses.course_id')
                    ->where('term_id', '=', $term_id)
                    ->whereIn('department', $departments)
                    ->select('courses.course_id')
                    ->toBase()
                )

            // Include deleted orders by default.
            // We will filter out deleted orders in the following WHERE clause based on the user's input.
            ->withTrashed()

            // Filter the types of orders that the user has not chosen to include.
            ->where(function($q) use ($include, $start, $end) {
                if ($include['deleted']) {
                    // The user would like to include deleted orders.
                    // Pick those orders where deleted_at isn't null and the deleted date falls in the date range.
                    $q->orWhere(function($q) use ($start, $end) {
                        $q->whereNotNull('orders.deleted_at')
                            ->where('orders.deleted_at', '>=', Carbon::parse($start))
                            ->where('orders.deleted_at', '<=', Carbon::parse($end));
                    });
                }
                if ($include['nondeleted']) {
                    // The user would like to include non-deleted orders.
                    // Pick those orders where deleted_at IS null and the created date falls in the date range.
                    $q->orWhere(function($q) use ($start, $end) {
                        $q->whereNull('orders.deleted_at')
                            ->where('orders.created_at', '>=', Carbon::parse($start))
                            ->where('orders.created_at', '<=', Carbon::parse($end));
                    });
                }
            })

            // Eager load all the related models that we get back.
            ->with([
                    'course.listings',
                    'course.user' => function($q){
                        $q->select('user_id', 'first_name', 'last_name');
                    },
                    'book.authors'
                ]);

            $results = $query->get()->toArray();

            // We get back a list of orders from the database. To be consistent with the courses report,
            // which must return a set of courses, we will transform this into a list of courses.

            // An array that will hold all the orders for each course, keyed by course_id.
            $courseOrders = [];
            foreach ($results as $order) {
                $course = $order['course'];
                $course_id = $course['course_id'];

                if (!isset($courseOrders[$course_id])){
                    $courseOrders[$course_id] = [];
                }

                $courseOrders[$course_id][] = $order;
            }

            // We need to run a second transformation now
            // so that we end up with an array of courses (right now we have an array of arrays of orders)
            $results = [];
            foreach ($courseOrders as $course_id => $orders) {
                // This course is the same for all orders in $orders. Just grab the first one.
                $course = $orders[0]['course'];

                // Make an array to hold all of the orders of the course.
                $course['orders'] = [];

                foreach ($orders as $order) {
                    // Null out this relation so that we don't have infinite loops.
                    $order['course'] = null;

                    // Add the order to the course's orders.
                    $course['orders'][] = $order;
                }

                // Store this course in the final results.
                $results[] = $course;
            }
        }
        elseif ($params['reportType'] == 'courses' && ($include['submitted'] || $include['notSubmitted'] || $include['noBook'])) {

            // The user has requested a courses report.
            $query = Course::visible()
                // Restrict the courses by term.
                ->where('term_id', '=', $term_id)

                // Restrict the courses by the departments that were selected by the user.
                // Only include courses where there exists at least one listing of that course
                // where the department of that listing is in the departments selected by the user.
                ->addWhereExistsQuery(
                    Listing
                        ::whereRaw('listings.course_id = courses.course_id')
                        ->whereIn('department', $departments)
                        ->toBase()
                )

                // Restrict the courses by the includes selected by the user.
                ->where(function($q) use($include) {
                    $courseOrdersQuery = Order::whereRaw('orders.course_id = courses.course_id')->toBase();

                    if ($include['submitted']){
                        // Include courses that have at least one non-deleted order.
                        $q->addWhereExistsQuery($courseOrdersQuery, 'or');
                    }
                    if ($include['noBook']){
                        // Include courses that are marked no book. This one's easy.
                        $q->orWhere('courses.no_book', '=', true);
                    }
                    if ($include['notSubmitted']){
                        // Include courses that aren't no book and that have no orders.
                        // This is effectively the negation of the other two options.
                        $q->orWhere(function($qInner) use($courseOrdersQuery){
                            $qInner
                                ->where('courses.no_book', '=', false)
                                ->addWhereExistsQuery($courseOrdersQuery, 'and', $not = true);
                        });
                    }
                })

                // Eager load all the related models that we get back.
                ->with([
                    'listings',
                    'user' => function($q){
                        // Only select what we need from users for security/information disclosure purposes.
                        $q->select('user_id', 'first_name', 'last_name');
                    },
                    'orders.book.authors'
                ]);


            $results = $query->get()->toArray();
        }
        else {
            throw new BadRequestHttpException("No includes were specified. Report would be empty.");
        }

        return (['courses' => $results]);
    }
}