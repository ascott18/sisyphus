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
        return view('courses.index');
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
        $query = Course::query();

        $query = $this->buildSearchQuery($request, $query);

        $query = $this->buildSortQuery($request, $query);

        $courses = $query->paginate(10);

        return response()->json($courses);
    }

//
//    /**
//     * Show the form for creating a new resource.
//     *
//     * @return \Illuminate\Http\Response
//     */
//    public function create()
//    {
//        //
//    }
//
//    /**
//     * Store a newly created resource in storage.
//     *
//     * @param  \Illuminate\Http\Request  $request
//     * @return \Illuminate\Http\Response
//     */
//    public function store(Request $request)
//    {
//        //
//    }
//
//    /**
//     * Display the specified resource.
//     *
//     * @param  int  $id
//     * @return \Illuminate\Http\Response
//     */
//    public function show($id)
//    {
//        //
//    }
//
//    /**
//     * Show the form for editing the specified resource.
//     *
//     * @param  int  $id
//     * @return \Illuminate\Http\Response
//     */
//    public function edit($id)
//    {
//        //
//    }
//
//    /**
//     * Update the specified resource in storage.
//     *
//     * @param  \Illuminate\Http\Request  $request
//     * @param  int  $id
//     * @return \Illuminate\Http\Response
//     */
//    public function update(Request $request, $id)
//    {
//        //
//    }
//
//    /**
//     * Remove the specified resource from storage.
//     *
//     * @param  int  $id
//     * @return \Illuminate\Http\Response
//     */
//    public function destroy($id)
//    {
//        //
//    }
}
