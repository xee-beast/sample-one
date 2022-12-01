<?php


namespace App\Http\Controllers\Staff\Settings;


use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LetterOfOfferTemplateRequest;
use App\Models\Accommodation;
use App\Models\Faculty;
use App\Models\LetterOfOfferSection;
use App\Models\LetterOfOfferTemplate;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class LetterOfOfferTemplateController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', LetterOfOfferTemplate::class);
        return view('settings.letter-of-offer.template.index');
    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', LetterOfOfferTemplate::class);
        return LetterOfOfferTemplate::getDataTable();
    }

    /**
     * @param LetterOfOfferTemplate $template
     * @return View
     * @throws AuthorizationException
     */
    public function show(LetterOfOfferTemplate $template): View
    {
        $this->authorize('view', $template);

        return $this->edit($template);
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', LetterOfOfferTemplate::class);

        return $this->edit(new LetterOfOfferTemplate());
    }

    /**
     * @param LetterOfOfferTemplateRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(LetterOfOfferTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', LetterOfOfferTemplate::class);

        $data = $request->validated();

        $template = LetterOfOfferTemplate::create($data);

        $sections = $request->get('sections');
        foreach($sections as $section){
            $list = [
                'section_name' => $section['name'],
                'section_id' => $section['section'],
            ];
            $template->sections()->attach($section['type'], $list);
        }

        Session::flash('status' , 'Record created successfully');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.offer-letters.templates.index')
        ]);
    }

    /**
     * @param LetterOfOfferTemplateRequest $request
     * @param LetterOfOfferTemplate $template
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(LetterOfOfferTemplateRequest $request, LetterOfOfferTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);
        $template->update($request->except(['sections']));

        $sections = $request->get('sections');
        $template->sections()->detach();
        foreach($sections as $section){
            $list = [
                'section_name' => $section['name'],
                'section_id' => $section['section'],
            ];
            $template->sections()->attach($section['type'], $list);
        }

        Session::flash('status' , 'Record updated successfully');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.offer-letters.templates.index')
        ]);
    }

    /**
     * @param LetterOfOfferTemplate $template
     * @return View
     * @throws AuthorizationException
     */
    public function edit(LetterOfOfferTemplate $template): View
    {
        $this->authorize('update', $template);
        $templateSections = DB::table('letter_of_offer_template_sections')->whereTemplateId($template->id)->orderBy('order', 'ASC')->get()->toArray();
        $sections = LetterOfOfferSection::whereEnabled(true)->select('id','name')->orderBy('name')->get();
        $faculties = Faculty::whereEnabled(true)->select('id','name')->orderBy('name')->get();
        return view('settings.letter-of-offer.template.edit',compact('template', 'sections', 'faculties', 'templateSections'));
    }

    /**
     * @param LetterOfOfferTemplate $template
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(LetterOfOfferTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return redirect()->route('staff.settings.offer-letters.templates.index')->withStatus('Section deleted!');
    }
}
