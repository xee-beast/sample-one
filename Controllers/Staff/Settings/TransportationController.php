<?php


namespace App\Http\Controllers\Staff\Settings;


use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TransportationRequest;
use App\Models\Location;
use App\Models\Transportation;
use App\Models\TransportationFeeAddon;
use App\Models\TransportationFeeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransportationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', Transportation::class);

        return view('settings.transportation.index');
    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', Transportation::class);
        return Transportation::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', Transportation::class);

        return $this->edit(new Transportation());
    }

    /**
     * @param TransportationRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(TransportationRequest $request): JsonResponse
    {
        $this->authorize('create', Transportation::class);

        $data = $request->validated();
        $data = $request->except('services','addons');

        $transportation = Transportation::create($data);

        $addons = $request->get('addons');
        $addonArr = array();
        foreach($addons as $addon){
            $addonArr[$addon['transportation_fee_addon_id']] = ['mandatory' => $addon['mandatory'] ?? false];
        }
        $transportation->addons()->sync($addonArr);

        $services = $request->get('services');
        $serviceArr = [];
        foreach($services as $service){
            $serviceArr[$service['transportation_fee_service_id']] = ['mandatory' => $service['mandatory']];
        }
        $transportation->services()->sync($serviceArr);

        session()->flash('status' , 'Transportation created!');
        return response()->json([
            'success'=> true,
            'message' => 'created successfully',
            'redirect_route' => route('staff.settings.products.transportation.index')
        ]);
    }

    /**
     * @param Transportation $transportation
     * @return View
     * @throws AuthorizationException
     */
    public function show(Transportation $transportation): View
    {
        $this->authorize('view', $transportation);

        return $this->edit($transportation);
    }

    /**
     * @param Transportation $transportation
     * @return View
     * @throws AuthorizationException
     */
    public function edit(Transportation $transportation): View
    {
        $this->authorize('update', $transportation);
        $addons = TransportationFeeAddon::where('enabled',1)->select('id','name')->get();
        $services = TransportationFeeService::where('enabled',1)->select('id','name')->get();
        $locations = Location::whereEnabled(true)->select('id','name')->get();

        $selectedAddons = $transportation->addons()->where('enabled',1)->get();
        $selectedServices = $transportation->services()->where('enabled',1)->get();

        $selectedAddonArray = [];
        foreach($selectedAddons as $addon){
            $selectedAddonArray[] = [
                'name' => $addon->name,
                'transportation_fee_addon_id' => $addon->id,
                'mandatory' => $addon->pivot->mandatory == 1
            ];
        }

        $selectedServicesArray = [];
        foreach($selectedServices as $service){
            $selectedServicesArray[] = [
                'transportation_fee_service_id' => $service->id,
                'mandatory' => $service->pivot->mandatory == 1 ? true : false
            ];
        }

        return view('settings.transportation.edit', compact('transportation', 'locations','addons','services','selectedAddonArray','selectedServicesArray'));
    }

    /**
     * @param TransportationRequest $request
     * @param Transportation $transportation
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(TransportationRequest $request, Transportation $transportation): JsonResponse
    {
        $this->authorize('update', $transportation);

        $data = $request->except('services','addons');

        $transportation->fill($data);
        $transportation->save();


        $addons = $request->get('addons');
        $addonArr = array();
        foreach($addons as $addon){
            $addonArr[$addon['transportation_fee_addon_id']] = ['mandatory' => $addon['mandatory'] ?? false];
        }
        $transportation->addons()->sync($addonArr);

        $services = $request->get('services');
        $serviceArr = [];
        foreach($services as $service){
            $serviceArr[$service['transportation_fee_service_id']] = ['mandatory' => $service['mandatory']];
        }
        $transportation->services()->sync($serviceArr);

        session()->flash('status' , 'Transportation updated!');
        return response()->json([
            'success'=> true,
            'message' => 'updated successfully',
            'redirect_route' => route('staff.settings.products.transportation.index')
        ]);
    }

    /**
     * @param Transportation $transportation
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Transportation $transportation): RedirectResponse
    {
        $this->authorize('delete', $transportation);
        if($transportation->hasResources()){
            return redirect()->back()->with('error','Transportation cannot be deleted as its attached to multiple product(s)');
        }

        $transportation->delete();

        return redirect()->route('staff.settings.products.transportation.index')->withStatus('Transportation deleted!');
    }
}
