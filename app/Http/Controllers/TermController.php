<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Term;
use PHPExcel_Cell;
use PHPExcel_IOFactory;
use SearchHelper;

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
    public function postImport(Request $request, $term_id)
    {
        $this->authorize('edit-terms');

        $term = Term::findOrFail($term_id);

        if (!$request->hasFile('file')) {
            return view('terms.import', ['term' => $term])
                ->withErrors(['no-file' => 'No file was uploaded!']);
        }
        $file = $request->file('file');

        if (!$file->isValid()) {
            return view('terms.import', ['term' => $term])
                ->withErrors(['bad-file' => 'There was an issue uploading the file. Please try again.']);
        }


        $fileName = $file->getRealPath();

        // Load the spreadsheet into memory.
        $reader = PHPExcel_IOFactory::createReaderForFile($fileName);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($fileName);

        // Measure the spreadsheet, and then find the locations of the columns that we care about.
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow(); // e.g. 10
        $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

        $relevantColumns = [
            'CAMPUS' => true,           // {RPT, CHN}
            'DEPT' => true,             // {CSCD, PSYC, ENGL, ...}
            'COURSE_ID' => true,        // CSCD371-01
            'TITLE' => true,            // .NET PROGRAMMING
            'XLST_COURSE_ID' => true,   // ENGL170-01
            'TERM' => true,             // 201620

            // Might be useful for future additions, but currently unused:
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
            $course = [];

            foreach ($columnIndiciesByLabel as $columnLabel => $colIndex) {
                $course[$columnLabel] = $worksheet->getCellByColumnAndRow($colIndex, $rowIndex)->getValue();
            }

            $courses[] = $course;
        }
        

        return $courses;



        return view('terms.confirmImport', ['term' => $term]);
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
