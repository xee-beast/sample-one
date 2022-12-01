<?php


namespace App\Http\Controllers\Staff\Settings;


use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TransportationFeeServiceRequest;
use App\Models\Location;
use App\Models\TransportationFeeService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransportationFeeServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', TransportationFeeService::class);

        return view('settings.transportation.fee-services.index');
    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', TransportationFeeService::class);
        return TransportationFeeService::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', TransportationFeeService::class);

        return $this->edit(new TransportationFeeService());
    }

    /**
     * @param TransportationFeeServiceRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(TransportationFeeServiceRequest $request): JsonResponse
    {
        $this->authorize('create', TransportationFeeService::class);

        $data = $request->validated();

        TransportationFeeService::create($data);

        session()->flash('status' , 'Service created!');
        return response()->json([
            'success'=> true,
            'message' => 'created successfully',
            'redirect_route' => route('staff.settings.fees.transportation.fee-services.index')
        ]);
    }

    /**
     * @param TransportationFeeService $fee_service
     * @return View
     * @throws AuthorizationException
     */
    public function show(TransportationFeeService $fee_service): View
    {
        $this->authorize('view', $fee_service);

        return $this->edit($fee_service);
    }

    /**
     * @param TransportationFeeService $fee_service
     * @return View
     * @throws AuthorizationException
     */
    public function edit(TransportationFeeService $fee_service): View
    {
        $this->authorize('update', $fee_service);

        $locations = Location::whereEnabled(true)->select('id','name')->get();
        return view('settings.transportation.fee-services.edit', compact('fee_service', 'locations'));
    }

    /**
     * @param TransportationFeeServiceRequest $request
     * @param TransportationFeeService $fee_service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(TransportationFeeServiceRequest $request, TransportationFeeService $fee_service): JsonResponse
    {
        $this->authorize('update', $fee_service);

        $data = $request->validated();

        $fee_service->fill($data);
        $fee_service->save();

        session()->flash('status' , 'Service updated!');
        return response()->json([
            'success'=> true,
            'message' => 'updated successfully',
            'redirect_route' => route('staff.settings.fees.transportation.fee-services.index')
        ]);
    }

    /**
     * @param TransportationFeeService $fee_service
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(TransportationFeeService $fee_service): RedirectResponse
    {
        $this->authorize('delete', $fee_service);
        if($fee_service->hasResources()){
            return redirect()->back()->with('error','Service cannot be deleted as its attached to multiple product(s)');
        }

        $fee_service->delete();

        return redirect()->route('staff.settings.fees.transportation.fee-services.index')->withStatus('Service deleted!');
    }
}
