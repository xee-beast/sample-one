<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PriceBookCategoryRequest;
use App\Http\Requests\Settings\SpecialOfferCategoryRequest;
use App\Models\SpecialOfferCategory;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class SpecialOfferCategoryController extends Controller
{
    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', SpecialOfferCategory::class);
        return view('settings.special-offers.categories.index');
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', SpecialOfferCategory::class);
        return SpecialOfferCategory::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', SpecialOfferCategory::class);

        return $this->edit(new SpecialOfferCategory());
    }

    /**
     * @param SpecialOfferCategoryRequest $request
     * @return mixed
     * @throws AuthorizationException
     */
    public function store(SpecialOfferCategoryRequest $request)
    {
        $this->authorize('create', SpecialOfferCategory::class);

        $data = $request->validated();

        $category = SpecialOfferCategory::create($data);

        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.special-offer-categories.index')
        ]);
    }

    /**
     * @param SpecialOfferCategory $special_offer_category
     * @return View
     * @throws AuthorizationException
     */
    public function show(SpecialOfferCategory $special_offer_category): View
    {
        $this->authorize('view', $special_offer_category);

        return $this->edit($special_offer_category);
    }

    /**
     * @param SpecialOfferCategory $special_offer_category
     * @return View
     * @throws AuthorizationException
     */
    public function edit(SpecialOfferCategory $special_offer_category): View
    {
        $this->authorize('view', $special_offer_category);

        return view('settings.special-offers.categories.edit', compact('special_offer_category'));
    }

    /**
     * @param SpecialOfferCategoryRequest $request
     * @param SpecialOfferCategory $special_offer_category
     * @return mixed
     * @throws AuthorizationException
     */
    public function update(SpecialOfferCategoryRequest $request, SpecialOfferCategory $special_offer_category)
    {
        $this->authorize('update', $special_offer_category);

        $data = $request->validated();

        $special_offer_category->fill($data);
        $special_offer_category->save();

        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.special-offer-categories.index')
        ]);
    }

    /**
     * @param SpecialOfferCategory $special_offer_category
     * @return mixed
     * @throws AuthorizationException
     */
    public function destroy(SpecialOfferCategory $special_offer_category)
    {
        $this->authorize('delete', $special_offer_category);

        $special_offer_category->delete();

        return redirect()->route('staff.settings.special-offer-categories.index')->withStatus('Category deleted!');
    }
}
