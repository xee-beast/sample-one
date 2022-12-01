<template>
        <div class="academic-col academic-items">
            <!-- program start -->
            <h3><i class="fa-solid fa-bars-progress"></i> Your Programs Summary</h3>
            <div class="products" >
              <div class="product-list" v-if="programs.length > 0">
                <div class="product-item" v-for="(program,key) in programs">
                  <div v-if="program.package">
                      <div class="products" >
                          <h5>{{program.package.name}}</h5>
                          <a href="javascript:;" @click.prevent="removePackagedProgram(program, key)" class="remove-product">
                              <i class="fas fa-times"></i>
                          </a>
                          <div class="mt-1 ms-2" v-for="(pkgProgram, itemKey) in program.programs">
                              <h6>{{pkgProgram.program}}</h6>
                              <p class="address">{{pkgProgram.location}} </p>
                              <p class="dates"><span>{{formattedDate(pkgProgram.start_date)}}</span> to <span>{{formattedDate(pkgProgram.end_date)}}</span></p>
                              <p class="duration">{{pkgProgram.length}} week(s)</p>

                              <div v-if="calculateBreak(pkgProgram.org_key,pkgProgram) !== ''" class="my-3 border-bottom border-top py-3 bg-light bg-gradient">
                                  <h6 class="text-center">{{calculateBreak(pkgProgram.org_key,pkgProgram)}} break</h6>
                              </div>
                          </div>
                      </div>
                  </div>

                    <!-- INDIVIDUAL PROGRAMS -->
                  <div v-else>
                      <h6>{{program.program}}</h6>
                      <p class="address">{{program.location}} | {{program.faculty}}</p>
                      <p class="dates"><span>{{formattedDate(program.start_date)}}</span> to <span>{{formattedDate(program.end_date)}}</span></p>
                      <p class="duration">{{program.length}} week(s)</p>
                      <a href="javascript:;" @click.prevent="removeProgram(program.org_key,program, key)" class="remove-product">
                          <i class="fas fa-times"></i>
                      </a>

                      <div class="sub-products" v-if="programServices(program).length > 0">
                          <div class="product-title">Additional Services</div>
                          <div class="product-list">
                              <div class="product-list" v-if="programServices(program).length > 0">
                                  <div class="product-item" v-for="(service,key) in programServices(program)">
                                      <h6>{{service.name}}</h6>
                                      <a href="javascript:;" @click.prevent="removeService(service)"
                                         class="remove-service">
                                          <i class="fas fa-times"></i>
                                      </a>
                                  </div>
                              </div>
                              <div v-else>
                                  <p class="text-center mt-3">No items selected</p>
                              </div>
                          </div>
                      </div>
                      <div v-if="calculateBreak(program.org_key,program) !== ''" class="my-3 border-bottom border-top py-3 bg-light bg-gradient">
                          <h6 class="text-center">{{calculateBreak(program.org_key,program)}} break</h6>
                      </div>
                  </div>

                </div>
              </div>
              <div v-else>
                 <p class="text-center mt-3">No items selected</p>
              </div>
            </div>

    </div>

</template>
<script>

import moment from 'moment';
import {useFormStore} from '../../../stores/applicationForm';
import generalHelpers from "../../../helpers/generalHelpers";

export default {
        name: "program-summary",

        setup(){
            const formStore  = useFormStore();
            const {getApplicationData,removeProgramSummary, removePackagedProgramSummary, removeProgramService, getCombinedProgramArray } = formStore;
            return {getApplicationData,removeProgramSummary, removePackagedProgramSummary, removeProgramService, getCombinedProgramArray };
        },

        data() {
            return {
                loading:false,
            };
        },

        mounted() {
        },
        computed :{
            programs(){
                return generalHelpers.getProgramByType(this.getApplicationData('programs'), 'summary');
            },
            services() {
                let services = this.getApplicationData('program_services');
                let list = services.filter((service) => {
                    return service.type === 'application';
                });
                return list;
            },
        },
        methods: {
            programServices(program){
                let services = this.getApplicationData('program_services');
                let list = services.filter((service) => {
                    return service.program_start_date === program.start_date && service.type !== 'application';
                });
                return list;
            },

            calculateBreak(key,program){
              let programs = this.getApplicationData('programs');
              let programLength = Number(key)+1;
              if(programs.length !== 1 &&  programs.length > programLength){
                  let nextItem = programs[programLength];
                  let nextStart = moment(nextItem.start_date);
                  let currentEnd = moment(program.end_date);
                  let weeks = nextStart.diff(currentEnd,'week');
                  if(weeks<=0)
                    return '';
                  return weeks +' week(s)';
              }
              return '';
            },

            removeProgram(index,program,loopIndex){

                let programs = [...this.getApplicationData('programs')];
                programs.splice(index, 1);
                let finalArr = this.getCombinedProgramArray(programs);
                if (this.checkBreak(finalArr)){
                    return generalHelpers.showToast("deleting this will create gap of more that 8 weeks between the course", false)
                }

                let message = 'You are about to delete this item!';
                if(loopIndex == 0){
                    message = 'If you have already selected accommodation or airport transfer items, you will need to add them again.';
                }
                let self = this;
                Swal.fire({
                    customClass: {
                        confirmButton: 'btn btn-info text-white px-3',
                        cancelButton: 'btn btn-outline-secondary px-3 mx-3',
                    },
                    buttonsStyling: false,
                    reverseButtons:true,
                    title: 'Are you sure?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    }).then((result) => {
                    if (result.isConfirmed) {
                        self.removeProgramSummary(index);
                        self.removeAttachedService(program);
                    }
                });
            },

            removePackagedProgram(pkg, loopIndex){

                let programs = [...this.getApplicationData('programs')];
                for (const resultKey in programs) {
                    for (const indexKey in programs) {
                        if (programs[indexKey].start_date == programs[resultKey].start_date) {
                            programs.splice(indexKey, 1);
                        }
                    }
                }

                let finalArr = this.getCombinedProgramArray(programs);
                if (this.checkBreak(finalArr)){
                    return generalHelpers.showToast("deleting this will create gap of more that 8 weeks between the course", false)
                }

                let message = 'You are about to delete this item!';
                let self = this;
                if(loopIndex == 0){
                    message = 'If you have already selected accommodation or airport transfer items, you will need to add them again.';
                }
                Swal.fire({
                    customClass: {
                        confirmButton: 'btn btn-info text-white px-3',
                        cancelButton: 'btn btn-outline-secondary px-3 mx-3',
                    },
                    buttonsStyling: false,
                    reverseButtons:true,
                    title: 'Are you sure?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    }).then((result) => {
                    if (result.isConfirmed) {
                        let programs = pkg.programs;
                        for (const resultKey in programs) {
                            self.removeProgramSummary(pkg, programs[resultKey].start_date);
                        }
                        // for (const program of pkg.programs) {
                            // self.removeProgramSummary(pkg, program.start_date);
                        // }
                    }
                });
            },

            checkBreak(programs){
                let check = false
                for (const programsKey in programs) {
                    let key = Number(programsKey);
                    if (programs[key+1]) {
                        let end1 = moment(programs[key+1].end_date);
                        end1.add(3, 'days');
                        let start2 = moment(programs[key].start_date);
                        let weeks = start2.diff(end1, 'weeks');
                        if (weeks > 8) {
                            check = true;
                        }
                    }
                }
                return check;
            },

            removeAttachedService(program){
              let allServices = this.getApplicationData('program_services');
              let attachedServices = allServices.filter((service) => {
                return service.program_id == program.program_id && service.faculty_id == program.faculty_id && service.program_start_date === program.start_date;
              });
              if(attachedServices.length  > 0){
                for(let i in attachedServices){
                    let index = allServices.indexOf(attachedServices[i]);
                    this.removeProgramService(index);
                }
              }
            },
            removeService(service){
                let self = this;
                  Swal.fire({
                      customClass: {
                          confirmButton: 'btn btn-info text-white px-3',
                          cancelButton: 'btn btn-outline-secondary px-3 mx-3',
                      },
                      buttonsStyling: false,
                      reverseButtons:true,
                      title: 'Are you sure?',
                      text: "You are about to delete this service!",
                      icon: 'warning',
                      showCancelButton: true,
                      }).then((result) => {
                      if (result.isConfirmed) {
                          let services = this.getApplicationData('program_services');
                          let index = services.indexOf(service);
                          self.removeProgramService(index);
                      }
                  });
            },

            formattedDate(date){
              return moment(date).format('DD/MM/YYYY', ['DD/MM/YY', 'YYYY/MM/DD']);
            }
        }

    }
</script>
