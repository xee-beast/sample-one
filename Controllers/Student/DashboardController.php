<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

    public function show()
    {
        return redirect()->route('applications.index');

//        return view('student.dashboard');
    }

}
