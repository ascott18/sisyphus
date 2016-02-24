<?php

use App\Models\Order;
use App\Models\Term;
use Illuminate\Database\Seeder;

class StressTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {


        $eaglenetFiles = [
            ["Winter", 2015],
            ["Spring", 2015],
            ["Fall", 2015],
        ];

        for ($year = 1980; $year <= 2011; $year++)
        {
            Term::createTermsForYear($year);

            $fileYear = rand(2013, 2015);

            foreach ($eaglenetFiles as $fileInfo) {
                $this->command->line("Parsing eaglenet $fileInfo[0] $fileYear as $year...");
                $fileName = str_replace(' ', '', $fileInfo[0]) . $fileYear;

                Artisan::call('parseCourseCsv', [
                    'termNumber' => $term_number = array_search($fileInfo[0], Term::$termNumbers),
                    'year' => $year,
                    'file' => "database/seeds/dataFiles/eagleNet$fileName.csv"
                ]);

                $minBookId = \App\Models\Book::max('book_id');
                $numBooksToMake = 100;

                factory(App\Models\Book::class, $numBooksToMake)->create()->each(function($book) {

                    $book->authors()->save(factory(App\Models\Author::class)->make());

                    // 50% chance of generating with two authors.
                    if (rand(0, 1)) {
                        $book->authors()->save(factory(App\Models\Author::class)->make());

                        // 25% chance of a third author
                        if (rand(0, 1)) {
                            $book->authors()->save(factory(App\Models\Author::class)->make());
                        }
                    }
                });

                $term = Term::where(['year' => $year, 'term_number' => $term_number])->firstOrFail();
                $termPeriodDayLength = $term->order_due_date->diffInDays($term->order_start_date);

                foreach ($term->courses as $course) {
                    // course has between zero and 2 orders.
                    $numOrders = rand(0, 2);
                    for ($i = 0; $i < $numOrders; $i++){
                        $order = new Order;

                        $order->created_at = $term->order_start_date->copy()->addDays(rand(0, $termPeriodDayLength));

                        // 50% chance to use one of the books we just made. 50% chance to use an existing book.
                        if (rand(0, 1))
                            $order->book_id = $minBookId + rand(1, $numBooksToMake);
                        else
                            $order->book_id = rand(1, $minBookId);

                        $course->orders()->save($order);
                    }

                    // If there were no orders, 50% chance to mark as no book.
                    if ($numOrders == 0 and rand(0, 1))
                    {
                        $course->no_book = true;
                        $course->no_book_marked = $term->order_start_date->copy()->addDays(rand(0, $termPeriodDayLength));
                    }
                }
            }
        }
    }
}
