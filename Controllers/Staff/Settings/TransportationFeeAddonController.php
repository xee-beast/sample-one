<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TransportationFeeAddonRequest;
use App\Models\TransportationFeeAddon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TransportationFeeAddonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', TransportationFeeAddon::class);

        return view('settings.transportation.addons.index');
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', TransportationFeeAddon::class);
        return TransportationFeeAddon::getDataTable();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', TransportationFeeAddon::class);
        return $this->edit(new TransportationFeeAddon());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param TransportationFeeAddonRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(TransportationFeeAddonRequest $request): JsonResponse
    {
        $this->authorize('create', TransportationFeeAddon::class);

        $data = $request->validated();

        TransportationFeeAddon::create($data);

        session()->flash('status' , 'Add-on created!');
        return response()->json([
            'success'=> true,
            'message' => 'Add-on created',
            'redirect_route' => route('staff.settings.fees.transportation.fee-addons.index')
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param TransportationFeeAddon $fee_addon
     * @return View
     * @throws AuthorizationException
     */
    public function show(TransportationFeeAddon $fee_addon): View
    {
        $this->authorize('view', $fee_addon);
        return $this->edit($fee_addon);
    }

    /**
     * Show the form for editing the specified resource.
     *  @param TransportationFeeAddon $fee_addon
     *  @return View
     *  @throws AuthorizationException
     */
    public function edit(TransportationFeeAddon $fee_addon): View
    {
        $this->authorize('update', $fee_addon);

        return view('settings.transportation.addons.edit', compact('fee_addon'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param TransportationFeeAddonRequest $request
     * @param  TransportationFeeAddon $fee_addon
     * @return JsonResponse
     */
    public function update(TransportationFeeAddonRequest $request, TransportationFeeAddon $fee_addon): JsonResponse
    {
        $this->authorize('update', $fee_addon);

        $data = $request->validated();

        $fee_addon->fill($data);
        $fee_addon->save();

        session()->flash('status' , 'Add-on updated!');
        return response()->json([
            'success'=> true,
            'message' => 'Add-on updated',
            'redirect_route' => route('staff.settings.fees.transportation.fee-addons.index')
        ]);
    }

    /**
     * @param TransportationFeeAddon $fee_addon
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(TransportationFeeAddon $fee_addon): RedirectResponse
    {
        $this->authorize('delete', $fee_addon);
        if($fee_addon->hasResources()){
            return redirect()->back()->with('error','Addon cannot be deleted as its attached to multiple product(s)');
        }

        $fee_addon->delete();

        return redirect()->route('staff.settings.fees.transportation.fee-addons.index')->withStatus('Add-on deleted!');
    }
}
