<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Listing;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use PHPExcel;
use PHPExcel_Cell;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ImportServiceProvider extends ServiceProvider
{
    // The value in the spreadsheet for courses that have an undetermined professor.
    const FACULTY_EMAIL_TBD = '#N/A';

    // Not relevant anymore, but in a past version of the report that gets imported here,
    // about 40% of professors were marked confidential.
    const FACULTY_EMAIL_CONFIDENTIAL = 'CONFID';


    /** The list of columns in the input report that we care about.
     * @var array
     */
    public static $RELEVANT_COLUMNS = [
        'COURSE_ID' => true,        // ART155-01
        'TITLE' => true,            // BEGINNING PAINTING
        'XLST_COURSE_ID' => true,   // ART450-01
        'XLST_TITLE' => true,       // PAINTING
        'FacultyEmail' => true,
        'FacultyLastName' => true,
        'FacultyFirstName' => true,

        // Might be useful for future additions, but currently unused:
        //'CAMPUS' => true,           // {RPT, CHN}
        //'DEPT' => true,             // {CSCD, PSYC, ENGL, ...}
        //'TERM' => true,             // 201620
        // 'GRP_MAX_ENRL'      // (max enrolment for the course, including all xlistings)
    ];

    /**
     * A mapping of replacements to make in the title of courses after the course title is normalized.
     *
     * @var array
     */
    protected static $title_transforms_post = [
        "Ii" => "II",
        "Iii" => "III",
        "Iv" => "IV",
        ".net" => ".NET",
        "Adst" => "ADST",
        "U.s." => "U.S.",
        "Us" => "US",
        "Nw" => "NW",
        "Pe" => "PE",
        "Pt" => "PT",
        "Mba" => "MBA",
        "Directed Study " => "Directed Study: ",
    ];

    /**
     * A mapping of replacements to make in the title of courses before the course title is normalized.
     *
     * @var array
     */
    protected static $title_transforms_pre = [
        "DS/" => "Directed Study ",
        "DIR ST" => "Directed Study ",
    ];


    /**
     * A collection of course numbers to ignore when importing courses.
     *
     * @var array
     */
    protected static $ignoredNumbers = [
        '199', // Directed & Independent study
        '299', // Directed & Independent study
        '399', // Directed & Independent study
        '499', // Directed & Independent study
        '599', // Directed & Independent study

        '295', // Internship
        '395', // Internship
        '495', // Internship
        '595', // Internship
        '694', // Internship/practicum for some departments
        '695', // Internship

        '600', // Thesis
        '601', // Research
    ];

    public static function importCourses($courses, $term_id)
    {
        // Get the term that we are importing for.
        /* @var $dbTerm Term */
        $dbTerm = Term::findOrFail($term_id);

        // A query that represents all of the courseIds that belong to the current term.
        // We will use it as a subquery below.
        $dbTermCourseIdsQuery = $dbTerm->courses()->select('course_id')->toBase();

        // This array will store all the feedback that will be presented to the user.
        // Each array is in a slightly different format. Check the place where each
        // array is assigned to if you need to discover what the format is. Or,
        // go look at /resources/terms/import.blade.php to see the Angular that displays this data.
        $results = [
            'newCourse' => [],
            'newListing' => [],
            'updatedListing' => [],
            'noChangeListing' => [],
            'deletedListing' => [],
            'deletedCourseWithOrders' => [],
            'deletedCourseWithoutOrders' => [],
        ];

        // We need to store all the courseIds that we touch so that
        // we can delete any courseIds that we don't touch after we're all done.
        $updatedDbCourseIds = [];


        $user = \Auth::user();
        foreach ($courses as $course) {
            // Ignore any courses that the user doesn't have permissions to create/edit
            if (!$user->can('modify-course', $course))
            {
                continue;
            }

            // Store all the listings that are already in the database for this course.
            // If a listing is not found in the database for a given input listing,
            // this array will record a null in its place so that there is always a
            // 1-to-1 mapping between this array and $course['listings'].
            $dbListings = [];

            /* @var $dbCourse Course */
            $dbCourse = null;

            $numListingsFound = 0;
            foreach ($course['listings'] as $listing) {
                // Search for an occurrence of this listing for this term
                // (the whereIn restricts the query to the current term without having to do a join).
                $dbListing = Listing::whereIn('course_id', $dbTermCourseIdsQuery)->where([
                    'department' => $listing['department'],
                    'number' => $listing['number'],
                    'section' => $listing['section']
                ])->first();

                if ($dbListing){
                    $numListingsFound++;
                    if (!$dbCourse) {
                        // If we haven't found a course yet (i.e. this is the first matching listing we've found),
                        // save a pointer to the course.
                        $dbCourse = $dbListing->course;
                        $dbListings[] = $dbListing;
                    }
                    elseif ($dbCourse->course_id != $dbListing->course_id) {
                        // We found another listing, but it is on a different course.
                        // So, we will ignore the listing. The other listing from the different course
                        // should get deleted when we come around to processing that course
                        // (in theory, anyway - i have no idea if this will happen in practice. sorry.).
                        $dbListings[] = null;
                    }
                    else{
                        $dbListings[] = $dbListing;
                    }
                }
                else{
                    // It is important that we fill gaps with nulls.
                    // The code below this relies on this happening.
                    $dbListings[] = null;
                }
            }

            if ($numListingsFound == 0){
                // If we didn't find any matching listings already in the database, then this is a brand new course!

                // We can't save the course with the listings stuck in its attributes table.
                // Take them out, and then save them on the course the proper way.
                $listings = $course['listings'];
                unset($course['listings']);

                $dbCourse = $dbTerm->courses()->save($course);
                $dbCourse->listings()->saveMany($listings);

                // Record that this course_id has been touched so that it doesn't get deleted later.
                $updatedDbCourseIds[$dbCourse->course_id] = true;

                // Use magic method access to attach the listings to the model.
                $dbCourse->listings;

                // Record this action to display it to the user.
                $results['newCourse'][] = $dbCourse;
            }
            else {
                // This course already exists in the database.
                // Try to reconcile the input data with the existing data.
                $i = 0;
                $updatedDbListingIds = [];

                // Record that this course_id has been touched so that it doesn't get deleted later.
                $updatedDbCourseIds[$dbCourse->course_id] = true;

                if ($course->user_id != $dbCourse->user_id){
                    // The professor of the course has changed.
                    // Update the course to the new user.
                    $dbCourse->user_id = $course->user_id;
                    $dbCourse->save();

                    // Attach the user to the model so it can be given as feedback to the user.
                    $dbCourse->user;
                    // Record this action to display it to the user.
                    $results['changedProfessor'][] = $dbCourse;
                }

                foreach ($course['listings'] as $listing) {
                    $dbListing = $dbListings[$i++];
                    if (!$dbListing){
                        // There was no corresponding dbListing for this input listing.
                        // Create the listing new.
                        $dbCourse->listings()->save($listing);

                        // Assign this listing into $dbListing so it gets recorded as being touched.
                        $dbListing = $listing;

                        // Record this action to display it to the user.
                        $results['newListing'][] = [$dbCourse, $dbListing];
                    }
                    else {
                        // There was already a corresponding listing in the database for this input listing.
                        // Update it if needed. Otherwise, record it as not needing changed.

                        // Save a copy of what the listing used to look like
                        // so that it can be presented to the user for feedback.
                        $oldListing = $dbListing->toArray();

                        // Fill the listing with the input data, and then check if anything
                        // actually changed by calling $dbListing->isDirty().
                        $dbListing->fill($listing->toArray());
                        if ($dbListing->isDirty()){
                            // Something did indeed change. Save it, and record this listing as updated for user feedback.
                            $dbListing->save();
                            $results['updatedListing'][] = [$oldListing, $dbListing];
                        }
                        else{
                            // Nothing changed about the listing. Record it as no change for user feedback.
                            $results['noChangeListing'][] = $dbListing;
                        }
                    }

                    // Record this listing as having been touched so that it doesn't get deleted.
                    $updatedDbListingIds[$dbListing->listing_id] = true;
                }

                // Next, check all the listings on the course for any that need to be deleted.
                $dbListingsCount = count($dbCourse->listings);
                foreach ($dbCourse->listings as $dbListing) {
                    if (!isset($updatedDbListingIds[$dbListing->listing_id])){
                        // This listing was not just created, updated, or observed in the input. It needs to be deleted.

                        if ($dbListingsCount == 1){
                            // The listing to be deleted is the last listing on the course.
                            // We need to delete the course as well.

                            static::deleteCourseForImport($results, $dbCourse);
                        }
                        else{
                            // There are other listings on the course besides this one.
                            // Deletion of this specific listing is safe.
                            $dbListing->delete();

                            $results['deletedListing'][] = $dbListing;
                        }

                        $dbListingsCount--;
                    }
                }

            }
        }

        // Look at all the courses on the term, and delete any courses
        // that we didn't just import.
        // Not the use of the visible() scope on the courses here.
        // This is important because we only import courses that are/will be visible to the user,
        // so we should also only delete courses that they can already see.
        foreach ($dbTerm->courses()->visible()->get() as $dbCourse) {
            if (!isset($updatedDbCourseIds[$dbCourse->course_id])){
                // this course was not created, updated, or observed in the input. It needs to be deleted.

                static::deleteCourseForImport($results, $dbCourse);
            }
        }

        return $results;
    }


    /** Parses a spreadsheet and returns an array of Course objects,
     * each with the appropriate Listings stored on it as $course['listings']
     *
     * @param PHPExcel $spreadsheet
     * @param $term_id
     * @return array
     * @throws \PHPExcel_Exception
     */
    public static function parseSpreadsheet(PHPExcel $spreadsheet, $term_id){

        // Measure the spreadsheet, and then find the locations of the columns that we care about.
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow(); // e.g. 10
        $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5



        // Scan through the header row and find where the RELEVANT_COLUMNS are.
        $columnIndexesByLabel = [];
        for ($colIndex = 0; $colIndex <= $highestColumnIndex; $colIndex++){
            $columnLabel = $worksheet->getCellByColumnAndRow($colIndex, 1)->getValue();

            if (isset(static::$RELEVANT_COLUMNS[$columnLabel]))
                $columnIndexesByLabel[$columnLabel] = $colIndex;
        }

        // Make sure that we found all the required columns,
        // and error out if we didn't find one of them.
        foreach (static::$RELEVANT_COLUMNS as $columnLabel => $_) {
            if (!isset($columnIndexesByLabel[$columnLabel])){
                throw new BadRequestHttpException("The provided spreadsheet was missing the $columnLabel column.");
            }
        }


        // Now, scan through all the data rows and pick out the RELEVANT_COLUMNS from each row.
        // We start $rowIndex at 2 because $rowIndex == 1 is the header row.
        $spreadsheetRows = [];
        for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++){
            $spreadsheetRow = [];

            foreach ($columnIndexesByLabel as $columnLabel => $colIndex) {
                $spreadsheetRow[$columnLabel] = $worksheet->getCellByColumnAndRow($colIndex, $rowIndex)->getValue();
            }

            $spreadsheetRows[] = $spreadsheetRow;
        }


        // A bucket is a collection of spreadsheet rows that all belong to the same course.
        // Go through all the rows and group the rows together in buckets as needed.
        // Buckets here are ArrayObjects instead of normal PHP arrays because
        // normal PHP arrays are value types, but we need to throw references around all over the place.
        $allBuckets = [];

        $primaryListingBucket = null;
        foreach ($spreadsheetRows as $spreadsheetRow) {
            $courseId = $spreadsheetRow['COURSE_ID'];
            $xlId = $spreadsheetRow['XLST_COURSE_ID'];

            if ($courseId && !$xlId){
                // This is just a regular old course with no XListings.
                // Pop it in its own bucket and store it.
                $allBuckets[] = new \ArrayObject([$spreadsheetRow]);
                $primaryListingBucket = null;
            }
            elseif (!$courseId && $xlId){
                if (!$spreadsheetRow['XLST_TITLE'] || $spreadsheetRow['XLST_TITLE'] == $primaryListingBucket[0]['TITLE']){
                    // This is a crosslisting with the same title as the original. Put it in the most recent bucket.
                    $primaryListingBucket[] = $spreadsheetRow;
                }
                else {
                    // This is a crosslisting with a different title from the original. Make it be its own course.
                    // We have to copy over faculty information from the 'parent' course since faculty information
                    // is never set on crosslistings.
                    $spreadsheetRow['FacultyEmail'] = $primaryListingBucket[0]['FacultyEmail'];
                    $spreadsheetRow['FacultyFirstName'] = $primaryListingBucket[0]['FacultyFirstName'];
                    $spreadsheetRow['FacultyLastName'] = $primaryListingBucket[0]['FacultyLastName'];
                    $allBuckets[] = new \ArrayObject([$spreadsheetRow]);
                }
            }
            elseif ($courseId == $xlId){
                // This is the primary listing of a course that has crosslistings.
                // Make a bucket for it and save the bucket so we can add more to it.
                $allBuckets[] = $primaryListingBucket = new \ArrayObject([$spreadsheetRow]);
            }
        }


        // Now that we've got all our buckets,
        // lets create Course, User, and Listing models
        // for everything that we found.
        $courses = [];
        $alreadyProcessed = [];
        foreach ($allBuckets as $bucket) {
            $bucket = $bucket->getArrayCopy();

            // Figure out the title of the course's listings.
            // Because of what we just did earlier
            // (throwing listings with a different XLST_TITLE than their parent into their own bucket),
            // we need to check both TITLE and XLST_TITLE to figure out what the title of the course is.
            if ($bucket[0]['TITLE'])
                $courseTitle = $bucket[0]['TITLE'];
            else
                $courseTitle = $bucket[0]['XLST_TITLE'];

            $courseTitle = strtr($courseTitle, static::$title_transforms_pre);
            $courseTitle = title_case($courseTitle);
            $courseTitle = strtr($courseTitle, static::$title_transforms_post);

            $userEmail = $bucket[0]['FacultyEmail'];
            $course = new Course();
            $course->term_id = $term_id;

            // Setup the user for the course.
            // Use the existing user if one exists, or create one if a user doesn't exist.
            if ($userEmail == static::FACULTY_EMAIL_TBD || $userEmail == static::FACULTY_EMAIL_CONFIDENTIAL){
                // Confidential users shouldn't be an issue anymore, but there was a time
                // where OIT refused to give us about 40% of professors' emails and names
                // because of a miscommunication between them and the Registrar.
                $course->user_id = null;
            }
            else{
                // According to John Kissack from OIT,
                // it is ALWAYS the case that a professor's net_id is the first half of their email address.
                // He said with some certainty that there are zero exceptions to that case.
                // I guess we will see if that is true.
                $net_id = preg_split('/@/', $userEmail)[0];
                $dbUser = User::where(['net_id' => $net_id])->first();

                if (!$dbUser){
                    $dbUser = new User;
                    $dbUser->net_id = $net_id;
                    $dbUser->email = $userEmail;
                    $dbUser->first_name = $bucket[0]['FacultyFirstName'];
                    $dbUser->last_name = $bucket[0]['FacultyLastName'];
                    $dbUser->save();
                }

                $course->user_id = $dbUser->user_id;
            }

            $listings = [];

            // Now, for each spreadsheet row in the bucket,
            // create a Listing on the course that is represented by the row.
            foreach ($bucket as $spreadsheetRow) {
                // If the XLST_COURSE_ID is set, use that as the course's id.
                // Otherwise, use the COURSE_ID.
                // THIS IS NOT THE course_id. Its a string that looks like "CSCD210-01".
                if (isset($spreadsheetRow['XLST_COURSE_ID']) && $spreadsheetRow['XLST_COURSE_ID']){
                    $id = $spreadsheetRow['XLST_COURSE_ID'];
                }
                else {
                    $id = $spreadsheetRow['COURSE_ID'];
                }

                // It is very possible for some crosslistings to get scattered around the spreadsheet.
                // This happens mainly when courses are crosslisted between different departments.
                // There will be a set of rows for the course (that will have 100% of the crosslistings)
                // in the AAST part of the spreadsheet, and then there will be a redundant set of these rows
                // in the HIST part of the spreadsheet for a course that is xlisted between AAST and HIST.
                // By checking this, we will ignore the whole second set of crosslistings, thus preventing duplication.
                if (isset($alreadyProcessed[$id])){
                    continue;
                }
                $alreadyProcessed[$id] = true;

                // Split the "CSCD210-01" string into its components.
                // Note that the number part of the string (210) can sometimes have a
                // letter at the end of it for some reason. This letter is significant,
                // which is why listing->number is a VARCHAR and not an INT column in the DB.
                $matches = [];
                preg_match('/([A-Z]{2,10})([0-9]{1,9}[A-Z]?)-([0-9]+)/', $id, $matches);

                if (count($matches) != 4){
                    // This should never, ever happen.
                    // If it does, it is a rare case, so just ignore the listing.
                } else {
                    $courseNumber = ltrim($matches[2], '0');

                    // If this course is something like internships, or thesis, etc.
                    // then ignore it.
                    if (in_array($courseNumber, static::$ignoredNumbers))
                        continue;

                    $listing = new Listing();
                    $listing->name = $courseTitle;
                    $listing->department = $matches[1];
                    $listing->number = $courseNumber;
                    $listing->section = intval($matches[3]);
                    $listings[] = $listing;
                }
            }

            // If the course had any listings at all,
            // store them on the course, and then add the course to our list
            // of courses that we have processed so far.
            if (count($listings)){
                $course['listings'] = $listings;
                $courses[] = $course;
            }
        }

        return $courses;
    }

    private static function deleteCourseForImport(&$results, Course $dbCourse){
        // Use magic method access to attach the listings to the model.
        // We need to do this so the user can see what this deletion actually happened on.
        $dbCourse->listings;

        // If the course has any listings that are ignored by our import,
        // don't delete the course. It was probably created manually by a user for a reason
        // (most likely because it is a one-off weird course that actually does need a book),
        // so keep it around.
        $ignoredListings = $dbCourse->listings->whereIn('number', static::$ignoredNumbers);
        if ($ignoredListings->count()){
            return;
        }

        if ($dbCourse->orders()->withTrashed()->count() == 0){
            // If the course doesn't have orders (including any deleted orders), just delete the course.

            // Make a copy of the course's info before we delete it.
            // If we don't, it will show up as 000-00 when it is displayed back to the user.
            $copyBeforeDelete = $dbCourse->toArray();

            $dbCourse->listings()->delete();
            $dbCourse->delete();

            $results['deletedCourseWithoutOrders'][] = $copyBeforeDelete;
        }else{
            // If the course has orders, delete all the orders and mark the course as nobook.
            // We need to maintain a record of the deleted orders that were placed.
            $dbCourse->orders()->delete();
            $dbCourse->no_book = true;
            $dbCourse->no_book_marked = Carbon::now();
            $dbCourse->save();

            $results['deletedCourseWithOrders'][] = $dbCourse;
        }
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Do nothing. We just have static methods here.
    }
}
