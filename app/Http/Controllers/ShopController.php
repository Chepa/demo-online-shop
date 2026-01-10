<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        return view('index');
    }

    public function show(Product $product)
    {
        return view('index');
    }

    public function cart()
    {
        return view('index');
    }

    public function orders(Request $request)
    {
        return view('index');
    }
}

