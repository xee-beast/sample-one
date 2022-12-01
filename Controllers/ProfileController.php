<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\ProfileRequest;

class ProfileController extends Controller
{

    /**
     * @return View
     */
    public function showProfile(){
        $user = auth()->user();
        return view('profile.profile',compact('user'));
    }

    /**
     * @param ProfileRequest $request
     * @return jsonResponse
     */
    public function updateProfile(ProfileRequest $request){
        $data = $request->all();

        $user = auth()->user();
        $user->update($data);

        return redirect()->back()->with('status','Profile updated!');
    }

    /**
     * @param PasswordChangeRequest $request
     * @return RedirectResponse
     */
    public function updatePassword(PasswordChangeRequest $request){

        $password = $request->get('password');
        $user = auth()->user();
        $user->update([
            'password'=> Hash::make($password)
        ]);

        return redirect()->back()->with('status','Password updated!');
    }
}
