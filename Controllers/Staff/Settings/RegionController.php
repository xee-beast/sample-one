<?php


namespace App\Http\Controllers\Staff\Settings;


use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\RegionRequest;
use App\Models\Region;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class RegionController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', Region::class);
        return view('settings.regions.index');
    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', Region::class);
        return Region::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', Region::class);

        return $this->edit(new Region());
    }

    /**
     * @param RegionRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(RegionRequest $request): JsonResponse
    {
        $this->authorize('create', Region::class);

        $data = $request->validated();

        Region::create($data);

        Session::flash('status' , 'Record created successfully');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.regions.index')
        ]);
    }

    /**
     * @param Region $region
     * @return View
     * @throws AuthorizationException
     */
    public function show(Region $region): View
    {
        $this->authorize('view', $region);

        return $this->edit($region);
    }

    /**
     * @param Region $region
     * @return View
     * @throws AuthorizationException
     */
    public function edit(Region $region): View
    {
        $this->authorize('update', $region);
        return view('settings.regions.edit',compact('region'));
    }

    /**
     * @param RegionRequest $request
     * @param Region $region
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(RegionRequest $request, Region $region): JsonResponse
    {
        $this->authorize('update', $region);

        $data = $request->validated();

        $region->fill($data);
        $region->save();

        Session::flash('status' , 'Record updated successfully');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.regions.index')
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Region $region): RedirectResponse
    {
        $this->authorize('delete', $region);
        if($region->hasResources()){
            return redirect()->back()->with('error','Region cannot be deleted as its attached to multiple countries');
        }

        $region->delete();

        return redirect()->route('staff.settings.regions.index')->withStatus('Region deleted!');
    }
}
