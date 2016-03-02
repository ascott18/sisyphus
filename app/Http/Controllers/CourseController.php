<?php

namespace App\Http\Controllers;


use App\Models\Course;
use App\Models\User;
use App\Providers\SearchServiceProvider;
use Illuminate\Http\Request;
use App\Models\Term;
use SearchHelper;

class CourseController extends Controller
{
    public static $CourseValidation = [
        'course.listings' => 'required',
        'course.listings.0' => 'required',
        'course.listings.0.name' => 'required|string',
        'course.listings.*.department' => 'required|string|min:2|max:10',
        'course.listings.*.number' => 'required|numeric',
        'course.listings.*.section' => 'required|numeric',
    ];

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getIndex(Request $request, $term_id = null)
    {
        $this->authorize("view-course-list");

        $user = $request->user();

        $currentTermIds = Term::currentTerms()->pluck('term_id');
        $userTermIds = Course::visible($user)->distinct()->pluck('term_id');

        $allRelevantTermIds = $currentTermIds->merge($userTermIds)->unique();

        $terms = Term::whereIn('term_id', $allRelevantTermIds)
            ->orderBy('term_id', 'DESC')
            ->get();

        $term_id = $term_id ? intval($term_id) : '';

        return view('courses.index', ['terms' => $terms, 'term_id' => $term_id]);
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
        $course = Course::with('listings')->findOrFail($id);

        $this->authorize("edit-course", $course);

        // All users, from which we will select a professor.
        // TODO: restrict this, since dept secretaries can also edit courses.
        $users = User::all(['first_name', 'last_name', 'user_id']);

        return view('courses.edit', ['panelTitle' => 'Edit Course', 'course' => $course, 'users' => $users]);
    }


    public function postEdit(Request $request, $id)
    {
        $dbCourse = Course::findOrFail($id);

        $this->authorize("edit-course", $dbCourse);
        $this->validate($request, static::$CourseValidation);

        $course = $this->cleanCourseForCreateOrEdit($request);
        unset($course['term_id']);

        $listings = $course['listings'];
        unset($course['listings']);


        for ($i = 0; $i < count($dbCourse->listings); $i++) {
            $existingListing = $dbCourse->listings[$i];

            if (isset($listings[$i]))
                $existingListing->update($listings[$i]);
            else
                $existingListing->delete();
        }
        for ($i = count($dbCourse->listings); $i < count($listings); $i++){
            $dbCourse->listings()->create($listings[$i]);
        }


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
        $course = $request->input('course');

        // TODO: silently delete any duplicate listings that come in. no need to yell at the user about it.


        $name = $course['listings'][0]['name'] = trim($course['listings'][0]['name']);

        foreach ($course['listings'] as &$listing) {
            $listing['department'] = strtoupper(trim($listing['department']));
            $listing['name'] = $name;
        }

        // The professor of a course is nullable. Check for empty strings and manually set to null.
        if (!isset($course['user_id']) || !$course['user_id']) $course['user_id'] = null;

        return $course;
    }

    public function postCreate(Request $request)
    {
        $this->authorize("create-courses");
        $this->validate($request, static::$CourseValidation);

        $course = $this->cleanCourseForCreateOrEdit($request);

        $listings = $course['listings'];
        unset($course['listings']);

        $dbCourse = new Course($course);

        // Authorize that the user can indeed create this course before actually saving it.
        $this->authorize("create-course", $dbCourse);

        // They can indeed create this course, so it is now safe to save to the database.

        $dbCourse->save();
        for ($i = 0; $i < count($listings); $i++){
            $dbCourse->listings()->create($listings[$i]);
        }

        return redirect('courses/details/' . $dbCourse->course_id);
    }


    /**
     * Build the search query for the courses controller
     *
     * @param \Illuminate\Database\Query $query
     * @param $tableState
     * @return \Illuminate\Database\Query
     */
    private function buildCourseSearchQuery($tableState, $query) {

        $predicateObject = [];
        if(isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject; // initialize predicate object

        if(isset($predicateObject->section) && $predicateObject->section != '')
            SearchHelper::sectionSearchQuery($query, $predicateObject->section);
        if(isset($predicateObject->name) && $predicateObject->name != '')
            $query = $query->where('name', 'LIKE', '%'.$predicateObject->name.'%');
        if(isset($predicateObject->professor) && $predicateObject->professor != '')
            SearchHelper::professorSearchQuery($query, $predicateObject->professor);

        return $query;
    }


    /**
     * Build the sort query for the courses controller
     *
     * @param \Illuminate\Database\Query $query
     * @param $tableState
     * @return \Illuminate\Database\Query
     */
    private function buildCourseSortQuery($tableState, $query) {
        if(isset($tableState->sort->predicate)) {
            $sorts = [
                'term_id' => [
                    'term_id', '',
                    'department', 'asc',
                    'number', 'asc',
                    'section', 'asc',
                ],
                'section' => [
                    'department', '',
                    'number', '',
                    'section', '',
                ],
                'professor' => [
                    'users.last_name', '',
                    'users.first_name', '',
                ],
                'name' => [
                    'name', '',
                ]
            ];

            SearchHelper::buildSortQuery($query, $tableState->sort, $sorts);
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

        $query = Course::visible($request->user())
            ->select('courses.*')
            ->distinct()
            ->with([
                'term',
                'user' => function($query) {
                    return $query->select('user_id', 'first_name', 'last_name');
                },
                'listings',
                'orders' => function($query) {
                    // Just grab the IDs, since the only reason that we're doing this is to count the orders.
                    // We need the course id so that we can match up the orders that we get back with each course.
                    return $query->select('course_id');
                },
            ]);


        if(isset($tableState->term_selected) && $tableState->term_selected != "") {
            $query = $query->where('term_id', '=', $tableState->term_selected);
        }

        if((isset($tableState->sort->predicate) && $tableState->sort->predicate == "professor")
         || isset($tableState->search->predicateObject->professor) ) {
            // only join in the professor when we actually need it
            $query->join('users','users.user_id', '=', 'courses.user_id');
        }

        $query = $query->join('listings', 'courses.course_id', '=', 'listings.course_id');

        $query = $this->buildCourseSearchQuery($tableState, $query);
        $query = $this->buildCourseSortQuery($tableState, $query);


        $courses = SearchServiceProvider::paginate($query, 10);

        return response()->json($courses);
    }
}
