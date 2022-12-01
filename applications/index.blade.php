<x-app-layout>
    <!-- ======= Styles ===========-->
    <x-slot name="styles">
        <style>
            .filter-datatable .filter-dropdown .vs__selected-options {
                height: 33px;
                background: #fff;
                overflow-y: auto;
            }
        </style>

    </x-slot>
    <!-- ======= Page Title ===========-->
    <x-slot name="tab_title">Applications</x-slot>

    <!-- ======= Page title =========-->
    <x-page-title
        title="Applications"
        :breadcrumbs="[
            'Applications'=> null,
        ]"
    >
    </x-page-title>

    <!-- ========= Grid ========== -->
    <div class="card">
        <div class="card-body">
            @if($canCreate)
                <div class="row">
                    <div class="col-12 text-end">
                        <a href="{{route('applications.create')}}" class="btn btn-info btn-sm text-white px-4 ms-2 text-decoration-none">New Application</a>
                    </div>
                </div>
            @endif
            <application-index :status="{{json_encode(config('constants.application_form.status'))}}" :user-type="{{json_encode($userTypes)}}" :user-is-staff="{{json_encode(auth()->user()->isStaff())}}" />
        </div>
    </div>



</x-app-layout>
