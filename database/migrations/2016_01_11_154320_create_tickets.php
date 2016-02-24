<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTickets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function(Blueprint $table) {
            $table->increments('ticket_id');


            // See https://github.com/laravel/framework/issues/11518 for why this is nullable.
            $table->nullableTimestamps();

            $table->string('title');
            $table->string('url');
            $table->string('department');
            $table->mediumText('body');
            $table->integer('status')->unsigned();

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('user_id')->on('users');
        });

        Schema::create('ticket_comments', function(Blueprint $table) {
            $table->increments('ticket_comment_id');
            $table->mediumText('body');


            // See https://github.com/laravel/framework/issues/11518 for why this is nullable.
            $table->nullableTimestamps();

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('user_id')->on('users');

            $table->integer('ticket_id')->unsigned();
            $table->foreign('ticket_id')->references('ticket_id')->on('tickets');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ticket_comments');
        Schema::drop('tickets');
    }
}
