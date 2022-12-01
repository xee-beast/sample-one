<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProgramFeeServiceRequest;
use App\Models\ProgramFeeService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class ProgramFeeServiceController extends Controller
{
    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', ProgramFeeService::class);
        return view('settings.programs.fee-services.index');
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', ProgramFeeService::class);
        return ProgramFeeService::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', ProgramFeeService::class);

        return $this->edit(new ProgramFeeService());
    }

    /**
     * @param ProgramFeeServiceRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(ProgramFeeServiceRequest $request): JsonResponse
    {
        $this->authorize('create', ProgramFeeService::class);

        $data = $request->validated();

        ProgramFeeService::create($data);

        Session::flash('status', 'Service created successfully');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.fees.programs.fee-services.index')
        ]);
    }

    /**
     * @param ProgramFeeService $fee_service
     * @return View
     * @throws AuthorizationException
     */
    public function show(ProgramFeeService $fee_service): View
    {
        $this->authorize('view', $fee_service);

        return $this->edit($fee_service);
    }

    /**
     * @param ProgramFeeService $fee_service
     * @return View
     * @throws AuthorizationException
     */
    public function edit(ProgramFeeService $fee_service): View
    {
        $this->authorize('update', $fee_service);

        return view('settings.programs.fee-services.edit',compact('fee_service'));
    }

    /**
     * @param ProgramFeeServiceRequest $request
     * @param ProgramFeeService $fee_service
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(ProgramFeeServiceRequest $request, ProgramFeeService $fee_service): JsonResponse
    {
        $this->authorize('update', $fee_service);

        $data = $request->validated();

        $fee_service->fill($data);
        $fee_service->save();

        Session::flash('status' , 'Program service updated successfully');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.fees.programs.fee-services.index')
        ]);
    }

    /**
     * @param ProgramFeeService $fee_service
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(ProgramFeeService $fee_service): RedirectResponse
    {
        $this->authorize('destroy', $fee_service);
        if($fee_service->hasResources()){
            return redirect()->back()->with('error','Service cannot be deleted as its attached to multiple product(s)');
        }

        $fee_service->delete();

        return redirect()->route('staff.settings.fees.programs.fee-services.index')->withStatus('Program service deleted!');
    }
}
