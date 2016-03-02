<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Listing;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\Term;
use PHPExcel_Cell;
use PHPExcel_IOFactory;
use SearchHelper;
use Symfony\Component\HttpFoundation\Response;

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
        $this->authorize('view-terms');

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
     * @param $term_id
     * @return \Illuminate\Http\Response
     */
    public function getDetails($term_id)
    {
        $this->authorize('view-terms');

        $term = Term::findOrFail($term_id);

        return view('terms.details', ['term' => $term]);
    }


    /** GET /terms/import/{term_id}
     *
     * Displays a page from which the user can select a file with course information to upload.
     *
     * @param $term_id
     * @return \Illuminate\Http\Response
     */
    public function getImport($term_id)
    {
        $this->authorize('edit-terms');

        $term = Term::findOrFail($term_id);

        return view('terms.import', ['term' => $term]);
    }


    /** POST /terms/import/{term_id}
     *
     * @param Request $request
     * @param $term_id
     * @return \Illuminate\Http\Response
     */
    public function postImportPreview(Request $request, $term_id)
    {
        $this->authorize('edit-terms');

        $term = Term::findOrFail($term_id);

        if (!$request->hasFile('file')) {
            return response(['success' => false, 'message' => 'No file was uploaded!'], Response::HTTP_BAD_REQUEST);
        }
        $file = $request->file('file');

        if (!$file->isValid()) {
            return response(['success' => false, 'message' => 'There was an issue uploading the file. Please try again.'], Response::HTTP_BAD_REQUEST);
        }


        $fileName = $file->getRealPath();

        // Load the spreadsheet into memory.
        $reader = PHPExcel_IOFactory::createReaderForFile($fileName);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($fileName);

        $courses = static::parseSpreadsheet($spreadsheet, $term_id);

        DB::beginTransaction();
        try{
            $actions = static::importCourses($courses, $term_id);
        }
        catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        DB::rollBack();

        return ['success' => true, 'actions' => $actions];
    }

    private static function importCourses($courses, $term_id){
        $dbTerm = Term::findOrFail($term_id);
        $dbTermCourseIdsQuery = $dbTerm->courses()->select('course_id')->toBase();


        $updatedDbCourseIds = [];

        foreach ($courses as $course) {
            $dbListings = [];

            $dbCourse = null;
            $numListingsFound = 0;
            foreach ($course['listings'] as $listing) {
                $dbListing = Listing::whereIn('course_id', $dbTermCourseIdsQuery)->where([
                    'department' => $listing['department'],
                    'number' => $listing['number'],
                    'section' => $listing['section']
                ])->first();

                if ($dbListing){
                    $numListingsFound++;
                    if (!$dbCourse) {
                        $dbCourse = $dbListing->course;
                        $dbListings[] = $dbListing;
                    }
                    elseif ($dbCourse->course_id != $dbListing->course_id) {
                        // We found another listing, but it is on a different course.
                        // So, we will ignore it.
                    }
                    else{
                        $dbListings[] = $dbListing;
                    }
                }
                else{
                    // It is important that we fill gaps with nulls.
                    $dbListings[] = null;
                }
            }

            if ($numListingsFound == 0){
                $listings = $course['listings'];

                // We can't save the course with the listings stuck in its attributes table.
                // Take them out, and then save them on the course the proper way.
                unset($course['listings']);
                $dbTerm->courses()->save($course);
                $course->listings()->saveMany($listings);
                $updatedDbCourseIds[$course->course_id] = true;
                // TODO: record this action as feedback to the user.
            }
            else {
                $i = 0;
                $updatedDbListingIds = [];
                $updatedDbCourseIds[$dbCourse->course_id] = true;
                foreach ($course['listings'] as $listing) {
                    $dbListing = $dbListings[$i++];
                    if (!$dbListing){
                        // TODO: record this action as feedback to the user.
                        $dbCourse->listings()->save($listing);
                        $dbListing = $listing;
                    }
                    else {
                        // TODO: check if this actually needs updating, and count an update and a noaction differently.
                        // TODO: record this action as feedback to the user.
                        $dbListing->update($listing->toArray());
                    }

                    $updatedDbListingIds[$dbListing->listing_id] = true;
                }

                $dbListingsCount = count($dbCourse->listings);
                foreach ($dbCourse->listings as $dbListing) {
                    if (!isset($updatedDbListingIds[$dbListing->listing_id])){
                        // this listing was not just created or updated. It needs to be deleted.

                        if ($dbListingsCount == 1){
                            // The listing to be deleted is the last listing on the course.
                            // We need to delete the course as well.

                            // if the course has orders, delete all the orders and mark the course as nobook.
                            // if the course doesn't have orders (including any deleted orders), just delete the course.
                            if (count($dbCourse->orders) == 0){
                                $dbCourse->listings()->delete();
                                $dbCourse->delete();
                                // TODO: record this action as feedback to the user/
                            }else{

                                $dbCourse->orders()->delete();
                                $dbCourse->no_book = true;
                                $dbCourse->no_book_marked = Carbon::now();
                                $dbCourse->save();
                                // TODO: record this action as feedback to the user/
                            }

                        }
                        else{
                            // There are other listings on the course besides this one.
                            // Deletion is safe.
                            $dbListing->delete();
                            // TODO: record this action as feedback to the user/
                        }

                        $dbListingsCount--;
                    }
                }

            }
        }


        foreach ($dbTerm->courses as $dbCourse) {
            if (!isset($updatedDbCourseIds[$dbCourse->course_id])){
                // this course was not just created or updated. It needs to be deleted.

                // if the course has orders, delete all the orders and mark the course as nobook.
                // if the course doesn't have orders (including any deleted orders), just delete the course.
                if (count($dbCourse->orders) == 0){
                    $dbCourse->listings()->delete();
                    $dbCourse->delete();
                    // TODO: record this action as feedback to the user/
                }else{

                    $dbCourse->orders()->delete();
                    $dbCourse->no_book = true;
                    $dbCourse->no_book_marked = Carbon::now();
                    $dbCourse->save();
                    // TODO: record this action as feedback to the user/
                }
            }
        }
    }

    private static function parseSpreadsheet($spreadsheet, $term_id){

        // Measure the spreadsheet, and then find the locations of the columns that we care about.
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow(); // e.g. 10
        $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

        $relevantColumns = [
            'SORTCOURSE' => true,       // CSCD371-01
            'TITLE' => true,            // .NET PROGRAMMING
            'XLST_COURSE_ID' => true,   // ENGL170-01

            // TODO: ask the bookstore if we only want CHN campus?
            // Might be useful for future additions, but currently unused:
            //'CAMPUS' => true,           // {RPT, CHN}
            //'DEPT' => true,             // {CSCD, PSYC, ENGL, ...}
            //'TERM' => true,             // 201620
            // 'GRP_MAX_ENRL'      // (max enrolment for the course, including all xlistings)
        ];


        // Scan through the header row and find where the $relevantColumns are.
        $columnIndiciesByLabel = [];
        for ($colIndex = 0; $colIndex <= $highestColumnIndex; $colIndex++){
            $columnLabel = $worksheet->getCellByColumnAndRow($colIndex, 1)->getValue();
            if (isset($relevantColumns[$columnLabel]))
                $columnIndiciesByLabel[$columnLabel] = $colIndex;
        }


        // Now, scan through all the data rows and pick out the $relevantColumns.
        $courses = [];
        for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++){
            $course = [
                'listings' => []
            ];

            foreach ($columnIndiciesByLabel as $columnLabel => $colIndex) {
                $course[$columnLabel] = $worksheet->getCellByColumnAndRow($colIndex, $rowIndex)->getValue();
            }

            $courses[] = $course;
        }


        // We're done with the spreadsheet now. Lets make sense of all this data!
        $bucketsById = [];
        $allBuckets = [];


        foreach ($courses as $course) {
            $courseId = $course['SORTCOURSE'];
            $xlId = $course['XLST_COURSE_ID'];


            $hasBucket = false;
            $hasBucket2 = false;
            if (isset($bucketsById[$courseId])){
                $bucket = $bucketsById[$courseId];
                $hasBucket = true;
            }

            if (isset($bucketsById[$xlId])){
                $bucket2 = $bucketsById[$xlId];
                $hasBucket2 = true;
            }

            if (!$xlId || (!$hasBucket && !$hasBucket2)) {
                $bucket = new \ArrayObject([$course]);

                $bucketsById[$courseId] = $bucket;
                $allBuckets[] = $bucket;
                if ($xlId)
                    $bucketsById[$xlId] = $bucket;
            }
            elseif ($hasBucket && !$hasBucket2) {
                $bucket[] = $course;
                $bucketsById[$xlId] = $bucket;
            }
            elseif (!$hasBucket && $hasBucket2) {
                $bucket2[] = $course;
                $bucketsById[$courseId] = $bucket2;
            }
            elseif ($bucket == $bucket2) {
                $bucket[] = $course;
            }
            else {
                // merge two buckets
                foreach ($bucket2 as $bucketCourse) {
                    $bucket[] = $bucketCourse;
                    $bucketsById[$bucketCourse['SORTCOURSE']] = $bucket;
                    $bucketsById[$bucketCourse['XLST_COURSE_ID']] = $bucket;
                }

                array_splice($allBuckets, array_search($bucket2, $allBuckets), 1);
            }
        }

        $courses = [];
        foreach ($allBuckets as $bucket) {
            $array = $bucket->getArrayCopy();

            $orderedBucket = from($array)
                ->orderBy('$v["SORTCOURSE"]')
                ->toArray();

            $courseTitle = from($array)
                ->first('$v["TITLE"]')['TITLE'];

            $course = new Course();
            $course->term_id = $term_id;
            $listings = [];
            // $firstCourse->user_id = NULL; // TODO;

            $addedIds = [];
            // do all the SORTCOURSE values before the XLST values.
            foreach ($orderedBucket as $spreadsheetRow) {
                $listing = static::addId($courseTitle, $spreadsheetRow['SORTCOURSE'], $addedIds);
                if ($listing) $listings[] = $listing;
            }
            foreach ($orderedBucket as $spreadsheetRow) {
                if (isset($spreadsheetRow['XLST_COURSE_ID']) && $spreadsheetRow['XLST_COURSE_ID']){

                    $listing = static::addId($courseTitle, $spreadsheetRow['XLST_COURSE_ID'], $addedIds);
                    if ($listing) $listings[] = $listing;
                }
            }

            if (count($listings)){
                $course['listings'] = $listings;
                $courses[] = $course;
            }
        }


        return $courses;
    }

    private static function addId($courseTitle, $id, &$addedIds) {
        if (isset($addedIds[$id])){
            return null;
        }
        $addedIds[$id] = true;

        $matches = [];
        preg_match('/([A-Z]+)([0-9]+[A-Z]?)-([0-9]+)/', $id, $matches);

        if (count($matches) != 4){
            echo "DIDNT MATCH WELL: $id";
            // TODO: WARN ABOUT THIS
        } else {
            $listing = new Listing();
            $listing->name = $courseTitle;
            $listing->department = $matches[1];
            $listing->number = ltrim($matches[2], '0');
            $listing->section = intval($matches[3]);
            return $listing;
        }
        return null;
    }


    /**
     * return array of matched string in the term names
     *
     * @param $searchTerm
     * @return array
     */
    private function searchTermNames($searchTerm) {
        $results = array();
        foreach(Term::$termNumbers as $key => $termName) {
            if (stripos($termName, $searchTerm) !== false) {
                $results[] = $key;
            }
        }

        // if there were no matches, make the search fail
        if(count($results) == 0) {
            $results[] = -1;
        }

        return $results;
    }

    /**
     * Build the search query for the term controller
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTermSearchQuery($tableState, $query) {
        if (isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject;
        else
            return $query;

        if(isset($predicateObject->term) && $predicateObject->term != "") {
            $termList = $this->searchTermNames($predicateObject->term);

            $query = $query->Where(function($sQuery) use ($termList) {
                for($i=0; $i<count($termList); $i++) {
                    $sQuery = $sQuery->orWhere('term_number', '=', $termList[$i]);
                }
            });
        }

        if (isset($predicateObject->year) && $predicateObject->year != '') {
            $query = $query->where('year', '=', $predicateObject->year);
        }

        return $query;
    }

    /**
     * Build the sort query for the term controller
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTermSortQuery($tableState, $query) {
        if (isset($tableState->sort->predicate)) {
            $sorts = [
                'term' => [
                    'term_number', '',
                    'year', '',
                ],
                'year' => [
                    'year', '',
                    'term_id', '',
                ],
                'order_start_date' => [
                    'order_start_date', '',
                ],
                'order_end_date' => [
                    'order_end_date', '',
                ]
            ];

            SearchHelper::buildSortQuery($query, $tableState->sort, $sorts);
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

        $this->authorize('view-terms');

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
        $this->authorize('edit-terms');

        $this->validate($request, [
            'order_start_date' => 'required|date',
            'order_due_date' => 'required|date',
        ]);

        $term = Term::findOrFail($term_id);

        $dates = $request->only('order_start_date', 'order_due_date');

        $term->update($dates);

        return $this->getDetails($term_id);
    }
}
