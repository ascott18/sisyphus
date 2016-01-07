<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Term;
use Cache;
use Carbon\Carbon;

class HomeController extends Controller
{
    const CACHE_MINUTES = 1;
    const NUM_TERM_CHARTS = 2;
    const NUM_RESPONSE_STATS = 10;

    /** GET: /
     *
     * @return \Illuminate\View\View
     */
    public function getIndex()
    {
        $this->authorize("all");

        return view('welcome', static::getCachedDashboardData());
    }

    private static function getCachedDashboardData(){
        // Cache this for 10 minutes.
        return Cache::remember('dashboard-' . \Auth::user()->user_id, static::CACHE_MINUTES, function() {
            return [
                'chartData' => static::getChartData(),
                'responseStats' => static::getResponseStats(),
                'cacheMins' => static::CACHE_MINUTES,
                'openTermsCount' => Term::currentTerms()->count(),
            ];
        });
    }

    private static function getChartData(){

        $chartData = [];

        $i = 0;
        $numChartsFound = 0;
        while ($numChartsFound < static::NUM_TERM_CHARTS) {
            $term = Term::currentOrPast()
                ->orderBy('term_id', 'DESC')
                ->skip($i++)
                ->first();

            if ($term == null)
                break;

            $courseCount = Course::visible()->where('term_id', '=', $term->term_id)->count();

            if ($courseCount == 0)
                continue;

            $numChartsFound++;

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

            $ordersByDate = static::getAccumulationsFromReport($orderReport);


            $noBookReport = Course::visible()->where('term_id', '=', $term->term_id)
                ->whereRaw('UNIX_TIMESTAMP(courses.no_book_marked) != 0')
                ->groupBy('date')
                ->orderBy('date')
                ->select(\DB::raw('count(courses.no_book_marked) as count, DATE(courses.no_book_marked) as date'))
                ->get();

            $noBookByDate = static::getAccumulationsFromReport($noBookReport);

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

            $currentCount = $lastRow['orders'] + $lastRow['nobook'];

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

            $chartData[] = [
                'name' => $term->displayName(),
                'status' => $term->getStatusDisplayString(),
                'term_id' => $term->term_id,
                'current_count' => $currentCount,
                'course_count' => $courseCount,
                'data' => $results
            ];
        }

        return $chartData;
    }


    private static function getResponseStats(){

        $data = [];

        $i = 0;
        $numChartsFound = 0;
        while ($numChartsFound < static::NUM_RESPONSE_STATS) {
            $term = Term::currentOrPast()
                ->orderBy('term_id')
                ->skip($i++)
                ->first();

            if ($term == null)
                break;

            $courseCount = Course::visible()->where('term_id', '=', $term->term_id)->count();

            if ($courseCount == 0)
                continue;

            $numChartsFound++;

            $coursesResponded = Course::visible()
                ->where('term_id', '=', $term->term_id)
                ->where(function($query) use ($term){
                    return $query
                        ->whereRaw('UNIX_TIMESTAMP(courses.no_book_marked) != 0 OR (SELECT COUNT(*) FROM orders where orders.course_id = courses.course_id) > 0');
                })

                ->count();

            $data[] = [
                'name' => $term->displayName(),
                'responded' => $coursesResponded,
                'total' => $courseCount,
                'percent' => intval($coursesResponded/$courseCount*100),
                'order_due_date' => $term->order_due_date->toDateString()
            ];
        }

        return $data;
    }

    private static function getAccumulationsFromReport($reportData)
    {
        $result = [];
        $total = 0;
        foreach ($reportData as $line) {
            $result[$line->date] = $total = $total + $line->count;
        }

        return $result;
    }

}