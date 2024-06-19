<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6">
                            <DialogTitle class="relative flex justify-between items-center mb-7">
                                <div class="text-primary text-base">
                                    {{ $t('Availability & absence')}}
                                    <span v-if="vacation.id"> {{$t('edit')}}</span>
                                    <span v-else> {{ $t('enter')}}</span>
                                </div>
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </DialogTitle>

                            <div class="relative z-40">


                                <div class="w-full items-center flex ml-4 my-4">
                                    <div class="flex items-center gap-x-0.5">
                                        <button class="text-black" @click="previousMonth">
                                            <ChevronLeftIcon class="h-5 w-5 text-primary"/>
                                        </button>
                                        <CalendarIcon class="h-5 w-5 text-primary"/>
                                        <button class="text-black" @click="nextMonth">
                                            <ChevronRightIcon class="h-5 w-5 text-primary"/>
                                        </button>
                                    </div>
                                    <div class="sDark ml-4">
                                        <h2>{{ createShowDate[0] }}</h2>
                                    </div>

                                </div>
                                <table class="w-full border-separate font-light">
                                    <tr class="text-gray-500 text-center">
                                        <th class="p-3 font-light">{{ $t('Mon') }}</th>
                                        <th class="p-3 font-light">{{ $t('Tue') }}</th>
                                        <th class="p-3 font-light">{{ $t('Wed') }}</th>
                                        <th class="p-3 font-light">{{ $t('Thu') }}</th>
                                        <th class="p-3 font-light">{{ $t('Fri') }}</th>
                                        <th class="p-3 font-light">{{ $t('Sat') }}</th>
                                        <th class="p-3 font-light">{{ $t('Sun') }}</th>
                                    </tr>
                                    <tr class="sDark grid-cols-7" v-for="(week, index) in vacationSelectCalendar" :key="index">
                                        <td class="col-span-1 " v-for="day in week" :key="day">
                                            <div class="p-3 font-light flex justify-center hover:cursor-pointer" :class="{ 'text-green-500': day.isToday, 'text-gray-400': !day.inMonth, 'bg-gray-800 rounded-full text-white' : vacation.date === day.date }" @click="selectDate(day.date)">
                                                {{ day.day }}
                                            </div>
                                        </td>
                                    </tr>
                                </table>


                                <div class="px-7">
                                    <div v-show="helpText.date" class="text-red-500 text-xs">
                                        {{ helpText.date }}
                                    </div>
                                    <!-- type selection --->
                                    <div class="mb-4">
                                        <fieldset class="mt-4">
                                            <div class="space-y-4 sm:flex sm:items-center sm:space-x-10 sm:space-y-0">
                                                <div v-for="type in availableTypes" :key="type.id" class="flex items-center">
                                                    <input :id="type.id" v-model="vacation.type" name="notification-method" :value="type.id" type="radio" :checked="vacation.type === type.id" class="h-5 w-5 border-2 border-green-300 text-green-600 focus:border-green-600 focus:ring-0 ring-0" />
                                                    <label :for="type.id" class="ml-3 block text-sm font-medium leading-6 text-gray-900">{{ type.title }}</label>
                                                </div>
                                                <div v-show="helpText.type" class="mt-1 text-red-500 text-xs">
                                                    {{ helpText.type }}
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>

                                    <!-- full day select -->
                                    <div class="mb-4">
                                        <div class="relative flex items-start">
                                            <div class="flex h-6 items-center">
                                                <input id="full_day" v-model="vacation.full_day" :checked="vacation.full_day" aria-describedby="full_day-description" name="full_day" type="checkbox" class="h-5 w-5 border-green-300 text-green-600 focus:ring-0 ring-0" />
                                            </div>
                                            <div class="ml-3 text-sm leading-6">
                                                <label for="full_day" class="font-medium text-gray-900">{{ $t('All day')}}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- time selection -->
                                    <div class="mb-4" v-if="!vacation.full_day">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label for="start_time" class="block text-xs font-medium leading-6 text-gray-600">{{ $t('Start time')}}</label>
                                                <div class="mt-1">
                                                    <input type="time" v-model="vacation.start_time" name="start_time" id="start_time" class="block w-full border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-artwork-buttons-create sm:text-sm sm:leading-6" />
                                                    <div v-show="helpText.start_time" class="mt-1 text-red-500 text-xs">
                                                        {{ helpText.start_time }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <label for="end_time" class="block text-xs font-medium leading-6 text-gray-600">{{ $t('End time')}}</label>
                                                <div class="mt-1">
                                                    <input type="time" v-model="vacation.end_time" name="end_time" id="end_time" class="block w-full border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-artwork-buttons-create sm:text-sm sm:leading-6" />
                                                    <div v-show="helpText.end_time" class="mt-1 text-red-500 text-xs">
                                                        {{ helpText.end_time }}
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- repeat select -->
                                    <div class="mb-4">
                                        <div class="relative flex items-start">
                                            <div class="flex h-6 items-center">
                                                <input id="is_serie" v-model="vacation.is_series" :checked="vacation.is_series" aria-describedby="repeat-description" name="repeat" type="checkbox" class="h-5 w-5 border-green-300 text-green-600 focus:ring-0 ring-0" :class="{'!text-gray-500' : vacation?.id}" :disabled="vacation?.id"/>
                                            </div>
                                            <div class="ml-3 text-sm leading-6">
                                                <label for="repeat" class="font-medium text-gray-900 flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18.212" height="12.893" viewBox="0 0 18.212 12.893">
                                                        <g id="Icon_ionic-ios-repeat" data-name="Icon ionic-ios-repeat" transform="translate(-4.5 -8.439)">
                                                            <path id="Pfad_274" data-name="Pfad 274" d="M17.429,10.217,15.835,8.632a.664.664,0,0,0-.645-.171.567.567,0,0,0-.166.071.662.662,0,0,0-.323.621.7.7,0,0,0,.2.432l.759.754H8.2a3.708,3.708,0,0,0-3.7,3.695v.759a.666.666,0,0,0,.664.664h0a.666.666,0,0,0,.664-.664v-.759A2.38,2.38,0,0,1,8.2,11.663h7.37l-.759.754a.673.673,0,0,0-.2.413.669.669,0,0,0,.664.73.656.656,0,0,0,.47-.194l1.688-1.679a1.026,1.026,0,0,0,.308-.735A1.057,1.057,0,0,0,17.429,10.217Z" transform="translate(0 0)" fill="#27233c"/>
                                                            <path id="Pfad_275" data-name="Pfad 275" d="M24.449,17.156h0a.666.666,0,0,0-.664.664v.759a2.38,2.38,0,0,1-2.371,2.371h-7.37L14.8,20.2a.673.673,0,0,0,.2-.417.669.669,0,0,0-.664-.73.656.656,0,0,0-.47.194l-1.688,1.679a1.031,1.031,0,0,0,0,1.47l1.594,1.584a.664.664,0,0,0,.645.171.567.567,0,0,0,.166-.071.662.662,0,0,0,.323-.621.7.7,0,0,0-.2-.432l-.759-.754h7.465a3.7,3.7,0,0,0,3.7-3.7v-.759A.664.664,0,0,0,24.449,17.156Z" transform="translate(-2.401 -2.837)" fill="#27233c"/>
                                                        </g>
                                                    </svg>

                                                    {{ $t('Repeat event')}}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- time selection -->
                                    <div class="mb-4" v-if="vacation.is_series">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label for="email" class="block text-xs font-medium leading-6 text-gray-600">{{  $t('Repetition') }}</label>
                                                <select id="location" v-model="vacation.series_repeat" name="location" class="mt-1 block w-full border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-artwork-buttons-create sm:text-sm sm:leading-6" :class="{'!bg-gray-200' : vacation?.id}" :disabled="vacation?.id">
                                                    <option value="weekly" selected>{{  $t('Weekly') }}</option>
                                                    <option value="daily">{{ $t('Daily')}}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label for="email" class="block text-xs font-medium leading-6 text-gray-600">{{ $t('Ends')}}</label>
                                                <div class="mt-1">
                                                    <input type="date" v-model="vacation.series_repeat_until" name="email" id="email" class="block w-full border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-artwork-buttons-create sm:text-sm sm:leading-6" :disabled="vacation?.id" :class="{'!bg-gray-100' : vacation?.id}" />
                                                    <div v-show="helpText.series_repeat_until" class="mt-1 text-red-500 text-xs">
                                                        {{ helpText.series_repeat_until }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <!-- comment section -->
                                    <div>
                                        <div>
                                            <div class="mt-1">
                                                <input type="text" v-model="vacation.comment" name="comment" id="comment" :placeholder="$t('Comment')" maxlength="20" max="20" class="block w-full border-0 py-3.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-artwork-buttons-create sm:text-sm sm:leading-6" />
                                                <div v-show="helpText.comment" class="mt-1 text-red-500 text-xs">
                                                    {{ helpText.comment }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="flex items-center justify-between px-7 mt-5">
                                <div>
                                    <button class="text-[#3017AD] text-xs underline underline-offset-2" @click="saveOrUpdateVacation(true)">{{ $t('Save & make further entries')}}</button>
                                </div>
                                <div >
                                    <BaseButton v-if="vacation.isDirty && !vacation.id" @click="saveOrUpdateVacation(false)" :text="$t('Save')"/>
                                    <BaseButton v-if="vacation.isDirty && vacation.id" @click="saveOrUpdateVacation(false)" :text="$t('Bearbeiten')"/>
                                    <BaseButton v-if="!vacation.isDirty && vacation.id" @click="checkVacationType" background-color="bg-red-600 hover:bg-red-700" :text="$t('Delete')"/>
                                    <BaseButton v-if="!vacation.isDirty && !vacation.id" @click="checkVacationType" background-color="bg-red-600 hover:bg-red-700" :text="$t('Cancel')"/>
                                </div>
                            </div>

                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <ConfirmDeleteModal
        v-if="showDeleteConfirmModal"
        :title="$t('Delete serial entry')"
        :button="$t('Delete individual entry')"
        :description="$t('Would you like to delete just this entry or the whole series?')"
        :is-series-delete="true"
        :is_budget="false"
        @closed="showDeleteConfirmModal = false"
        @complete_delete="deleteCompleteSeries"
        @delete="deleteAvailabilityOrVacation" />
</template>
<script>
import {ChevronLeftIcon, ChevronRightIcon, XIcon, CalendarIcon} from "@heroicons/vue/solid";
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";
import {useForm} from "@inertiajs/vue3";
import dayjs from "dayjs";
import {router} from "@inertiajs/vue3";
import Button from "@/Jetstream/Button.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";

export default {
    name: "AddEditVacationsModal",
    components: {
        BaseButton,
        ConfirmDeleteModal,
        ChevronRightIcon, Button, ChevronLeftIcon,
        XIcon, Dialog, DialogTitle, DialogPanel, TransitionChild, TransitionRoot, CalendarIcon
    },
    data(){
        return {
            open: true,
            vacation: useForm({
                id: this.editVacation ? this.editVacation.id : null,
                start_time: this.editVacation ? this.editVacation.start_time : null,
                end_time: this.editVacation ? this.editVacation.end_time : null,
                date: this.editVacation ? this.editVacation.date : null,
                type: this.editVacation ? this.editVacation.type : 'available',
                full_day: this.editVacation ? this.editVacation.full_day : false,
                is_series:  this.editVacation ? this.editVacation.is_series : false,
                series_repeat: this.editVacation?.series ? this.editVacation?.series.frequency : 'weekly',
                series_repeat_until: this.editVacation?.series ? this.editVacation?.series.end_date : null,
                comment: this.editVacation ? this.editVacation.comment : null,
                type_before_update: this.editVacation ? this.editVacation.type : null,
            }),
            helpText: {
                date: '',
                type: '',
                start_time: '',
                end_time: '',
                series_repeat_until: '',
                comment: '',
            },
            availableTypes: [
                { id: 'available', title: this.$t('Availability') },
                { id: 'vacation', title: this.$t('Absence') },
            ],
            showDeleteConfirmModal: false
        }
    },
    emits: ['closed'],
    props: ['editVacation', 'user','type', 'vacationSelectCalendar', 'dateToShow', 'createShowDate'],
    methods: {
        closeModal(bool){
            this.vacation.reset();
            this.$emit('closed', bool)
        },
        selectDate(date){
            this.vacation.date = date;
        },
        checkVacationType(){
            if(this.editVacation.is_series === true){
                this.showDeleteConfirmModal = true
            } else {
                this.deleteAvailabilityOrVacation()
            }
        },
        saveOrUpdateVacation(withNewModal = false){
            // add checks here

            // check if date is selected
            if(!this.vacation.date){
                this.helpText.date = this.$t('Please select a date.')
                return;
            } else {
                this.helpText.date = ''
            }

            // check if type is selected
            if(!this.vacation.type){
                this.helpText.type = this.$t('Please select a type.')
                return;
            } else {
                this.helpText.type = ''
            }

            // check if start time and end time is selected if not full day
            if(!this.vacation.full_day){
                if(!this.vacation.start_time){
                    this.helpText.start_time = this.$t('Please select a start time.')
                    return;
                } else {
                    this.helpText.start_time = ''
                }
                if(!this.vacation.end_time){
                    this.helpText.end_time = this.$t('Please select an end time.')
                    return;
                } else {
                    this.helpText.end_time = ''
                }

                // check if start time is before end time
                if(this.vacation.start_time > this.vacation.end_time){
                    this.helpText.start_time = this.$t('The start time must be before the end time.')
                    return;
                } else {
                    this.helpText.start_time = ''
                }

                // check if start time and end time is not the same
                if(this.vacation.start_time === this.vacation.end_time){
                    this.helpText.start_time = this.$t('The start time and the end time must not be the same.')
                    return;
                } else {
                    this.helpText.start_time = ''
                }
            }

            // check if series repeat until is selected if is series
            if(this.vacation.is_series){
                if(!this.vacation.series_repeat_until){
                    this.helpText.series_repeat_until = this.$t('Please select an end date.')
                    return;
                } else {
                    this.helpText.series_repeat_until = ''
                }
            }

            // check if comment is not longer than 20 characters
            if(this.vacation.comment){
                if(this.vacation.comment.length > 20){
                    this.helpText.comment = this.$t('The comment must not be longer than 20 characters.')
                    return;
                } else {
                    this.helpText.comment = ''
                }
            }

            // check if vacation is new or update


            if(this.vacation.id === null){
                if(this.type === 'freelancer'){
                    this.vacation.post(route('freelancer.vacation.add', this.user.id), {
                        preserveScroll: true, preserveState: true, onFinish: () => {
                            if (withNewModal){
                                this.resetForm()
                            } else {
                                this.closeModal(true)
                            }
                        }
                    })
                }else{
                    this.vacation.post(route('user.vacation.add', this.user.id), {
                        preserveScroll: true, preserveState: true, onFinish: () => {
                            if (withNewModal){
                                this.resetForm()
                            } else {
                                this.closeModal(true)
                            }
                        }
                    })
                }

            } else {
                if(this.vacation.type_before_update === 'available') {
                    this.vacation.patch(route('update.availability', this.vacation.id), {
                        preserveScroll: true, preserveState: true, onFinish: () => {
                            if (withNewModal){
                                this.resetForm()
                            } else {
                                this.closeModal(true)
                            }
                        }
                    })
                }

                if (this.vacation.type_before_update === 'vacation') {
                    this.vacation.patch(route('update.vacation', this.vacation.id), {
                        preserveScroll: true, preserveState: true, onFinish: () => {
                            if (withNewModal){
                                this.resetForm()
                            } else {
                                this.closeModal(true)
                            }
                        }
                    })
                }
            }
        },
        resetForm(){
            this.vacation.id = null;
            this.vacation.start_time = null;
            this.vacation.end_time = null;
            this.vacation.date = null;
            this.vacation.type = 'available';
            this.vacation.full_day = false;
            this.vacation.is_series = false;
            this.vacation.series_repeat = 'weekly';
            this.vacation.series_repeat_until = null;
            this.vacation.comment = null;
            this.vacation.type_before_update = null;
        },
        deleteAvailabilityOrVacation(){
            if(this.vacation.type_before_update === 'available') {
                this.vacation.delete(route('delete.availability', this.vacation.id), {
                    preserveScroll: true, preserveState: true, onFinish: () => {
                        this.closeModal(true)
                    }
                })
            }
            if (this.vacation.type_before_update === 'vacation'){
                this.vacation.delete(route('delete.vacation', this.vacation.id), {
                    preserveScroll: true, preserveState: true, onFinish: () => {
                        this.closeModal(true)
                    }
                })
            }
        },

        deleteCompleteSeries(){
            if(this.vacation.type_before_update === 'available') {
                this.vacation.delete(route('delete.availability.series', this.editVacation.series_id), {
                    preserveScroll: true, preserveState: true, onFinish: () => {
                        this.closeModal(true)
                    }
                })
            }
            if (this.vacation.type_before_update === 'vacation'){
                this.vacation.delete(route('delete.vacation.series', this.editVacation.series_id), {
                    preserveScroll: true, preserveState: true, onFinish: () => {
                        this.closeModal(true)
                    }
                })
            }
        },
        previousMonth() {
            const currentMonth = new Date(this.createShowDate[1].date);
            router.reload({
                data: {
                    vacationMonth: this.subtractOneMonth(currentMonth),
                }
            })
        },
        nextMonth() {
            const currentMonth = new Date(this.createShowDate[1].date);

            router.reload({
                data: {
                    vacationMonth: this.addOneMonth(currentMonth),
                }
            })
        },
        addOneMonth(dateObj) {
            const day = dayjs(dateObj)
            return day.add(+1, 'month').format('YYYY-MM-DD');
        },
        subtractOneMonth(dateObj) {
            return dayjs(dateObj).subtract(1, 'month').format('YYYY-MM-DD');
        }
    }
}
</script>
<style scoped>

</style>
