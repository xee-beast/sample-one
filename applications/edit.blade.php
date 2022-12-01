<x-app-layout>
    <!-- ======= Styles ===========-->
    <x-slot name="styles"></x-slot>
    <!-- ======= Page Title ===========-->
    <x-slot name="tab_title">{{$application->exists ? "Application ".$application->id .': '.$application->detail->full_name : "New Application"}}</x-slot>

    <!-- ========= Details ========== -->
    <div class="row">
        <div class="col">
            <div class="card">
                <application-wizard :countries="{{json_encode($countries)}}" :visas="{{json_encode($visas)}}"
                    :application-detail="{{json_encode($applicationDetail)}}" :languages="{{json_encode($languages)}}"
                    :user-type="{{json_encode(auth()->user()->user_type)}}" :insurances="{{json_encode($insurances)}}"
                    :status="{{json_encode(config('constants.application_form.status'))}}"
                    :application="{{json_encode($application->exists?$application:null)}}"
                    :application-programs="{{json_encode($application->exists? $applicationPrograms :null)}}"
                    :application-services="{{json_encode($application->exists? $applicationServices :null)}}"
                    :application-payments="{{json_encode($application->exists? $application->payment :null)}}"
                    :application-accommodations="{{json_encode($application->exists? $applicationAccommodation :null)}}"
                    :application-insurances="{{json_encode($application->exists? $applicationInsurances :null)}}"
                    :application-accommodation-services="{{json_encode($application->exists? $applicationAccommodationServices :null)}}"
                    :application-accommodation-addons="{{json_encode($application->exists? $applicationAccommodationAddons :null)}}"
                    :application-transportations="{{json_encode($application->exists? $applicationTransportation :null)}}"
                    :application-transportation-services="{{json_encode($application->exists? $applicationTransportationServices :null)}}"
                    :application-transportation-addons="{{json_encode($application->exists? $applicationTransportationAddons :null)}}"
                    :document-types="{{json_encode($documentTypes)}}"
                    :documents="{{json_encode($application->exists? $application->documents :null)}}"
                    >
                </application-wizard>
            </div>
        </div>
    </div>


    <!-- ======= Scripts ===========-->
</x-app-layout>
