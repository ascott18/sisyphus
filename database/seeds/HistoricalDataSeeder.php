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
        \App\Models\User::create([
            'first_name' => "Initial",
            'last_name' => "Data",
        ]);

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

        foreach ($eaglenetFiles as $fileInfo) {
            $this->command->line("Parsing eaglenet $fileInfo[0] $fileInfo[1]...");
            $fileName = str_replace(' ', '', $fileInfo[0]) . $fileInfo[1];
            Artisan::call('parseCourseCsv', [
                'termNumber' => array_search($fileInfo[0], Term::$termNumbers),
                'year' => $fileInfo[1],
                'file' => "database/seeds/dataFiles/eagleNet$fileName.csv"
            ]);
        }

        $this->command->line("Parsing MBS data...");
        Artisan::call('parseMbs', [
            'file' => 'database/seeds/dataFiles/QPRINT.txt'
        ]);
    }
}
