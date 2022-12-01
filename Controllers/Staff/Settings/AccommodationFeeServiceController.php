<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AccommodationFeeServiceRequest;
use App\Models\AccommodationFeeService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class AccommodationFeeServiceController extends Controller
{
    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', AccommodationFeeService::class);
        return view('settings.accommodation.fee-services.index');
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', AccommodationFeeService::class);
        return AccommodationFeeService::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', AccommodationFeeService::class);

        return $this->edit(new AccommodationFeeService());
    }

    /**
     * @param AccommodationFeeServiceRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(AccommodationFeeServiceRequest $request): JsonResponse
    {
        $this->authorize('create', AccommodationFeeService::class);

        $data = $request->validated();

        AccommodationFeeService::create($data);

        Session::flash('status' , 'Service created!');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.fees.accommodation.fee-services.index')
        ]);
    }

    /**
     * @param AccommodationFeeService $fee_service
     * @return View
     * @throws AuthorizationException
     */
    public function show(AccommodationFeeService $fee_service): View
    {
        $this->authorize('view', $fee_service);

        return $this->edit($fee_service);
    }

    /**
     * @param AccommodationFeeService $fee_service
     * @return View
     * @throws AuthorizationException
     */
    public function edit(AccommodationFeeService $fee_service): View
    {
        $this->authorize('update', $fee_service);

        return view('settings.accommodation.fee-services.edit',compact('fee_service'));
    }

    /**
     * @param AccommodationFeeServiceRequest $request
     * @param AccommodationFeeService $fee_service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(AccommodationFeeServiceRequest $request, AccommodationFeeService $fee_service): JsonResponse
    {
        $this->authorize('update', $fee_service);

        $data = $request->validated();

        $fee_service->fill($data);
        $fee_service->save();

        Session::flash('status' , 'Service updated!');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.fees.accommodation.fee-services.index')
        ]);
    }

    /**
     * @param AccommodationFeeService $fee_service
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(AccommodationFeeService $fee_service): RedirectResponse
    {
        $this->authorize('delete', $fee_service);
        if($fee_service->hasResources()){
            return redirect()->back()->with('error','Service cannot be deleted as its attached to multiple product(s)');
        }

        $fee_service->delete();

        return redirect()->route('staff.settings.fees.accommodation.fee-services.index')->withStatus('Service deleted!');
    }
}
