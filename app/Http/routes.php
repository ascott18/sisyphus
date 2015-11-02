<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


Route::controller('books', 'BookController');

Route::controller('orders', 'OrderController');

Route::controller('courses', 'CourseController');

Route::controller('messages', 'MessageController');

Route::controller('terms', 'TermController');

Route::controller('/', 'HomeController');


