<?php


namespace App\Http\Controllers\Staff\Settings;


use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SpecialOfferRequest;
use App\Models\Location;
use App\Models\Program;
use App\Models\ProgramFeeService;
use App\Models\ProgramPriceBook;
use App\Models\Region;
use App\Models\SpecialOffer;
use App\Models\SpecialOfferCategory;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class SpecialOfferController extends Controller
{
    /**
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        $categories = SpecialOfferCategory::whereEnabled(true)->orderBy('name')->get();
        $regions = Region::whereEnabled(true)->orderBy('name')->get();
        $this->authorize('viewAny', SpecialOffer::class);
        return view('settings.special-offers.index', compact('regions', 'categories'));
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     */
    public function getDataTableList(): JsonResponse
    {
        $this->authorize('viewAny', SpecialOffer::class);
        return SpecialOffer::getDataTable();
    }

    /**
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', SpecialOffer::class);

        return $this->edit(new SpecialOffer());
    }

    /**
     * @param SpecialOfferRequest $request
     * @return mixed
     * @throws AuthorizationException
     */
    public function store(SpecialOfferRequest $request)
    {
        $this->authorize('create', SpecialOffer::class);

        $check = $this->validateOffer($request);
        if ($request->get('enabled') && !$check){
            return response()->json([
                'success'=> false,
                'message' => 'Offer is in conflict with another offer and it cannot be saved'
            ]);
        }

        $specialOfferData = $request->except(['pricebooks', 'services', 'locations']);
        $specialOfferData['application_submitted_from'] = Carbon::parse($request->get('application_submitted_from'))->format('Y-m-d');
        $specialOfferData['application_submitted_to'] = Carbon::parse($request->get('application_submitted_to'))->format('Y-m-d');
        $specialOfferData['application_program_from'] = Carbon::parse($request->get('application_program_from'))->format('Y-m-d');
        $specialOfferData['application_program_to'] = Carbon::parse($request->get('application_program_to'))->format('Y-m-d');
        $offer = SpecialOffer::create($specialOfferData);

        if ($request->get('locations')) {
            $offer->locations()->sync($request->get('locations'));
        }

        foreach($request->get('pricebooks') as $pricebook){
            $list = [
                'program_id' => $pricebook['program_id']
            ];
            $offer->pricebooks()->attach($pricebook['pricebook_id'], $list);
        }

        $serviceArr = array();
        foreach($request->get('services') as $service){
            if($service['off_type']===config('constants.special_offer_service_off_types.percentage')){
                $service['value'] = $service['value']/100;
            }
            $serviceArr[$service['service_id']] = [
                'off_type' => $service['off_type'],
                'value' => $service['value'],
                'override_duration' => $service['override_duration'] == 1,
                'duration_length_start' => $service['min_length'],
                'duration_length_end' => $service['max_length'],
            ];
        }
        $offer->services()->sync($serviceArr);


        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.special-offers.index')
        ]);
    }

    /**
     * @param SpecialOfferRequest $request
     * @param SpecialOffer $offer
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(SpecialOfferRequest $request, SpecialOffer $special_offer): JsonResponse
    {
        $this->authorize('update', $special_offer);

        $check = $this->validateOffer($request, $special_offer->id);
        if ($request->get('enabled') && !$check){
            return response()->json([
                'success'=> false,
                'message' => 'Offer is in conflict with another offer and it cannot be saved'
            ]);
        }

        $specialOfferData = $request->except(['pricebooks', 'services', 'locations']);
        $specialOfferData['application_submitted_from'] = Carbon::parse($request->get('application_submitted_from'))->format('Y-m-d');
        $specialOfferData['application_submitted_to'] = Carbon::parse($request->get('application_submitted_to'))->format('Y-m-d');
        $specialOfferData['application_program_from'] = Carbon::parse($request->get('application_program_from'))->format('Y-m-d');
        $specialOfferData['application_program_to'] = Carbon::parse($request->get('application_program_to'))->format('Y-m-d');
        $special_offer->fill($specialOfferData);
        $special_offer->save();

        if ($request->get('locations')) {
            $special_offer->locations()->sync($request->get('locations'));
        }

        $special_offer->pricebooks()->detach();
        foreach($request->get('pricebooks') as $pricebook){
            $list = [
                'program_id' => $pricebook['program_id']
            ];
            $special_offer->pricebooks()->attach($pricebook['pricebook_id'], $list);
        }

        $serviceArr = array();
        foreach($request->get('services') as $service){
            if($service['off_type']===config('constants.special_offer_service_off_types.percentage')){
                $service['value'] = $service['value']/100;
            }
            $serviceArr[$service['service_id']] = [
                'off_type' => $service['off_type'],
                'value' => $service['value'],
                'override_duration' => $service['override_duration'] == 1,
                'duration_length_start' => $service['min_length'],
                'duration_length_end' => $service['max_length'],
            ];
        }
        $special_offer->services()->sync($serviceArr);

        return response()->json([
            'success'=> true,
            'redirect_route' => route('staff.settings.special-offers.index')
        ]);
    }

    /**
     * @param SpecialOffer $special_offer
     * @return View
     * @throws AuthorizationException
     */
    public function show(SpecialOffer $special_offer): View
    {
        $this->authorize('view', $special_offer);

        return $this->edit($special_offer);
    }

    /**
     * @param SpecialOffer $special_offer
     * @return View
     * @throws AuthorizationException
     */
    public function edit(SpecialOffer $special_offer): View
    {
        $this->authorize('view', $special_offer);
        $locations = Location::whereEnabled(true)->orderBy('name')->get();
        $categories = SpecialOfferCategory::whereEnabled(true)->orderBy('name')->get();
        $regions = Region::whereEnabled(true)->orderBy('name')->get();
        $programs = Program::whereEnabled(true)->orderBy('name')->get();
        $programPricebooks = ProgramPriceBook::whereEnabled(true)->where('expired',0)->orderBy('name')->get();
        $selectedPricebooks = $special_offer->pricebooks()->get();
        $services = ProgramFeeService::whereEnabled(true)->orderBy('name')->get();
        $selectedServices = $special_offer->services()->get();
        $selectedLocations = $special_offer->locations()->get();

        return view('settings.special-offers.edit', compact('special_offer', 'locations', 'categories', 'regions',
        'programs', 'programPricebooks', 'selectedPricebooks', 'services', 'selectedServices', 'selectedLocations'));
    }

    /**
     * @param SpecialOffer $special_offer
     * @return mixed
     * @throws AuthorizationException
     */
    public function destroy(SpecialOffer $special_offer)
    {
        $this->authorize('delete', $special_offer);

        $special_offer->locations()->detach();
        $special_offer->pricebooks()->detach();
        $special_offer->services()->detach();
        $special_offer->delete();

        return redirect()->route('staff.settings.special-offers.index')->withStatus('Special offer deleted!');
    }

    /**
     * @param SpecialOfferRequest $request
     * @return bool
     */
    public function validateOffer(SpecialOfferRequest $request, $offerId = false): bool
    {
        // getting all offers
        $offers = SpecialOffer::whereEnabled(true)->get();

        foreach ($offers as $offer){

            // for update, skipping self object
            if ($offerId && ($offer->id == $offerId)){
                continue;
            }

            // checking for same student location
            $sameLocation = false;
            if(($request->get('onshore')==0 && $request->get('offshore')==0) && ($offer->onshore==0 && $offer->offshore==0)){
                $sameLocation = true;
            } elseif(($request->get('onshore')==1) && ($offer->onshore==1 && $offer->offshore==1)){
                $sameLocation = true;
            } elseif(($request->get('onshore')==1) && ($offer->onshore==1 && $offer->offshore==0)){
                $sameLocation = true;
            } elseif(($request->get('offshore')==1) && ($offer->onshore==1 && $offer->offshore==1)){
                $sameLocation = true;
            } elseif(($request->get('offshore')==1) && ($offer->onshore==0 && $offer->offshore==1)){
                $sameLocation = true;
            }

            // checking region and locations
            if (($request->get('region_id') === $offer->region_id) && $sameLocation && !empty(array_intersect($offer->locations->pluck('id')->toArray(), $request->get('locations')))){
                // checking for application submitted dates overlap
                if (
                    (Carbon::parse($request->get('application_submitted_from'))->lte(Carbon::parse($offer->application_submitted_to)) &&
                    Carbon::parse($request->get('application_submitted_to'))->gte(Carbon::parse($offer->application_submitted_from)))
                    &&
                    (Carbon::parse($request->get('application_program_from'))->lte(Carbon::parse($offer->application_program_to)) &&
                     Carbon::parse($request->get('application_program_to'))->gte(Carbon::parse($offer->application_program_from)))

                ){
                    return false;
                }
            }
        }

        return true;
    }
}
