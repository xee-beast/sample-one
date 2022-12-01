<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CalendarCategoryRequest;
use App\Models\CalendarCategory;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarCategoryController extends Controller
{
    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', CalendarCategory::class);
        return view('settings.calendar-categories.index');
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', CalendarCategory::class);
        return CalendarCategory::getDataTable();
    }

    public function create()
    {
        $this->authorize('create', CalendarCategory::class);

        return $this->edit(new CalendarCategory());
    }

    public function store(CalendarCategoryRequest $request)
    {
        $this->authorize('create', CalendarCategory::class);

        $data = $request->validated();
        $data['description'] = $data['description'] ?? '';
        $category = CalendarCategory::create($data);

        return redirect()->route('staff.settings.dates.categories.index')->withStatus('Category created!');
    }

    public function show(CalendarCategory $category)
    {
        $this->authorize('view', $category);

        return $this->edit($category);
    }

    public function edit(CalendarCategory $category)
    {
        $this->authorize('update', $category);

        return view('settings.calendar-categories.edit',compact('category'));
    }

    public function update(CalendarCategoryRequest $request, CalendarCategory $category)
    {
        $this->authorize('update', $category);

        $data = $request->validated();
        $data['description'] = $data['description'] ?? '';
        $category->fill($data);
        $category->save();

        return redirect()->route('staff.settings.dates.categories.index')->withStatus('Category updated!');

    }

    public function destroy(CalendarCategory $category)
    {
        $this->authorize('delete', $category);
        if($category->hasResources()){
            return redirect()->back()->with('error','Category cannot be deleted as its attached to multiple program(s)');
        }

        $category->delete();

        return redirect()->route('staff.settings.dates.categories.index')->withStatus('Category deleted!');
    }
}
