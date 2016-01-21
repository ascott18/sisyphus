<?php

namespace App\Http\Controllers;


use App\Models\Course;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Term;
use Illuminate\Database\Query\Builder;

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

        if($request->input('professor')) {
            $query = $query->where(function($sQuery) use ($request) {
                $sQuery = $sQuery->where('users.first_name', 'LIKE', '%'.$request->input('professor').'%')
                    ->orWhere('users.last_name', 'LIKE', '%'.$request->input('professor').'%');

                $searchArray = preg_split("/[\s,]+/", $request->input('professor'));
                if(count($searchArray) == 2) {
                    $sQuery = $sQuery->orWhere('users.first_name', 'LIKE', '%'.$searchArray[0].'%')
                        ->where('users.last_name', 'LIKE', '%'.$searchArray[1].'%')
                        ->orWhere('users.last_name', 'LIKE', '%'.$searchArray[0].'%')
                        ->where('users.first_name', 'LIKE', '%'.$searchArray[1].'%');
                }

                return $sQuery;
            });
        }

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
        $column = $request->input('sort');
        if ($column) {
            if ($column == "section"){
                if ($request->input('dir')) {
                    $query = $query->orderBy("department", "desc");
                    $query = $query->orderBy("course_number", "desc");
                    $query = $query->orderBy("course_section", "desc");
                } else {
                    $query = $query->orderBy("department");
                    $query = $query->orderBy("course_number");
                    $query = $query->orderBy("course_section");
                }
            } else if($request->input('sort') == "professor") {
                if($request->input('dir')) {
                    $query = $query->orderBy('users.last_name', "desc");
                    $query = $query->orderBy('users.first_name', "desc");
                } else {
                    $query = $query->orderBy('users.last_name');
                    $query = $query->orderBy('users.first_name');
                }
            } else {
                if ($request->input('dir'))
                    $query = $query->orderBy($request->input('sort'), "desc");
                else
                    $query = $query->orderBy($request->input('sort'));

                // If sorting by term, sort by the dept & numbers as secondaries.
                if ($column == 'term_id'){
                    $query = $query->orderBy("department");
                    $query = $query->orderBy("course_number");
                    $query = $query->orderBy("course_section");
                }
            }
        }

        return $query;
    }

    protected static function buildFilteredCourseQuery($query, User $user){
        if ($user->may('view-dept-courses'))
        {
            $departments = $user->departments()->lists('department');
            $query = $query->whereIn('department', $departments);
        }
        elseif (!$user->may('view-all-courses'))
        {
            $query = $query->where('user_id', $user->user_id);
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
        $this->authorize("view-course-list");

        $query = Course::visible($request->user());

        if($request->input('term_id')) {
            $query = $query->where('term_id', '=', $request->input('term_id'));
        }

        if($request->input('sort') == "professor" || $request->input('professor')) {
            $query->join('users','users.user_id', '=', 'courses.user_id');
        }

        $query = $this->buildSearchQuery($request, $query);
        $query = $this->buildSortQuery($request, $query);
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
