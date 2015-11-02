<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTermsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->increments('term_id');

            $table->integer('term_number');
            $table->date('order_start_date');
            $table->date('order_due_date');
            $table->unsignedInteger('year');

            $table->timestamps();
        });

        Schema::table('courses', function(Blueprint $table) {
            $table->integer('term_id')->unsigned();
            $table->foreign('term_id')->references('term_id')->on('terms');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses', function(Blueprint $table) {
            $table->dropForeign('courses_term_id_foreign');
            $table->dropColumn('term_id');
        });

        Schema::drop('terms');
    }
}
