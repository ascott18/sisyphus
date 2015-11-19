<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Term;

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
        $terms = Term::orderBy('order_due_date', 'DESC')->paginate(10);

        return view('terms.index', ['terms' => $terms]);
    }


    /** GET /terms/details/{term_id}
     *
     * Display a details view of the term from which the user can make changes.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDetails($term_id)
    {
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
    private function buildTermSearchQuery($request, $query) {
        if($request->input('term')) {

            $searchArray = preg_split("/[\s]/", $request->input('term')); // split the terms
            //print_r($searchArray);
            if(count($searchArray) == 1) {
                $termList = $this->searchTermNames($request->input('term'));
                for($i=0; $i<count($termList); $i++) {
                    $query = $query->orWhere("term_number", "=", $termList[$i]);      // this will take entire search into term field
                }
                $query = $query->orWhere("year", "=", $request->input('term'));
            } else if(count($searchArray) == 2) {
                $query = $query->where(function($sQuery) use ($searchArray, $request) {     // create subquery to find term
                    $termList = $this->searchTermNames($request->input('term'));
                    if(count($termList) > 0) {
                        for ($i = 0; $i < count($termList); $i++) {
                            $sQuery = $sQuery->orWhere("term_number", "=", $termList[$i]);      // this will take entire search into term field
                        }
                    } else {
                        $termList = $this->searchTermNames($searchArray[0]);
                        if(count($termList) > 0) {
                            for ($i = 0; $i < count($termList); $i++) {
                                $sQuery = $sQuery->orWhere("term_number", "=", $termList[$i]);      // this will take entire search into term field
                            }
                        }
                        $sQuery = $sQuery->where("year", "=", $searchArray[1]);

                    }

                    return $sQuery;

                    /*
                    for($i=0; $i<count($searchArray); $i++) {                               // need to check other cases of 2 where search term is term and year
                        $termList = $this->searchTermNames($searchArray[$i]);               // look for term
                        //print_r($termList);
                        for($j=0; $j<count($termList); $j++) {                              // iterate over results
                            $sQuery = $sQuery->orwhere("term_number", "=", $termList[$j]);
                        }
                        if(count($termList)>0)
                            $sQuery = $sQuery->Where("year", "=", $searchArray[$i]);          // apply each to year as well to catch those
                        else
                            $sQuery = $sQuery->orWhere("year", "=", $searchArray[$i]);          // apply each to year as well to catch those

                    }
                    */
                });
            } else if(count($searchArray) == 3) {                                           // if they enter three things, its either term term year, or year term term
                $query = $query->where(function($sQuery) use ($searchArray) {
                    for($i=0; $i<2; $i++) {                                                 // look at both combinations
                        $termList = $this->searchTermNames($searchArray[$i] . " " . $searchArray[$i+1]);
                        foreach($termList as $term)
                            $sQuery = $sQuery->orWhere("term_number", "=", $term);
                    }
                });
                $query = $query->Where(function($sQuery) use ($searchArray) {              // place each in the year category to see if any of those stick
                    for($i=0; $i<count($searchArray); $i++) {
                        $sQuery = $sQuery->orWhere("year", "=", $searchArray[$i]);
                    }
                });
            }
        }

        //print_r($query->toSql());
        //print("\n");

        return $query;
    }

    /**
     * Build the sort query for the term controller
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTermSortQuery($request, $query) {
        if($request->input('sort')) {
            if($request->input('sort') == "term") {
                if($request->input('dir')) {
                    $query = $query->orderBy("term_number", "desc")
                                    ->orderBy("year", "desc");
                } else {
                    $query = $query->orderBy("term_number")
                                    ->orderBy("year");
                }
            } else {
                if ($request->input('dir'))
                    $query = $query->orderBy($request->input('sort'), "desc");
                else
                    $query = $query->orderBy($request->input('sort'));
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

        $query = Term::query();

        $query = $this->buildTermSearchQuery($request, $query);

        $query = $this->buildTermSortQuery($request, $query);

        //print_r($query->toSql());

        $terms = $query->paginate(10);


        foreach($terms as $term) {
            $term->termName = $term->termName();
            $term->status = $term->getStatusDisplayString();
            $term->orderStartDate = $term->order_start_date->toFormattedDateString(); // This is how we eager load the start date
            $term->orderDueDate = $term->order_due_date->toFormattedDateString(); // This is how we eager load the due date
        }

        return response()->json($terms);
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
        $term = Term::findOrFail($term_id);

        $dates = $request->only('order_start_date', 'order_due_date');

        $term->update($dates);

        return $this->getDetails($term_id);
    }

    public function getCheck($term_id)
    {
        $term = Term::with("courses.orders.book")->findOrFail($term_id);

        return view('terms.check',['term'=>$term]);
    }

    /**
     * Build the search query for the term detail list
     *
     * @param \Illuminate\Database\Query $query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Query
     */
    private function buildDetailSearchQuery($request, $query) {
        if($request->input('section')) {
            $searchArray = preg_split("/[\s-]/", $request->input('section'));
            if(count($searchArray) == 2) {
                $query = $query->where('department', 'LIKE', '%'.$searchArray[0].'%')
                    ->where('course_number', 'LIKE', '%'.$searchArray[1].'%')
                    ->orWhere('course_number', 'LIKE', '%'.$searchArray[0].'%')
                    ->where('course_section', 'LIKE', '%'.$searchArray[1].'%')
                    ->orWhere('department', 'LIKE', '%'.$searchArray[0].'%')
                    ->where('course_section', 'LIKE', '%'.$searchArray[1].'%');
            } elseif(count($searchArray) == 3) {
                $query = $query->where('department', 'LIKE', '%'.$searchArray[0].'%')
                    ->where('course_number', 'LIKE', '%'.$searchArray[1].'%')
                    ->where('course_section', 'LIKE', '%'.$searchArray[2].'%');
            } else {
                for($i=0; $i<count($searchArray); $i++) {
                    $query = $query->where('department', 'LIKE', '%'.$searchArray[$i].'%')
                        ->orWhere('course_number', 'LIKE', '%'.$searchArray[$i].'%')
                        ->orWhere('course_section', 'LIKE', '%'.$searchArray[$i].'%');
                }
            }
        }

        if($request->input('name'))
            $query = $query->where('course_name', 'LIKE', '%'.$request->input('name').'%');

        return $query;
    }

    /**
     * Build the sort query for the term detail list
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildDetailSortQuery($request, $query) {
        if($request->input('sort'))
            if($request->input('sort') == "section"){
                if($request->input('dir')) {
                    $query = $query->orderBy("department", "desc");
                    $query = $query->orderBy("course_number", "desc");
                    $query = $query->orderBy("course_section", "desc");
                } else {
                    $query = $query->orderBy("department");
                    $query = $query->orderBy("course_number");
                    $query = $query->orderBy("course_section");
                }
            } else {
                if($request->input('dir'))
                    $query = $query->orderBy($request->input('sort'), "desc");
                else
                    $query = $query->orderBy($request->input('sort'));
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

        $query = \App\Models\Course::query();


        if($request->input('term_id'))
            $query = $query->where('term_id', '=', $request->input('term_id')); // find the term ID

        $query = $this->buildDetailSearchQuery($request, $query); // build the search terms query

        $query = $this->buildDetailSortQuery($request, $query); // build the sort query

        $courses = $query->paginate(10); // get paginated result

        return response()->json($courses);
    }

}
