<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

    public function show(){
        return redirect()->route('applications.index');
//        return view('agent.dashboard');
    }

}
