<?php

use Illuminate\Database\Seeder;
use App\Models\Term;

class TermsSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($year = 2015; $year <= 2017; $year++)
        {
            Term::createTermsForYear($year);
        }

    }
}
