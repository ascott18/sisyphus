<?php

use Illuminate\Database\Seeder;

class BooksAndAuthorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Models\Book::class, 50*config('database.seed_scale'))->create()->each(function($book) {

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
    }
}
