<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-100" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-100 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6">
                            <DialogTitle class="relative flex justify-between items-center mb-7">
                                <div class="text-primary text-base">
                                    Verfügbarkeit & Abwesenheit eintragen
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
                                        <th class="p-3 font-light">Mo</th>
                                        <th class="p-3 font-light">Di</th>
                                        <th class="p-3 font-light">Mi</th>
                                        <th class="p-3 font-light">Do</th>
                                        <th class="p-3 font-light">Fr</th>
                                        <th class="p-3 font-light">Sa</th>
                                        <th class="p-3 font-light">So</th>
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
                                    <!-- type selection --->
                                    <div class="mb-4">
                                        <fieldset class="mt-4">
                                            <div class="space-y-4 sm:flex sm:items-center sm:space-x-10 sm:space-y-0">
                                                <div v-for="type in availableTypes" :key="type.id" class="flex items-center">
                                                    <input :id="type.id" v-model="vacation.type" name="notification-method" :value="type.id" type="radio" :checked="vacation.type === type.id" class="h-5 w-5 border-2 border-green-300 text-green-600 focus:border-green-600 focus:ring-0 ring-0" />
                                                    <label :for="type.id" class="ml-3 block text-sm font-medium leading-6 text-gray-900">{{ type.title }}</label>
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
                                                <label for="full_day" class="font-medium text-gray-900">Ganztags</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- time selection -->
                                    <div class="mb-4" v-if="!vacation.full_day">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label for="email" class="block text-xs font-medium leading-6 text-gray-600">Startuhrzeit</label>
                                                <div class="mt-1">
                                                    <input type="time" name="email" id="email" class="block w-full border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                                </div>
                                            </div>
                                            <div>
                                                <label for="email" class="block text-xs font-medium leading-6 text-gray-600">Enduhrzeit</label>
                                                <div class="mt-1">
                                                    <input type="time" name="email" id="email" class="block w-full border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- repeat select -->
                                    <div class="mb-4">
                                        <div class="relative flex items-start">
                                            <div class="flex h-6 items-center">
                                                <input id="full_day" v-model="vacation.is_series" :checked="vacation.is_series" aria-describedby="repeat-description" name="repeat" type="checkbox" class="h-5 w-5 border-green-300 text-green-600 focus:ring-0 ring-0" />
                                            </div>
                                            <div class="ml-3 text-sm leading-6">
                                                <label for="repeat" class="font-medium text-gray-900 flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18.212" height="12.893" viewBox="0 0 18.212 12.893">
                                                        <g id="Icon_ionic-ios-repeat" data-name="Icon ionic-ios-repeat" transform="translate(-4.5 -8.439)">
                                                            <path id="Pfad_274" data-name="Pfad 274" d="M17.429,10.217,15.835,8.632a.664.664,0,0,0-.645-.171.567.567,0,0,0-.166.071.662.662,0,0,0-.323.621.7.7,0,0,0,.2.432l.759.754H8.2a3.708,3.708,0,0,0-3.7,3.695v.759a.666.666,0,0,0,.664.664h0a.666.666,0,0,0,.664-.664v-.759A2.38,2.38,0,0,1,8.2,11.663h7.37l-.759.754a.673.673,0,0,0-.2.413.669.669,0,0,0,.664.73.656.656,0,0,0,.47-.194l1.688-1.679a1.026,1.026,0,0,0,.308-.735A1.057,1.057,0,0,0,17.429,10.217Z" transform="translate(0 0)" fill="#27233c"/>
                                                            <path id="Pfad_275" data-name="Pfad 275" d="M24.449,17.156h0a.666.666,0,0,0-.664.664v.759a2.38,2.38,0,0,1-2.371,2.371h-7.37L14.8,20.2a.673.673,0,0,0,.2-.417.669.669,0,0,0-.664-.73.656.656,0,0,0-.47.194l-1.688,1.679a1.031,1.031,0,0,0,0,1.47l1.594,1.584a.664.664,0,0,0,.645.171.567.567,0,0,0,.166-.071.662.662,0,0,0,.323-.621.7.7,0,0,0-.2-.432l-.759-.754h7.465a3.7,3.7,0,0,0,3.7-3.7v-.759A.664.664,0,0,0,24.449,17.156Z" transform="translate(-2.401 -2.837)" fill="#27233c"/>
                                                        </g>
                                                    </svg>

                                                    Wiederholungstermin
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- time selection -->
                                    <div class="mb-4" v-if="vacation.is_series">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label for="email" class="block text-xs font-medium leading-6 text-gray-600">Endet</label>
                                                <select id="location" v-model="vacation.series_repeat" name="location" class="mt-1 block w-full border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                    <option value="weekly" selected>Wöchentlich</option>
                                                    <option value="daily">Täglich</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label for="email" class="block text-xs font-medium leading-6 text-gray-600">Endet</label>
                                                <div class="mt-1">
                                                    <input type="date" v-model="vacation.series_repeat_until" name="email" id="email" class="block w-full border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <!-- comment section -->
                                    <div>
                                        <div>
                                            <div class="mt-1">
                                                <input type="text" v-model="vacation.comment" name="comment" id="comment" placeholder="Kommentar" maxlength="20" max="20" class="block w-full border-0 py-3.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="flex items-center justify-between px-7 mt-5">
                                <div>
                                    <button class="text-[#3017AD] text-xs underline underline-offset-2">Speichern & weitere Einträge machen</button>
                                </div>
                                <div >
                                    <AddButton v-if="vacation.isDirty" @click="saveOrUpdateVacation" type="save" mode="modal" text="Speichern"/>
                                    <AddButton v-if="!vacation.isDirty && vacation.id" @click="saveOrUpdateVacation" type="delete" mode="modal" text="Löschen"/>
                                    <AddButton v-if="!vacation.isDirty" @click="closeModal(true)" type="delete" mode="modal" text="Abbrechen"/>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
<script>
import {ChevronLeftIcon, ChevronRightIcon, XIcon, CalendarIcon} from "@heroicons/vue/solid";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";
import {useForm} from "@inertiajs/inertia-vue3";
import dayjs from "dayjs";
import {Inertia} from "@inertiajs/inertia";
import Button from "@/Jetstream/Button.vue";

export default {
    name: "AddEditVacationsModal",
    components: {
        ChevronRightIcon, Button, ChevronLeftIcon,
        AddButton, XIcon, Dialog, DialogTitle, DialogPanel, TransitionChild, TransitionRoot, CalendarIcon
    },
    data(){
        return {
            open: true,
            vacation: useForm({
                id: this.editVacation ? this.editVacation.id : null,
                start_time: this.editVacation ? this.editVacation.start_time : null,
                end_time: this.editVacation ? this.editVacation.end_time : null,
                date: '',
                type: 'available',
                full_day: false,
                is_series: false,
                series_repeat: 'weekly',
                series_repeat_until: null,
                comment: ''
            }),
            helpText: '',
            availableTypes: [
                { id: 'available', title: 'Verfügbarkeit' },
                { id: 'vacation', title: 'Abwesenheit' },
            ]
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
        saveOrUpdateVacation(){
            if(this.vacation.from === null || this.vacation.until === null){
                this.helpText = 'Bitte wähle ein Start und/oder End Datum';
                return;
            }
            if(dayjs(this.vacation.from) > dayjs(this.vacation.until)){
                this.helpText = 'Startdatum darf nicht nach dem Enddatum liegen!'
                return
            } else {
                this.helpText = '';
            }
            if(this.vacation.id === null){
                if(this.type === 'freelancer'){
                    this.vacation.post(route('freelancer.vacation.add', this.user.id), {
                        preserveScroll: true, preserveState: true, onFinish: () => {
                            this.closeModal(true)
                        }
                    })
                }else{
                    this.vacation.post(route('user.vacation.add', this.user.id), {
                        preserveScroll: true, preserveState: true, onFinish: () => {
                            this.closeModal(true)
                        }
                    })
                }

            } else {
                if(this.type === 'freelancer'){
                    this.vacation.patch(route('freelancer.vacation.update', this.vacation.id), {
                        preserveScroll: true, preserveState: true, onFinish: () => {
                            this.closeModal(true)
                        }
                    })
                }else{
                    this.vacation.patch(route('user.vacation.update', this.vacation.id), {
                        preserveScroll: true, preserveState: true, onFinish: () => {
                            this.closeModal(true)
                        }
                    })
                }

            }
        },
        previousMonth() {
            const currentMonth = new Date(this.createShowDate[1].date);

            Inertia.reload({
                data: {
                    vacationMonth: this.subtractOneMonth(currentMonth),
                }
            })
        },
        nextMonth() {
            const currentMonth = new Date(this.createShowDate[1].date);

            Inertia.reload({
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
