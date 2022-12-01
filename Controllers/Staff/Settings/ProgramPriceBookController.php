<?php


namespace App\Http\Controllers\Staff\Settings;


use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PriceBookRequest;
use App\Models\ProgramPriceBook;
use App\Models\ProgramPriceBookCategory;
use App\Models\ProgramPriceBookFee;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProgramPriceBookController extends Controller
{
    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', ProgramPriceBook::class);

        $categories = ProgramPriceBookCategory::all();

        return view('settings.programs.pricebooks.index', compact('categories'));
    }

    /**
     * @throws AuthorizationException
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', ProgramPriceBook::class);
        return ProgramPriceBook::getDataTable();
    }

    public function create()
    {
        $this->authorize('create', ProgramPriceBook::class);
        $categories = ProgramPriceBookCategory::whereEnabled(true)->get();
        return view('settings.programs.pricebooks.create', compact('categories'));
    }

    /**
     * @param PriceBookRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(PriceBookRequest $request): JsonResponse
    {
        $this->authorize('create', ProgramPriceBook::class);

        $expiryDate = Carbon::parse($request->get('expiry_date'))->format('Y-m-d');
        $request->merge([
            'expiry_date' => $expiryDate
        ]);
        $formData = $request->except('rates','delete_fees');
        $formData['expired'] = Carbon::today()->gt(Carbon::parse($request->get('expiry_date')));

        $pricebook = ProgramPriceBook::create($formData);
        $rates = $request->get('rates');

        // create or update records
        foreach ( $rates as $rate ) {
            $id = $rate['id'] ?? null;
            $pricebook->rates()->updateOrCreate(
                ['id' => $id],
                [
                    'min' => $rate['min'],
                    'max' => $rate['max'],
                    'price' => $rate['price']
                ]
            );
        }

        session()->flash('status' , $pricebook->expired ? 'Pricebook created but it is expired!' : 'Pricebook created successfully!');
        return response()->json([
            'success'=> true,
            'message' => 'Pricebook created successfully',
            'redirect_route' => route('staff.settings.fees.programs.pricebooks.index')
        ]);

    }

    /**
     * @param ProgramPriceBook $pricebook
     * @return View
     * @throws AuthorizationException
     */
    public function show(ProgramPriceBook $pricebook): View
    {
        $this->authorize('view', $pricebook);

        return $this->edit($pricebook);
    }

    /**
     * @param ProgramPriceBook $pricebook
     * * @return View
     * @throws AuthorizationException
     */
    public function edit(ProgramPriceBook $pricebook): View
    {
        $this->authorize('update', $pricebook);

        $categories = ProgramPriceBookCategory::whereEnabled(true)->get();
        return view('settings.programs.pricebooks.edit', compact('pricebook', 'categories'));
    }

    /**
     * @param PriceBookRequest $request
     * @param ProgramPriceBook $pricebook
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(PriceBookRequest $request, ProgramPriceBook $pricebook): JsonResponse
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
            ProgramPriceBookFee::destroy($idsToDelete);
        }

        // create or update records
        foreach ( $rates as $rate ) {
            $id = $rate['id'] ?? null;
            $pricebook->rates()->updateOrCreate(
                ['id' => $id],
                [
                    'min' => $rate['min'],
                    'max' => $rate['max'],
                    'price' => $rate['price']
                ]
            );
        }

        session()->flash('status' , $pricebook->expired ? 'Pricebook updated but it is expired!' : 'Pricebook updated successfully!');
        return response()->json([
            'success'=> true,
            'message' => 'Pricebook updated successfully',
            'redirect_route' => route('staff.settings.fees.programs.pricebooks.index')
        ]);
    }

    /**
     * @param ProgramPriceBook $pricebook
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(ProgramPriceBook $pricebook): RedirectResponse
    {
        $this->authorize('delete', $pricebook);
        if($pricebook->hasResources()){
            return redirect()->back()->with('error','Pricebook cannot be deleted as its attached to multiple program(s)');
        }

        $pricebook->rates()->delete();
        $pricebook->delete();

        return redirect()->route('staff.settings.fees.programs.pricebooks.index')->withStatus('Pricebook deleted!');
    }

    /**
     * @param ProgramPriceBook $pricebook
     * @return mixed
     * @throws AuthorizationException
     */
    public function clonePriceBook(ProgramPriceBook $pricebook): JsonResponse
    {
        $this->authorize('create', $pricebook);

        $pricebook->replicateRow();

        return response()->json([
            'success'=> true,
        ]);
    }
}
