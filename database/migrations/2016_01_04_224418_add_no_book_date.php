<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNoBookDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courses', function(Blueprint $table) {
            $table->timestamp('no_book_marked');
        });
        Schema::table('orders', function(Blueprint $table) {
            $table->softDeletes();

            $table->integer('deleted_by')->nullable()->unsigned();
            $table->foreign('deleted_by')->references('user_id')->on('users');
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
            $table->dropColumn('no_book_marked');
        });
        Schema::table('orders', function(Blueprint $table) {
            $table->dropForeign('orders_deleted_by_foreign');
            $table->dropColumn('deleted_by');

            $table->dropSoftDeletes();
        });
    }
}
