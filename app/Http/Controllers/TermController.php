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
     * Build the search query for the term controller
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTermSearchQuery($request, $query) {
        if($request->input('term'))
            $query = $query->where('year', '=', $request->input('term'));


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

    /** GET: /terms/term-list?page={}&{sort=}&{dir=}&{title=}&{publisher=}&{isbn=}
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
}
