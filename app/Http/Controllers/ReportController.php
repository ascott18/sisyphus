<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Models\Course;

class ReportController extends Controller
{
    public static $options = ['Course Number', 'Course Section', 'Instructor', 'Course Title', 'Book Title'
        , 'Author', 'Edition', 'Publisher', 'Required', 'Notes'];

    public function getIndex(Request $request)
    {
        $this->authorize("make-reports");

        $user = $request->user();

        $currentTermIds = Term::currentTerms()->lists('term_id');
        $currentTerm = Term::currentTerms()->first();
        $currentTermId = $currentTerm ? $currentTerm->term_id : '';
        $userTerms = Course::visible($user)->select('term_id')->get();

        $terms = Term::whereIn('term_id', $userTerms)
            ->orWhereIn('term_id', $currentTermIds)
            ->orderBy('term_id', 'DESC')
            ->get();

        foreach ($terms as $term) {
            $term['display_name'] = $term->displayName();
        }


        return view('reports.index',['terms' => $terms, 'currentTermId' => $currentTermId, 'options' => static::$options]);
    }
}