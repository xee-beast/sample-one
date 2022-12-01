<?php


namespace App\Http\Controllers\Staff\Settings;


use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PackagedProgramRequest;
use App\Models\Faculty;
use App\Models\PackagedProgram;
use App\Models\ProgramPriceBook;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class PackagedProgramController extends Controller
{
    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAll', PackagedProgram::class);

        return view('settings.packaged-programs.index');
    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAll', PackagedProgram::class);
        return PackagedProgram::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', PackagedProgram::class);

        return $this->edit(new PackagedProgram());
    }

    /**
     * @param PackagedProgramRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(PackagedProgramRequest $request): JsonResponse
    {
        $this->authorize('create', PackagedProgram::class);

        $packaged_program = PackagedProgram::create(($request->except(['programs'])));

        $programs = $request->get('programs');
        $programArr = array();
        foreach($programs as $program){
            $programArr[] = [
                'program_id' => $program['program_id'],
                'faculty_id' => $program['faculty_id'],
                'discount' => $this->getDiscountFieldValue($program['discount'], false)
            ];
        }
        $packaged_program->programs()->attach($programArr);

        Session::flash('status' , 'Package created!');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.products.packaged-programs.index')
        ]);
    }

    /**
     * @param PackagedProgram $packaged_program
     * @return View
     * @throws AuthorizationException
     */
    public function show(PackagedProgram $packaged_program): View
    {
        $this->authorize('view', $packaged_program);

        return $this->edit($packaged_program);
    }

    /**
     * @param PackagedProgram $packaged_program
     * @return View
     * @throws AuthorizationException
     */
    public function edit(PackagedProgram $packaged_program): View
    {
        $this->authorize('update', $packaged_program);

        $selectedPrograms = $packaged_program->programs()->get();
        $faculties = Faculty::whereEnabled(true)->select('id', 'name')->get();
        $facultyPrograms = [];
        foreach ( Faculty::all() as $faculty ){
            $facultyPrograms[$faculty->id] = $faculty->programs()->select('id', 'name')->get();
        }

        $selectedProgramsArray = [];
        foreach($selectedPrograms as $program){
            $selectedProgramsArray[] = [
                'program_id' => $program->id,
                'faculty_id' => $program->pivot->faculty_id,
                'discount' => $this->getDiscountFieldValue($program->pivot->discount, true),
            ];
        }

        return view('settings.packaged-programs.edit',compact('packaged_program', 'selectedProgramsArray', 'faculties', 'facultyPrograms'));
    }


    /**
     * @param PackagedProgramRequest $request
     * @param PackagedProgram $packaged_program
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(PackagedProgramRequest $request, PackagedProgram $packaged_program): JsonResponse
    {
        $this->authorize('update', $packaged_program);

        $packaged_program->update(($request->except(['programs'])));
        $programs = $request->get('programs');
        $packaged_program->programs()->detach();
        $programArr = array();
        foreach($programs as $program){
            $programArr[] = [
                'program_id' => $program['program_id'],
                'faculty_id' => $program['faculty_id'],
                'discount' => $this->getDiscountFieldValue($program['discount'], false)
            ];
        }
        $packaged_program->programs()->attach($programArr);

        Session::flash('status' , 'Package updated!');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.products.packaged-programs.index')
        ]);
    }

    /**
     * @param PackagedProgram $packaged_program
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(PackagedProgram $packaged_program): RedirectResponse
    {
        $this->authorize('delete', $packaged_program);

        $packaged_program->programs()->detach();
        $packaged_program->delete();

        return redirect()->route('staff.settings.products.packaged-programs.index')->withStatus('Package deleted!');
    }

    /**
     * @param $value
     * @param $toGet
     * @return float|int
     */
    public function getDiscountFieldValue($value = 0 , $toGet = false): float|int
    {
        if ($toGet){
            return round($value*100,2);
        }
        return $value/100;
    }
}
