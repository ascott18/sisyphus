<?php

namespace App\Http\Controllers;

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

        $userTerms = Course::visible($user)->distinct()->lists('term_id');

        $departments=Course::visible($user)->distinct()->lists('department');

        $terms = Term::whereIn('term_id', $userTerms)
            ->orderBy('order_start_date', 'DESC')
            ->get();

        $currentTermId = $terms->first()->term_id;
        foreach ($terms as $term) {
            $term['display_name'] = $term->displayName();
        }
        return view('reports.index',['departments' => $departments,'terms' => $terms, 'currentTermId' => $currentTermId]);
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

        $params=$request->all();
        $start=$params['startDate'];
        $end=$params['endDate'];
        //$dept=$params['dept'];



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
                ->where('term_id', '=', $term_id)
                ->select('course_id')
                ->toBase())
            ->withTrashed();
            if ($include['deleted']) {
                $query->whereNotNull('orders.deleted_at');
            }
            elseif ($include['nondeleted']) {
                $query->whereNull('orders.deleted_at');
            }

            $query
                ->where('orders.created_at', '>=', Carbon::parse($start))
                ->where('orders.created_at', '<=', Carbon::parse($end))
                ->with(['course', 'book.authors']);

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
                ->where(function($q) use($include) {
                    $courseOrdersQuery = Order::where('orders.course_id', '=', DB::raw('courses.course_id'))->toBase();

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
                ->with(['user', 'orders.book.authors']);


            $results = $query->get()->toArray();
        }
        else {
            throw new BadRequestHttpException("No includes were specified. Report would be empty.");
        }



        return (['courses' => $results, 'start' => $start]);
    }
}