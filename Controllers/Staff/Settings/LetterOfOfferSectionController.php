<?php

namespace App\Http\Controllers\Staff\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LetterOfOfferSectionRequest;
use App\Models\LetterOfOfferSection;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class LetterOfOfferSectionController extends Controller
{

    // todo : remove after testing
    public function testLof(){
        $data = ["HTML ONE GOES HERE" , "page_break", "HTML TWO GOES HERE", "page_break", "HTML THREE GOES HERE"];
        $pdf = Pdf::loadView('layouts.letter-of-offer.main', array('data' => $data));
        return $pdf->download('lof.pdf');
    }
    /**
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', LetterOfOfferSection::class);
        return view('settings.letter-of-offer.section.index');
    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', LetterOfOfferSection::class);
        return LetterOfOfferSection::getDataTable();
    }

    /**
     * @param LetterOfOfferSection $section
     * @return View
     * @throws AuthorizationException
     */
    public function show(LetterOfOfferSection $section): View
    {
        $this->authorize('view', $section);

        return $this->edit($section);
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', LetterOfOfferSection::class);

        return $this->edit(new LetterOfOfferSection());
    }

    /**
     * @param LetterOfOfferSectionRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(LetterOfOfferSectionRequest $request): JsonResponse
    {
        $this->authorize('create', LetterOfOfferSection::class);

        $data = $request->validated();

        LetterOfOfferSection::create($data);

        Session::flash('status' , 'Record created successfully');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.offer-letters.sections.index')
        ]);
    }

    /**
     * @param LetterOfOfferSectionRequest $request
     * @param LetterOfOfferSection $section
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(LetterOfOfferSectionRequest $request, LetterOfOfferSection $section): JsonResponse
    {
        $this->authorize('update', $section);

        $data = $request->validated();

        $section->fill($data);
        $section->save();

        Session::flash('status' , 'Record updated successfully');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.offer-letters.sections.index')
        ]);
    }

    /**
     * @param LetterOfOfferSection $section
     * @return View
     * @throws AuthorizationException
     */
    public function edit(LetterOfOfferSection $section): View
    {
        $this->authorize('update', $section);
        return view('settings.letter-of-offer.section.edit',compact('section'));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(LetterOfOfferSection $section): RedirectResponse
    {
        $this->authorize('delete', $section);

        $section->delete();

        return redirect()->route('staff.settings.offer-letters.sections.index')->withStatus('Section deleted!');
    }
}
