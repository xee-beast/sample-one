<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\VisaRequest;
use App\Models\Visa;
use App\Services\EbecasService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class VisasController extends Controller
{
    protected EbecasService $ebecasService;

    function __construct(EbecasService $ebecasService){
        $this->ebecasService = $ebecasService;
    }

    public function index()
    {

        $this->authorize('viewAny', Visa::class);
        return view('settings.visas.index');
    }

    /**
     * Returns data table list for countries index.
     * @return JsonResponse
     * @throws Exception
     * @throws AuthorizationException
     */
    public function getDataTableList(){
        $this->authorize('viewAny', Visa::class);
        return Visa::getDataTable();
    }

    public function create()
    {
        $this->authorize('create', Visa::class);

        return $this->edit(new Visa());
    }

    public function store(VisaRequest $request)
    {
        $this->authorize('create', Visa::class);

        $validated = $request->validated();

        $visa = Visa::create($validated);

        return redirect()->route('staff.settings.visas.edit',$visa)->withStatus('Visa created!');
    }

    public function show(Visa $visa)
    {
        $this->authorize('view', $visa);

        return $this->edit($visa);
    }

    public function edit(Visa $visa)
    {
        $this->authorize('view', $visa);

        $visa_types = $this->ebecasService->getVisaTypes();

        return view('settings.visas.edit',compact('visa', "visa_types"));
    }

    public function update(VisaRequest $request, Visa $visa)
    {
        $this->authorize('view', $visa);

        $validated = $request->validated();

        $visa->fill($validated);
        $visa->save();

        return redirect()->route('staff.settings.visas.edit',$visa)->withStatus('Visa updated!');
    }

    public function destroy(Visa $visa)
    {
        $this->authorize('delete', $visa);

        if($visa->hasResources()){
            return redirect()->back()->with('error','Visa cannot be deleted as its attached to multiple resource(s)');
        }

        $visa->delete();

        return redirect()->route('staff.settings.visas.index')->withStatus('Visa deleted!');

    }
}
