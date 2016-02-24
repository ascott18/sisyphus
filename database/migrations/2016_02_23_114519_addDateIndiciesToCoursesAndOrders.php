<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDateIndiciesToCoursesAndOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // These are so that our activities query on the dashboard doesn't get slower with time.

        Schema::table('courses', function(Blueprint $table) {
            $table->index('no_book_marked', 'idx_no_book_marked');
        });

        Schema::table('orders', function(Blueprint $table) {
            $table->index('created_at', 'idx_created_at');
            $table->index('deleted_at', 'idx_deleted_at');
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
            $table->dropIndex('idx_no_book_marked');
        });

        Schema::table('orders', function(Blueprint $table) {
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_deleted_at');
        });
    }
}
