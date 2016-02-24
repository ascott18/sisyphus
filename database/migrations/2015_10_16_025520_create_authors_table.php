<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->increments('author_id');
            $table->string('first_name', 100);
            $table->string('last_name', 100);

            $table->integer('book_id')->unsigned();


            $table->foreign('book_id')->references('book_id')->on('books')->onDelete('cascade');


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
        Schema::drop('authors');
    }
}
