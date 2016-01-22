<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Course;
use App\Models\Term;
use Cache;
use Carbon\Carbon;

class HomeController extends Controller
{
    const CACHE_MINUTES = 5;
    const NUM_TERM_CHARTS = 2;
    const NUM_RESPONSE_STATS = 15;
    const ACTIVITY_STATS_DAYS = 30;

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
        // Cache this for x minutes.
        return Cache::remember('dashboard-' . \Auth::user()->user_id, static::CACHE_MINUTES, function() {
            return [
                'chartData' => static::getChartData(),
                'responseStats' => static::getResponseStats(),
                'cacheMins' => static::CACHE_MINUTES,
                'openTermsCount' => Term::currentTerms()->count(),
                'activityStats' => static::getActivityStats(),
                'newBookCount' => Book::where('created_at', '>=', Carbon::now()->subDays(30))->count()
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
                        ->on('orders.created_at', '=', \DB::raw("(SELECT MIN(o.created_at) FROM orders o WHERE o.course_id = courses.course_id AND o.deleted_at IS NULL)"))
                        ->whereNotNull('orders.deleted_at');
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
                'name' => $term->display_name,
                'status' => $term->status,
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
                ->whereRaw('(courses.no_book_marked IS NOT NULL
                        OR (SELECT 1 FROM orders WHERE orders.course_id = courses.course_id and orders.deleted_at IS NULL LIMIT 1))')
                ->count();

            $data[] = [
                'name' => $term->display_name,
                'responded' => $coursesResponded,
                'total' => $courseCount,
                'percent' => intval($coursesResponded/$courseCount*100),
                'order_due_date' => $term->order_due_date->toDateString()
            ];
        }

        return $data;
    }

    private static function getActivityStats(){

        $start = Carbon::today()->subDays(static::ACTIVITY_STATS_DAYS);

        $activityReport = Course::visible()
            ->join('orders', function ($join) {
                $join
                    ->on('orders.course_id', '=', 'courses.course_id')
                    ->on('courses.no_book_marked', 'IS', \DB::raw('NULL'))
                    ->whereNull('orders.deleted_at');
            })
            ->groupBy('date')
            ->orderBy('date')
            ->having('date', '>=', $start)
            ->select(\DB::raw('count(*) as count,
                DATE(CASE WHEN no_book_marked is not NULL
                    THEN no_book_marked
                    ELSE orders.created_at end) as date'))
            ->get();

        while ($start < Carbon::today()){
            $f = $activityReport->filter(function($value) use ($start){
                return $value['date'] == $start->toDateString();
            });

            $count = count($f);

            if ($count == 0){
                $activityReport[] = [
                    'date' => $start->toDateString(),
                    'count' => 0
                ];
            }

            $start->addDays(1);
        }

        return $activityReport;
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