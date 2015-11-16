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
        Schema::create('user_departments', function (Blueprint $table) {
            $table->increments('user_department_id');

            $table->string('department', 10);
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('user_id')->on('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_departments');
    }
}