<?php

use Illuminate\Database\Seeder;

class OrdersSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Models\Order::class, 50)->create()->each(function($u) {

            $book = App\Models\Book::orderByRaw("RAND()")->first();
            $u->book_id = $book->book_id;

        });
    }
}
