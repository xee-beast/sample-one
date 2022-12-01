<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AccommodationRequest;
use App\Models\Accommodation;
use App\Models\AccommodationCategory;
use App\Models\AccommodationFeeAddon;
use App\Models\AccommodationFeeService;
use App\Models\AccommodationPriceBook;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AccommodationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', Accommodation::class);
        return view('settings.accommodation.index');
    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws Exception
     */
    public function accommodationsList(): JsonResponse
    {
        $this->authorize('viewAny', Accommodation::class);
        return Accommodation::getDataTable();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', Accommodation::class);
        $priceBooks = AccommodationPriceBook::where('enabled',1)->where('expired',0)->orderBy('name')->select('id','name')->get();
        $categories = AccommodationCategory::where('enabled',1)->select('id','name')->orderBy('name')->get();
        $addons = AccommodationFeeAddon::where('enabled',1)->select('id','name')->orderBy('name')->get();
        $services = AccommodationFeeService::where('enabled',1)->select('id','name')->orderBy('name')->get();
        return view('settings.accommodation.create',compact('priceBooks', 'categories','services', 'addons'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AccommodationRequest  $request
     * @return JsonResponse
     */
    public function store(AccommodationRequest $request): JsonResponse
    {
        $accommodation = Accommodation::create($request->all());

        $addons = $request->get('addons');
        $addonArr = array();
        foreach($addons as $addon){
            $addonArr[$addon['accommodation_fee_addon_id']] = ['mandatory' => $addon['mandatory'] ?? false];
        }
        $accommodation->addons()->sync($addonArr);

        $services = $request->get('services');
        $serviceArr = array();
        foreach($services as $service){
            $serviceArr[$service['accommodation_fee_service_id']] = ['mandatory' => $service['mandatory']];
        }
        $accommodation->services()->sync($serviceArr);

        session()->flash('status' , 'Accommodation created!');
        return response()->json([
            'success'=> true,
            'message' => 'Accommodation created!',
            'redirect_route' => route('staff.settings.products.accommodation.index')
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param Accommodation $accommodation
     * @return View
     * @throws AuthorizationException
     */
    public function show(Accommodation $accommodation): View
    {
        $accommodation = $accommodation->load('priceBook');
        return $this->edit($accommodation);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Accommodation $accommodation
     * @return View
     * @throws AuthorizationException
     */
    public function edit(Accommodation $accommodation): View
    {
        $this->authorize('update', $accommodation);
        $accommodation = $accommodation->load('priceBook', 'category');
        $priceBooks = AccommodationPriceBook::where('enabled',1)->where('expired',0)->orderBy('name')->select('id','name')->get();
        $categories = AccommodationCategory::where('enabled',1)->select('id','name')->orderBy('name')->get();
        $addons = AccommodationFeeAddon::where('enabled',1)->select('id','name')->orderBy('name')->get();
        $selectedAddons = $accommodation->addons()->where('enabled',1)->get();

        $selectedAddonArray = [];
        foreach($selectedAddons as $addon){
            $selectedAddonArray[] = [
                'name' => $addon->name,
                'accommodation_fee_addon_id' => $addon->id,
                'mandatory' => $addon->pivot->mandatory == 1
            ];
        }

        $services = AccommodationFeeService::where('enabled',1)->select('id','name')->get();
        $selectedServices = $accommodation->services()->get();
        $selectedServicesArray = [];
        foreach($selectedServices as $service){
            $selectedServicesArray[] = [
                'accommodation_fee_service_id' => $service->id,
                'mandatory' => $service->pivot->mandatory == 1 ? true : false
            ];
        }
        return view('settings.accommodation.edit',compact('accommodation','priceBooks', 'categories','selectedServicesArray','services', 'addons', 'selectedAddonArray'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param AccommodationRequest $request
     * @param Accommodation $accommodation
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(AccommodationRequest $request, Accommodation $accommodation): JsonResponse
    {
        $this->authorize('update', $accommodation);
        $accommodation->update($request->except(['addons','deletedAddons']));

        $addons = $request->get('addons');
        $addonArr = array();
        foreach($addons as $addon){
            $addonArr[$addon['accommodation_fee_addon_id']] = ['mandatory' => $addon['mandatory'] ?? false];
        }
        $accommodation->addons()->sync($addonArr);

        $services = $request->get('services');
        $serviceArr = [];
        foreach($services as $service){
            $serviceArr[$service['accommodation_fee_service_id']] = ['mandatory' => $service['mandatory']];
        }
        $accommodation->services()->sync($serviceArr);
        session()->flash('status' , 'Accommodation updated!');
        return response()->json([
            'success'=> true,
            'message' => 'Accommodation updated!',
            'redirect_route' => route('staff.settings.products.accommodation.index')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Accommodation $accommodation
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Accommodation $accommodation): RedirectResponse
    {
        $this->authorize('delete', $accommodation);
        if($accommodation->hasResources()){
            return redirect()->back()->with('error','Accommodation cannot be deleted as its attached to multiple product(s)');
        }

        $accommodation->addons()->detach();
        $accommodation->services()->detach();
        $accommodation->delete();

        return redirect()->route('staff.settings.products.accommodation.index')->withStatus('Accommodation deleted!');
    }
}
