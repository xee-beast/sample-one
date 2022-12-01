<x-app-layout>
    <!-- ======= Styles ===========-->
    <x-slot name="styles">
        <style>
            .edit-link{
                float: right;
                margin-right: 10px;
            }
        </style>
    </x-slot>
    <!-- ======= Page Title ===========-->
    <x-slot name="tab_title">{{$application->exists ? "Application ". $application->id .': ' .$application->detail->full_name : "New Application"}}</x-slot>

    <application-tracking-header :application="{{json_encode($application)}}" :status="{{json_encode(config('constants.application_form.status'))}}"></application-tracking-header>

    <div class="wizard-data">
        <div class="row">
            <div class="col-md-8 accordion-main">
                <!-- Personal details start -->

                <div class="accordion" id="app-details">

                    <!--                    personal details -->

                    <application-tracking-summary
                        :application="{{json_encode($application)}}"
                    >
                    </application-tracking-summary>

                    <div>
                        <application-tracking-documents
                            :application="{{json_encode($application)}}"
                            :user-permissions="{{json_encode($userPermissions)}}"
                        >
                        </application-tracking-documents>
                    </div>

                    <!-- Comments -->
                    <div>
                        <h3>Comments</h3>
                        <div>
                            
                            <textarea class="form-control application-comment-txtarea" rows="5" disabled>{!! ($application->comments) !!}</textarea>
                            
                        </div>
                    </div>

                </div>
            </div>


            <div class="col-md-4 accordion-side">
                <div class="accordion" id="accordionPanelsStayOpenExample">
                    @if(auth()->user()->isStaff())

                        <application-tracking-detail
                            :application="{{json_encode($application)}}"
                            :status="{{json_encode(config('constants.application_form.status'))}}"
                            :user-permissions="{{json_encode($userPermissions)}}"
                            :user-type="{{json_encode($userTypes)}}"
                        >
                        </application-tracking-detail>

                        <application-tracking-offer
                            :application="{{json_encode($application)}}"
                            :status="{{json_encode(config('constants.application_form.status'))}}"
                        >

                        </application-tracking-offer>

                        <application-tracking-activity  :application="{{json_encode($application)}}"  />
                    @endif
                </div>
            </div>
        </div>
    </div>


    <!-- ======= Scripts ===========-->
    <x-slot name="scripts">
    </x-slot>
</x-app-layout>
