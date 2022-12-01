<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AccommodationCategoryRequest;
use App\Models\AccommodationCategory;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AccommodationCategoryController extends Controller
{
    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', AccommodationCategory::class);
        return view('settings.accommodation.categories.index');
    }

    /** @return JsonResponse
     * @throws AuthorizationException
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', AccommodationCategory::class);
        return AccommodationCategory::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', AccommodationCategory::class);

        return $this->edit(new AccommodationCategory());
    }

    /**
     * @param AccommodationCategoryRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(AccommodationCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', AccommodationCategory::class);

        $data = $request->validated();

        $category = AccommodationCategory::create($data);

        session()->flash('status' , 'Category created!');
        return response()->json([
            'success'=> true,
            'message' => 'Category created!',
            'redirect_route' => route('staff.settings.products.accommodation-categories.index')
        ]);
    }

    /**
     * @param AccommodationCategory $accommodation_category
     * @return View
     * @throws AuthorizationException
     */
    public function show(AccommodationCategory $accommodation_category): View
    {
        $this->authorize('view', $accommodation_category);

        return $this->edit($accommodation_category);
    }

    /**
     * @param AccommodationCategory $accommodation_category
     * @return View
     * @throws AuthorizationException
     */
    public function edit(AccommodationCategory $accommodation_category): View
    {
        $this->authorize('view', $accommodation_category);

        return view('settings.accommodation.categories.edit', compact('accommodation_category'));
    }

    /**
     * @param AccommodationCategoryRequest $request
     * @param AccommodationCategory $accommodation_category
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(AccommodationCategoryRequest $request, AccommodationCategory $accommodation_category): JsonResponse
    {
        $this->authorize('update', $accommodation_category);

        $data = $request->validated();

        $accommodation_category->fill($data);
        $accommodation_category->save();

        session()->flash('status' , 'Accommodation updated successfully!');
        return response()->json([
            'success'=> true,
            'message' => 'Category updated!',
            'redirect_route' => route('staff.settings.products.accommodation-categories.index')
        ]);
    }

    /**
     * @param AccommodationCategory $accommodation_category
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(AccommodationCategory $accommodation_category)
    {
        $this->authorize('delete', $accommodation_category);
        if($accommodation_category->hasResources()){
            return redirect()->back()->with('error','Category cannot be deleted as its attached to accommodation(s)');
        }

        $accommodation_category->delete();

        return redirect()->route('staff.settings.products.accommodation-categories.index')->withStatus('Category deleted!');
    }
}
