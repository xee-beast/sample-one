<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Classes\Settings\EbecasSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\EbecasRequest;
use App\Services\EbecasService;
use Illuminate\Http\Request;

class EbecasController extends Controller
{

    public function edit(EbecasSettings $ebecasSettings)
    {
        return view('settings.ebecas.edit',compact('ebecasSettings'));
    }

    public function update(EbecasRequest $request, EbecasSettings $ebecasSettings)
    {
        $validated = $request->validated();

        $ebecasSettings->fill($validated);
        $ebecasSettings->save();

        return redirect()->route('staff.settings.integrations.ebecas.edit')->withStatus('Details updated!');

    }

    /**
     * Test the connection to eBECAS with the settings stored.
     * @param EbecasSettings $ebecasSettings
     * @param EbecasService $ebecasService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function testConnection(EbecasSettings $ebecasSettings, EbecasService $ebecasService)
    {
        $result = $ebecasService->testConnection();

        if($result){
            return redirect()->route('staff.settings.integrations.ebecas.edit')->withStatus('Connection Successful');
        }
        return redirect()->route('staff.settings.integrations.ebecas.edit')->withError('Unable to connect to eBECAS');
    }


}
