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
            foreach (Term::$termNumbers as $term_number => $term_name)
            {
                $dateBase = str_pad((int)($term_number/40*11 + 1), 2, "0", STR_PAD_LEFT);

                Term::create([
                    'term_number' => $term_number,
                    'year' => $year,
                    'order_start_date' => "$year-$dateBase-01",
                    'order_due_date' => "$year-$dateBase-28",
                ]);
            }
        }

    }
}
