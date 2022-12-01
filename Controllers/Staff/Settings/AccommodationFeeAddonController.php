<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AccommodationFeeAddonRequest;
use App\Models\AccommodationFeeAddon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use  Illuminate\Http\RedirectResponse;

class AccommodationFeeAddonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', AccommodationFeeAddon::class);

        return view('settings.accommodation.addons.index');
    }

    /**
     * @throws AuthorizationException
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', AccommodationFeeAddon::class);
        return AccommodationFeeAddon::getDataTable();
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', AccommodationFeeAddon::class);
        return $this->edit(new AccommodationFeeAddon());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AccommodationFeeAddonRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(AccommodationFeeAddonRequest $request): JsonResponse
    {
        $this->authorize('create', AccommodationFeeAddon::class);

        $data = $request->validated();

        AccommodationFeeAddon::create($data);

        session()->flash('status' , 'Add-on created successfully!');
        return response()->json([
            'success'=> true,
            'message' => 'Add-on created successfully',
            'redirect_route' => route('staff.settings.fees.accommodation.fee-addons.index')
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param AccommodationFeeAddon $fee_addon
     * @return View
     * @throws AuthorizationException
     */
    public function show(AccommodationFeeAddon $fee_addon): View
    {
        $this->authorize('view', $fee_addon);
        return $this->edit($fee_addon);
    }

    /**
     * Show the form for editing the specified resource.
     *  @param AccommodationFeeAddon $fee_addon
     *  @return View
     *  @throws AuthorizationException
     */
    public function edit(AccommodationFeeAddon $fee_addon): View
    {
        $this->authorize('update', $fee_addon);

        return view('settings.accommodation.addons.edit', compact('fee_addon'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param AccommodationFeeAddonRequest $request
     * @param  AccommodationFeeAddon $fee_addon
     * @return JsonResponse
     */
    public function update(AccommodationFeeAddonRequest $request, AccommodationFeeAddon $fee_addon): JsonResponse
    {
        $this->authorize('update', $fee_addon);

        $data = $request->validated();

        $fee_addon->fill($data);
        $fee_addon->save();

        session()->flash('status' , 'Add-on updated successfully!');
        return response()->json([
            'success'=> true,
            'message' => 'Add-on updated successfully',
            'redirect_route' => route('staff.settings.fees.accommodation.fee-addons.index')
        ]);
    }

    /**
     * @param AccommodationFeeAddon $fee_addon
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(AccommodationFeeAddon $fee_addon): RedirectResponse
    {
        $this->authorize('delete', $fee_addon);
        if($fee_addon->hasResources()){
            return redirect()->back()->with('error','Addon cannot be deleted as its attached to multiple product(s)');
        }

        $fee_addon->delete();

        return redirect()->route('staff.settings.fees.accommodation.fee-addons.index')->withStatus('Add-on deleted!');
    }
}
