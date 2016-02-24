<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function(Blueprint $table) {
            $table->string('ewu_id', 10);
        });

        Schema::create('user_departments', function (Blueprint $table) {
            $table->increments('user_department_id');

            $table->string('department', 10);
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('user_id')->on('users');


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
        Schema::table('users', function(Blueprint $table) {
            $table->dropColumn('ewu_id');
        });

        Schema::drop('user_departments');
    }
}
