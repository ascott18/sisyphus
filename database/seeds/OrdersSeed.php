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
        factory(App\Models\Course::class, 50)->create()->each(function($u) {

        });

        factory(App\Models\Order::class, 50)->create()->each(function($u) {

        });
    }
}
