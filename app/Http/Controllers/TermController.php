<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Term;
use SearchHelper;

class TermController extends Controller
{
    /** GET /terms
     *
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $this->authorize('view-terms');

        $maxYear = Term::max('year');
        $thisYear = Carbon::now()->year;

        if ($maxYear <= $thisYear){
            for($year = $maxYear + 1; $year <= $thisYear + 1; $year++){
                Term::createTermsForYear($year);
            }
        }

        return view('terms.index');
    }


    /** GET /terms/details/{term_id}
     *
     * Display a details view of the term from which the user can make changes.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDetails($term_id)
    {
        $this->authorize('view-terms');

        $term = Term::findOrFail($term_id);

        return view('terms.details', ['term' => $term]);
    }

    /**
     * return array of matched string in the term names
     *
     * @param $searchTerm
     * @return array
     */
    private function searchTermNames($searchTerm) {
        $results = array();
        foreach(Term::$termNumbers as $key => $termName) {
            if (stripos($termName, $searchTerm) !== false) {
                $results[] = $key;
            }
        }

        // if there were no matches, make the search fail
        if(count($results) == 0) {
            $results[] = -1;
        }

        return $results;
    }

    /**
     * Build the search query for the term controller
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTermSearchQuery($tableState, $query) {
        if (isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject; // initialize predicate object
        else
            return $query;

        if(isset($predicateObject->term) && $predicateObject->term != "") {
            $termList = $this->searchTermNames($predicateObject->term);

            $query = $query->Where(function($sQuery) use ($termList) {
                for($i=0; $i<count($termList); $i++) {
                    $sQuery = $sQuery->orWhere('term_number', '=', $termList[$i]);            // this will take entire search into term field
                }
            });
        }

        if (isset($predicateObject->year) && $predicateObject->year != '') {
            $query = $query->where('year', '=', $predicateObject->year);              // this will search for matching year
        }

        return $query;
    }

    /**
     * Build the sort query for the term controller
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTermSortQuery($tableState, $query) {
        if (isset($tableState->sort->predicate)) {
            $sorts = [
                'term' => [
                    'term_number', '',
                    'year', '',
                ],
                'year' => [
                    'year', '',
                    'term_id', '',
                ],
                'order_start_date' => [
                    'order_start_date', '',
                ],
                'order_end_date' => [
                    'order_end_date', '',
                ]
            ];

            SearchHelper::buildSortQuery($query, $tableState->sort, $sorts);
        }
        return $query;
    }

    /** GET: /terms/term-list?page={}
     * Searches the term list
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function getTermList(Request $request)
    {
        $tableState = json_decode($request->input('table_state'));

        $this->authorize('view-terms');

        $query = Term::query();

        $query = $this->buildTermSearchQuery($tableState, $query);
        $query = $this->buildTermSortQuery($tableState, $query);

        return $query->paginate(15);
    }


    /** POST /terms/details/{term_id}
     *
     * Accept updated dates for the given term.
     *
     * @param Request $request
     * @param $term_id
     * @return \Illuminate\Http\Response
     */
    public function postDetails(Request $request, $term_id)
    {
        $this->authorize('edit-terms');

        $term = Term::findOrFail($term_id);

        $dates = $request->only('order_start_date', 'order_due_date');

        $term->update($dates);

        return $this->getDetails($term_id);
    }

}
