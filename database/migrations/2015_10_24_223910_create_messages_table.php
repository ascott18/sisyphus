<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->increments('message_id');

            $table->string('subject', 255);
            $table->mediumText('body');

            $table->integer('owner_user_id')->unsigned();
            $table->foreign('owner_user_id')->references('user_id')->on('users');

            $table->timestamps();
        });

        Schema::table('users', function(Blueprint $table) {
            $table->dropIndex('users_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('messages');
    }
}
