<?php

use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Models\User::class, 1)->create([
            'first_name' => 'Arthur',
            'last_name' => 'Aardvark', // Need him to be first in our alphabetically sorted list.
            'net_id' => 'aAardvark92',
            'email' => "ascott18@gmail.com"
        ]);

        factory(App\Models\User::class, 20*config('database.seed_scale'))->create();

        factory(App\Models\UserDepartment::class, 50*config('database.seed_scale'))->create();
    }
}
