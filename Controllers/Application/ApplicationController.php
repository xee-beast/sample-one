<?php


namespace App\Http\Controllers\Application;


use App\Events\ApplicationFormStatusChanged;
use App\Events\ApplicationSubmitted;
use App\Events\ApplicationTimeLine;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\ApplicationDetailRequest;
use App\Http\Requests\Application\ApplicationInsuranceRequest;
use App\Http\Requests\Application\ApplicationPaymentRequest;
use App\Http\Requests\Application\ApplicationStatusUpdateRequest;
use App\Http\Requests\ApplicationDocumentRequest as RequestsApplicationDocumentRequest;
use App\Models\Accommodation;
use App\Models\AccommodationCategory;
use App\Models\Application;
use App\Models\ApplicationDetail;
use App\Models\ApplicationDocument;
use App\Models\ApplicationPayment;
use App\Models\Calendar;
use App\Models\Country;
use App\Models\Faculty;
use App\Models\Insurance;
use App\Models\Language;
use App\Models\Location;
use App\Models\PackagedProgram;
use App\Models\Program;
use App\Models\Transportation;
use App\Models\User;
use App\Models\Visa;
use App\Services\ApplicationOfferService;
use App\Services\ApplicationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Services\EbecasService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use phpDocumentor\Reflection\Types\Boolean;
use stdClass;

class ApplicationController extends Controller
{
    private EbecasService $ebecasService;
    protected ApplicationService $applicationService;
    protected ApplicationOfferService $applicationOfferService;
    protected ProductService $productService;
    /**
     * Instantiate a new ApplicationController instance.
     */
    public function __construct(EbecasService $ebecasService,
                                ApplicationService $applicationService,
                                ApplicationOfferService $applicationOfferService,
                                ProductService $productService)
    {
        $this->ebecasService = $ebecasService;
        $this->applicationService = $applicationService;
        $this->applicationOfferService = $applicationOfferService;
        $this->productService = $productService;
    }


    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('viewAny', Application::class);
        $user = auth()->user();
        $userTypes = array_values(config('constants.user_types'));

        $canCreate = $user->can('create', Application::class);

        return view('applications.index', compact('userTypes', 'canCreate'));
    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', Application::class);
        return Application::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', Application::class);

        return $this->edit(new Application());
    }

    /**
     * @param Application $application
     * @return View|RedirectResponse
     * @throws AuthorizationException
     */
    public function show(Application $application): View|RedirectResponse
    {
        $this->authorize('view', $application);

        $user = auth()->user();

        //If user is not staff and application is in draft, redirect to the edit route
        if(!$user->isStaff() and $application->isStatusDraft())
            return redirect()->route('applications.edit',$application);

        $userPermissions = [
            'edit_application' => $user->can('update', $application),
            'edit_application_properties' => $user->can('updateProperties',$application)
        ];

        $userTypes = array_values(config('constants.user_types'));

        $application = $application->load(['createdBy', 'detail', 'payment.paymentMethod', 'detail.nationality', 'detail.birthCountry', 'detail.currentResidenceCountry',
            'detail.visaApplyingFor', 'detail.visaType', 'detail.firstLanguage', 'detail.visaApplicationLocation', 'programs', 'programs.services', 'programServices',
            'accommodations', 'accommodations.addons', 'accommodations.services', 'accommodations.category', 'transportations', 'transportations.addons',
            'transportations.services','documents', 'submittedBy', 'applicationOwner']);

        $this->applicationService->setApplicationSummaryData($application);

        return view('applications.show',compact('application', 'userPermissions', 'userTypes'));
    }

    /**
     * @param Application $application
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getApplicationData(Application $application): JsonResponse
    {
        $this->authorize('view', $application);

        $application = $application->load(['createdBy', 'detail', 'payment.paymentMethod', 'detail.nationality', 'detail.birthCountry', 'detail.currentResidenceCountry',
            'detail.visaApplyingFor', 'detail.visaType', 'detail.firstLanguage', 'detail.visaApplicationLocation', 'programs', 'programs.services',
            'accommodations', 'accommodations.addons', 'accommodations.services', 'accommodations.category','documents', 'applicationOwner']);

        $this->applicationService->setApplicationSummaryData($application);

        return response()->json([
           'data' => $application,
           'statues' => config('constants.application_form.status')
        ]);

    }

    /**
     * @param ApplicationDocument $applicationDocument
     * @return
     * @throws AuthorizationException
     */
    public function getApplicationDocument(ApplicationDocument $applicationDocument): mixed
    {
        $application  = $applicationDocument->application;

        $this->authorize('view', $application);

        $headers = [
            'Content-Disposition' => "attachment; filename=$application->id - $applicationDocument->type - $applicationDocument->name",
        ];

        return Response::make(Storage::disk('s3')->get("$applicationDocument->path"), 200, $headers);
    }

    /**
     * @param Application $application
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function submitApplication(Application $application, RequestsApplicationDocumentRequest $request): JsonResponse
    {
        $this->authorize('update', $application);

        try{
            DB::beginTransaction();

            if(!$this->applicationService->hasPassportType($request,$application)){
                return response()->json([
                    'success' => false,
                    'message' => 'Passport document is required.',
                 ]);
            }

            if(request()->has('document')){
               $response  = $this->uploadDocuments($application, $request);
               if($response === false){
                    return response()->json([
                        'success' => false,
                        'message' => 'An error occured during document upload. Try again!'
                    ]);
               }
            }

            $oldStatus = $application->status;

            if ($application->status === 'draft') {
                $application->submitted_by = auth()->user()->id;
                $application->owner = auth()->user()->id;
                $application->submitted_on = Carbon::now();
                $route = route('applications.submit.success', $application);
                $application->status = 'submitted';
                ApplicationTimeLine::dispatch([
                    'application' => $application,
                    'user' => auth()->user(),
                    'event_type' => 'application_submitted'
                ]);
                ApplicationSubmitted::dispatch(
                    $application,
                    $oldStatus
                );
            }else{
                $route = route('applications.show', $application);
                ApplicationTimeLine::dispatch([
                    'application' => $application,
                    'user' => auth()->user(),
                    'event_type' => 'application_details_updated'
                ]);
            }

            if($request->has('comments')){
                $comments = $request->get('comments');
                if(empty($comments) || $comments==='null'){
                    $comments = null;
                }
                $application->comments = $comments;
            }
            $application->save();

            $application->refresh();
            if($oldStatus!==$application->status){
                ApplicationTimeLine::dispatch([
                    'application' => $application,
                    'old_status' => $oldStatus,
                    'new_status' => 'submitted',
                    'user' => auth()->user(),
                    'event_type' => 'status_changed'
                ]);
            }

            Db::commit();
            return response()->json([
                'success' => true,
                'redirect_url' => $route,
             ]);

        }catch(Exception $exp){
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'server_error' => $exp->getMessage()
             ]);
        }
    }

    /**
     * @param $application
     * @param $request
     * @return mixed
     */
    public function uploadDocuments($application, $request)
    {
        $data =  $request->get('document');
        $documents =  $request->file('document');

        for($i = 0; $i < count($data); $i++){
            $document = $data[$i];
            $file = $documents[$i]['file'];
            $name = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $uploadName = $application->id .'_'. time() .'_'. uniqid().'.'.$extension;
            $filePath = "application_documents/$uploadName";
            $response = Storage::disk('s3')->put($filePath, file_get_contents($file));
            if($response === false){
                return $response;
            }
            $document = $application->documents()->create([
                'type' =>$document['type'],
                'name' => $name,
                'path' => $filePath,
                'user_id' => Auth::id()
            ]);

            ApplicationTimeLine::dispatch([
                'application' => $application,
                'document' => $document,
                'user' => auth()->user(),
                'event_type' => 'document_created'
            ]);
        }
        return true;
    }

    /**
     * @param Application $application
     * @return View
     * @throws AuthorizationException
     */
    public function showApplicationSubmitSuccess(Application $application): View
    {
        $this->authorize('view', $application);

        return view('applications.submit-success', compact('application'));
    }

    /**
     * @param ApplicationStatusUpdateRequest $request
     * @param Application $application
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateApplicationStatus(ApplicationStatusUpdateRequest $request, Application $application): JsonResponse
    {
        try {
            $this->authorize('updateProperties', $application);

            $oldStatus = $application->status;
            $newStatus = $request->get('status');
            $application->status = $newStatus;

            if ( $oldStatus === 'draft' && $newStatus === 'submitted' ) {
                $application->submitted_by = auth()->user()->id;
                $application->submitted_on = Carbon::now();
            }

            $application->save();

            ApplicationFormStatusChanged::dispatch(
                $application,
                $oldStatus,
                $newStatus,
                auth()->user()
            );

            ApplicationTimeLine::dispatch([
                'application' => $application,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user' => auth()->user(),
                'event_type' => 'status_changed'
            ]);


            return response()->json([
                'success'=> true,
                'message' => 'Application updated!',
                'data' => $application
            ]);
        }
        catch (Exception $e){
            return response()->json([
                'success'=> false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param Application $application
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function createStudentInEbecas(Application $application): JsonResponse
    {
        $this->authorize('updateProperties', $application);

        if($application->applicationOwner->isAgent() && ! $application->applicationOwner->agent){
            return response()->json([
                'success'=> false,
                'message' => 'Unable to perform the task: The owner of the application does not have an agent associated.',
                'data' => $application
            ]);
        }

        $response = $this->applicationService->createStudentInEbecas($application->id);
        if ($response['success'] && $response['data'] ){
            $student_id = $response['data'];

            $studentRecord  = $this->applicationService->getStudentNumberFromEbecas($student_id);
            $application->student_id = $student_id;
            if ($studentRecord['data']){
                $application->student_number =$studentRecord['data']['StudentNo'];
            }
            $application->save();
            ApplicationTimeLine::dispatch([
                'application' => $application,
                'user' => auth()->user(),
                'event_type' => 'student_created'
            ]);
        }

        return response()->json([
            'success'=> $response['success'],
            'message' => empty($response['message']) ?
                               $response['success']? "Student created!" : "Try again later" :
                               $response['message'],
            'data' => $application
        ]);
    }

    /**
     * @param Application $application
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function createOfferInEbecas(Application $application): JsonResponse
    {
        $this->authorize('updateProperties', $application);


        if($application->offer_id){
            return response()->json([
                'success'=> false,
                'message' => 'The application already has an offer linked.',
                'data' => $application
            ]);
        }

        if($application->isStatusDraft()){
            return response()->json([
                'success'=> false,
                'message' => 'The application is in a draft status. Submit the application to create an offer.',
                'data' => $application
            ]);
        }

        if($application->applicationOwner->isAgent() && ! $application->applicationOwner->agent){
            return response()->json([
                'success'=> false,
                'message' => 'Unable to perform the task: The owner of the application does not have an agent associated.',
                'data' => $application
            ]);
        }


        $response = $this->applicationOfferService->createOfferInEbecas($application);

        if ($response['success'] and $response['data'] ){
            $application->offer_id = $response['data'];
            $application->save();

            ApplicationTimeLine::dispatch([
                'application' => $application,
                'user' => auth()->user(),
                'event_type' => 'offer_created'
            ]);
        }


        return response()->json([
            'success'=> $response['success'],
            'message' => empty($response['message']) ?
                $response['success']? "Offer created!" : "An error occurred! Try again later." :
                $response['message'],
            'data' => $application
        ]);
    }


    /**
     * Get the offer details from Ebecas given the offer id.
     * @param Application $application
     * @return JsonResponse
     */
    public function getOfferDetails(Application $application): JsonResponse
    {
        if(!$application->offer_id){
            return response()->json([
                'success'=> true,
                'data'=> null
            ]);
        }

         $offerData = $this->applicationOfferService->getOfferFromEbecas($application->offer_id);

        return response()->json([
            'success'=> true,
            'data'=> $offerData
        ]);
    }


    /**
     * sync the application status from offer details from Ebecas.
     * @param Application $application
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function syncOfferDetails(Application $application): JsonResponse{
        $this->authorize('updateProperties', $application);

        if(!$application->offer_id)
            return response()->json([
                'success'=>false,
                'message'=>'There is not an offer linked to the application',
                'data'=>[]
            ]);

        $response = $this->applicationOfferService->syncOffer($application);
        if(!$response['success']){
            return response()->json($response['offerData']);
        }

        return response()->json([
            'success'=> true,
            'message' => 'Offer Sync\'d successfully!',
            'data'=>$response['offerData']
        ]);
    }



    /**
     * @param Application $application
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeOfferFromApplication(Application $application): JsonResponse
    {
        $this->authorize('updateProperties', $application);

        $offer_id = $application->offer_id;
        $application->offer_id = null;
        $application->save();

        ApplicationTimeLine::dispatch([
            'application' => $application,
            'user' => auth()->user(),
            'offer_id' => $offer_id,
            'event_type' => 'offer_removed'
        ]);

        return response()->json([
            'success'=> true,
            'message' => 'Offer removed from the application.',
            'data' => $application
        ]);
    }

    /**
     * @param Application $application
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeStudentFromApplication(Application $application): JsonResponse
    {

        $this->authorize('updateProperties', $application);

        if ( $application->offer_id ){
            return response()->json([
                'success'=> false,
                'message' => 'There is an offer linked to this application. Unlink it first and try again...',
            ]);
        }

        $student_number = $application->student_number;
        $application->student_number = null;
        $application->student_id = null;
        $application->offer_id = null;
        $application->save();
        ApplicationTimeLine::dispatch([
            'application' => $application,
            'user' => auth()->user(),
            'old_student_id' => $student_number,
            'event_type' => 'student_removed'
        ]);

        return response()->json([
            'success'=> true,
            'message' => 'Student removed from the application!',
            'data' => $application
        ]);
    }

    /**
     * @param Application $application
     * @return View|RedirectResponse
     * @throws AuthorizationException
     */
    public function edit(Application $application): View|RedirectResponse
    {
        $this->authorize('update', $application);

        //If application exists, and it is not draft, redirect to the show as the user should not be able to edit it
        if($application->exists and !auth()->user()->isStaff() and !$application->isStatusDraft())
            return redirect()->route('applications.show',$application);


        $countries = Country::whereEnabled(true)->orderBy('name')->get();
        $visas = Visa::whereEnabled(true)->orderBy('name')->get();
        $languages = Language::whereEnabled(true)->orderBy('name')->get();
        $insurances = Insurance::orderBy('id')->get();

        if ( $application->exists ) {
            $applicationDetail = ApplicationDetail::whereApplicationId($application->id)->first();
            $application = $application->load(['createdBy', 'detail', 'detail.nationality', 'detail.birthCountry', 'detail.currentResidenceCountry',
                'detail.visaApplyingFor', 'detail.visaType', 'detail.firstLanguage', 'detail.visaApplicationLocation', 'applicationOwner']);
        }else{
            $applicationDetail = new ApplicationDetail();
        }

        $applicationPrograms = $this->applicationService->getApplicationProgramsData($application, true);
        $applicationServices = $this->applicationService->getApplicationProgramServices($application);
        $applicationInsurances = $this->applicationService->getApplicationInsurancesData($application);
        $applicationAccommodation = $this->applicationService->getApplicationAccommodationsData($application);
        $applicationAccommodationServices = $this->applicationService->getApplicationAccommodationServices($application);
        $applicationAccommodationAddons = $this->applicationService->getApplicationAccommodationAddons($application);
        $applicationTransportation = $this->applicationService->getApplicationTransportationData($application);
        $applicationTransportationServices = $this->applicationService->getApplicationTransportationServices($application);
        $applicationTransportationAddons = $this->applicationService->getApplicationTransportationAddons($application);

        $documentTypes = config('constants.application_form.documents');

        return view('applications.edit',compact('application', 'countries', 'visas', 'applicationDetail', 'languages','applicationPrograms',
            'applicationServices','insurances','applicationAccommodation','applicationAccommodationServices','applicationAccommodationAddons', 'applicationInsurances',
            'applicationTransportation','applicationTransportationServices','applicationTransportationAddons','documentTypes'));
    }

    /**
     * @param ApplicationPaymentRequest $request
     * @param Application $application
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeApplicationPayment(ApplicationPaymentRequest $request, Application $application): JsonResponse
    {
        $this->authorize('update', $application);
        $data = $request->all();
        $clonedData = $data;
        $clonedData['app_amounts'] = $this->applicationService->getApplicationAmountWithTax($application->id);
        $data['default_amount'] = $this->productService->calculateFee($clonedData, config('constants.imagine_product_types.payment_method'), $application->id);
        $data['default_tax'] = $this->productService->calculateTax($data['default_amount'], $data['payment_method_id'], config('constants.imagine_product_types.payment_method'), $application->id);
        $data['amount'] = $data['default_amount'];
        $data['tax'] = $data['default_tax'];
        $applicationPaymentMethod = ApplicationPayment::create($data);

        return response()->json([
            'success'=> true,
            'message' => 'Application updated!',
            'data' => $applicationPaymentMethod
        ]);
    }


    /**
     * @param ApplicationPaymentRequest $request
     * @param Application $application
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateApplicationPayment(ApplicationPaymentRequest $request, Application $application): JsonResponse
    {
        $this->authorize('update', $application);
        $data = $request->all();
        $clonedData = $data;
        $clonedData['app_amounts'] = $this->applicationService->getApplicationAmountWithTax($application->id);
        $data['default_amount'] = $this->productService->calculateFee($clonedData, config('constants.imagine_product_types.payment_method'), $application->id);
        $data['default_tax'] = $this->productService->calculateTax($data['default_amount'], $data['payment_method_id'], config('constants.imagine_product_types.payment_method'), $application->id);
        $data['amount'] = $data['default_amount'];
        $data['tax'] = $data['default_tax'];


        $applicationPaymentMethod = $application->payment->fill($data);
        $applicationPaymentMethod->save();

        return response()->json([
            'success'=> true,
            'message' => 'Application update!',
            'data' => $applicationPaymentMethod
        ]);
    }

    /**
     * @param ApplicationDetailRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeApplicationWithPersonalDetails(ApplicationDetailRequest $request): JsonResponse
    {
        $this->authorize('create', Application::class);

        $application = Application::create([
            "status" => "draft",
            "created_by" => Auth::user()->id,
            "owner" => Auth::user()->id,
        ]);

        $request->merge(['application_id' => $application->id]);

        $request['dob'] = Carbon::parse($request->get('dob'))->format('Y-m-d');
        $request['current_visa_expiry'] = !is_null($request->get('current_visa_expiry')) ? Carbon::parse($request->get('current_visa_expiry'))->format('Y-m-d') : null;
        $applicationDetail = ApplicationDetail::create($request->all());

        ApplicationTimeLine::dispatch([
            'application' => $application,
            'user' => auth()->user(),
            'event_type' => 'application_created'
        ]);

        session()->flash('status' , 'Application created!');
        return response()->json([
            'success'=> true,
            'message' => 'Application created!',
            'data' => $applicationDetail
        ]);
    }

    /**
     * @param ApplicationDetailRequest $request
     * @param Application $application
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateApplicationPersonalDetail(ApplicationDetailRequest $request, Application $application): JsonResponse
    {
        $this->authorize('update', $application);
        $applicationDetail = $application->detail;
        $clearState = false;

        try {

            $request['dob'] = Carbon::parse($request->get('dob'))->format('Y-m-d');
            $request['current_visa_expiry'] = !is_null($request->get('current_visa_expiry')) ? Carbon::parse($request->get('current_visa_expiry'))->format('Y-m-d') : null;
            $applicationDetail->dob = $request['dob'];
            $applicationDetail->visa_applying_for = $request['visa_applying_for'];

            if($applicationDetail->isDirty('dob') || $applicationDetail->isDirty('visa_applying_for')){
                $clearState = true;
                $this->clearApplicationResources($application);
            }

            $applicationDetail->fill($request->all());
            $applicationDetail->save();
            $applicationDetail->load('visaApplyingFor');

            return response()->json([
                'success'=> true,
                'message' => 'Application updated!',
                'data' => $applicationDetail,
                'clearState' => $clearState
            ]);

        } catch(Exception $e){

            return response()->json([
                'success'=> false,
                'message' => $e->getMessage(),
                'clearState' => false
            ]);

        }
    }

    /**
     *  Remove application's resources if application dob or visa has been updated
     *  @param Application $application
     */
    public function clearApplicationResources($application){
        $application->insurances()->detach();
        $application->programs()->detach();
        $application->programServices()->detach();
        $application->accommodations()->detach();
        $application->accommodationServices()->detach();
        $application->accommodationAddons()->detach();
        $application->transportations()->detach();
        $application->transportationServices()->detach();
        $application->transportationAddons()->detach();
    }
    /**
     * @param ApplicationInsuranceRequest $request
     * @param Application $application
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateApplicationInsurance(ApplicationInsuranceRequest $request, Application $application): JsonResponse
    {
        $this->authorize('update', $application);

        $application->insurances()->detach();
        foreach($request->all() as $insurance){
            $list = [
                'faculty_id' => $insurance['faculty_id'],
                'start_date' => Carbon::parse($insurance['start_date']),
                'end_date' => Carbon::parse($insurance['end_date']),
                'duration' => $insurance['duration'],
                'default_amount' => $this->productService->calculateFee($insurance,config('constants.imagine_product_types.insurance'), $application->id)
            ];
            $list['amount'] = $list['default_amount'];
            $list['default_tax'] = $this->productService->calculateTax($list['default_amount'], $insurance['insurance_id'], config('constants.imagine_product_types.insurance'), $application->id);
            $list['tax'] = $this->productService->calculateTax($list['amount'], $insurance['insurance_id'], config('constants.imagine_product_types.insurance'), $application->id);
            $application->insurances()->attach($insurance['insurance_id'], $list);
        }

        // update application fees
        if ($application->payment){
            $this->applicationService->updateApplicationPaymentMethodFee($application->id, $application->payment->payment_method_id);
        }

        return response()->json([
            'success'=> true,
            'message' => 'Application updated!',
            'data' => $application->insurances
        ]);
    }

    /** Get Courses according to student selected criteria
     * @param Application $application
     * @return JsonResponse
     */
    public function getFacultyPrograms(Request $request, Application $application): JsonResponse
    {
        $applicationDetail = $application->detail;
        $age = Carbon::parse($applicationDetail->dob)->age;
        $visa  = $applicationDetail->visa_applying_for;
        $visa_length = $applicationDetail->visaApplyingFor->max_weeks;
        $search = $request->get('search');

        $locationList = Location::whereEnabled(true)->with(['faculties'=>function($faculty) use($age, $visa, $visa_length, $search){
            $faculty->whereEnabled(true)->with(['programs'=>function($program) use ($age, $visa, $visa_length, $search){
                $program->whereEnabled(true)->where(function($q1) use ($age){
                    $q1->where('age_restriction_enabled',0)->orWhere(function($q2) use ($age){
                        $q2->where('min_age','<=',$age)->where('max_age','>=',$age);
                    });
                })->where(function($q3) use($visa){
                    $q3->where('visa_restriction_enabled',0)->orWhere(function($q4) use ($visa){
                        $q4->whereHas('visas', function ($q5) use ($visa) {
                            $q5->where('id', $visa);
                        });
                    });
                })
                ->where(function($q6) use($visa_length){
                    $q6->where('length_restriction_enabled',0)->orWhere(function($q7) use ($visa_length){
                        $q7->where(function($q8) use ($visa_length){
                            $q8->where(function($q9) use ($visa_length){
                                $q9->where('min_length','<=',$visa_length)->where('max_length','<=',$visa_length);
                            })->orWhere(function($q10) use ($visa_length){
                                $q10->where('min_length','<=',$visa_length)->where('max_length','>=',$visa_length);
                            });
                        });
                    });
                })
                ->where(function($q11) use ($search){
                    $q11->whereHas('priceBook', function ($q12) use ($search){
                        $q12->where('expired', 0)->where('enabled',1);
                        if(!empty($search)){
                            $q12->where('programs.name', 'like', '%' . $search . '%');
                        }
                    });
                })->with('optionalServices');
            }])->whereHas('programs',function($program) use ($age, $visa, $visa_length, $search){
                $program->whereEnabled(true)->where(function($q1) use ($age){
                    $q1->where('age_restriction_enabled',0)->orWhere(function($q2) use ($age){
                        $q2->where('min_age','<=',$age)->where('max_age','>=',$age);
                    });
                })->where(function($q3) use($visa){
                    $q3->where('visa_restriction_enabled',0)->orWhere(function($q4) use ($visa){
                        $q4->whereHas('visas', function ($q5) use ($visa) {
                            $q5->where('id', $visa);
                        });
                    });
                })
                ->where(function($q6) use($visa_length){
                    $q6->where('length_restriction_enabled',0)->orWhere(function($q7) use ($visa_length){
                        $q7->where(function($q8) use ($visa_length){
                            $q8->where(function($q9) use ($visa_length){
                                $q9->where('min_length','<=',$visa_length)->where('max_length','<=',$visa_length);
                            })->orWhere(function($q10) use ($visa_length){
                                $q10->where('min_length','<=',$visa_length)->where('max_length','>=',$visa_length);
                            });
                        });
                    });
                })
                ->where(function($q11) use ($search){
                    $q11->whereHas('priceBook', function ($q12) use ($search) {
                        $q12->where('expired', 0)->where('enabled',1);
                        if(!empty($search)){
                            $q12->where('programs.name', 'like', '%' . $search . '%');
                        }
                    });
                });
            });
        }])
        ->whereHas('faculties',function($faculty) use ($age, $visa,$visa_length, $search){
            $faculty->whereEnabled(true)->whereHas('programs',function($program) use($age, $visa,$visa_length, $search){
                $program->whereEnabled(true)->where(function($q1) use ($age){
                    $q1->where('age_restriction_enabled',0)->orWhere(function($q2) use ($age){
                        $q2->where('min_age','<=',$age)->where('max_age','>=',$age);
                    });
                })->where(function($q3) use($visa){
                    $q3->where('visa_restriction_enabled',0)->orWhere(function($q4) use ($visa){
                        $q4->whereHas('visas', function ($q5) use ($visa) {
                            $q5->where('id', $visa);
                        });
                    });
                })
                ->where(function($q6) use($visa_length){
                    $q6->where('length_restriction_enabled',0)->orWhere(function($q7) use ($visa_length){
                        $q7->where(function($q8) use ($visa_length){
                            $q8->where(function($q9) use ($visa_length){
                                $q9->where('min_length','<=',$visa_length)->where('max_length','<=',$visa_length);
                            })->orWhere(function($q10) use ($visa_length){
                                $q10->where('min_length','<=',$visa_length)->where('max_length','>=',$visa_length);
                            });
                        });
                    });
                })
                ->where(function($q11) use ($search){
                    $q11->whereHas('priceBook', function ($q12) use ($search){
                        $q12->where('expired', 0)->where('enabled',1);
                        if(!empty($search)){
                            $q12->where('programs.name', 'like', '%' . $search . '%');
                        }
                    });
                });
            });
        })
        ->get()->toArray();

        $locations = array();
        foreach($locationList as $location){
            $faculties = array();
            foreach($location['faculties'] as $faculty){
                $programs = array();
                foreach($faculty['programs'] as $program){
                    $isCalendarEnabled = Calendar::find($program['pivot']['calendar_id'])->enabled;
                    if($isCalendarEnabled){
                        array_push($programs,$program);
                    }
                }
                $faculty['programs'] = $programs;
                array_push($faculties, $faculty);
            }
            $location['faculties'] = $faculties;
            array_push($locations, $location);
        }

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    /**
     * @param Application $application
     * @return JsonResponse
     */
    public function getPackagedPrograms(Application $application): JsonResponse
    {
        $locations = Location::whereEnabled(true)->get()->toArray();
        $applicationDetail = $application->detail;
        $age = Carbon::parse($applicationDetail->dob)->age;
        $visa  = $applicationDetail->visa_applying_for;
        $visa_length = $applicationDetail->visaApplyingFor->max_weeks;

        $packagedPrograms = PackagedProgram::whereEnabled(true)
            ->with(['programs'=>function($program) use ($age, $visa, $visa_length){
                $program->whereEnabled(true)->where(function($q1) use ($age){
                    $q1->where('age_restriction_enabled',0)->orWhere(function($q2) use ($age){
                        $q2->where('min_age','<=',$age)->where('max_age','>=',$age);
                    });
                })->where(function($q3) use($visa){
                    $q3->where('visa_restriction_enabled',0)->orWhere(function($q4) use ($visa){
                        $q4->whereHas('visas', function ($q5) use ($visa) {
                            $q5->where('id', $visa);
                        });
                    });
                })
                    ->where(function($q6) use($visa_length){
                        $q6->where('length_restriction_enabled',0)->orWhere(function($q7) use ($visa_length){
                            $q7->where(function($q8) use ($visa_length){
                                $q8->where(function($q9) use ($visa_length){
                                    $q9->where('min_length','<=',$visa_length)->where('max_length','<=',$visa_length);
                                })->orWhere(function($q10) use ($visa_length){
                                    $q10->where('min_length','<=',$visa_length)->where('max_length','>=',$visa_length);
                                });
                            });
                        });
                    })
                    ->where(function($q11){
                        $q11->whereHas('priceBook', function ($q12){
                            $q12->where('expired', 0)->where('enabled',1);
                        });
                    })->with('optionalServices');
            }])
            ->whereHas('programs',function($program) use ($age, $visa, $visa_length){
                $program->whereEnabled(true)->where(function($q1) use ($age){
                    $q1->where('age_restriction_enabled',0)->orWhere(function($q2) use ($age){
                        $q2->where('min_age','<=',$age)->where('max_age','>=',$age);
                    });
                })->where(function($q3) use($visa){
                    $q3->where('visa_restriction_enabled',0)->orWhere(function($q4) use ($visa){
                        $q4->whereHas('visas', function ($q5) use ($visa) {
                            $q5->where('id', $visa);
                        });
                    });
                })
                    ->where(function($q6) use($visa_length){
                        $q6->where('length_restriction_enabled',0)->orWhere(function($q7) use ($visa_length){
                            $q7->where(function($q8) use ($visa_length){
                                $q8->where(function($q9) use ($visa_length){
                                    $q9->where('min_length','<=',$visa_length)->where('max_length','<=',$visa_length);
                                })->orWhere(function($q10) use ($visa_length){
                                    $q10->where('min_length','<=',$visa_length)->where('max_length','>=',$visa_length);
                                });
                            });
                        });
                    })
                    ->where(function($q11){
                        $q11->whereHas('priceBook', function ($q12){
                            $q12->where('expired', 0)->where('enabled',1);
                        });
                    });
            })->get();

        foreach ($packagedPrograms as $packagedProgram){
            // no need to add package with no programs
            if (!count($packagedProgram->programs))
                continue;

            // checking if any programs were removed due to the validation, if so then we will discard the whole package
            if(count($packagedProgram->fresh()->programs)>(count($packagedProgram->programs))){
                continue;
            }
            $faculty = Faculty::with('location')->find($packagedProgram->programs->first()->pivot->faculty_id);
            foreach ($locations as $key => $location){
                if ($location['id'] === $faculty->location->id) {
                    $packagedProgramArr = $packagedProgram->toArray();
                    $locations[$key]['package_programs'][] = $packagedProgramArr;
                }
            }
        }

        foreach($locations as $key=>$location){ // remove locations that do not have any packaged programs
            if(!isset($location['package_programs'])){
                unset($locations[$key]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    /**
     * @param Application $application
     * @param $facultyId
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getFacultyPaymentMethods(Application $application, $facultyId): JsonResponse
    {
        $this->authorize('update', $application);

        $paymentPlans = $this->applicationService->paymentPlanByFaculty($facultyId);
        $paymentMethods = $this->applicationService->paymentMethodsByFaculty($facultyId);

        return response()->json([
           'success' => true,
           'has_payment_plan' => !is_null($paymentPlans),
           'payment_methods' => $paymentMethods
        ]);
    }

    /** Get calendar dates for program selected
     * @param Request $request
     * @param int $program
     * @return JsonResponse
     */
    public function getProgramCalendarDates(Request $request, $program): JsonResponse
    {
        $faculty = Faculty::where('id',$request->get('faculty_id'))->first();
        $programObj = $faculty->programs()->where('id',$program)->first();
        $calendarId = $programObj->pivot->calendar_id;
        $ebecasProductId = $programObj->pivot->ebecas_product_id;
        $calendar = Calendar::find($calendarId);
        $dates = $calendar->dates()->select('id','start_date','weeks')->whereDate('start_date','>',Carbon::today())->orderBy('start_date','asc')->get()->toArray();
        return response()->json([
            'success' => true,
            'dates' => $dates,
            'ebecas_product_id' => $ebecasProductId,
        ]);
    }

    /** Get calendar dates for program selected
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateEndDate(Request $request): JsonResponse
    {
        $facultyId = $request->get('faculty_id');
        $startDate = $request->get('start_date');
        $programId = $request->get('program_id');
        $length = $request->get('length');
        $response = $this->ebecasService->getFacultyEndDate($facultyId, $startDate ,  $length);
        if(is_array($response) && isset($response['EndDate'])){
            $endDateObj = Carbon::parse($response['EndDate']);
            return response()->json(['success' => true, 'data' => ['endDate'=>$endDateObj->format('Y-m-d'), 'formattedDate'=>$endDateObj->format('d/m/Y')]]);
        } else {
            return response()->json(['success' => false, 'data' => null]);
        }

    }

    /** Add/Update programs and program services against application
     * @param Request $request
     * @param application $application
     * @return JsonResponse
     */
    public function storeApplicationPrograms(Request $request , Application $application): JsonResponse
    {
       try {

            $validation = $this->validateProgram($request);

           if ($validation){
               return response()->json([
                   'success' => false,
                   'message' => $validation
               ]);
           }

            $clearState = false;
            DB::transaction(function () use ($request, $application,&$clearState) {
                $programs = $request->get('programs');
                $services = $request->get('services');

                $selectedEarliestDate = $programs[0]['start_date'];
                $savedEarliestProgram = $application->programs()->orderBy('start_date','asc')->first();
                if($savedEarliestProgram){
                    $savedEarliestDate = $savedEarliestProgram->pivot->start_date;
                    if($selectedEarliestDate !== $savedEarliestDate){
                        $clearState = true;
                        $this->clearApplicationResources($application);
                    }

                }

                $application->programs()->detach();
                $application->programServices()->detach();

                $this->attachProgramsToApplication($application, $programs, $services);

                foreach($services as $service){
                    $programPivotData = $application->programs()->wherePivot('program_id',$service['program_id'])
                                                ->wherePivot('faculty_id',$service['faculty_id'])
                                                ->wherePivot('start_date',$service['program_start_date'])
                                                ->first();
                    $service['length'] = $programPivotData->pivot->length;
                    $list = [
                            'program_start_date' => $service['program_start_date'],
                            'program_id' => $service['program_id'],
                            'faculty_id' => $service['faculty_id'],
                            'mandatory' => $service['mandatory'],
                            'default_amount' => $this->productService->calculateFee($service, config('constants.imagine_product_types.program_service'), $application->id)
                    ];
                    $list['amount'] = $list['default_amount'];
                    $list['default_tax'] = $this->productService->calculateTax($list['default_amount'], $service['service_id'], config('constants.imagine_product_types.program_service'), $application->id);
                    $list['tax'] = $this->productService->calculateTax($list['amount'], $service['service_id'], config('constants.imagine_product_types.program_service'), $application->id);
                    $application->programServices()->attach($service['service_id'], $list);
                }

                // update application fees
                if ($application->payment){
                    $this->applicationService->updateApplicationPaymentMethodFee($application->id, $application->payment->payment_method_id);
                }

            });
            return response()->json([
                'success' => true,
                'clearState' => $clearState,
                'message' => 'Application programs updated'
            ]);

       } catch(Exception $e){

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);

       }

    }

    /**
     * Validates conditions of gap between programs and overlapping if exist between programs
     * @param Request $request
     * @return string|null
     */
    public function validateProgram($request)
    {
        $programs = $request->get('programs');
        $check1 = $this->validateProgramOverlapping($programs);
        if(isset($check1)){
            return $check1;
        }

        $check2 = $this->validateProgramBreaks($programs);
        return $check2;
    }

    /**
     * Validates if overlapping exists between program dates
     * @param array $programs
     * @return string|null
     */
    public function validateProgramOverlapping($programs){
        $error = null;
        $finalProgramsArr = $this->getOrganizedProgramArray($programs);
        foreach ($finalProgramsArr as $key => $program){
            if (!isset($finalProgramsArr[$key+1])) continue;

            $start2 = Carbon::parse($finalProgramsArr[$key+1]->start_date);
            $start1 = Carbon::parse($program->start_date);
            $end1 = Carbon::parse($program->end_date);
            if ($start2->gte($start1) && $start2->lte($end1)){
                $error = 'Two or more programs are overlapping';
            }
        }
        return $error;
    }


    /**
     * Validates if break of more than 8 weeks exists between program dates
     * @param array $programs
     * @return string|null
     */
    public function validateProgramBreaks($programs){
        $error = null;
        $finalProgramsArr = $this->getOrganizedProgramArray($programs);
        foreach ($finalProgramsArr as $key => $program){
            if (!isset($finalProgramsArr[$key+1])) continue;

            $end1 = Carbon::parse($program->end_date);
            $start2 = Carbon::parse($finalProgramsArr[$key+1]->start_date);
            $diff = $start2->diffInWeeks($end1);
            if($diff > 8){
                $error = 'Gap of more than 8 weeks is not allowed between courses';
            }
        }
        return $error;
    }

    /**
     * Convert simple programs array to blocks of programs with each block containing one start date and one end date combining all programs within a package
     * @param array $programs
     * @return array
     */
    public function getOrganizedProgramArray($programs){
        $finalArr = array();
        $pkgArr = array();
        foreach($programs as $program){
            if($program['package']===false){
                $obj = new stdClass();
                $obj->start_date = $program['start_date'];
                $obj->end_date = $program['end_date'];
                array_push($finalArr, $obj);
            } else {
                $packageId = $program['package']['id'];
                if(isset($pkgArr[$packageId])){
                    array_push($pkgArr[$packageId], $program);
                } else {
                    $pkgArr[$packageId] = array();
                    array_push($pkgArr[$packageId], $program);
                }
            }
        }

        foreach($pkgArr as $program){
            $length = sizeof($program);
            $obj = new stdClass();
            $obj->start_date = $program[0]['start_date'];
            $obj->end_date = $program[$length-1]['end_date'];
            array_push($finalArr, $obj);
        }
        usort($finalArr, function($a, $b) {return strcmp($a->start_date, $b->start_date);});
        return $finalArr;
    }


    public function attachProgramsToApplication($application, $programs, &$services){
        foreach($programs as $program){
            $type = $program['package'] ? config('constants.imagine_product_types.packaged_program'): config('constants.imagine_product_types.program');
            $list = [
                    'faculty_id' => $program['faculty_id'],
                    'start_date' => $program['start_date'],
                    'end_date' => $program['end_date'],
                    'length' => $program['length'],
                    'packaged_program_id' => $program['package'] ? $program['package']['id'] : null,
                    'default_amount' => $this->productService->calculateFee($program, $type, $application->id)
            ];
            $list['amount'] = $list['default_amount'];
            $application->programs()->attach($program['program_id'], $list);
            $this->getMandatoryServices($program, $services);
        }

    }

    public function getMandatoryServices($program, &$services){
        $programObj = Program::find($program['program_id']);
        $mandatoryServices = $programObj->mandatoryServices()->get();
        foreach($mandatoryServices as $mandatoryService) {
            if($mandatoryService->type == 'application' &&  array_search($mandatoryService->id, array_column( $services , 'service_id')) !== false ){
                continue ;
            }

            if($mandatoryService->type == 'course' &&  $this->checkIfProgramServiceAlreadyExists($services, $mandatoryService, $program)){
                continue ;
            }

            $services[] = [
                'service_id' => $mandatoryService->id,
                'program_id' => $program['program_id'],
                'faculty_id' => $program['faculty_id'],
                'mandatory' => $mandatoryService->pivot->mandatory,
                'program_start_date' => $program['start_date'],
            ];
        }
    }

    /** checks if service already exists in services array with the program it attaches to
     * @param array $services
     * @param ProgramFeeService $mandatoryService
     * @param array $program
     * @return bool
     */
    public function checkIfProgramServiceAlreadyExists($services, $mandatoryService, $program){
        $found = false;
        foreach($services as $service){
            if($service['service_id']===$mandatoryService->id && $service['program_id']===$program['program_id']){
                $found = true;
            }
        }
        return $found;
    }

    /** Get Accommodation and categories according to student selected criteria
     * @param ApplicationDetail $application
     * @return JsonResponse
     */
    public function getAccommodations(Application $application): JsonResponse{
        $applicationDetail = $application->detail;
        $age = Carbon::parse($applicationDetail->dob)->age;
        $accommodationCategories = AccommodationCategory::whereEnabled(true)->with(['accommodations'=>function($accommodation) use($age){
            $accommodation->whereEnabled(true)->where(function($q1){
                $q1->whereHas('priceBook', function ($q2) {
                    $q2->where('expired', 0)->where('enabled',1);
                });
            })->with('optionalServices','optionalAddons');
        }])->where('age_restricted',0)->orWhere(function($q2) use ($age){
            $q2->where('min','<=',$age)->where('max','>=',$age);
        })->whereHas('accommodations',function($accommodation) use($age){
            $accommodation->whereEnabled(true)->where(function($q1){
                $q1->whereHas('priceBook', function ($q2) {
                    $q2->where('expired', 0)->where('enabled',1);
                });
            });
        })->get();

        return response()->json([
            'success' => true,
            'data' => $accommodationCategories
        ]);
    }

    /** Add/Update accommodations , accommodation services and addons against application
     * @param Request $request
     * @param application $application
     * @return JsonResponse
     */
    public function storeApplicationAccommodations(Request $request , Application $application): JsonResponse
    {
        $clearState = false;
        try {
            DB::transaction(function () use ($request, $application,&$clearState) {
                $accommodations = $request->get('accommodations');
                $services = $request->get('services');
                $addons = $request->get('addons');
                $selectedEarliestDate = null;
                $savedEarliestDate = null;
                if(sizeof($accommodations) > 0){
                    $selectedEarliestDate = $accommodations[0]['start_date'];
                }

                $savedAccommodation = $application->accommodations()->orderBy('pivot_start_date','asc')->first();
                if($savedAccommodation){
                    $savedEarliestDate = $savedAccommodation->pivot->start_date;
                }

                if($selectedEarliestDate !== $savedEarliestDate){
                    $clearState = true;
                    $application->insurances()->detach();
                }

                $application->accommodations()->detach();
                $application->accommodationServices()->detach();
                $application->accommodationAddons()->detach();

                foreach($accommodations as $accommodation){
                    $list = [
                            'faculty_id' => $accommodation['faculty_id'],
                            'start_date' => $accommodation['start_date'],
                            'end_date' => $accommodation['end_date'],
                            'weeks' => $accommodation['weeks'],
                            'days' => $accommodation['days'],
                            'default_amount' => $this->productService->calculateFee($accommodation,config('constants.imagine_product_types.accommodation'), $application->id)
                    ];
                    $list['amount'] = $list['default_amount'];
                    $application->accommodations()->attach($accommodation['accommodation_id'], $list);
                    $this->getMandatoryAccommodationServices($accommodation, $services);
                    $this->getMandatoryAccommodationAddons($accommodation, $addons);
                }

                foreach($services as $service){
                    $accommodationPivotData = $application->accommodations()->wherePivot('accommodation_id',$service['accommodation_id'])
                                                ->wherePivot('faculty_id',$service['faculty_id'])
                                                ->wherePivot('start_date',$service['accommodation_start_date'])
                                                ->first();
                    $service['weeks'] = $accommodationPivotData->pivot->weeks;
                    $service['days'] = $accommodationPivotData->pivot->days;
                    $list = [
                            'accommodation_start_date' => $service['accommodation_start_date'],
                            'accommodation_id' => $service['accommodation_id'],
                            'faculty_id' => $service['faculty_id'],
                            'mandatory' => $service['mandatory'],
                            'default_amount' => $this->productService->calculateFee($service,config('constants.imagine_product_types.accommodation_service'), $application->id)
                    ];
                    $list['amount'] = $list['default_amount'];
                    $list['default_tax'] = $this->productService->calculateTax($list['default_amount'], $service['service_id'], config('constants.imagine_product_types.accommodation_service'), $application->id);
                    $list['tax'] = $this->productService->calculateTax($list['amount'], $service['service_id'], config('constants.imagine_product_types.accommodation_service'), $application->id);
                    $application->accommodationServices()->attach($service['service_id'], $list);
                }

                foreach($addons as $addon){
                    $accommodationPivotData = $application->accommodations()->wherePivot('accommodation_id',$addon['accommodation_id'])
                                                ->wherePivot('faculty_id',$addon['faculty_id'])
                                                ->wherePivot('start_date',$addon['accommodation_start_date'])
                                                ->first();

                    $addon['weeks'] = $accommodationPivotData->pivot->weeks;
                    $addon['days'] = $accommodationPivotData->pivot->days;
                    $list = [
                            'accommodation_start_date' => $addon['accommodation_start_date'],
                            'accommodation_id' => $addon['accommodation_id'],
                            'faculty_id' => $addon['faculty_id'],
                            'mandatory' => $addon['mandatory'],
                            'default_amount' => $this->productService->calculateFee($addon,config('constants.imagine_product_types.accommodation_addon'), $application->id)
                    ];
                    $list['amount'] = $list['default_amount'];
                    $list['default_tax'] = $this->productService->calculateTax($list['default_amount'], $addon['addon_id'], config('constants.imagine_product_types.accommodation_addon'), $application->id);
                    $list['tax'] = $this->productService->calculateTax($list['amount'], $addon['addon_id'], config('constants.imagine_product_types.accommodation_addon'), $application->id);
                    $application->accommodationAddons()->attach($addon['addon_id'], $list);
                }

                // update application fees
                if ($application->payment){
                    $this->applicationService->updateApplicationPaymentMethodFee($application->id, $application->payment->payment_method_id);
                }
            });
            return response()->json([
                'success' => true,
                'clearState' => $clearState,
                'message' => 'Application accommodations updated'
            ]);

        } catch(Exception $e){

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);

        }

    }

    /** include mandatory services in array
     * @param $accommodation
     * @param $services
     * @return array
     */
    public function getMandatoryAccommodationServices($accommodation, &$services){

        $accommodationObj = Accommodation::find($accommodation['accommodation_id']);
        $mandatoryServices = $accommodationObj->mandatoryServices()->get();
        foreach($mandatoryServices as $mandatoryService) {
            if($mandatoryService->type == 'application' &&  array_search($mandatoryService->id, array_column( $services , 'service_id')) !== false ){
                continue ;
            }

            if($mandatoryService->type == 'product' &&  $this->checkIfAccommodationServiceAlreadyExists($services, $mandatoryService, $accommodation)){
                continue ;
            }

            $services[] = [
                'service_id' => $mandatoryService->id,
                'mandatory' => $mandatoryService->pivot->mandatory,
                'accommodation_id' => $accommodation['accommodation_id'],
                'faculty_id' => $accommodation['faculty_id'],
                'accommodation_start_date' => $accommodation['start_date']
            ];
        }
    }

    /** checks if service already exists in services array with the accommodation it attaches to
     * @param array $services
     * @param AccommodationFeeService $mandatoryService
     * @param array $accommodation
     * @return bool
     */
    public function checkIfAccommodationServiceAlreadyExists($services, $mandatoryService, $accommodation){
        $found = false;
        foreach($services as $service){
            if($service['service_id']===$mandatoryService->id && $service['accommodation_id']===$accommodation['accommodation_id']){
                $found = true;
            }
        }
        return $found;
    }



    /** include mandatory addons in array
     * @param $accommodation
     * @param $addons
     * @return JsonResponse
     */
    public function getMandatoryAccommodationAddons($accommodation, &$addons){
        $accommodationObj = Accommodation::find($accommodation['accommodation_id']);
        $mandatoryAddons = $accommodationObj->mandatoryAddons()->get();
        foreach($mandatoryAddons as $mandatoryAddon) {
            $addons[] = [
                'addon_id' => $mandatoryAddon->id,
                'mandatory' => $mandatoryAddon->pivot->mandatory,
                'accommodation_id' => $accommodation['accommodation_id'],
                'faculty_id' => $accommodation['faculty_id'],
                'accommodation_start_date' => $accommodation['start_date']
            ];
        }
    }

    /** Get Transportation and services according to student selected criteria
     * @param ApplicationDetail $application
     * @return JsonResponse
     */
    public function getTransportation(Application $application): JsonResponse{
        $faculty_id = request()->get('faculty_id');
        $faculty = Faculty::find($faculty_id);
        $location = $faculty->location;
        $return  = 0;
        $applicationDetail = $application->detail;
        $age = Carbon::parse($applicationDetail->dob)->age;

        if(request()->has('return'))
            $return  = request()->get('return');

        $transportationList = Transportation::whereEnabled(true)->where('return',$return)
                            ->where(function($q1) use($location){
                                $q1->where('origin',$location->id)->orWhere('destination',$location->id);
                            })->with(['optionalServices'=>function($q2) use ($age){
                                    $q2->where('age_restriction_enabled',0)->orWhere(function($q2) use ($age){
                                        $q2->where('min_age','<=',$age)->where('max_age','>=',$age);
                                    });
                            },'optionalAddons'])
                            ->get();

        return response()->json([
            'success' => true,
            'data' => $transportationList
        ]);
    }

    public function storeApplicationTransportation(Request $request, Application $application){
        try {
            DB::transaction(function () use ($request, $application) {
                $transportations = $request->get('transportations');
                $services = $request->get('services');
                $addons = $request->get('addons');

                $application->transportations()->detach();
                $application->transportationServices()->detach();
                $application->transportationAddons()->detach();

                foreach($transportations as $transportation){
                    $list = [
                        'faculty_id' => $transportation['faculty_id'],
                        'default_amount' => $this->productService->calculateFee($transportation,config('constants.imagine_product_types.transportation'), $application->id)
                    ];
                    $list['amount'] = $list['default_amount'];
                    $list['default_tax'] = $this->productService->calculateTax($list['default_amount'], $transportation['transportation_id'], config('constants.imagine_product_types.transportation'), $application->id);
                    $list['tax'] = $this->productService->calculateTax($list['amount'], $transportation['transportation_id'], config('constants.imagine_product_types.transportation'), $application->id);
                    $application->transportations()->attach($transportation['transportation_id'], $list);
                    $this->getMandatoryTransportationServices($transportation, $services, $application);
                    $this->getMandatoryTransportationAddons($transportation, $addons);
                }

                foreach($services as $service){
                    $list = [
                            'transportation_id' => $service['transportation_id'],
                            'faculty_id' => $service['faculty_id'],
                            'mandatory' => $service['mandatory'],
                            'default_amount' => $this->productService->calculateFee($service,config('constants.imagine_product_types.transportation_service'), $application->id)
                    ];
                    $list['amount'] = $list['default_amount'];
                    $list['default_tax'] = $this->productService->calculateTax($list['default_amount'], $service['service_id'], config('constants.imagine_product_types.transportation_service'),  $application->id);
                    $list['tax'] = $this->productService->calculateTax($list['amount'], $service['service_id'], config('constants.imagine_product_types.transportation_service'),  $application->id);
                    $application->transportationServices()->attach($service['service_id'], $list);
                }

                foreach($addons as $addon){
                    $list = [
                            'transportation_id' => $addon['transportation_id'],
                            'faculty_id' => $addon['faculty_id'],
                            'mandatory' => $addon['mandatory'],
                            'default_amount' => $this->productService->calculateFee($addon,config('constants.imagine_product_types.transportation_addon'), $application->id)
                    ];
                    $list['amount'] = $list['default_amount'];
                    $list['default_tax'] = $this->productService->calculateTax($list['default_amount'], $addon['addon_id'], config('constants.imagine_product_types.transportation_addon'),  $application->id);
                    $list['tax'] = $this->productService->calculateTax($list['amount'], $addon['addon_id'], config('constants.imagine_product_types.transportation_addon'),  $application->id);
                    $application->transportationAddons()->attach($addon['addon_id'], $list);
                }

                // update application fees
                if ($application->payment){
                    $this->applicationService->updateApplicationPaymentMethodFee($application->id, $application->payment->payment_method_id);
                }
            });
            return response()->json([
                'success' => true,
                'message' => 'Application accommodations updated'
            ]);

        } catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);

        }
    }

    /**
     * @param Application $application
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateApplicationOwnership(Application $application, User $user): JsonResponse
    {
        $this->authorize('updateProperties', $application);

        $oldOwner = $application->applicationOwner;

        $application->owner = $user->id;
        $application->save();

        $application->refresh();

        ApplicationTimeLine::dispatch([
            'application' => $application,
            'old_owner' => $oldOwner,
            'user' => auth()->user(),
            'event_type' => 'owner_updated'
        ]);

        return response()->json([
           'data' => $application->applicationOwner,
           'success' => true
        ]);
    }

     /** include mandatory services in array
     * @param $accommodation
     * @param $services
     * @return array
     */
    public function getMandatoryTransportationServices($transportation, &$services, $application){

        $applicationDetail = $application->detail;
        $age = Carbon::parse($applicationDetail->dob)->age;
        $transportationObj = Transportation::find($transportation['transportation_id']);
        $mandatoryServices = $transportationObj->mandatoryServices()->get();
        foreach($mandatoryServices as $mandatoryService) {
            if(($mandatoryService->age_restriction_enabled===0) || ($mandatoryService->age_restriction_enabled===1 && ($mandatoryService->min_age <=$age && $mandatoryService->max_age >= $age))){
                $services[] = [
                    'service_id' => $mandatoryService->id,
                    'mandatory' => $mandatoryService->pivot->mandatory,
                    'transportation_id' => $transportation['transportation_id'],
                    'faculty_id' => $transportation['faculty_id'],
                ];
            }
        }
    }

    /** include mandatory addons in array
     * @param $accommodation
     * @param $addons
     * @return JsonResponse
     */
    public function getMandatoryTransportationAddons($transportation, &$addons){
        $transportationObj = Transportation::find($transportation['transportation_id']);
        $mandatoryAddons = $transportationObj->mandatoryAddons()->get();
        foreach($mandatoryAddons as $mandatoryAddon) {
            $addons[] = [
                'addon_id' => $mandatoryAddon->id,
                'mandatory' => $mandatoryAddon->pivot->mandatory,
                'transportation_id' => $transportation['transportation_id'],
                'faculty_id' => $transportation['faculty_id']
            ];
        }
    }

    /**
     * Delete application document by id from s3 as well as from DB
     * @param ApplicationDocument $document
     * @return JsonResponse
     */
    public function deleteDocument(ApplicationDocument $document){
        $this->authorize('update', $document->application);
        if($document->application->exists and !auth()->user()->isStaff() and !$document->application->isStatusDraft()){
            return response()->json([
                'success' => false,
                'message' => 'Permission Denied'
            ]);
        }

        try{
            $eventData = [
                'application' => $document->application,
                'document' => $document->toArray(),
            ];
            if(Storage::disk('s3')->exists($document->path)) {
                Storage::disk('s3')->delete($document->path);
            }
            $document->delete();

            $eventData['user'] = auth()->user();
            $eventData['event_type'] = 'document_removed';

            ApplicationTimeLine::dispatch($eventData);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted'
            ]);
        }catch(Exception $exp){
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ]);
        }
    }

    /**
     * Return application timeline from activity
     * @param Application $application
     * @return JsonResponse
     */
    public function loadActivity(Application $application){
        $activities = $application->activities()->with('user')->orderBy('application_activities.id','desc')->simplePaginate(5);
        return response()->json([
            'success' =>true,
            'data' =>$activities
        ]);
    }

    /**
     * Return application timeline from activity
     * @param Application $application
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function searchOffer(Application $application, Request $request): JsonResponse
    {
        $this->authorize('updateProperties', $application);

        $offerData = $this->applicationOfferService->getOfferFromEbecas($request->get('search'));

        return response()->json([
            'success'=> true,
            'data'=> $offerData
        ]);
    }

    /**
     * @param Application $application
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function linkOffer(Application $application, Request $request): JsonResponse
    {
        $this->authorize('updateProperties', $application);

        if($application->applicationOwner->isAgent() && ! $application->applicationOwner->agent){
            return response()->json([
                'success'=> false,
                'message' => 'Unable to perform the task: The owner of the application does not have an agent associated.',
                'data' => $application
            ]);
        }

        $application->offer_id = $request->get('offer_id', null);
        $application->save();

        return response()->json([
            'success' => true,
            'data' => $application
        ]);
    }

}
