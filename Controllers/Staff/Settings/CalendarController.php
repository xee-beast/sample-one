<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CalendarRequest;
use App\Models\Calendar;
use App\Models\CalendarCategory;
use App\Models\CalendarDate;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class CalendarController extends Controller
{

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', Calendar::class);
        return view('settings.calendars.index');
    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', Calendar::class);
        return Calendar::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', Calendar::class);

        return $this->edit(new Calendar());
    }

    /**
     * @param CalendarRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(CalendarRequest $request): JsonResponse
    {
        $this->authorize('create', Calendar::class);

        $formData = $request->except(['dates','deleted_records']);
        $dates = $request->get('dates');

        // updating dates
        $calendar = Calendar::create($formData);
        $calendar->save();

        // create date records
        foreach ( $dates as $date ) {
            $calendar->dates()->create(
                [
                    'start_date' => Carbon::parse($date['start_date'])->format("Y-m-d"),
                    'weeks' => $date['weeks']
                ]
            );
        }

        Session::flash('status' , 'Record created successfully');
        return response()->json([
            'success'=> true,
            'records' => $calendar->dates()->get(),
            'redirect_route' => route('staff.settings.dates.calendars.index')
        ]);
    }

    /**
     * @param Calendar $calendar
     * @return View
     * @throws AuthorizationException
     */
    public function show(Calendar $calendar): View
    {
        $this->authorize('view', $calendar);

        return $this->edit($calendar);
    }


    /**
     * @param Calendar $calendar
     * @return View
     * @throws AuthorizationException
     */
    public function edit(Calendar $calendar): View
    {
        $this->authorize('update', $calendar);

        $categories = CalendarCategory::whereEnabled(true)->get();
        return view('settings.calendars.edit',compact('calendar', 'categories'));
    }

    /**
     * @param CalendarRequest $request
     * @param Calendar $calendar
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(CalendarRequest $request, Calendar $calendar): JsonResponse
    {
        $this->authorize('update', $calendar);

        $formData = $request->except(['dates','deleted_records']);
        $dates = $request->get('dates');

        // updating dates
        $calendar->fill($formData);
        $calendar->save();

        // deleting dates
        if ( !empty($request->get('deleted_records')) ){
            CalendarDate::destroy($request->get('deleted_records'));
        }

        // updating dates
        // create or update records
        foreach ( $dates as $date ) {
            $id = $date['id'] ?? null;
            $calendar->dates()->updateOrCreate(
                ['id' => $id],
                [
                    'start_date' => Carbon::parse($date['start_date'])->format("Y-m-d"),
                    'weeks' => $date['weeks']
                ]
            );
        }

        Session::flash('status' , 'Record updated successfully');
        return response()->json([
            'success'=> true,
            'records' => $calendar->dates->sortBy('start_date')->values(),
            'redirect_route' => route('staff.settings.dates.calendars.index')
        ]);

    }

    /**
     * @param Calendar $calendar
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Calendar $calendar): RedirectResponse
    {
        $this->authorize('delete', $calendar);
        if($calendar->hasResources()){
            return redirect()->back()->with('error','Calendar cannot be deleted as its attached to multiple program(s)');
        }

        $calendar->dates()->delete();
        $calendar->delete();

        return redirect()->route('staff.settings.dates.calendars.index')->withStatus('Calendar deleted!');
    }

    /**
     * @param Calendar $calendar
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function cloneCalendar(Calendar $calendar): JsonResponse
    {
        $this->authorize('create', $calendar);

        $dates = $calendar->dates;
        $calendar->replicateRow($dates);

        return response()->json([
            'success'=> true,
        ]);
    }
}
