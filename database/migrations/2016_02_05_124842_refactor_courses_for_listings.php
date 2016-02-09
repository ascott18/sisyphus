<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RefactorCoursesForListings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->increments('listing_id');

            $table->integer('course_id')->unsigned();
            $table->foreign('course_id')->references('course_id')->on('courses');

            $table->string('name', 255);
            $table->integer('number')->unsigned();
            $table->string('department', 10);
            $table->integer('section')->unsigned();

            $table->index('department', 'idx_department');
            $table->index('number', 'idx_number');

            $table->timestamps();
        });

        Schema::table('courses', function(Blueprint $table) {
            $table->dropIndex('idx_department');
            $table->dropIndex('idx_course_number');
            $table->dropColumn('course_name');
            $table->dropColumn('course_number');
            $table->dropColumn('course_section');
            $table->dropColumn('department');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('department', 10);
            $table->string('course_name', 255);
            $table->integer('course_number')->unsigned();
            $table->integer('course_section')->unsigned();
            $table->index('department', 'idx_department');
            $table->index('course_number', 'idx_course_number');
        });

        Schema::drop('listings');
    }
}
