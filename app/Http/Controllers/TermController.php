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
        $this->authorize("view-terms");

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
        $this->authorize("view-terms");

        $term = Term::findOrFail($term_id);

        return view('terms.details', ['term' => $term]);
    }

    /**
     * return array of matched string in the term names
     *
     */
    private function searchTermNames($searchTerm) {
        $results = array();
        foreach(Term::$termNumbers as $key => $termName) {
            if(stripos($termName, $searchTerm) !== false) {
                $results[] = $key;
            }
        }
        return $results;
    }

    /**
     * Build the search query for the term controller
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTermSearchQuery($tableState, $query) {
        $predicateObject = [];
        if(isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject; // initialize predicate object

        if(isset($predicateObject->term)) {
            $termList = $this->searchTermNames($predicateObject->term);

            $query = $query->Where(function($sQuery) use ($termList) {
                for($i=0; $i<count($termList); $i++) {
                    $sQuery = $sQuery->orWhere("term_number", "=", $termList[$i]);            // this will take entire search into term field
                }
            });
        }

        if(isset($predicateObject->year) && $predicateObject->year != "") {
            $query = $query->where("year", "=", $predicateObject->year);              // this will search for matching year
        }

        return $query;
    }

    /**
     * Build the sort query for the term controller
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTermSortQuery($tableState, $query) {
        if(isset($tableState->sort->predicate)){
            $sort = $tableState->sort;
            if($sort->predicate == "term") {
                if($sort->reverse == 1) {
                    $query = $query->orderBy("term_number", "desc")
                                    ->orderBy("year", "desc");
                } else {
                    $query = $query->orderBy("term_number")
                                    ->orderBy("year");
                }
            } else {
                if ($sort->reverse == 1)
                    $query = $query->orderBy($sort->predicate, "desc");
                else
                    $query = $query->orderBy($sort->predicate);
            }
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

        $this->authorize("view-terms");

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
        $this->authorize("edit-terms");

        $term = Term::findOrFail($term_id);

        $dates = $request->only('order_start_date', 'order_due_date');

        $term->update($dates);

        return $this->getDetails($term_id);
    }

    public function getCheck($term_id)
    {
        $this->authorize("view-terms"); // TODO: maybe a different permission for this?

        $term = Term::findOrFail($term_id);
        $courses = Course::visible()->where('term_id', '=', $term_id)->with(['orders.book', 'user'])->get();

        return view('terms.check', ['term' => $term, 'courses' => $courses]);
    }

    /**
     * Build the search query for the term detail list
     *
     * @param \Illuminate\Database\Query $query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Query
     */
    private function buildDetailSearchQuery($tableState, $query) {
        $predicateObject = [];
        if(isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject; // initialize predicate object

        if(isset($predicateObject->section))
            SearchHelper::sectionSearchQuery($query, $predicateObject->section);

        if(isset($predicateObject->name))
            $query = $query->where('course_name', 'LIKE', '%'.$predicateObject->name.'%');

        return $query;
    }

    /**
     * Build the sort query for the term detail list
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildDetailSortQuery($tableState, $query) {
        if(isset($tableState->sort->predicate)) {
            $sort = $tableState->sort;
            if ($sort->predicate == "section") {
                if ($sort->reverse == 1) {
                    $query = $query->orderBy("department", "desc");
                    $query = $query->orderBy("course_number", "desc");
                    $query = $query->orderBy("course_section", "desc");
                } else {
                    $query = $query->orderBy("department");
                    $query = $query->orderBy("course_number");
                    $query = $query->orderBy("course_section");
                }
            } else {
                if ($sort->reverse == 1)
                    $query = $query->orderBy($sort->predicate, "desc");
                else
                    $query = $query->orderBy($sort->predicate);
            }
        }

        return $query;
    }


    /** GET: /books/book-detail-list?page={}&{sort=}&{dir=}&{section=}&{course_name=}&{ordered_by=}
     * Searches the book list
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function getTermDetailList(Request $request)
    {
        $tableState = json_decode($request->input('table_state'));

        $this->authorize("view-terms");

        $query = \App\Models\Course::visible($request->user());

        if(isset($tableState->term_id) && $tableState->term_id != "")
            $query = $query->where('term_id', '=', $tableState->term_id);

        $query = $this->buildDetailSearchQuery($tableState, $query); // build the search terms query
        $query = $this->buildDetailSortQuery($tableState, $query); // build the sort query

        $courses = $query->paginate(10); // get paginated result

        return response()->json($courses);
    }

}
