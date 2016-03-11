<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Listing;
use App\Models\User;
use App\Providers\ImportServiceProvider;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\Term;
use PHPExcel_Cell;
use PHPExcel_IOFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Providers\SearchServiceProvider;


class TermController extends Controller
{

    /** GET /terms
     *
     * Display a listing of all terms.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $this->authorize('view-terms');

        $maxYear = Term::max('year');
        $thisYear = Carbon::now()->year;

        // Create the terms up to a year in the future.
        // This is the only way for new terms to be created - for users to visit this page.
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


    /** GET: /terms/term-list?page={}
     *
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


    /** POST /terms/import-preview/{term_id}
     *
     * Performs a dry run of a data import using a transaction that will be rolled back.
     * Allows the user to preview an import before they commit to it.
     *
     * @param Request $request
     * @param $term_id
     * @return \Illuminate\Http\Response
     * @throws Exception
     */
    public function postImportPreview(Request $request, $term_id)
    {
        return $this->importHelper($request, $term_id, false);
    }


    /** POST /terms/import-data/{term_id}
     *
     * Finalizes a data import.
     *
     * @param Request $request
     * @param $term_id
     * @return \Illuminate\Http\Response
     * @throws Exception
     */
    public function postImportData(Request $request, $term_id)
    {
        return $this->importHelper($request, $term_id, true);
    }


    /**
     * Performs a data import from a user-provided spreadsheet.
     * This import is wrapped in a transaction. Unless otherwise specified,
     * this transaction will automatically be rolled back at the end of this call,
     * effectively performing a dry run of this import in order to determine the side effects.
     *
     * @param Request $request
     * @param $term_id
     * @param bool|false $doCommitTransaction
     * @return array|Response
     * @throws Exception
     */
    private function importHelper(Request $request, $term_id, $doCommitTransaction = false)
    {
        $this->authorize('modify-courses');

        // We don't actually need the term here, but we do need to do a findOrFail in case the given term_id is invalid.
        $term = Term::findOrFail($term_id);

        // Do some basic verification on the uploaded file.
        if (!$request->hasFile('file')) {
            return response(['success' => false, 'message' => 'No file was uploaded!'], Response::HTTP_BAD_REQUEST);
        }
        $file = $request->file('file');

        if (!$file->isValid()) {
            return response(['success' => false, 'message' => 'There was an issue uploading the file. Please try again.'], Response::HTTP_BAD_REQUEST);
        }

        // The uploaded file will be in a temp directory somewhere (depends on the server configuration).
        $fileName = $file->getRealPath();

        // Load the spreadsheet into memory.
        // Blanket catch exceptions because the error handling of PHPExcel doesn't seem to be all that good.
        // If the user uploads something that isnt an understandable spreadsheet format, any number
        // of errors could happen here, so we just catch everything and report that a bad file was given.
        try{
            $reader = PHPExcel_IOFactory::createReaderForFile($fileName);

            // Setting this makes the loading of the spreadsheet considerably faster.
            $reader->setReadDataOnly(true);

            // Actually load up the spreadsheet.
            $spreadsheet = $reader->load($fileName);
        }
        catch (Exception $e){
            throw new BadRequestHttpException("The file provided is not in a format that could be understood.", $e);
        }

        DB::beginTransaction();

        // In the event of any exceptions during our parsing or importing,
        // roll back the transaction and then rethrow the exception so it will be
        // logged and presented to the user.
        try{
            // Parse the spreadsheet into all of its courses and listings.
            // Any users found in the spreadsheet will actually be created in the DB in this call,
            // so make sure to start the transaction before it.
            // Courses and Listings will not be created until the importCourses call, however.
            $courses = ImportServiceProvider::parseSpreadsheet($spreadsheet, $term_id);

            // This step will determine everything that is needed to import the courses and their listings
            // into the database, including any necessary creates, updates, or deletions.
            // We are returned a list of actions taken that will be displayed back to the user so they can
            // get some feedback on what is going on with the data that they tried to import.
            $actions = ImportServiceProvider::importCourses($courses, $term_id);
        }
        catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // If we get here, everything went well.
        // If this was a dry run, we still want to roll back the transaction.
        // If it was a for real run, go ahead and commit it!
        if ($doCommitTransaction){
            DB::commit();
        }
        else{
            DB::rollBack();
        }

        return ['success' => true, 'actions' => $actions];
    }


    /**
     * Given a string to search for, returns an array of matching term numbers.
     * Term numbers are the keys from Term::$termNumbers.
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
     * Build the search query for the term list
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

        if (isset($predicateObject->term) && $predicateObject->term != '') {
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
     * Build the sort query for the term list
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

            SearchServiceProvider::buildSortQuery($query, $tableState->sort, $sorts);
        }
        return $query;
    }

}
