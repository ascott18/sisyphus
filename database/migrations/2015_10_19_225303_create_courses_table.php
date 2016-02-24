<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->increments('course_id');

            $table->string('department', 10);

            $table->string('course_name', 255);
            $table->integer('course_number')->unsigned();
            $table->integer('course_section')->unsigned();



            // See https://github.com/laravel/framework/issues/11518 for why this is nullable.
            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('courses');
    }
}
