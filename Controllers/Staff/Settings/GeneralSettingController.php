<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Classes\Settings\GeneralSettings;
use Illuminate\View\View;

class GeneralSettingController extends Controller
{

    /**
     * Display users tab in general settings.
     *
     * @return view
     */

    public function users(GeneralSettings $generalSettings){
        $this->authorize('update', Setting::class);
        $users = User::where('user_type',config('constants.user_types.staff'))->where('enabled',1)->get()->transform(function ($user){
            return [
                'id' => $user->id,
                'full_name' => $user->first_name.' '.$user->last_name
            ];
        });
        $selected_users = $generalSettings->notifiers_on_agent_registration;
        return view('settings.general-settings.users',compact('users','selected_users'));
    }

     /**
     * Display application form tab in general settings.
     *
     * @return view
     */

    public function applicationForm(GeneralSettings $generalSettings){
        $this->authorize('update', Setting::class);
        $users = User::where('user_type',config('constants.user_types.staff'))->where('enabled',1)->get()->transform(function ($user){
            return [
                'id' => $user->id,
                'full_name' => $user->first_name.' '.$user->last_name
            ];
        });
        $selected_users = $generalSettings->notifiers_on_app_form_submission;
        return view('settings.general-settings.application-form',compact('users','selected_users'));
    }

    /**
     * Update settings for users tab.
     *
     * @param Request $request
     * @param GeneralSettings $generalSettings
     * @return jsonResponse
     */
    public function storeUserSettings(Request $request, GeneralSettings $generalSettings){
        $this->authorize('update', Setting::class);
        $generalSettings->notifiers_on_agent_registration = $request->get('notifiers_on_agent_registration') !== null
                                                                ?  $request->get('notifiers_on_agent_registration')
                                                                : null;
        $generalSettings->save();
        return response()->json([
            'success' => true,
            'message' => 'Settings updated'
        ]);
    }

    /**
     * Update settings for application form tab.
     *
     * @param Request $request
     * @param GeneralSettings $generalSettings
     * @return jsonResponse
     */
    public function storeApplicationSettings(Request $request, GeneralSettings $generalSettings){
        $this->authorize('update', Setting::class);

        $generalSettings
            ->notifiers_on_app_form_submission = $request->get('notifiers_on_app_form_submission') !== null
                                                    ?  $request->get('notifiers_on_app_form_submission')
                                                    : null;
        $generalSettings->save();
        return response()->json([
            'success' => true,
            'message' => 'Settings updated'
        ]);
    }
}
