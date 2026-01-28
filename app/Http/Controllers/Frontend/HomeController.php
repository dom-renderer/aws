<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use \App\Models\HomePageSetting;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $sections = HomePageSetting::oldest('ordering')->get();

        return view('frontend.home', compact('sections'));
    }
}
