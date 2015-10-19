<?php

namespace App\Http\Controllers;

use App\Models\Order;

class OrderController extends Controller
{

    /** GET: /orders/
     *
     * @return \Illuminate\View\View
     */
    public function getIndex()
    {
        $orders = Order::paginate(10);

        return view('orders.index', ['orders' => $orders]);
    }
}