<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/


function dbRandom($class, $primaryKey)
{
    static $tables = [];

    if (!isset($tables[$class]))
    {
        $highestId = $class::max($primaryKey);
        $tables[$class] = $highestId;
    }

    return random_int(1, $tables[$class]);
}

$factory->define(App\Models\Book::class, function (Faker\Generator $faker) {

    $faker->addProvider(new Faker\Provider\BookTitle($faker));

    return [
        'title' => $faker->bookTitle,
        'isbn13' => $faker->isbn13,
        'asin' => str_random(10),
        'publisher' => $faker->company,
    ];
});


$factory->define(App\Models\Author::class, function (Faker\Generator $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
    ];
});


$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'net_id' => str_random(10),
        'email' => $faker->email,
    ];
});


$factory->define(App\Models\Order::class, function (Faker\Generator $faker) {
    return [
        'quantity_requested' => $faker->numberBetween(5, 50),
        'ordered_by_name' => $faker->name,
        'book_id' => dbRandom(App\Models\Book::class, 'book_id'),
        'course_id' => dbRandom(App\Models\Course::class, 'course_id'),
    ];
});



$factory->define(App\Models\Course::class, function (Faker\Generator $faker) {
    return [
        'department' => strtoupper($faker->randomLetter . $faker->randomLetter . $faker->randomLetter . $faker->randomLetter),
        'course_number' => random_int(98, 698),
        'course_section' => random_int(1, 4),
        'course_name' => ucwords($faker->words(random_int(3, 6), true)),
        'user_id' => dbRandom(App\Models\User::class, 'user_id'),
    ];
});
