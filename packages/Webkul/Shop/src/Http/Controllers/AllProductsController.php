<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\View\View;

class AllProductsController extends Controller
{
    /**
     * Display all products in a dedicated storefront page.
     */
    public function index(): View
    {
        return view('shop::search.index', [
            'query' => '',
            'suggestion' => null,
            'allProducts' => true,
        ]);
    }
}
