<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeListingNumberVarchar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // This was done because there are courses like "RIDE 530N-40"
        Schema::table('listings', function(Blueprint $table) {
            $table->string('number', 10)->change();

            $table->index('section', 'idx_section');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('listings', function(Blueprint $table) {
            $table->integer('number')->unsigned()->change();

            $table->dropIndex('idx_section');
        });
    }
}
