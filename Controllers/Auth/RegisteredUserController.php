<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserSelfRegistered;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Psy\Util\Json;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $countries = Country::enabled()->select('id','name')->orderBy('name')->get();
        return view('auth.register',compact('countries'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'first_name' => ['required', 'string', 'min:2', 'max:100'],
                'last_name' => ['required', 'string', 'min:2', 'max:100'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'user_type' => [
                    Rule::in([config('constants.user_types.student'), config('constants.user_types.agent')])
                    ],
                'terms_and_conditions' => ['required','boolean','accepted'],
                'company'=>['required_if:user_type,agent'],
                'country_id' => ['required'],
                'mobile' => ['nullable'],
                recaptchaFieldName() => recaptchaRuleName()
            ],
        );

        $user = new User;
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->mobile = $validated['mobile'];
        $user->password = Hash::make($validated['password']);
        $user->country_id = $validated['country_id'];
        $user->user_type = $validated['user_type'];
        $user->company = $validated['company'];
        $user->enabled = true;
        $user->save();

        event(new Registered($user));
        // user self register event
        UserSelfRegistered::dispatch($user);
        Auth::login($user);

        return response()->json([
            'success' => true,
            'redirect_route' => RouteServiceProvider::HOME
        ]);
    }
}
