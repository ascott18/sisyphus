<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\SelfHandling;

use \App\Models\Book;
use \App\Models\Course;
use \App\Models\Order;
use \App\Models\Author;
use \App\Models\Term;

class ParseMBS extends Command implements SelfHandling
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parseMbs {file}';


    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->argument('file');

        $data = file_get_contents ( $this->argument('file') );

        $pageHeaderReg = <<<'EOL'
|\d\d/\d\d/\d\d  Store:   .*?(Dept.*?Actual).*?(Author.*?Used).*? --     ---    ----|s
EOL;

        $docFooterReg = <<<'EOL'
|Total Number Of Courses.*|s
EOL;

        // Save the data in the footer so we can compare it to what we actually parse.
        $footerContents = [];
        $headerContents = [];

        // Strip out the page header from each page and save it.
        preg_match($pageHeaderReg, $data, $headerContents);
        $data = preg_replace($pageHeaderReg, "", $data);

        // Parse the term from the report.
        // TODO: what does this string look like for summer?
        $term = [];
        preg_match("/([FWS])(\\d+)/", $headerContents[0], $term);

        if ($term[1] == "F") $termName = "Fall";
        if ($term[1] == "W") $termName = "Winter";
        if ($term[1] == "S") $termName = "Spring";
        $termNumber = array_search($termName, Term::$termNumbers);
        $dbTerm = Term::where(['term_number' => $termNumber, 'year' => "20$term[2]"])->firstOrFail();

        // From the page header, calculate the column widths.
        // This only works for the left-aligned columns.
        $headerColumnWidths = [];
        for($i=1; $i<=2; $i++) {
            $headerColumns = [];
            preg_match_all("/[A-Za-z\\/]*\\s*/", $headerContents[$i], $headerColumns);
            foreach ($headerColumns[0] as $col) {
                $headerColumnWidths[trim($col)] = strlen($col);
            }
        }


        // Create a regex to match each book based on our column widths.
        $bookReg = "";
        $bookRegGroups = [];
        $foundAuthor = false;
        $groupIndex=0;
        foreach ($headerColumnWidths as $colName => $colWidth) {
            if ($colName == "Author") $foundAuthor = true;
            if ($colName == "New") break;
            if (!$foundAuthor) continue;

            if ($colName == "RQ") $colWidth = 3;

            $colWidth = $colWidth - 1;
            $bookReg = $bookReg . "(.{{$colWidth}})\\s";
            $bookRegGroups[$colName] = $groupIndex++ + 1;
        }
        $bookReg = "/^\\s*$bookReg/";


        // Do the same for the course data line.
        $courseDataReg = "";
        $courseRegGroups = [];
        $groupIndex=0;
        foreach ($headerColumnWidths as $colName => $colWidth) {
            $colWidth = $colWidth - 1;

            // Ugh. Why is this data so dumb. Sometimes the professor can overflow its column.
            if ($colName == "Professor") $colWidth = $colWidth + 5;

            $courseDataReg = $courseDataReg . "(.{{$colWidth}})\\s";
            $courseRegGroups[$colName] = $groupIndex++ + 1;

            if ($colName == "Professor") break;
        }
        $courseDataReg = "/^\\s*$courseDataReg/";


        // Strip out the document footer and save it.
        preg_match($docFooterReg, $data, $footerContents);
        $data = preg_replace($docFooterReg, "", $data);


        // Pull some aggregate data from the footer for verification later.
        $footerMatches = [];
        preg_match("/(\\d+)[^\\d]*?(\\d+)/", $footerContents[0], $footerMatches);
        $coursesExpected = (int)$footerMatches[1];
        $booksExpected = (int)$footerMatches[2];


        // Add some dashes to the end of the data so the last course doesn't get overlooked.
        $data = $data . "-------";


        // Strip out all empty lines.
        for($i = 0; $i < 10; $i++)
        {
            $data = preg_replace("/\r?\n(\r?\n)/", "\\1", $data);
        }


        // Get all courses and their books.
        $courseMatches = [];
        $allCoursesReg = <<<EOL
/
-{50,}
.*?
-{100,}
.*?
-{5}
/xs
EOL;
        preg_match_all($allCoursesReg, $data, $courseMatches, PREG_SET_ORDER);


        // Report how many courses were found.
        $coursesFound = count($courseMatches);
        echo ("Found $coursesFound courses.\n");

        // Verify that we found all of them.
        if (!assert($coursesExpected == $coursesFound, "Didn't get all courses.")) exit;


        // Holds the more-or-less final version of the parsed data.
        $parsedData = [];

        // Count how many books we find so we can verify that we parse them all.
        $booksFound = 0;

        // Go through all of our matched courses,
        // and parse out their information and their books.
        foreach ($courseMatches as $match) {
            $course = $match[0];

            $lineIndex = 1;
            $lines = explode("\n", $course);
            $courseData = [];
            preg_match($courseDataReg, $lines[$lineIndex++], $courseData);

            // Verify that our column width regex actually matched, and fail if it doesn't
            if (count($courseData) < 2){
                echo ("parse failed: $courseData[0]\n");
                exit;
            }

            // Check if this course is a no book course.
            $courseData['noText'] = false;
            if (preg_match("/No Text/", $lines[$lineIndex++])){
                $courseData['noText'] = true;
                $lineIndex++;
            }

            // Parse out all of the course's books.
            $books = [];
            $book = [];
            while (preg_match($bookReg, $lines[$lineIndex++], $book)){
                $booksFound++;

                $books[] = $book;

                for($i = 1; $i < count($book); $i++){
                    // echo array_search($i, $bookRegGroups) . ": " . $book[$i] . "\n";
                }
            }

            // Now that we have all the data from the course,
            // copy it into our final parsed array.
            $courseData['books'] = $books;
            $parsedData[] = $courseData;
        }

        // Make sure we found every book that the report said was in there.
        if (!assert($booksExpected == $booksFound, "Didn't get all books.")) exit;


        $bookNumProcessing = 0;
        $coursesNotFound = 0;
        foreach ($parsedData as $course ) {

            // Parse out the "CSCD 525" column.
            $deptCourses = [];
            $departmentCourse = $course[$courseRegGroups['Dept/Course']];
            preg_match("/([A-Z]+) (.*)/", $departmentCourse, $deptCourses);

            if (count($deptCourses) < 3){
                echo "Could not match dept/cn from $departmentCourse";
                exit;
            }

            // Course numbers can be in ugly formats like "210 & 211",
            // so expand those out.
            $courseNumbers = $this->decipherNumbers($deptCourses[2]);

            foreach ($courseNumbers as $courseNumber) {
                $courseNumber = trim($courseNumber);

                $sectionData = $course[$courseRegGroups['Section']];

                // If the section number is listed as "ALL" (quite common in the data),
                // pull all of the section numbers that we have in the database for this course.
                $sectionNumbers = null;
                if (trim($sectionData) == "ALL") {
                    $sectionNumbers = Course::where([
                        'term_id' => $dbTerm->term_id,
                        'department' => $deptCourses[1],
                        'course_number' => $courseNumber,
                    ])->lists('course_section');
                }
                else {
                    // If the section number is not "ALL",
                    // expand out the numbers again like we did for course numbers.
                    $sectionNumbers = $this->decipherNumbers($sectionData);
                }


                foreach ($sectionNumbers as $sectionNumber) {
                    $sectionNumber = trim($sectionNumber);

                    $dbCourse = Course::where([
                        'term_id' => $dbTerm->term_id,
                        'department' => $deptCourses[1],
                        'course_number' => $courseNumber,
                        'course_section' => $sectionNumber,
                    ])->first();

                    if (!$dbCourse) {
                        echo ("$deptCourses[1] $courseNumber-$sectionNumber was not found.\n");
                        $coursesNotFound++;
                        continue;
                    }

                    if ($course['noText']) {
                        $dbCourse->no_book = true;
                        $dbCourse->save();
                    }

                    foreach ($course['books'] as $book ) {
                        $bookNumProcessing++;

                        $isbn = str_replace("-", "", $book[$bookRegGroups['ISBN']]);

                        $dbBook = Book::firstOrNew(['isbn13' => $isbn]);
                        $dbBook->title = title_case($book[$bookRegGroups['Title']]);
                        $dbBook->isbn13 = $isbn;
                        $dbBook->publisher = title_case($book[$bookRegGroups['Publisher']]);
                        // TODO: save edition (there is no DB field yet)
                        $dbBook->save();

                        Author::firstOrCreate([
                            'last_name' => title_case($book[$bookRegGroups['Author']]),
                            'book_id' => $dbBook->book_id,
                        ]);

                        $dbOrder = new Order;
                        $dbOrder->book_id = $dbBook->book_id;
                        $dbOrder->course_id = $dbCourse->course_id;
                        $dbOrder->save();
                    }
                }
            }
        }

        echo ("MBS parse complete.\n");
        echo ("$coursesNotFound courses were not found in the database.\n");
        echo ("$bookNumProcessing books were saved.\n");

    }

    public function decipherNumbers($in)
    {
        /* Parses the following forms into every number that they represent:
            123 - 125
            123
            123 & 456
            123, 456 & 789
            01 - 03, 05 & 06
            02, 04, 27 & 75
            01-03,05, 27 & 75
            100/400
        */

        $chunks = explode(",", $in);

        $out = [];

        foreach ($chunks as $chunk) {
            $noSpace = str_replace(" ", "", $chunk);

            if (preg_match("/\\d+-\\d+/", $noSpace)){
                $extremes = explode("-", $noSpace);

                $out = array_merge($out, range($extremes[0], $extremes[1]));
            }
            elseif (preg_match("/\\d+([A-Z]?)&\\d+\\1/", $noSpace)){
                $out = array_merge($out, explode("&", $noSpace));
            }
            elseif (preg_match("/\\d+([A-Z]?)\\/\\d+\\1/", $noSpace)){
                $out = array_merge($out, explode("/", $noSpace));
            }
            elseif (preg_match("/\\d+([A-Z]?)/", $chunk)){
                $out[] = $chunk;
            }
        }

        return $out;
    }
}



/*
 * BEWARE TO ALL YE WHO EXPECT THE DATA OF THESE FILES TO BE IN REASONABLE FORMATS:
 * YOU WILL WRITE THIS, AND THEN REALIZE IT IS USELESS BECAUSE OF HOW MANY EDGE CASES THERE ARE.
 *
 *
// Matches the first row of a course, like "SOCI 101   01    BARTLETT"
        $courseTitleReg = <<<'EOL'
/
([A-Z]{3,4})\s+                 # 1 Match the course dept

                                # 2 Course number matching
(
(?:
    (?:

        (?:[0-9]+\s-\s[0-9]+) 	# Match ranges of course numbers (e.g. 232 - 234)
	|
		(?:[0-9]+S?             # or match a single number (followed by an optional "S", denoting a semester class.)
		                        # Optionally followed by an ampersand-or-slash-delimited list of more numbers (e.g. 355 & 455)
			(?:(?:\s&\s|\/)[0-9]+)*
	 	)
	)
	(?:,\s)? 					# Allow commas in between any of the previous.
)+								# Match any number of the previous. This is what lets us find weird ones.
)
\s+


                                # 3 Section number matching
(	ALL     					# Match literal "ALL" sections.
|
(?:
    (?:

        (?:[0-9]+\s-\s[0-9]+) 	# Match ranges of sections (e.g. 01 - 03)
	|
		(?:[0-9]+				# or a single section
		    (?:
			   (?:\sONLY)       # Optionally followed by the word "ONLY"
			|
			   (?:\s&\s[0-9]+)* # or followed by an ampersand-delimited list of more sections (e.g. 01 & 02 & 03)
			)
	 	)
	)
	(?:,\s)? 					# Allow commas in between any of the previous.
)+								# Match any number of the previous. This is what lets us find weird ones like "01 - 03, 05 & 06"
)								# Capture the whole thing.

\s{2,}(.*?)\s{2,}               # 4 Match the professor
/xs
EOL;


 */