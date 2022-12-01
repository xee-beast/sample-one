<?php


namespace App\Http\Controllers\Staff\Settings;


use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\FacultyInsuranceRequest;
use App\Http\Requests\Settings\FacultyPaymentMethodRequest;
use App\Http\Requests\Settings\FacultyProgramRequest;
use App\Http\Requests\Settings\FacultyRequest;
use App\Http\Requests\FacultyAccommodationRequest;
use App\Http\Requests\Settings\FacultyTransportationRequest;
use App\Models\Accommodation;
use App\Models\AccommodationFeeAddon;
use App\Models\AccommodationFeeService;
use App\Models\Calendar;
use App\Models\Faculty;
use App\Models\Insurance;
use App\Models\Location;
use App\Models\PackagedProgram;
use App\Models\PaymentMethod;
use App\Models\Program;
use App\Models\ProgramFeeService;
use App\Models\Transportation;
use App\Models\TransportationFeeAddon;
use App\Models\TransportationFeeService;
use App\Services\EbecasService;
use App\Services\FacultyService;
use App\Services\EbecasProductsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class FacultyController extends Controller
{
    public EbecasProductsService $ebecasProductsService;
    protected EbecasService $ebecasService;
    protected FacultyService $facultyService;

    function __construct(EbecasService $ebecasService, FacultyService $facultyService,
    EbecasProductsService $ebecasProductsService){
        $this->ebecasService = $ebecasService;
        $this->facultyService = $facultyService;
        $this->ebecasProductsService = $ebecasProductsService;
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAll', Faculty::class);
        $locations = Location::whereEnabled(true)->get();

        return view('settings.faculties.index', compact('locations'));
    }


    /**
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAll', Faculty::class);
        return Faculty::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', Faculty::class);

        return $this->edit(new Faculty());
    }

    /**
     * @param FacultyRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(FacultyRequest $request): JsonResponse
    {
        $this->authorize('create', Faculty::class);

        $data = $request->validated();

        Faculty::create($data);

        Session::flash('status' , 'Faculty created!');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.faculties.index')
        ]);
    }

    /**
     * @param Faculty $faculty
     * @return View
     * @throws AuthorizationException
     */
    public function show(Faculty $faculty): View
    {
        $this->authorize('view', $faculty);

        return $this->edit($faculty);
    }

    /**
     * @param Faculty $faculty
     * @return View
     * @throws AuthorizationException
     */
    public function showDetailTab(Faculty $faculty): View
    {
        $this->authorize('view', $faculty);

        $locations = Location::whereEnabled(true)->get();

        return view('settings.faculties.components.detail',compact('faculty', 'locations'));
    }

    /**
     * @param Faculty $faculty
     * @return View
     * @throws AuthorizationException
     */
    public function showProgramTab(Faculty $faculty): View
    {
        $this->authorize('view', $faculty);

        $programs = Program::whereEnabled(1)->get();
        $services = ProgramFeeService::whereEnabled(1)->get();
        $calendars = Calendar::whereEnabled(true)->get();
        $ebecasCourseProducts = $this->facultyService->getSortedEbecasProductsByFacultyAndType($faculty, config('constants.ebecas_product_types.course'));
        $ebecasOtherProducts = $this->facultyService->getSortedEbecasProductsByFacultyAndType($faculty, config('constants.ebecas_product_types.other'));

        $selectedProgramsArray = $this->facultyService->getProgramsArray($faculty->programs()->get());
        $selectedServicesArray = $this->facultyService->getServicesArray($faculty->programServices()->get());

        return view('settings.faculties.components.program',compact('faculty', 'calendars',
            'programs', 'selectedProgramsArray', 'ebecasCourseProducts', 'ebecasOtherProducts', 'services', 'selectedServicesArray'));
    }

    /**
     * @param Faculty $faculty
     * @return View
     * @throws AuthorizationException
     */
    public function showAccommodationTab(Faculty $faculty): View
    {
        $this->authorize('view', $faculty);

        $accommodationProducts = $this->facultyService->getSortedEbecasProductsByFacultyAndType($faculty, config('constants.ebecas_product_types.accommodation'));
        $arrangementProducts = $this->facultyService->getSortedEbecasProductsByFacultyAndType($faculty, config('constants.ebecas_product_types.accommodation_arrangement'));
        $otherProducts = $accommodationProducts;

        $accommodations = Accommodation::whereEnabled(1)->get();
        $feeAddons = AccommodationFeeAddon::whereEnabled(1)->get();

        $otherProducts =array_merge($arrangementProducts, $otherProducts);
        $accommodationServices = AccommodationFeeService::get();

        $selectedAccommodations = $this->facultyService->getAccommodationsArray($faculty->accommodations()->get());
        $selectedAccommodationServices = $this->facultyService->getAccommodationServicesArray($faculty->accommodationServices()->get());
        $selectedAddons = $this->facultyService->getAccommodationAddonsArray($faculty->accommodationAddons()->get());

        return view('settings.faculties.components.accommodation', compact('selectedAccommodationServices','faculty', 'accommodationProducts', 'otherProducts','selectedAccommodations','accommodations','accommodationServices','feeAddons','selectedAddons'));
    }

    /**
     * @param Faculty $faculty
     * @return View
     * @throws AuthorizationException
     */
    public function showTransportationTab(Faculty $faculty): View
    {
        $this->authorize('view', $faculty);

        $transportations = Transportation::where('origin',$faculty->location_id)->orWhere('destination',$faculty->location_id)->get();
        $transportationServices = TransportationFeeService::whereEnabled(1)->get();
        $transportationAddons = TransportationFeeAddon::whereEnabled(1)->get();

        $selectedTransportations = $this->facultyService->getTransportationsArray($faculty->transportations()->get());
        $selectedTransportationServices = $this->facultyService->getTransportationServicesArray($faculty->transportationServices()->get());
        $selectedAddons = $this->facultyService->getTransportationAddonsArray($faculty->transportationAddons()->get());

        $transportationProducts = $this->facultyService->getSortedEbecasProductsByFacultyAndType($faculty, config('constants.ebecas_product_types.airport'));
        $otherProducts = $transportationProducts;

        return view('settings.faculties.components.transportation', compact('selectedTransportationServices','faculty', 'transportationProducts', 'otherProducts','selectedTransportations','transportations','transportationServices','transportationAddons','selectedAddons'));
    }

    /**
     * @param Faculty $faculty
     * @return View
     * @throws AuthorizationException
     */
    public function showInsuranceTab(Faculty $faculty): View
    {
        $this->authorize('view', $faculty);

        $insuranceProducts = $this->facultyService->getSortedEbecasProductsByFacultyAndType($faculty, config('constants.ebecas_product_types.insurance'));
        $insurances = Insurance::orderBy('type')->with('fees')->get();
        $selectedInsurances = $this->facultyService->getInsuranceArray($faculty->insurances()->get());
        return view('settings.faculties.components.insurance', compact('faculty','insuranceProducts','insurances','selectedInsurances'));
    }

    /**
     * @param Faculty $faculty
     * @return View
     * @throws AuthorizationException
     */
    public function showPaymentMethodTab(Faculty $faculty): View
    {
        $this->authorize('view', $faculty);

        $products = $this->facultyService->getSortedEbecasProductsByFacultyAndType($faculty, config('constants.ebecas_product_types.other'));
        $paymentMethods = PaymentMethod::whereEnabled(true)->orderBy('name')->get();
        $selectedPaymentMethods = $this->facultyService->getPaymentMethodArray($faculty->paymentMethods()->get());
        return view('settings.faculties.components.payment-method', compact('faculty','paymentMethods','products','selectedPaymentMethods'));
    }

    /**
     * @param Faculty $faculty
     * @return View
     * @throws AuthorizationException
     */
    public function edit(Faculty $faculty): View
    {
        $this->authorize('update', $faculty);

        $locations = Location::whereEnabled(true)->get();

        return view('settings.faculties.edit',compact('faculty', 'locations'));
    }


    /**
     * @param FacultyRequest $request
     * @param Faculty $faculty
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(FacultyRequest $request, Faculty $faculty): JsonResponse
    {
        $this->authorize('update', $faculty);

        $data = $request->validated();

        $faculty->fill($data);
        $faculty->save();

        Session::flash('status' , 'Faculty updated!');
        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.faculties.index')
        ]);
    }

    /**
     * @param FacultyProgramRequest $request
     * @param Faculty $faculty
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateFacultyProgram(FacultyProgramRequest $request, Faculty $faculty): JsonResponse
    {
        $this->authorize('update', $faculty);

        $requestPrograms = array_column($request->get('programs'),'program_id');
        $facultyPrograms = $faculty->programs->pluck('id')->toArray();
        if(count($requestPrograms) < count($facultyPrograms)){
            $programIdsDeleted = array_diff($facultyPrograms, $requestPrograms);
            $record = DB::table('packaged_faculty_program')->whereIn('program_id', $programIdsDeleted)
                ->whereFacultyId($faculty->id)->count();
            if ($record){
                return response()->json([
                    'success'=> false,
                    'message' => 'Cannot delete the program(s) as they are attached to a packed program. Remove it from the packaged program and try again.'
                ]);
            }
        }

        $faculty->programs()->sync(
            $this->facultyService->getProgramSyncArray($request->get('programs'))
        );
        $faculty->programServices()->sync(
            $this->facultyService->getServiceSyncArray($request->get('services'))
        );

        $this->facultyService->detachProductPrograms($faculty);

        session()->flash('status' , 'Faculty updated!');
        return response()->json([
            'success'=> true,
            'message' => 'Faculty updated!',
            'redirect_route' => route('staff.settings.faculties.index')
        ]);

    }

     /**
     * @param FacultyAccommodationRequest $request
     * @param Faculty $faculty
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateFacultyAccommodation(FacultyAccommodationRequest $request, Faculty $faculty): JsonResponse
    {

        $this->authorize('update', $faculty);

        $faculty->accommodations()->sync(
            $this->facultyService->getAccommodationSyncArray($request->get('accommodations'))
        );
        $faculty->accommodationServices()->sync(
            $this->facultyService->getAccommodationServiceSyncArray($request->get('services'))
        );

        $faculty->accommodationAddons()->sync(
            $this->facultyService->getAccommodationAddonsSyncArray($request->get('addons'))
        );

        session()->flash('status' , 'Faculty updated!');
        return response()->json([
            'success'=> true,
            'message' => 'Faculty updated!',
            'redirect_route' => route('staff.settings.faculties.index')
        ]);

    }

    /**
     * @param FacultyTransportationRequest $request
     * @param Faculty $faculty
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateFacultyTransportation(FacultyTransportationRequest $request, Faculty $faculty): JsonResponse
    {

        $this->authorize('update', $faculty);

        $faculty->transportations()->sync(
            $this->facultyService->getTransportationSyncArray($request->get('transportations'))
        );

        $faculty->transportationServices()->sync(
            $this->facultyService->getTransportationServiceSyncArray($request->get('services'))
        );

        $faculty->transportationAddons()->sync(
            $this->facultyService->getTransportationAddonsSyncArray($request->get('addons'))
        );

        session()->flash('status' , 'Faculty updated!');
        return response()->json([
            'success'=> true,
            'message' => 'Faculty updated!',
            'redirect_route' => route('staff.settings.faculties.index')
        ]);

    }

    /**
     * @param FacultyInsuranceRequest $request
     * @param Faculty $faculty
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateInsurance(FacultyInsuranceRequest $request, Faculty $faculty): JsonResponse
    {
        $this->authorize('update', $faculty);
        DB::table('faculty_insurance')->whereFacultyId($faculty->id)->whereInsuranceId($request->get('insurance_id'))->delete();
        $this->facultyService->getInsuranceSyncArray($request->all(), $faculty);

        session()->flash('status' , 'Faculty updated!');
        return response()->json([
            'success'=> true,
            'message' => 'Faculty updated!',
            'redirect_route' => route('staff.settings.faculties.index')
        ]);

    }

    /**
     * @param FacultyPaymentMethodRequest $request
     * @param Faculty $faculty
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updatePaymentMethod(FacultyPaymentMethodRequest $request, Faculty $faculty): JsonResponse
    {
        $this->authorize('update', $faculty);
        $faculty->paymentMethods()->sync(
            $this->facultyService->getPaymentMethodsSyncArray($request->get('payment_methods'))
        );

        session()->flash('status' , 'Faculty updated!');
        return response()->json([
            'success'=> true,
            'message' => 'Faculty updated!',
            'redirect_route' => route('staff.settings.faculties.index')
        ]);

    }

    /**
     * @param Faculty $faculty
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Faculty $faculty): RedirectResponse
    {
        $this->authorize('delete', $faculty);
        if($faculty->hasResources()){
            return redirect()->back()->with('error','Faculty cannot be deleted as its attached to multiple resource(s)');
        }

        $faculty->programs()->detach();
        $faculty->programServices()->detach();
        $faculty->programServices()->detach();
        $faculty->accommodations()->detach();
        $faculty->accommodationServices()->detach();
        $faculty->accommodationAddons()->detach();
        $faculty->transportations()->detach();
        $faculty->transportationServices()->detach();
        $faculty->transportationAddons()->detach();
        $faculty->insurances()->detach();
        $faculty->paymentMethods()->detach();
        $faculty->delete();

        return redirect()->route('staff.settings.faculties.index')->withStatus('Faculty deleted!');
    }
}
