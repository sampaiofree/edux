<?php

namespace App\Http\Controllers;

use App\Support\HomePageDataBuilder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HomePageDataBuilder $builder): View
    {
        return view('home.index', $builder->build());
    }
}
