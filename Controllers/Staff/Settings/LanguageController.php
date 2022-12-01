<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LanguageRequest;
use App\Models\Language;
use App\Services\EbecasService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{

    protected EbecasService $ebecasService;
    function __construct(EbecasService $ebecasService){
        $this->ebecasService = $ebecasService;
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', Language::class);
        return view('settings.languages.index');
    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', Language::class);
        return Language::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', Language::class);
        return $this->edit(new Language());
    }

    /**
     * @param LanguageRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(LanguageRequest $request): JsonResponse
    {
        $this->authorize('create', Language::class);

        $data = $request->validated();

        Language::create($data);

        Session::flash('status' , 'Created successfully!');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.languages.index')
        ]);
    }

    /**
     * @param Language $language
     * @return View
     * @throws AuthorizationException
     */
    public function show(Language $language): View
    {
        $this->authorize('view', $language);

        return $this->edit($language);
    }

    /**
     * @param Language $language
     * @return View
     * @throws AuthorizationException
     */
    public function edit(Language $language): View
    {
        $this->authorize('update', $language);
        $languageList = $this->ebecasService->getAllLanguages();
        $languageList = $languageList['LanguageList'];
        return view('settings.languages.edit',compact('language','languageList'));
    }

    /**
     * @param LanguageRequest $request
     * @param Language $language
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(LanguageRequest $request, Language $language): JsonResponse
    {
        $this->authorize('update', $language);

        $data = $request->validated();

        $language->fill($data);
        $language->save();

        Session::flash('status' , 'Updated successfully!');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.languages.index')
        ]);
    }

    /**
     * @param Language $language
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Language $language): RedirectResponse
    {
        $this->authorize('delete', $language);
        if($language->hasResources()){
            return redirect()->back()->with('error','Language cannot be deleted as its attached to multiple application(s)');
        }

        $language->delete();

        return redirect()->route('staff.settings.languages.index')->withStatus('Language deleted!');
    }
}
