<template>
    <div class="bg-secondary rounded flex text-sm group relative mb-2" v-if="!vacation.has_conflicts">
        <div class="hidden group-hover:block" v-if="$can('can manage workers') || hasAdminRole()">
            <div class="absolute w-full h-full rounded-lg flex justify-center align-middle items-center gap-2">
                <button type="button" @click="openShowEditVacationModal"
                        class="rounded-full bg-artwork-buttons-create p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                </button>
                <button type="button" @click="checkIfVacationIsSeries"
                        class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="py-4 px-3 text-secondaryHover ">
            <div class="flex items-center">
                <div>
                    {{ vacation.date_casted }}
                </div>
                <div v-if="!vacation.full_day">
                    , {{ vacation.start_time }} - {{ vacation.end_time }}
                </div>
                <div v-if="vacation.is_series" class="flex items-center ml-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="fff" width="18.212" height="12.893" viewBox="0 0 18.212 12.893" class="text-white">
                        <g id="Icon_ionic-ios-repeat" data-name="Icon ionic-ios-repeat" transform="translate(-4.5 -8.439)">
                            <path id="Pfad_274" data-name="Pfad 274" d="M17.429,10.217,15.835,8.632a.664.664,0,0,0-.645-.171.567.567,0,0,0-.166.071.662.662,0,0,0-.323.621.7.7,0,0,0,.2.432l.759.754H8.2a3.708,3.708,0,0,0-3.7,3.695v.759a.666.666,0,0,0,.664.664h0a.666.666,0,0,0,.664-.664v-.759A2.38,2.38,0,0,1,8.2,11.663h7.37l-.759.754a.673.673,0,0,0-.2.413.669.669,0,0,0,.664.73.656.656,0,0,0,.47-.194l1.688-1.679a1.026,1.026,0,0,0,.308-.735A1.057,1.057,0,0,0,17.429,10.217Z" transform="translate(0 0)" fill="#fff"/>
                            <path id="Pfad_275" data-name="Pfad 275" d="M24.449,17.156h0a.666.666,0,0,0-.664.664v.759a2.38,2.38,0,0,1-2.371,2.371h-7.37L14.8,20.2a.673.673,0,0,0,.2-.417.669.669,0,0,0-.664-.73.656.656,0,0,0-.47.194l-1.688,1.679a1.031,1.031,0,0,0,0,1.47l1.594,1.584a.664.664,0,0,0,.645.171.567.567,0,0,0,.166-.071.662.662,0,0,0,.323-.621.7.7,0,0,0-.2-.432l-.759-.754h7.465a3.7,3.7,0,0,0,3.7-3.7v-.759A.664.664,0,0,0,24.449,17.156Z" transform="translate(-2.401 -2.837)" fill="#fff"/>
                        </g>
                    </svg>

                </div>
            </div>
            <p v-if="vacation.comment">&bdquo;{{ vacation.comment }}&rdquo;</p>
        </div>


    </div>

    <div  v-else>
        <div class="rounded flex text-sm group relative mb-2">
            <div class="hidden group-hover:block" v-if="$can('can manage workers') || hasAdminRole()">
                <div class="absolute w-full h-full rounded-lg flex justify-center align-middle items-center gap-2">
                    <button type="button" @click="openShowEditVacationModal"
                            class="rounded-full bg-artwork-buttons-create p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                        </svg>
                    </button>
                    <button type="button" @click="checkIfVacationIsSeries"
                            class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                             stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="py-4 px-3">
                <div class="flex items-center gap-x-1 font-bold">
                    <svg id="Gruppe_1882" data-name="Gruppe 1882" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14">
                        <rect id="Rechteck_688" data-name="Rechteck 688" width="14" height="14" fill="#eb7a3d"/>
                        <path id="Icon_metro-warning" data-name="Icon metro-warning" d="M8.571,3.015,13.6,13.037H3.542L8.571,3.015Zm0-1.087a.867.867,0,0,0-.713.523L2.735,12.66c-.392.7-.059,1.268.742,1.268H13.664c.8,0,1.134-.571.742-1.268h0L9.284,2.451A.867.867,0,0,0,8.571,1.928Zm.75,9.75a.75.75,0,1,1-.75-.75A.75.75,0,0,1,9.321,11.678Zm-.75-1.5a.75.75,0,0,1-.75-.75V7.178a.75.75,0,1,1,1.5,0v2.25A.75.75,0,0,1,8.571,10.178Z" transform="translate(-1.571 -0.928)" fill="#fcfcfb" stroke="#fcfcfb" stroke-width="0.2"/>
                    </svg>

                    {{ $t('Conflict with your shift!') }}
                </div>
                <div class="flex items-center">
                    <div>
                        {{ vacation.date_casted }}
                    </div>
                    <div v-if="!vacation.full_day">
                        , {{ vacation.start_time }} - {{ vacation.end_time }}
                    </div>
                    <div v-if="vacation.is_series" class="flex items-center ml-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="fff" width="18.212" height="12.893" viewBox="0 0 18.212 12.893" class="text-white">
                            <g id="Icon_ionic-ios-repeat" data-name="Icon ionic-ios-repeat" transform="translate(-4.5 -8.439)">
                                <path id="Pfad_274" data-name="Pfad 274" d="M17.429,10.217,15.835,8.632a.664.664,0,0,0-.645-.171.567.567,0,0,0-.166.071.662.662,0,0,0-.323.621.7.7,0,0,0,.2.432l.759.754H8.2a3.708,3.708,0,0,0-3.7,3.695v.759a.666.666,0,0,0,.664.664h0a.666.666,0,0,0,.664-.664v-.759A2.38,2.38,0,0,1,8.2,11.663h7.37l-.759.754a.673.673,0,0,0-.2.413.669.669,0,0,0,.664.73.656.656,0,0,0,.47-.194l1.688-1.679a1.026,1.026,0,0,0,.308-.735A1.057,1.057,0,0,0,17.429,10.217Z" transform="translate(0 0)" fill="#fff"/>
                                <path id="Pfad_275" data-name="Pfad 275" d="M24.449,17.156h0a.666.666,0,0,0-.664.664v.759a2.38,2.38,0,0,1-2.371,2.371h-7.37L14.8,20.2a.673.673,0,0,0,.2-.417.669.669,0,0,0-.664-.73.656.656,0,0,0-.47.194l-1.688,1.679a1.031,1.031,0,0,0,0,1.47l1.594,1.584a.664.664,0,0,0,.645.171.567.567,0,0,0,.166-.071.662.662,0,0,0,.323-.621.7.7,0,0,0-.2-.432l-.759-.754h7.465a3.7,3.7,0,0,0,3.7-3.7v-.759A.664.664,0,0,0,24.449,17.156Z" transform="translate(-2.401 -2.837)" fill="#fff"/>
                            </g>
                        </svg>

                    </div>
                </div>
                <p v-if="vacation.comment">&bdquo;{{ vacation.comment }}&rdquo;</p>
            </div>

        </div>
        <div class="my-2.5 text-sm">
            <p v-for="conflict in vacation.conflicts">
                {{ $t('{username} has scheduled you on {date} {start} - {end}, contrary to your original entry.', { username: conflict.user_name, date: conflict.date_casted, start: conflict.start_time, end: conflict.end_time }) }}
            </p>
        </div>

    </div>

    <AddEditVacationsModal :createShowDate="createShowDate" :edit-vacation="vacation" :user="user" v-if="showEditVacationModal" :vacationSelectCalendar="vacationSelectCalendar" @closed="showEditVacationModal = false" />

    <ConfirmDeleteModal
        v-if="showDeleteSeriesConfirmModal"
        :title="modalTexts.title"
        :button="modalTexts.button"
        :description="modalTexts.description"
        :is-series-delete="isSeries"
        :is_budget="false"
        @closed="showDeleteSeriesConfirmModal = false"
        @complete_delete="deleteCompleteSeries"
        @delete="deleteAvailabilityOrVacation" />
</template>
<script>
import {defineComponent} from 'vue'
import Button from "@/Jetstream/Button.vue";
import AddEditVacationsModal from "@/Pages/Users/Components/AddEditVacationsModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import Permissions from "@/Mixins/Permissions.vue";
import {router} from "@inertiajs/vue3";
export default defineComponent({
    name: "SingleUserVacation",
    mixins: [Permissions],
    components: {ConfirmDeleteModal, AddEditVacationsModal, Button},
    props: ['vacation', 'user','type', 'createShowDate', 'vacationSelectCalendar'],
    data(){
        return {
            showEditVacationModal: false,
            showDeleteConfirmModal: false,
            showDeleteSeriesConfirmModal: false,
            modalTexts: {
                title: 'Urlaub Löschen?',
                description: 'Bist du sicher, dass du den ausgewählten Urlaub löschen möchtest?',
                button: 'Löschen'
            },
            isSeries: false
        }
    },
    computed: {
    },
    methods: {
        openShowEditVacationModal(){
            router.reload({
                data: {
                    vacationMonth: this.vacation.date,
                },
                onFinish: () => {
                    this.showEditVacationModal = true
                }
            })
        },
        deleteCompleteSeries(){
            if(this.vacation.type === 'available') {
                router.delete(route('delete.availability.series', this.vacation.series_id), {
                    preserveScroll: true, onFinish: () => {
                        this.showDeleteSeriesConfirmModal = false
                    }
                })
            }
            if (this.vacation.type === 'vacation'){
                router.delete(route('delete.vacation.series', this.vacation.series_id), {
                    preserveScroll: true, onFinish: () => {
                        this.showDeleteSeriesConfirmModal = false
                    }
                })
            }
        },
        checkIfVacationIsSeries(){
            if(this.vacation.is_series){
                this.modalTexts.title = 'Serieneintrag löschen'
                this.modalTexts.description = 'Möchtest Du nur diesen Eintrag löschen oder die ganze Serie?'
                this.modalTexts.button = 'Einzeleintrag löschen'
                this.isSeries = true
                this.showDeleteSeriesConfirmModal = true
            } else {
                this.isSeries = false
                this.showDeleteSeriesConfirmModal = true
            }
        },
        deleteAvailabilityOrVacation(){
            if(this.vacation.type === 'available') {
                router.delete(route('delete.availability', this.vacation.id), {
                    preserveScroll: true, preserveState: true, onFinish: () => {
                        this.closeModal(true)
                    }
                })
            }
            if (this.vacation.type === 'vacation'){
                router.delete(route('delete.vacation', this.vacation.id), {
                    preserveScroll: true, preserveState: true, onFinish: () => {
                        this.closeModal(true)
                    }
                })
            }
        }
    },
})
</script>
<style scoped>

</style>
