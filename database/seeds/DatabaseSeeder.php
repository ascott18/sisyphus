<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->call(TermsSeed::class);

        $this->call(UserTableSeeder::class);
        $this->call(BooksAndAuthorsSeeder::class);

        $this->call(OrdersSeed::class);


        Model::reguard();
    }
}
