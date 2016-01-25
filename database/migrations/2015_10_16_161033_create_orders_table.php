<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('order_id');

            $table->integer('book_id')->unsigned();
            $table->foreign('book_id')->references('book_id')->on('books');

            $table->integer('status')->unsigned(); // this has since been removed.
            $table->string('ordered_by_name'); // this has since been removed.
            $table->integer('quantity_requested')->unsigned(); // this has since been removed.
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
        Schema::drop('orders');
    }
}
