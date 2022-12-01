<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Models\Insurance;
use App\Models\InsuranceFee;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class InsurancesController extends Controller
{

    /**
    * @return View
    * @throws AuthorizationException
    */
    public function index(): View
    {
        $this->authorize('viewAll', Insurance::class);
        $insurances = Insurance::all();

        return view('settings.insurance.index',compact('insurances'));
    }


    /**
     * @param Insurance $insurance
     * @return View
     * @throws AuthorizationException
     */
    public function edit(Insurance $insurance): View
    {
        $this->authorize('update', $insurance);

        $fees = $insurance->fees()->orderby('duration','asc')->get();

        return view('settings.insurance.edit',compact('insurance','fees'));
    }

    public function update(Request $request, Insurance $insurance)
    {
        $this->authorize('update', $insurance);

        $data =  $request->get('fees');
        // validations
        $durations = array_column($data, 'duration');
        $haveDuplicates = (array_diff_assoc($durations, array_unique($durations)));
        if ( ! empty($haveDuplicates) ) {
            return response()->json(['success'=> false, 'message' => 'The duration must be unique.']);
        }

        // removing deleted records
        $idsToDelete = $request->get('deleted_records');
        if ( ! empty($idsToDelete) ){
            // removing records from faculty insurance relation
            DB::table('faculty_insurance')->whereIn('insurance_fee_id', $idsToDelete)->delete();
            InsuranceFee::destroy($idsToDelete);
        }

        // create or update records
        foreach ( $data as $fee ) {
            $id = $fee['id'] ?? null;
            $insurance->fees()->updateOrCreate(
                ['id' => $id],
                [
                    'duration' => $fee['duration'],
                    'fee' => $fee['fee']
                ]
            );
        }

        Session::flash('status' , 'Records updated successfully!');
        return response()->json([
            'success'=> true,
            'records' => $insurance->fees()->orderBy('duration')->get(),
            'message' => 'Records updated successfully'
        ]);
    }

}
