<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProgramRequest;
use App\Models\Program;
use App\Models\ProgramFeeService;
use App\Models\ProgramPriceBook;
use App\Models\Visa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class ProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        $this->authorize('viewAny', Program::class);
        return view('settings.programs.index');
    }

    /**
     * @return JsonResponse
     */
    public function programsList(): JsonResponse
    {
        $this->authorize('viewAny', Program::class);
        return Program::getDataTable();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        $this->authorize('create', Program::class);
        $visas = Visa::where('enabled',1)->select('id','name')->get();
        $priceBooks = ProgramPriceBook::where('enabled',1)->where('expired',0)->orderBy('name')->select('id','name')->get();
        $services = ProgramFeeService::where('enabled',1)->select('id','name')->orderBy('name')->get();
        return view('settings.programs.create',compact('priceBooks','visas','services'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ProgramRequest  $request
     * @return JsonResponse
     */
    public function store(ProgramRequest $request)
    {
        $program = Program::create($request->except('active_visas','services'));
        $program->visas()->sync($request->get('active_visas'));

        $services = $request->get('services');
        $serviceArr = array();
        foreach($services as $service){
            $serviceArr[$service['program_fee_service_id']] = ['mandatory' => $service['mandatory']];
        }
        $program->services()->sync($serviceArr);
        session()->flash('status' , 'Program created!');
        return response()->json([
            'success'=> true,
            'message' => 'Program created!',
            'redirect_route' => route('staff.settings.products.programs.index')
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  Program $program
     * @return View
     */
    public function show(Program $program)
    {
        $program = $program->load('priceBook');
        return $this->edit($program);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Program $program
     * @return View
     */
    public function edit(Program $program)
    {
        $this->authorize('update', Program::class);
        $program = $program->load('priceBook');
        $visas = Visa::where('enabled',1)->select('id','name')->get();
        $priceBooks = ProgramPriceBook::where('enabled',1)->where('expired',0)->orderBy('name')->select('id','name')->get();
        $services = ProgramFeeService::where('enabled',1)->select('id','name')->orderBy('name')->get();
        $selectedServices = $program->services()->get();
        $selectedVisas = $program->visas()->pluck('id')->toArray();
        $selectedServicesArray = [];
        foreach($selectedServices as $service){
            $selectedServicesArray[] = [
                'program_fee_service_id' => $service->id,
                'mandatory' => $service->pivot->mandatory == 1 ? true : false
            ];
        }
        return view('settings.programs.edit',compact('program','priceBooks','visas','services','selectedServicesArray','selectedVisas'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Program $program
     * @return JsonResponse
     */
    public function update(ProgramRequest $request, Program $program)
    {
        $this->authorize('update', $program);
        $data = $request->except('active_visas','services');
        $program->update($data);
        $program->visas()->sync($request->get('active_visas'));

        $services = $request->get('services');
        $serviceArr = array();
        foreach($services as $service){
            $serviceArr[$service['program_fee_service_id']] = ['mandatory' => $service['mandatory']];
        }
        $program->services()->sync($serviceArr);
        session()->flash('status' , 'Program updated!');
        return response()->json([
            'success'=> true,
            'message' => 'Program updated successfully',
            'redirect_route' => route('staff.settings.products.programs.index')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Program $program
     * @return \Illuminate\Http\Response
     */
    public function destroy(Program $program)
    {
        $this->authorize('delete', $program);
        if($program->hasResources()){
            return redirect()->back()->with('error','Program cannot be deleted as its attached to multiple resource(s)');
        }
        $program->visas()->detach();
        $program->services()->detach();
        $program->delete();
        return redirect()->route('staff.settings.products.programs.index')->withStatus('Program deleted!');
    }
}
