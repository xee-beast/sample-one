<x-app-layout>
    <!-- ======= Styles ===========-->
    <x-slot name="styles"></x-slot>
    <!-- ======= Page Title ===========-->
    <x-slot name="tab_title">{{$application->exists ? "Application ".$application->id .': '.$application->detail->full_name : "New Application"}}</x-slot>

    <div class="row">
        <div class="col">
            <div class="thanks-outer">
                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
                    <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                </svg>
                <h1 class="site-header__title ms-2 mt-2" data-lead-id="site-header-title">Application # {{$application->id}} <span>Submitted</span></h1>

                <div class="mt-2"><h2>Thank you! </h2></div>
                <div class="row mt-3">
                    <div class="col-12 text-end">
                        <a href="{{route('applications.show',$application)}}" class="btn btn-info text-white px-4 ms-2 text-decoration-none">Continue to application</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

</x-app-layout>