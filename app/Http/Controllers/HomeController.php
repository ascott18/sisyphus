<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Term;
use Carbon\Carbon;

class HomeController extends Controller
{
    /** GET: /
     *
     * @return \Illuminate\View\View
     */
    public function getIndex()
    {
        $this->authorize("all");

        $terms = Term::currentTerms()->get();
        if (count($terms) == 0)
            $terms = Term::recentTerms(45)->get();

        $termData = [];

        foreach ($terms as $term) {
            $courseCount = Course::visible()->where('term_id', '=', $term->term_id)->count();

            $orderReport = Course::visible()->where('term_id', '=', $term->term_id)
                ->join('orders', function ($join) {
                    $join
                        ->on('orders.course_id', '=', 'courses.course_id')
                        ->on('orders.created_at', '=', \DB::raw("(SELECT MIN(o.created_at) FROM orders o WHERE o.course_id = courses.course_id)"));
                })
                ->groupBy('date')
                ->orderBy('date')
                ->select(\DB::raw('count(*) as count, DATE(orders.created_at) as date'))
                ->get();

            $ordersByDate = $this->getAccumulationsFromReport($orderReport);


            $noBookReport = Course::visible()->where('term_id', '=', $term->term_id)
                ->whereRaw('UNIX_TIMESTAMP(courses.no_book_marked) != 0')
                ->groupBy('date')
                ->orderBy('date')
                ->select(\DB::raw('count(courses.no_book_marked) as count, DATE(courses.no_book_marked) as date'))
                ->get();

            $noBookByDate = $this->getAccumulationsFromReport($noBookReport);

            $start = $term->order_start_date;
            $end = $term->order_due_date;

            $results = [];

            $lastRow = [
                'orders' => 0,
                'nobook' => 0,
            ];
            while ($start->diffInDays($end) > 0 && $start->isPast()){
                $dateString = $start->toDateString();
                $results[] = $lastRow = [
                    'date' => $dateString,
                    'orders' => isset($ordersByDate[$dateString]) ? $ordersByDate[$dateString] : $lastRow['orders'],
                    'nobook' => isset($noBookByDate[$dateString]) ? $noBookByDate[$dateString] : $lastRow['nobook'],
                ];
                $start->addDays(1);
            }

            // For everything from now until the end of the term, display nothing.
            if ($start->diffInDays($end) > 1){
                $dateString = $start->toDateString();
                $results[] = $lastRow = [
                    'date' => $dateString,
                    'orders' => null,
                    'nobook' => null,
                ];

                $dateString = $end->toDateString();
                $results[] = $lastRow = [
                    'date' => $dateString,
                    'orders' => null,
                    'nobook' => null,
                ];
            }

            $termData[] = [
                'name' => $term->displayName(),
                'term_id' => $term->term_id,
                'course_count' => $courseCount,
                'data' => $results
            ];
        }

        return view('welcome', ['chartData' => $termData]);
    }

    private function getAccumulationsFromReport($reportData)
    {
        $result = [];
        $total = 0;
        foreach ($reportData as $line) {
            $result[$line->date] = $total = $total + $line->count;
        }

        return $result;
    }

}