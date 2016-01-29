<?php

namespace App\Http\Controllers;


use App\Models\Course;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Term;
use Illuminate\Database\Query\Builder;
use SearchHelper;

class CourseController extends Controller
{
    public static $CourseValidation = [
        'course.department' => 'required|string|min:2|max:10',
        'course.course_name' => 'required|string',
        'course.course_number' => 'required|numeric',
        'course.course_section' => 'required|numeric',
    ];

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getIndex(Request $request)
    {
        $this->authorize("view-course-list");

        $user = $request->user();

        $currentTermIds = Term::currentTerms()->pluck('term_id');
        $userTermIds = Course::visible($user)->distinct()->pluck('term_id');

        $allRelevantTermIds = $currentTermIds->merge($userTermIds)->unique();

        $terms = Term::whereIn('term_id', $allRelevantTermIds)
            ->orderBy('term_id', 'DESC')
            ->get();

        return view('courses.index', ['terms' => $terms]);
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

        // We will show deleted orders in red on this screen.
        $course->orders = $course->orders()->withTrashed()->get();

        return view('courses.details', ['course' => $course]);
    }


    /**
     * Display the page to edit the course.
     *
     * @param $id integer The id of the course to edit.
     * @return \Illuminate\Http\Response
     */
    public function getEdit($id)
    {
        $course = Course::findOrFail($id);

        $this->authorize("edit-course", $course);

        // All users, from which we will select a professor.
        $users = User::all(['first_name', 'last_name', 'user_id']);

        return view('courses.edit', ['panelTitle' => 'Edit Course', 'course' => $course, 'users' => $users]);
    }


    public function postEdit(Request $request, $id)
    {
        // TODO: always uppercase the department so that we don't require
        // the user to make it uppercase when they type it in.

        $dbCourse = Course::findOrFail($id);

        $this->authorize("edit-course", $dbCourse);
        $this->validate($request, static::$CourseValidation);

        $course = $this->cleanCourseForCreateOrEdit($request);
        unset($course['term_id']);

        $dbCourse->update($course);
        $dbCourse->save();

        return redirect('courses/details/' . $dbCourse->course_id);
    }


    /**
     * Display the page to create a course.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCreate($term_id)
    {
        $this->authorize("create-courses");

        // All users, from which we will select a professor.
        $users = User::all(['first_name', 'last_name', 'user_id']);

        $term = Term::findOrFail($term_id);

        return view('courses.edit', ['panelTitle' => 'New Course', 'users' => $users, 'term_id' => $term_id, 'term_name' => $term->displayName()]);
    }


    private function cleanCourseForCreateOrEdit(Request $request)
    {
        $course = $request->get('course');

        $course['department'] = trim($course['department']);
        $course['course_name'] = trim($course['course_name']);

        // The professor of a course is nullable. Check for empty strings and manually set to null.
        if (!isset($course['user_id']) || !$course['user_id']) $course['user_id'] = null;

        return $course;
    }

    public function postCreate(Request $request)
    {
        $this->authorize("create-courses");
        $this->validate($request, static::$CourseValidation);

        $course = $this->cleanCourseForCreateOrEdit($request);

        $dbCourse = new Course($course);

        // Authorize that the user can indeed create this course before actually saving it.
        $this->authorize("create-course", $dbCourse);

        // They can indeed create this course, so it is now safe to save to the database.
        $dbCourse->save();

        return redirect('courses/details/' . $dbCourse->course_id);
    }


    /**
     * Build the search query for the courses controller
     *
     * @param \Illuminate\Database\Query $query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Query
     */
    private function buildSearchQuery($tableState, $query) {

        $predicateObject = [];
        if(isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject; // initialize predicate object

        if(isset($predicateObject->section))
            SearchHelper::sectionSearchQuery($query, $predicateObject->section);

        if(isset($predicateObject->name))
            $query = $query->where('course_name', 'LIKE', '%'.$predicateObject->name.'%');

        if(isset($predicateObject->professor))
            SearchHelper::professorSearchQuery($query, $predicateObject->professor);

        return $query;
    }


    /**
     * Build the sort query for the courses controller
     *
     * @param \Illuminate\Database\Query $query
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Query
     */
    private function buildSortQuery($tableState, $query) {
        if(isset($tableState->sort->predicate)){
            $sort = $tableState->sort;
            if ($sort->predicate == "section"){
                if ($sort->reverse == 1) {
                    $query = $query->orderBy("department", "desc");
                    $query = $query->orderBy("course_number", "desc");
                    $query = $query->orderBy("course_section", "desc");
                } else {
                    $query = $query->orderBy("department");
                    $query = $query->orderBy("course_number");
                    $query = $query->orderBy("course_section");
                }
            } else if($sort->predicate == "professor") {
                if($sort->reverse == 1) {
                    $query = $query->orderBy('users.last_name', "desc");
                    $query = $query->orderBy('users.first_name', "desc");
                } else {
                    $query = $query->orderBy('users.last_name');
                    $query = $query->orderBy('users.first_name');
                }
            } else {
                if ($sort->reverse == 1)
                    $query = $query->orderBy($sort->predicate, "desc");
                else
                    $query = $query->orderBy($sort->predicate);

                // If sorting by term, sort by the dept & numbers as secondaries.
                if ($sort->predicate == 'term_id'){
                    $query = $query->orderBy("department");
                    $query = $query->orderBy("course_number");
                    $query = $query->orderBy("course_section");
                }
            }
        }

        return $query;
    }

    /** GET: /courses/course-list?page={}&{sort=}&{dir=}&{section=}&{name=}
     * Searches the book list
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCourseList(Request $request)
    {
        $tableState = json_decode($request->input('table_state'));

        $this->authorize("view-course-list");

        $query = Course::visible($request->user());

        if(isset($tableState->term_selected) && $tableState->term_selected != "") {
            $query = $query->where('term_id', '=', $tableState->term_selected);
        }


        if((isset($tableState->sort->predicate) && $tableState->sort->predicate == "professor")
            || isset($tableState->search->predicateObject->professor) ) { // only join when we actually need it

            $query->join('users','users.user_id', '=', 'courses.user_id');

        }

        $query = $this->buildSearchQuery($tableState, $query);
        $query = $this->buildSortQuery($tableState, $query);
        $query = $query->with("term");
        $query = $query->with("user");


        $courses = $query->paginate(10);

        foreach ($courses as $course) {
            $course->term->term_name = $course->term->termName();


            $course->order_count = Order::query()
                ->where('course_id', '=', $course->course_id)
                ->count();
        }

        return response()->json($courses);
    }
}
