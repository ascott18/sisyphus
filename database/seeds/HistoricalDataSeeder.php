<?php

use App\Models\Term;
use Illuminate\Database\Seeder;
use App\Console\Commands\ParseCourseCsv;
use App\Console\Commands\ParseMBS;

class HistoricalDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $eaglenetFiles = [
            ["Winter", 2012],
            ["Spring", 2012],
           //  ["Summer", 2015],
            ["Fall", 2013],
            ["Fall", 2014],
            ["Fall", 2015],
            ["Winter", 2013],
            ["Winter", 2014],
            ["Winter", 2015],
            ["Winter", 2016],
            ["Spring", 2013],
            ["Spring", 2014],
            ["Spring", 2015],
            ["Spring", 2016],
            ["Spring Semester", 2016],
        ];

        $spreadsheetFiles = [
            ["F14Bookstore_Order_Course_List.xls", 2014, "Fall"],
            ["W15Bookstore_Order_Course_List.xls", 2015, "Winter"],
            ["S15Bookstore_Order_Course_List.xls", 2015, "Spring"],
            ["SU15Bookstore_Order_Course_List.xls", 2015, "Summer"],
            ["F15Bookstore_Order_Course_List.xls", 2015, "Fall"],
            ["W16Bookstore_Order_Course_List.xls", 2016, "Winter"],
            ["S16Bookstore_Order_Course_List.xls", 2016, "Spring"],
            ["20160304SummerCourseOfferings.xlsx", 2016, "Summer"],
        ];

        // Don't do this anymore - it isn't good data (lacks proper instructor information)
        // It was never intended to be a permanant thing - just useful while we were developing
        // before we got the real data from OIT.
//        foreach ($eaglenetFiles as $fileInfo) {
//            $this->command->line("Parsing eaglenet $fileInfo[0] $fileInfo[1]...");
//            $fileName = str_replace(' ', '', $fileInfo[0]) . $fileInfo[1];
//            Artisan::call('parseCourseCsv', [
//                'termNumber' => array_search($fileInfo[0], Term::$termNumbers),
//                'year' => $fileInfo[1],
//                'file' => "database/seeds/dataFiles/eagleNet$fileName.csv"
//            ]);
//        }

        foreach ($spreadsheetFiles as $fileInfo) {
            $this->command->line("Parsing spreadsheet $fileInfo[2] $fileInfo[1]...");
            Artisan::call('parseCourseSpreadsheet', [
                'termNumber' => array_search($fileInfo[2], Term::$termNumbers),
                'year' => $fileInfo[1],
                'file' => "database/seeds/dataFiles/$fileInfo[0]"
            ]);
        }

        $this->command->line("Parsing MBS data...");
        Artisan::call('parseMbs', [
            'file' => 'database/seeds/dataFiles/QPRINT.txt'
        ]);
    }
}
