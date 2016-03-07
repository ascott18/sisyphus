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

        $this->call(RolesSeed::class);

        $this->call(HistoricalDataSeeder::class);


        Model::reguard();
    }
}
