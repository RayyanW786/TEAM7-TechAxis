<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;

class CartPageController extends Controller
{
    public function show()
    {
        return view('storefront.cart.show');
    }
}
