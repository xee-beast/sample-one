<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PriceBookCategoryRequest;
use App\Models\AccommodationPriceBookCategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class AccommodationPriceBookCategoryController extends Controller
{
    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', AccommodationPriceBookCategory::class);
        return view('settings.accommodation.pricebooks.categories.index');
    }

    /** @return JsonResponse
     * @throws AuthorizationException
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', AccommodationPriceBookCategory::class);
        return AccommodationPriceBookCategory::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', AccommodationPriceBookCategory::class);

        return $this->edit(new AccommodationPriceBookCategory());
    }

    /**
     * @param PriceBookCategoryRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws AuthorizationException
     */
    public function store(PriceBookCategoryRequest $request)
    {
        $this->authorize('create', AccommodationPriceBookCategory::class);

        $data = $request->validated();

        $category = AccommodationPriceBookCategory::create($data);

        return redirect()->route('staff.settings.fees.accommodation.pricebook-categories.index')->withStatus('Pricebook category created!');
    }

    /**
     * @param AccommodationPriceBookCategory $pricebook_category
     * @return View
     * @throws AuthorizationException
     */
    public function show(AccommodationPriceBookCategory $pricebook_category): View
    {
        $this->authorize('view', $pricebook_category);

        return $this->edit($pricebook_category);
    }

    /**
     * @param AccommodationPriceBookCategory $pricebook_category
     * @return View
     * @throws AuthorizationException
     */
    public function edit(AccommodationPriceBookCategory $pricebook_category): View
    {
        $this->authorize('view', $pricebook_category);

        return view('settings.accommodation.pricebooks.categories.edit', ['accommodationPriceBookCategory' => $pricebook_category]);
    }

    /**
     * @param PriceBookCategoryRequest $request
     * @param AccommodationPriceBookCategory $pricebook_category
     * @throws AuthorizationException
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(PriceBookCategoryRequest $request, AccommodationPriceBookCategory $pricebook_category)
    {
        $this->authorize('update', $pricebook_category);

        $data = $request->validated();

        $pricebook_category->fill($data);
        $pricebook_category->save();

        return redirect()->route('staff.settings.fees.accommodation.pricebook-categories.index')->withStatus('Pricebook category updated!');
    }

    /**
     * @param AccommodationPriceBookCategory $pricebook_category
     * @throws AuthorizationException
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(AccommodationPriceBookCategory $pricebook_category)
    {
        $this->authorize('delete', $pricebook_category);
        if($pricebook_category->hasResources()){
            return redirect()->back()->with('error','Category cannot be deleted as its attached to multiple accommodation(s)');
        }

        $pricebook_category->delete();

        return redirect()->route('staff.settings.fees.accommodation.pricebook-categories.index')->withStatus('Pricebook category deleted!');
    }
}
