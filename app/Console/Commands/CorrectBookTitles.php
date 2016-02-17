<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Book;

class CorrectBookTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'correctBookTitles {cachedTitles}';

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
        $data = file_get_contents($this->argument('cachedTitles'));

        $data = explode("\n", $data);
        $cachedBooks = array();

        if ($data) {
            foreach ($data as $row) {

                $book = str_getcsv ( $row );

                array_add($cachedBooks, $book[0], $book[1]);

                $this->info("ISBN " . $book[0]);
                $this->info("Title " . $book[1]);

            }

            $db_books = Book::take(2)->get();

            foreach ($db_books as $book) {
                $this->info($book->isbn13);
                $this->info($book->title);

                $googleResponse = file_get_contents("https://www.googleapis.com/books/v1/volumes?q=isbn:".$book->isbn13);




                $this->info($googleResponse);

            }

        }
    }
}
