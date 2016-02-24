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


/**
 * @param $class Illuminate\Database\Eloquent\Model::class
 * @param $primaryKey string The name of the primary key of the given model.
 * @return int A random primary key from the table of the provided model.
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
        'name' => $faker->lastName,
    ];
});


$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    $firstName = $faker->firstName;
    $lastName = $faker->lastName;

    $netId = str_slug($lastName, '') . strtolower($firstName[0]) . random_int(1, 99);

    return [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'net_id' => $netId,
        'email' => $netId . "@fake.email",
    ];
});


$factory->define(App\Models\Order::class, function (Faker\Generator $faker) {
    return [
        'book_id' => dbRandom(App\Models\Book::class, 'book_id'),
        'course_id' => dbRandom(App\Models\Course::class, 'course_id'),
    ];
});


$factory->define(App\Models\Course::class, function (Faker\Generator $faker) {
    $faker->addProvider(new Faker\Provider\Department($faker));

    return [
        'user_id' => dbRandom(App\Models\User::class, 'user_id'),
        'term_id' => dbRandom(App\Models\Term::class, 'term_id'),
    ];
});

$factory->define(App\Models\UserDepartment::class, function (Faker\Generator $faker) {
    $faker->addProvider(new Faker\Provider\Department($faker));

    return [
        'department' => $faker->departmentCode,
        'user_id' => dbRandom(App\Models\User::class, 'user_id'),
    ];
});

