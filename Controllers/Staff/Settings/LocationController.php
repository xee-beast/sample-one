<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LocationStoreRequest;
use App\Http\Requests\Settings\LocationUpdateRequest;
use App\Models\Application;
use App\Models\Location;
use App\Services\EbecasService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LocationController extends Controller
{

    private EbecasService $ebecasService;

    /**
     * Instantiate a new UserController instance.
     */
    public function __construct(EbecasService $ebecasService)
    {
        $this->ebecasService = $ebecasService;
    }


    /**
     * Get the faculties for a given location
     * @param Location $location
     * @return JsonResponse
     */
    public function getFacultiesByLocation(Location $location){

        $faculties = $this->ebecasService->getFacultiesByLocation($location->id);

        return response()->json(['faculties'=>$faculties['data']]);

    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', Location::class);

        return view('settings.locations.index');

    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', Location::class);
        return Location::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', Location::class);

        return $this->edit(new Location());
    }

    /**
     * @param LocationStoreRequest $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(LocationStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Location::class);

        $validated = $request->validated();

        $location = Location::create($validated);

        return redirect()->route('staff.settings.locations.index')->withStatus('Location created!');
    }

    /**
     * @param Location $location
     * @return View
     * @throws AuthorizationException
     */
    public function show(Location $location): View
    {
        $this->authorize('view', $location);

        return $this->edit($location);
    }

    /**
     * @param Location $location
     * @return View
     * @throws AuthorizationException
     */
    public function edit(Location $location): View
    {
        $this->authorize('update', $location);

        $locations_list = $this->ebecasService->getAllLocations();
        $locations_list=$locations_list['data'];

        return view('settings.locations.edit',compact('location','locations_list'));
    }

    /**
     * @param LocationUpdateRequest $request
     * @param Location $location
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(LocationUpdateRequest $request, Location $location): RedirectResponse
    {
        $this->authorize('update', $location);

        $validated = $request->validated();

        $location->fill($validated);
        $location->save();

        return redirect()->route('staff.settings.locations.index')->withStatus('Location updated!');


    }

    /**
     * @param Location $location
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Location $location): RedirectResponse
    {
        $this->authorize('update', $location);
        if($location->hasResources()){
            return redirect()->back()->with('error','Location cannot be deleted as its attached to multiple resource(s)');
        }

        $location->delete();

        return redirect()->route('staff.settings.locations.index')->withStatus('Location deleted!');
    }

}
