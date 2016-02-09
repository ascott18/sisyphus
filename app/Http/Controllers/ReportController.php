<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Ticket;
use Carbon\Carbon;
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


    public function getIndex(Request $request)
    {
        $this->authorize("make-reports");

        $user = $request->user();

        $departments = Course::visible($user)
            ->join('listings', 'listings.course_id', '=', 'courses.course_id')
            ->distinct()
            ->pluck('department');

        $terms = Term::whereIn('term_id', Course::visible($user)->distinct()->select('term_id')->toBase())
            ->orderBy('order_start_date', 'DESC')
            ->get();

        $currentTermId = $terms->first()->term_id;

        return view('reports.index', compact('departments', 'terms', 'currentTermId'));
    }

    public function postSubmitReport(Request $request)
    {
        $this->authorize("make-reports");


        $this->validate($request, [
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'term_id' => 'required|exists:terms,term_id',
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



        /*  deleted
            nondeleted
            submitted
            notSubmitted
            noBook
         */


        $include = $params['include'];

        $term_id = $params['term_id'];

        if ($include['deleted'] || $include['nondeleted']){
            $query = Order::whereIn('course_id',
                Course::visible()
                ->join('listings', 'listings.course_id', '=', 'courses.course_id')
                ->where('term_id', '=', $term_id)
                ->whereIn('department', $departments)
                ->select('courses.course_id')
                ->toBase())
            ->withTrashed()
            ->where(function($q) use ($include, $start, $end) {
                if ($include['deleted']) {
                    $q->orWhere(function($q) use ($start, $end) {
                        $q->whereNotNull('orders.deleted_at')
                            ->where('orders.deleted_at', '>=', Carbon::parse($start))
                            ->where('orders.deleted_at', '<=', Carbon::parse($end));
                    });
                }
                if ($include['nondeleted']) {
                    $q->orWhere(function($q) use ($start, $end) {
                        $q->whereNull('orders.deleted_at')
                            ->where('orders.created_at', '>=', Carbon::parse($start))
                            ->where('orders.created_at', '<=', Carbon::parse($end));
                    });
                }
            })
            ->with([
                    'course.listings',
                    'course.user' => function($q){
                        $q->select('user_id', 'first_name', 'last_name');
                    },
                    'book.authors'
                ]);

//            $sql = $query->toSql();
            $results = $query->get()->toArray();

            // We get back a list of orders. To be consistent with the other query,
            // which has to return a set of courses, we will transform this into a list of courses.
            $courseOrders = [];
            foreach ($results as $order) {
                $course = $order['course'];
                $course_id = $course['course_id'];

                if (!isset($courseOrders[$course_id])){
                    $courseOrders[$course_id] = [];
                }

                $courseOrders[$course_id][] = $order;
            }

            $results = [];
            foreach ($courseOrders as $course_id => $orders) {
                $course = $orders[0]['course'];
                $course['orders'] = [];

                // Null out this relation so that we don't have forever recursion
                foreach ($orders as $order) {
                    $order['course'] = null;
                    $course['orders'][] = $order;
                }

                $results[] = $course;
            }
        }
        elseif ($include['submitted'] || $include['notSubmitted'] || $include['noBook']) {
            $query = Course::visible()
                ->where('term_id', '=', $term_id)
                ->addWhereExistsQuery(
                    Listing
                        ::whereRaw('listings.course_id = courses.course_id')
                        ->whereIn('department', $departments)
                        ->toBase()
                )
                ->where(function($q) use($include) {
                    $courseOrdersQuery = Order::whereRaw('orders.course_id = courses.course_id')->toBase();

                    if ($include['submitted']){
                        $q->addWhereExistsQuery($courseOrdersQuery, 'or');
                    }
                    if ($include['noBook']){
                        $q->orWhere('courses.no_book', '=', true);
                    }
                    if ($include['notSubmitted']){
                        $q->orWhere(function($qInner) use($courseOrdersQuery){
                            $qInner
                                ->where('courses.no_book', '=', false)
                                ->addWhereExistsQuery($courseOrdersQuery, 'and', $not = true);
                        });
                    }
                })
                ->with([
                    'listings',
                    'user' => function($q){
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