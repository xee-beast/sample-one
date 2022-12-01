<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CountryRequest;
use App\Models\Country;
use App\Models\Region;
use App\Services\EbecasService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    protected EbecasService $ebecasService;
    function __construct(EbecasService $ebecasService){
        $this->ebecasService = $ebecasService;
    }


    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        $this->authorize('viewAny', Country::class);
        $regions = Region::whereEnabled(true)->select('id', 'name')->get();
        return view('settings.countries.index', compact('regions'));
    }

    /**
     * Returns data table list for countries index.
     * @return JsonResponse
     * @throws Exception
     */
    public function getDataTableList(){
        $this->authorize('viewAny', Country::class);
        return Country::getDataTable();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        $this->authorize('create', Country::class);

        return $this->edit(new Country());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CountryRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(CountryRequest $request)
    {
        $this->authorize('create', Country::class);

        $data = $request->validated();

        $country = Country::create($data);

        return redirect()->route('staff.settings.countries.edit',$country)->withStatus('Country created!');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Country  $country
     * @return View
     */
    public function show(Country $country)
    {
        $this->authorize('view', $country);

        return $this->edit($country);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Country  $country
     * @return View
     */
    public function edit(Country $country)
    {
        $this->authorize('view', $country);
        $countryList = $this->ebecasService->getAllCountries();
        $countryList = $countryList['CountryList'];
        $regions = Region::whereEnabled(true)->select('id', 'name')->get()->toArray();
        return view('settings.countries.edit',compact('country','countryList', 'regions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Settings\CountryRequest  $request
     * @param  \App\Models\Country  $country
     * @return \Illuminate\Http\Response
     */
    public function update(CountryRequest $request, Country $country)
    {
        $this->authorize('update', $country);

        $data = $request->validated();

        $country->fill($data);
        $country->save();

        return redirect()->route('staff.settings.countries.edit',$country)->withStatus('Country updated!');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Country  $country
     * @return \Illuminate\Http\Response
     */
    public function destroy(Country $country)
    {
        $this->authorize('delete', $country);
        if($country->hasResources()){
            return redirect()->back()->with('error','Country cannot be deleted as its attached to multiple application(s)');
        }

        $country->delete();

        return redirect()->route('staff.settings.countries.index')->withStatus('Country deleted!');
    }
}
