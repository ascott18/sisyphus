<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $users = \App\Models\User::paginate(10);

        foreach ($users as $user) {
            $sortedDepts = $user->departments->toArray();
            usort($sortedDepts, function($a, $b){
                return strcmp($a['department'], $b['department']);
            });

            $user->sortedDepartments = $sortedDepts;
        }

        return view('users.index', ['users' => $users]);
    }

}
