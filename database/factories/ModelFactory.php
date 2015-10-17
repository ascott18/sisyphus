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

$factory->define(App\Models\Book::class, function (Faker\Generator $faker) {

    $faker->addProvider(new Faker\Provider\BookTitle($faker));

    return [
        'title' => $faker->bookTitle,
        'isbn13' => $faker->isbn13,
        'asin' => str_random(10),
    ];
});


$factory->define(App\Models\Author::class, function (Faker\Generator $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
    ];
});


$factory->define(App\Models\Order::class, function (Faker\Generator $faker) {
    return [
        'quantityRequested' => $faker->numberBetween(5, 50),
        'orderedByName' => $faker->name,
        'book_id' => App\Models\Book::orderByRaw("RAND()")->first()->book_id,
    ];
});
