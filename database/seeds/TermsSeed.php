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
        // Don't seed a ton unless we're on a server because seeding is pretty slow.
        for ($year = config("app.env") == 'production' ? 2012 : 2016; $year <= 2017; $year++)
        {
            Term::createTermsForYear($year);
        }

    }
}
