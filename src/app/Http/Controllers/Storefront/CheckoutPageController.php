<?php

namespace App\Http\Controllers\Storefront;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CheckoutPageController extends Controller
{
    public function show(Request $request)
    {
        return view('storefront.checkout.show');
    }
}
