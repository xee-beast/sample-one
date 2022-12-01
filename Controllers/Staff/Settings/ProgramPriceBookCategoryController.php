<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PriceBookCategoryRequest;
use App\Models\ProgramPriceBookCategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class ProgramPriceBookCategoryController extends Controller
{
    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', ProgramPriceBookCategory::class);
        return view('settings.programs.pricebooks.categories.index');
    }

    /**
     * @throws AuthorizationException
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', ProgramPriceBookCategory::class);
        return ProgramPriceBookCategory::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', ProgramPriceBookCategory::class);

        return $this->edit(new ProgramPriceBookCategory());
    }

    /**
     * @param PriceBookCategoryRequest $request
     * @return mixed
     * @throws AuthorizationException
     */
    public function store(PriceBookCategoryRequest $request)
    {
        $this->authorize('create', ProgramPriceBookCategory::class);

        $data = $request->validated();

        $category = ProgramPriceBookCategory::create($data);

        return redirect()->route('staff.settings.fees.programs.pricebook-categories.index')->withStatus('Pricebook category created!');
    }

    /**
     * @param ProgramPriceBookCategory $pricebooks_category
     * @return View
     * @throws AuthorizationException
     */
    public function show(ProgramPriceBookCategory $pricebook_category): View
    {
        $this->authorize('view', $pricebook_category);

        return $this->edit($pricebook_category);
    }

    /**
     * @param ProgramPriceBookCategory $pricebooks_category
     * @return View
     * @throws AuthorizationException
     */
    public function edit(ProgramPriceBookCategory $pricebook_category): View
    {
        $this->authorize('view', $pricebook_category);

        return view('settings.programs.pricebooks.categories.edit', ['programPriceBookCategory' => $pricebook_category]);
    }

    /**
     * @param PriceBookCategoryRequest $request
     * @param ProgramPriceBookCategory $pricebooks_category
     * @throws AuthorizationException
     */
    public function update(PriceBookCategoryRequest $request, ProgramPriceBookCategory $pricebook_category)
    {
        $this->authorize('update', $pricebook_category);

        $data = $request->validated();

        $pricebook_category->fill($data);
        $pricebook_category->save();

        return redirect()->route('staff.settings.fees.programs.pricebook-categories.index')->withStatus('Pricebook category updated!');
    }

    /**
     * @param ProgramPriceBookCategory $pricebooks_category
     * @throws AuthorizationException
     */
    public function destroy(ProgramPriceBookCategory $pricebook_category)
    {
        $this->authorize('delete', $pricebook_category);
        if($pricebook_category->hasResources()){
            return redirect()->back()->with('error','Category cannot be deleted as its attached to multiple program(s)');
        }

        $pricebook_category->delete();

        return redirect()->route('staff.settings.fees.programs.pricebook-categories.index')->withStatus('Pricebook category deleted!');
    }
}
