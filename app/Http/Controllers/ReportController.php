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

class ReportController extends Controller
{
    public static $options = ['Course Number', 'Course Section', 'Instructor', 'Course Title', 'Book Title','ISBN'
        , 'Author', 'Edition', 'Publisher', 'Required', 'Notes'];

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
        $params=$request->all();
        $start=$params['startDate'];
        $end=$params['endDate'];

        $query=DB::table('orders')
                    ->join('courses','orders.course_id','=','courses.course_id')
                    ->join('books','orders.book_id','=','books.book_id')
                    ->join('users','orders.placed_by','=','users.user_id')
                        ->where('orders.created_at','>',Carbon::parse($start))
                        ->where('orders.created_at','<=',Carbon::parse($end))
                    ->get();

       return (['orders'=>$query, 'start'=>$start]);

    }
}