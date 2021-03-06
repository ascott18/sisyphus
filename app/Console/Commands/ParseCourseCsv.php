<?php

namespace App\Console\Commands;

use App\Models\Listing;
use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\SelfHandling;

use \App\Models\Book;
use \App\Models\Course;
use \App\Models\Order;
use \App\Models\Author;
use \App\Models\Term;
use \App\Models\User;

class ParseCourseCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parseCourseCsv {termNumber} {year} {file}';

    protected $title_transforms_post = [
        "Ii" => "II",
        "Iii" => "III",
        "Iv" => "IV",
        ".net" => ".NET",
        "Adst" => "ADST",
        "U.s." => "U.S.",
        "Directed Study " => "Directed Study: ",
    ];

    protected $title_transforms_pre = [
        "DS/" => "Directed Study ",
        "DIR ST" => "Directed Study ",
    ];

    protected $user_transforms = [
        "chrisFrankPeters" => "cpeters",
        "thomasBraceCapaul" => "tcapaul",
    ];

    // TODO: when this blacklisting makes it into the real data harvesting, make this list configurable by administrators.
    protected $ignoredNumbers = [
        199, // Directed & Independent study
        299, // Directed & Independent study
        399, // Directed & Independent study
        499, // Directed & Independent study
        599, // Directed & Independent study

        295, // Internship
        395, // Internship
        495, // Internship
        595, // Internship
        694, // Internship/practicum for some departments
        695, // Internship

        600, // Thesis
        601, // Research
    ];

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
        ])->first();

        if ($term == null){
            echo "Skipping " . $this->argument('file') . " because the term was not found in the database.\n";
            return;
        }

        $numParsed = 0;

        foreach ($data as $row) {
            $csv = str_getcsv ( $row );

            // Not a data row
            if (count($csv) <= 5 || !is_numeric($csv[1]))
                continue;

            $courseNumber = $csv[3];
            $dept = trim($csv[2]);
            $section = $csv[4];
            $prof = $csv[19];
            $title = $csv[7];

            if (in_array($courseNumber, $this->ignoredNumbers))
                continue;

            if (!is_numeric($csv[18])) {
                // Check the XL Remaining - some courses are messed up and are missing a column)
                // in these cases, the professor will be in column 18 instead of 19.
                $prof = $csv[18];
            }
            $numParsed++;

            $title = strtr($title, $this->title_transforms_pre);
            $title = title_case($title);
            $title = strtr($title, $this->title_transforms_post);

            $prof = str_replace(" (P)", "", $prof);
            $prof = explode(",", $prof)[0];
            $prof = trim($prof);
            $profNames = explode(" ", $prof);

            $fake_net_id = camel_case($prof);
            $fake_net_id = strtr($fake_net_id, $this->user_transforms);

            // TODO: this isn't how we're going to seed users.
            // Or courses for that matter. This whole file exists only
            // so that we can get the MBS parsing done.
            if ($profNames[0] == 'TBA')
                $user = null;
            else{
                $user = User::firstOrNew(['net_id' => $fake_net_id]);
                $user->first_name = $profNames[0];
                $user->last_name = $profNames[count($profNames) - 1];
                $user->save();
            }

            // In particular, this is definitely only for testing.
            // In reality, only manager-type users need departments assigned.
            // $user->departments()->updateOrCreate(['department' => $dept]);


            $course = new Course;
            $course->user_id = $user ? $user->user_id : null;
            $course->term_id = $term->term_id;

            $course->created_at = $term->order_start_date->copy()->addDays(-1);

            $course->save();

            $listing = new Listing;
            $listing->department = $dept;
            $listing->number = $courseNumber;
            $listing->section = $section;
            $listing->name = $title;


            $course->listings()->save($listing);
        }

        echo "Parsed $numParsed courses from Eaglenet csv.\n";
    }
}


