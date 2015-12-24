<?php

namespace App\Http\Controllers;


use App\Models\Course;
use Illuminate\Http\Request;
use App\Models\Term;
use Illuminate\Database\Query\Builder;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $this->authorize("view-course-list");

        $terms = Term::orderBy('term_id', 'DESC')->get();
        $currentTerm = Term::currentTerms()->first();

        return view('courses.index', ['terms' => $terms, 'currentTermId' => $currentTerm ? $currentTerm->term_id : '']);
    }



    /**
     * Display a listing of the resource.
     *
     * @param $id integer The id of the course to display details for.
     * @return \Illuminate\Http\Response
     */
    public function getDetails($id)
    {
        $course = Course::findOrFail($id);

        $this->authorize("view-course", $course);

        return view('courses.details', ['course' => $course]);
    }


    /**
     * Build the search query for the courses controller
     *
     * @param \Illuminate\Database\Query $query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Query
     */
    private function buildSearchQuery($request, $query) {
        if($request->input('section')) {
            $searchArray = preg_split("/[\s-]/", $request->input('section'));
            foreach($searchArray as $key => $field) {
                $searchArray[$key] = ltrim($field, '0');
            }
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
     * Build the sort query for the courses controller
     *
     * @param \Illuminate\Database\Query $query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Query
     */
    private function buildSortQuery($request, $query) {
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

    /** GET: /courses/course-list?page={}&{sort=}&{dir=}&{section=}&{name=}
     * Searches the book list
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCourseList(Request $request)
    {
        $this->authorize("view-course-list");

        $query = Course::query();

        if ($request->user()->may('view-dept-courses'))
        {
            $departments = $request->user()->departments()->lists('department');
            $query = $query->whereIn('department', $departments);
        }
        elseif (!$request->user()->may('view-all-courses'))
        {
            $query = $query->where('user_id', $request->user()->user_id);
        }

        if($request->input('term_id')) {
            $query = $query->where('term_id', '=', $request->input('term_id'));
        }

        $query = $this->buildSearchQuery($request, $query);
        $query = $this->buildSortQuery($request, $query);
        $query = $query->with("term");

        $courses = $query->paginate(10);

        foreach ($courses as $course) {
            $course->term->term_name = $course->term->termName();
        }

        return response()->json($courses);
    }
}
