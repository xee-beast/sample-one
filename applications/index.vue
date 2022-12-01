<template>
    <div class="mt-4">
        <div class="filter-datatable">
            <div class="row filter-dropdown">
                <div class="col-4">
                    <Datepicker v-model="filter.date" range autoApply :change="reloadDataTable()" placeholder="Submission Date" :format="format" :closeOnAutoApply="true" :enableTimePicker="false"></Datepicker>
                </div>
                <div class="col-4">
                    <v-select :loading="status.length <= 0"
                              :clearable="false"
                              v-model="filter.status"
                              :multiple="true"
                              :options="applicationStatusDropdown"
                              :reduce="option => option.value"
                              placeholder="Select Status"
                              :change="reloadDataTable()"
                    >
                    </v-select>
                </div>
                <div v-if="userIsStaff" class="col-3">
                    <v-select id="getSearchModule"
                              v-model="filter.user_type"
                              :options="userTypeDropdown"
                              :reduce="option => option.value"
                              :multiple="true"
                              append-to-body
                              placeholder="Filter User Type"
                              :change="reloadDataTable()"
                    >
                    </v-select>
                </div>
            </div>
        <table class="table table-bordered table-striped" id="application-table">
            <thead>
            <tr>
                <th scope="col" >ID</th>
                <th scope="col" >Student</th>
                <th scope="col" >Student Id</th>
                <th scope="col" >Status</th>
                <th scope="col" >Owner</th>
                <th scope="col" >Agent Name</th>
                <th scope="col" >User Type</th>
                <th scope="col" >Submitted on</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
        </div>
    </div>
</template>

<script>
    import moment from 'moment';
    import 'datatables.net-dt/js/dataTables.dataTables';
    import 'datatables.net-dt/css/jquery.dataTables.min.css';
    import vSelect from "vue-select";
    import Datepicker from "@vuepic/vue-datepicker";
    import generalHelpers from "../../helpers/generalHelpers";
    export default {
        components: {
            vSelect,
            Datepicker
        },
        props: ['status', 'userType', 'userIsStaff'],
        data() {
            var self = this;
            return {
                datatable:null,
                filter:{
                    date:null,
                    status:null,
                    user_type: null
                },
                // datepicker
                format: "dd/MM/yyyy"
            }
        },
        mounted() {
            this.setDataTable();
        },
        methods: {
            setDataTable(){
                var self = this;
                this.datatable = $('#application-table').DataTable( {
                    dom: 'f <tilp>',
                    language: {
                        processing: '<i class="fa fa-spinner fa-spin" style="font-size:24px;color:rgb(75, 183, 245);"></i>'
                    },
                    processing: true,
                    serverSide: true,
                    ordering: true,
                    order: [[ 0, 'desc' ]],
                    responsive: true,
                    pageLength: 25,
                    ajax: {
                        url: route('applications.list'),
                        data: function ( d ) {
                            d.date = self.filter.date,
                            d.user_type = self.filter.user_type,
                            d.status =  self.filter.status
                        },
                        beforeSend: function(){
                            // Here, manually add the loading icon.
                            $('#application-table > tbody').html(
                                '<tr class="odd">' +
                                '<td valign="top" colspan="7" class="dataTables_empty">' +
                                '<i class="fa fa-spinner fa-spin" style="font-size:24px;color:rgb(75, 183, 245);"></i>' +
                                '</td>' +
                                '</tr>'
                            );
                        }
                    },
                    columns: [
                        {data: 'id', name: 'id', ordering: true},
                        {data: 'student', name: 'detail.first_name' },
                        {data: 'student_number', name: 'student_number' },
                        {data: 'status', name: 'status' },
                        {data: 'owner', name: 'owner', visible: this.userIsStaff },
                        {data: 'agent_name', name: 'agent_name'},
                        {data: 'user_type', name: 'applicationOwner.user_type', visible: this.userIsStaff},
                        {data: 'submitted_on', name: 'submitted_on' },
                    ]
                });
            },
            reloadDataTable(){
                if ( this.filter.date ){
                    this.filter.date[0] = moment(this.filter.date[0], ["YYYY-M-D H:m"]).format('YYYY/MM/DD');
                    this.filter.date[1] = moment(this.filter.date[1], ["YYYY-M-D H:m"]).format('YYYY/MM/DD');
                }
                if ( this.datatable ) {
                    this.datatable.draw();
                }
            }
        },
        computed: {
            applicationStatusDropdown(){
                let statuses = [];
                for ( let item in this.status) {
                    statuses.push({label:this.status[item].label, value: item})
                }
                return statuses;
            },
            userTypeDropdown(){
                let userTypes = [];
                for ( let item in this.userType) {
                    userTypes.push({label: generalHelpers.capitalizeFirstLetter(this.userType[item]), value: this.userType[item]})
                }
                return userTypes;
            }
        },
    }
</script>

