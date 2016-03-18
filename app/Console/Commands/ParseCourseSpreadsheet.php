<?php

namespace App\Console\Commands;

use App\Models\Term;
use App\Models\User;
use App\Providers\ImportServiceProvider;
use Auth;
use Illuminate\Console\Command;
use PHPExcel_IOFactory;

class ParseCourseSpreadsheet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parseCourseSpreadsheet {termNumber} {year} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /** @var Term $term */
        $term = Term::where([
            'term_number' => $this->argument('termNumber'),
            'year' => $this->argument('year')
        ])->first();

        if ($term == null){
            echo "Skipping " . $this->argument('file') . " because the term was not found in the database.\n";
            return;
        }

        $term_id = $term->term_id;

        $fileName = $this->argument('file');

        // Load the spreadsheet into memory.
        $reader = PHPExcel_IOFactory::createReaderForFile($fileName);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($fileName);



        // IMPORTANT

        // We will parse these spreadsheets as Connie so that we only get CSCD/CPLA data in here,
        // since we are only doing a compsci rollout for now.

        // If you want to parse everything, you will need to make a role that can see all courses,
        // and then you will need to seed a user that has that role, and then you will need to place their net_id here.
        $net_id = 'cbean';
        echo "Processing spreadsheet as user $net_id \n";
        Auth::login(User::where(['net_id' => $net_id])->firstOrfail());





        $courses = ImportServiceProvider::parseSpreadsheet($spreadsheet, $term_id);

        // Attempt to prevent out of memory errors.
        $spreadsheet->garbageCollect();
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        unset($reader);

        $actions = ImportServiceProvider::importCourses($courses, $term_id);

        foreach ($actions as $actionName => $actionList) {
            echo "Result: $actionName - " . count($actionList) . " actions.\n";
        }

    }
}
