<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /** GET: /
     *
     * @return \Illuminate\View\View
     */
    public function getIndex()
    {
        $this->authorize("all");

        return view('welcome');
    }
}