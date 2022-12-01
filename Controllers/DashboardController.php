<?php

namespace App\Http\Controllers;

use App\Services\EbecasStudentService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

    public function routeDashboard()
    {


        //To-do: Re-enable when the dashboards for each user type are implemented
        $target = match (auth()->user()->user_type){
            default => route('applications.index')

//            'staff'=> route('staff.dashboard.show'),
//            'agent'=> route('agent.dashboard.show'),
//            'student'=> route('student.dashboard.show'),

        };
        return redirect($target);
    }

}
