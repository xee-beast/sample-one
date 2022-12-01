<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AccommodationPriceBookRequest;
use App\Models\AccommodationPriceBook;
use App\Models\AccommodationPriceBookCategory;
use App\Models\AccommodationPriceBookFee;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use  Illuminate\Http\RedirectResponse;

class AccommodationPriceBookController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', AccommodationPriceBook::class);

        $categories = AccommodationPriceBookCategory::all();
        return view('settings.accommodation.pricebooks.index', compact('categories'));
    }

    /**
     * @throws AuthorizationException
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', AccommodationPriceBook::class);
        return AccommodationPriceBook::getDataTable();
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', AccommodationPriceBook::class);
        $categories = AccommodationPriceBookCategory::whereEnabled(true)->get();
        return view('settings.accommodation.pricebooks.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AccommodationPriceBookRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(AccommodationPriceBookRequest $request): JsonResponse
    {
        $this->authorize('create', AccommodationPriceBook::class);

        $expiryDate = Carbon::parse($request->get('expiry_date'))->format('Y-m-d');
        $request->merge([
            'expiry_date' => $expiryDate
        ]);
        $formData = $request->except('rates','delete_fees');
        $formData['expired'] = Carbon::today()->gt(Carbon::parse($request->get('expiry_date')));

        $pricebook = AccommodationPriceBook::create($formData);
        $rates = $request->get('rates');

        // create or update records
        foreach ( $rates as $rate ) {
            $id = $rate['id'] ?? null;
            $pricebook->rates()->updateOrCreate(
                ['id' => $id],
                [
                    'min' => $rate['min'],
                    'max' => $rate['max'],
                    'weekly_fee' => $rate['weekly_fee'],
                    'daily_fee' => $rate['daily_fee'],
                ]
            );
        }

        session()->flash('status' , $pricebook->expired ? 'Pricebook created but it is expired!' : 'Pricebook created successfully!');
        return response()->json([
            'success'=> true,
            'message' => 'Pricebook created successfully',
            'redirect_route' => route('staff.settings.fees.accommodation.pricebooks.index')
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param AccommodationPriceBook $pricebook
     * @return View
     * @throws AuthorizationException
     */
    public function show(AccommodationPriceBook $pricebook): View
    {
        $this->authorize('view', $pricebook);
        return $this->edit($pricebook);
    }

    /**
     * Show the form for editing the specified resource.
     *  @param AccommodationPriceBook $pricebook
     *  @return View
     *  @throws AuthorizationException
     */
    public function edit(AccommodationPriceBook $pricebook): View
    {
        $this->authorize('update', $pricebook);

        $categories = AccommodationPriceBookCategory::whereEnabled(true)->get();
        return view('settings.accommodation.pricebooks.edit', compact('pricebook', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param AccommodationPriceBookRequest $request
     * @param  AccommodationPriceBook $pricebook
     * @return JsonResponse
     */
    public function update(AccommodationPriceBookRequest $request, AccommodationPriceBook $pricebook): JsonResponse
    {
        $this->authorize('update', $pricebook);
        $expiryDate = Carbon::parse($request->get('expiry_date'))->format('Y-m-d');
        $request->merge([
            'expiry_date' => $expiryDate
        ]);
        $formData = $request->except('rates','delete_fees');
        $formData['expired'] = Carbon::today()->gt(Carbon::parse($request->get('expiry_date')));

        $pricebook->update($formData);

        $rates = $request->get('rates');

        // removing deleted records
        $idsToDelete = $request->get('delete_fees');
        if ( ! empty($idsToDelete) ){
            AccommodationPriceBookFee::destroy($idsToDelete);
        }

        // create or update records
        foreach ( $rates as $rate ) {
            $id = $rate['id'] ?? null;
            $pricebook->rates()->updateOrCreate(
                ['id' => $id],
                [
                    'min' => $rate['min'],
                    'max' => $rate['max'],
                    'weekly_fee' => $rate['weekly_fee'],
                    'daily_fee' => $rate['daily_fee'],
                ]
            );
        }

        session()->flash('status' , $pricebook->expired ? 'Pricebook updated but it is expired!' : 'Pricebook updated successfully!');
        return response()->json([
            'success'=> true,
            'message' => 'Pricebook updated successfully',
            'redirect_route' => route('staff.settings.fees.accommodation.pricebooks.index')
        ]);
    }

    /**
     * @param AccommodationPriceBook $pricebook
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(AccommodationPriceBook $pricebook): RedirectResponse
    {
        $this->authorize('delete', $pricebook);
        if($pricebook->hasResources()){
            return redirect()->back()->with('error','Pricebook cannot be deleted as its attached to multiple accommodation(s)');
        }

        $pricebook->rates()->delete();
        $pricebook->delete();

        return redirect()->route('staff.settings.fees.accommodation.pricebooks.index')->withStatus('Pricebook deleted!');
    }

    /**
     * @param AccommodationPriceBook $pricebook
     * @return mixed
     * @throws AuthorizationException
     */
    public function clonePriceBook(AccommodationPriceBook $pricebook): JsonResponse
    {
        $this->authorize('create', $pricebook);

        $pricebook->replicateRow();

        return response()->json([
            'success'=> true,
        ]);
    }
}
