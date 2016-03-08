<?php

namespace App\Http\Controllers;


use App\Models\Course;
use App\Models\Listing;
use App\Models\User;
use App\Providers\SearchServiceProvider;
use Illuminate\Http\Request;
use App\Models\Term;
use Illuminate\Validation\ValidationException;

class CourseController extends Controller
{
    /**
     * The validation rules for creating or updating a course.
     *
     * @var array
     */
    public static $CourseValidation = [
        'course.term_id' => 'required|numeric',
        'course.listings' => 'required',
        'course.listings.0' => 'required',
        'course.listings.0.name' => 'required|string',
        'course.listings.*.department' => 'required|string|min:2|max:10|regex:/[A-Za-z]{2,10}/',
        'course.listings.*.number' => 'required|regex:/[0-9]{1,9}[A-Z]?/',
        'course.listings.*.section' => 'required|numeric',
    ];


    /** GET: /courses/{$term_id?}
     *
     * Display a listing of courses.
     * If term_id is provided, the filter on the grid will be initially set to that term..
     *
     * @param Request $request
     * @param null $term_id
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


    /** GET: /courses/details/course_id
     *
     * Displays the details of a course.
     *
     * @param $course_id integer The id of the course to display details for.
     * @return \Illuminate\Http\Response
     */
    public function getDetails($course_id)
    {
        $course = Course::findOrFail($course_id);

        $this->authorize("view-course", $course);

        // We will show deleted orders in red on this screen,
        // so we need to include deleted ones on the course.
        $course->orders = $course->orders()->withTrashed()->get();

        return view('courses.details', ['course' => $course]);
    }


    /** GET: /courses/edit/{$course_id}
     *
     * Display the page to edit a course.
     *
     * @param Request $request
     * @param $course_id int The id of the course to edit.
     * @return \Illuminate\Http\Response
     */
    public function getEdit(Request $request, $course_id)
    {
        $course = Course::with('listings')->findOrFail($course_id);

        $this->authorize("modify-course", $course);

        // All users, from which we will select a professor.
        // Only select what we need for security/information disclosure purposes.
        $users = User::all(['first_name', 'last_name', 'user_id']);

        return view('courses.edit', [
            'panelTitle' => 'Edit Course',
            'course' => $course,
            'users' => $users,
            'term_id' => $course->term->term_id,
            'term_name' => $course->term->displayName()]);
    }


    /** POST: /courses/edit/{$course_id}
     *
     * Process a request to edit a course.
     *
     * @param Request $request
     * @param $course_id The id of the course to edit.
     * @return \Illuminate\Http\Response
     */
    public function postEdit(Request $request, $course_id)
    {
        $dbCourse = Course::findOrFail($course_id);

        $this->authorize("modify-course", $dbCourse);
        $this->validate($request, static::$CourseValidation);

        $course = $this->cleanCourseForCreateOrEdit($request);

        $this->validateNoDuplicateListings($request, $course, $course_id);

        unset($course['term_id']);

        $listings = $course['listings'];
        unset($course['listings']);


        for ($i = 0; $i < count($dbCourse->listings); $i++) {
            $existingListing = $dbCourse->listings[$i];

            if (isset($listings[$i])){
                $existingListing->update($listings[$i]);
            }
            else{
                $existingListing->delete();
            }
        }
        for ($i = count($dbCourse->listings); $i < count($listings); $i++){
            $dbCourse->listings()->create($listings[$i]);
        }


        $dbCourse->update($course);
        $dbCourse->save();

        return redirect('courses/details/' . $dbCourse->course_id);
    }


    /** GET: /courses/create/{$term_id}
     *
     * Display the page to create a course.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCreate($term_id)
    {
        $this->authorize("modify-courses");

        // All users, from which we will select a professor.
        // Only select what we need for security/information disclosure purposes.
        $users = User::all(['first_name', 'last_name', 'user_id']);

        $term = Term::findOrFail($term_id);

        return view('courses.edit', [
            'panelTitle' => 'New Course',
            'users' => $users,
            'term_id' => $term_id,
            'term_name' => $term->displayName()
        ]);
    }


    /** POST: /courses/create
     *
     * Process a request to create a course
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws ValidationException
     */
    public function postCreate(Request $request)
    {
        $this->authorize("modify-courses");
        $this->validate($request, static::$CourseValidation);

        $course = $this->cleanCourseForCreateOrEdit($request);

        $this->validateNoDuplicateListings($request, $course, null);

        $listings = $course['listings'];
        unset($course['listings']);

        $dbCourse = new Course($course);

        // Authorize that the user can indeed create this course before actually saving it.
        $this->authorize("modify-course", $dbCourse);

        // They can indeed create this course, so it is now safe to save to the database.
        $dbCourse->save();
        for ($i = 0; $i < count($listings); $i++){
            $dbCourse->listings()->create($listings[$i]);
        }

        return redirect('courses/details/' . $dbCourse->course_id);
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

        $this->authorize('view-course-list');

        $query = Course::visible($request->user())
            ->select('courses.*')
            ->distinct()
            ->with([
                'term',
                'user' => function($query) {
                    // Only select what we need from users for security/information disclosure purposes.
                    return $query->select('user_id', 'first_name', 'last_name');
                },
                'listings',
                'orders' => function($query) {
                    // Just grab the IDs, since the only reason that we're doing this is to count the orders.
                    // We need the course id so that we can match up the orders that we get back with each course.
                    return $query->select('course_id');
                },
            ]);


        if (isset($tableState->term_selected) && $tableState->term_selected != "") {
            $query = $query->where('term_id', '=', $tableState->term_selected);
        }

        if ((isset($tableState->sort->predicate) && $tableState->sort->predicate == 'professor')
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


    private function cleanCourseForCreateOrEdit(Request $request)
    {
        $course = $request->input('course');

        // All listings of a course have a name, and it must be the same for all listings.
        // The request that we get will have the name of the course on the first listing.
        // Take this name, and set it on all of our listings.
        $name = $course['listings'][0]['name'] = trim($course['listings'][0]['name']);

        foreach ($course['listings'] as &$listing) {
            $listing['department'] = strtoupper(trim($listing['department']));
            $listing['name'] = $name;
            $listing['number'] = trim($listing['number']);
            $listing['section'] = trim($listing['section']);
        }

        // Delete any duplicate listings on the course.
        // This can only be done once we propagate the name to all of the listings
        // (otherwise they won't all be comparable).
        // The call to array_values will rebase the indicies to count up from 0 once again after holes are made.
        $course['listings'] = array_values(array_unique($course['listings'], SORT_REGULAR));

        // The professor of a course is nullable. Check for empty strings and manually set to null.
        if (!isset($course['user_id']) || !$course['user_id']){
            $course['user_id'] = null;
        }

        return $course;
    }


    private function validateNoDuplicateListings(Request $request, $course, $course_id = null)
    {
        $term_id = $course['term_id'];

        foreach ($course['listings'] as &$listing) {
            $existingListings = Listing::join('courses', 'courses.course_id', '=', 'listings.course_id')
                ->where('term_id', '=', $term_id)
                ->where(array_only($listing, ['department', 'number', 'section']));
            if ($course_id)
                $existingListings->where('listings.course_id', '!=', $course_id);

            if ($existingListings->count()){
                throw new ValidationException(null, $this->buildFailedValidationResponse($request, [
                    'The listing ' . $listing['department'] . ' ' . $listing['number'] .
                    '-' . $listing['section'] . ' already exists.'
                ]));
            }
        }
    }


    /**
     * Build the search query for the courses controller
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildCourseSearchQuery($tableState, $query) {

        if (isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject;
        else
            return $query;

        if (isset($predicateObject->section) && $predicateObject->section != '')
            SearchServiceProvider::sectionSearchQuery($query, $predicateObject->section);

        if (isset($predicateObject->name) && $predicateObject->name != '')
            $query = $query->where('name', 'LIKE', '%'.$predicateObject->name.'%');

        if (isset($predicateObject->professor) && $predicateObject->professor != '')
            SearchServiceProvider::professorSearchQuery($query, $predicateObject->professor);

        return $query;
    }


    /**
     * Build the sort query for the courses controller
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildCourseSortQuery($tableState, $query) {
        if (isset($tableState->sort->predicate)) {
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

            SearchServiceProvider::buildSortQuery($query, $tableState->sort, $sorts);
        }
        
        return $query;
    }
}
