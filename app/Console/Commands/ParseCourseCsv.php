<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\SelfHandling;

use \App\Models\Book;
use \App\Models\Course;
use \App\Models\Order;
use \App\Models\Author;
use \App\Models\Term;
use \App\Models\User;

class ParseCourseCsv extends Command implements SelfHandling
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parseCourseCsv {termNumber} {year} {file}';


    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {

        $data = file_get_contents ( $this->argument('file') );

        $data = explode("\n", $data);

        $term = Term::where([
            'term_number' => $this->argument('termNumber'),
            'year' => $this->argument('year')
        ])->firstOrFail();

        $numParsed = 0;

        foreach ($data as $row) {
            $csv = str_getcsv ( $row );

            if (count($csv) > 5 && is_numeric($csv[1])){
                $numParsed++;

                $dept = $csv[2];
                $courseNumber = $csv[3];
                $section = $csv[4];
                $title = $csv[7];
                $prof = $csv[19];

                $prof = str_replace(" (P)", "", $prof);
                $prof = explode(",", $prof)[0];
                $prof = trim($prof);
                $profNames = explode(" ", $prof);

                $fake_net_id = camel_case($prof);

                // TODO: this isn't how we're going to seed users.
                // Or courses for that matter. This whole file exists only
                // so that we can get the MBS parsing done.
                $user = User::firstOrNew(['net_id' => $fake_net_id]);
                $user->first_name = $profNames[0];
                $user->last_name = $profNames[count($profNames) - 1];
                $user->save();

                // In particular, this is definitely only for testing.
                // In reality, only manager-type users need departments assigned.
                // $user->departments()->updateOrCreate(['department' => $dept]);


                $course = new Course;
                $course->department = $dept;
                $course->course_number = $courseNumber;
                $course->course_section = $section;
                $course->course_name = title_case($title);
                $course->user_id = $user->user_id;
                $course->term_id = $term->term_id;
                $course->save();
            }
        }

        echo "Parsed $numParsed courses from Eaglenet csv.\n";
    }
}


