<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('authors', function(Blueprint $table) {
            $table->string('name', 100);
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
        });

        Schema::table('orders', function(Blueprint $table) {
            $table->integer('placed_by')->unsigned();
            $table->foreign('placed_by')->references('user_id')->on('users');

            $table->dropColumn('ordered_by_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('authors', function(Blueprint $table) {
            $table->dropColumn('name');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
        });

        Schema::table('orders', function(Blueprint $table) {
            $table->string('ordered_by_name');
        });
    }
}
