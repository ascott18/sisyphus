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
    public static $options = [
        'course_number' => 'Course Number',
        'course_section' => 'Course Section',
        'course_instructor' => 'Instructor',
        'course_title' => 'Course Title',
        'book_title' => 'Book Title',
        'book_isbn' => 'ISBN',
        'book_authors' => 'Author',
        'book_edition' => 'Edition',
        'book_publisher' => 'Publisher',
        'order_required' => 'Required',
        'order_notes' => 'Notes'
    ];

    public function getIndex(Request $request)
    {
        $this->authorize("make-reports");

        $user = $request->user();

        $userTerms = Course::visible($user)->distinct()->lists('term_id');

        $terms = Term::whereIn('term_id', $userTerms)
            ->orderBy('order_start_date', 'DESC')
            ->get();

        $currentTermId = $terms->first()->term_id;
        foreach ($terms as $term) {
            $term['display_name'] = $term->displayName();
        }


        return view('reports.index',['terms' => $terms, 'currentTermId' => $currentTermId, 'options' => static::$options]);
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

            if ($include['deleted'] && $include['nondeleted']){
                // do nothing
            }
            elseif ($include['deleted']) {
                $query->whereNotNull('orders.deleted_at');
            }
            elseif ($include['nondeleted']) {
                $query->whereNull('orders.deleted_at');
            }

            $query
                ->where('orders.created_at', '>=', Carbon::parse($start))
                ->where('orders.created_at', '<=', Carbon::parse($end))
                ->with(['course', 'book.authors']);
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
        }
        else {
            throw new BadRequestHttpException("No includes were specified. Report would be empty.");
        }


        $sql = $query->toSql();
        $results = $query->get();




        $query = Course::visible();


        $query = DB::table('orders')
                    ->join('courses','orders.course_id','=','courses.course_id')
                    ->join('books','orders.book_id','=','books.book_id')
                    ->join('users','orders.placed_by','=','users.user_id')
                    ->get();

       return (['orders'=>$query, 'start'=>$start]);

    }
}