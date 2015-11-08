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
            ["Summer", 2015],
            ["Fall", 2015],
        ];

        foreach ($eaglenetFiles as $fileInfo) {
            $this->command->line("Parsing eaglenet $fileInfo[0] $fileInfo[1]...");
            Artisan::call('parseCourseCsv', [
                'termNumber' => array_search($fileInfo[0], Term::$termNumbers),
                'year' => $fileInfo[1],
                'file' => "database/seeds/dataFiles/eagleNet$fileInfo[0]$fileInfo[1].csv"
            ]);
        }

        $this->command->line("Parsing MBS data...");
        Artisan::call('parseMbs', [
            'file' => 'database/seeds/dataFiles/MBSFall2015.txt'
        ]);
    }
}
