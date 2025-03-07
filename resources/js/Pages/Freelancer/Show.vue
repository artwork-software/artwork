<template>
    <AppLayout :title="$t('Freelancer') + ' ' + freelancer.first_name + ' ' + freelancer.last_name + ' ' + $t('edit')">
        <div class="mt-12 ml-14">
            <div class="flex justify-between items-center">
                <div class="group block flex-shrink-0">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center">
                            <img class="inline-block h-16 w-16 rounded-full object-fill" :src="freelancer.profile_photo_url" alt="" />
                        </div>
                        <div class="ml-3">
                            <h3 class="headline1">
                                {{ freelancer.first_name }} {{freelancer.last_name}}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-10">
                <div class="hidden sm:block">
                    <div class="">
                        <nav class="-mb-px flex space-x-8 uppercase xxsDark" aria-label="Tabs">
                            <div v-for="tab in tabs" v-show="tab.has_permission" :key="tab.name" @click="changeTab(tab.id)" :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create font-bold' : 'border-transparent', 'whitespace-nowrap border-b-2 py-2 px-1 cursor-pointer']" :aria-current="tab.current ? 'page' : undefined">{{ tab.name }}</div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-12 ml-14 mr-40">
            <!-- Einsatzplan -->
            <div v-if="currentTab === 1">
                <UserShiftPlan type="freelancer"
                               :totalPlannedWorkingHours="totalPlannedWorkingHours"
                               :date-value="dateValue"
                               :whole-week-date-period="wholeWeekDatePeriod"
                               :events-with-total-planned-working-hours="eventsWithTotalPlannedWorkingHours"
                               :projects="projects"
                               :event-types="eventTypes"
                               :rooms="rooms"
                               :vacations="vacations"
                               :shift-qualifications="shiftQualifications"
                               :firstProjectShiftTabId="firstProjectShiftTabId"
                               :user-to-edit-whole-week-date-period-vacations="freelancer_to_edit_whole_week_date_period_vacations"
                               :user-to-edit-id="freelancer.id"/>
                <Availability type="freelancer"
                              :create-show-date="createShowDate"
                              :show-vacations-and-availabilities-date="showVacationsAndAvailabilitiesDate"
                              :vacation-select-calendar="vacationSelectCalendar"
                              :calendar-data="calendarData"
                              :date-to-show="dateToShow"
                              :user="freelancer"
                              :vacations="vacations"
                              :availabilities="availabilities"/>
            </div>
            <!-- Persönliche Daten -->
            <div v-if="currentTab === 2">
                <!-- Profilbild, Name, Nachname -->
                <div class="grid grid-cols-1 sm:grid-cols-8 gap-4">
                    <div class="col-span-1">
                        <input
                            ref="photoInput"
                            type="file"
                            class="hidden"
                            @change="updatePhotoPreview"
                        >
                        <!-- Current Profile Photo -->
                        <div v-show="! photoPreview" class="mt-2">
                            <img :src="freelancer.profile_photo_url" :alt="freelancer.first_name"  @click="selectNewPhoto" class="rounded-full h-20 w-20 object-cover cursor-pointer">
                        </div>
                        <!-- New Profile Photo Preview -->
                        <div v-show="photoPreview" class="mt-2" @click="selectNewPhoto">
                                <span
                                    class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                                    :style="'background-image: url(\'' + photoPreview + '\');'"
                                />
                        </div>

                    </div>
                    <div class="col-span-3">
                        <TextInputComponent id="first_name" v-model="freelancerData.first_name" @focusout="saveFreelancer" :label="$t('First name')" />
                    </div>
                    <div class="col-span-4">
                        <TextInputComponent id="last_name" v-model="freelancerData.last_name" @focusout="saveFreelancer" :label="$t('Last name')" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">
                    <div class="col-span-1">
                        <TextInputComponent readonly label="" id="textFreelancer" disabled v-model="freelancerData.placeholder" />
                    </div>
                    <div class="col-span-1">
                        <TextInputComponent v-model="freelancerData.position" @focusout="saveFreelancer" :disabled="checkCanEdit" :readonly="checkCanEdit" name="position" id="position" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Position')" />
                    </div>
                    <div class="col-span-1">
                        <TextInputComponent type="email" v-model="freelancerData.email" @focusout="saveFreelancer" :disabled="checkCanEdit" :readonly="checkCanEdit" name="email" id="email" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Email')" />
                    </div>
                    <div class="col-span-1">
                        <TextInputComponent type="text" v-model="freelancerData.phone_number" @focusout="saveFreelancer" :disabled="checkCanEdit" :readonly="checkCanEdit" name="phone_number" id="phone_number" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Phone number')" />
                    </div>
                    <div class="col-span-1">
                        <TextInputComponent type="text" v-model="freelancerData.street" @focusout="saveFreelancer" :disabled="checkCanEdit" :readonly="checkCanEdit" name="street" id="street" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Street')" />
                    </div>
                    <div class="col-span-1"></div>
                    <div class="col-span-1">
                        <TextInputComponent type="text" v-model="freelancerData.zip_code" @focusout="saveFreelancer" :disabled="checkCanEdit" :readonly="checkCanEdit" name="zip_code" id="zip_code" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Zip code')" />
                    </div>
                    <div class="col-span-1">
                        <TextInputComponent type="text" v-model="freelancerData.location" @focusout="saveFreelancer" :disabled="checkCanEdit" :readonly="checkCanEdit" name="location" id="location" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Location')" />
                    </div>
                    <div class="col-span-full">
                        <TextareaComponent rows="4" v-model="freelancerData.note" @focusout="saveFreelancer" :disabled="checkCanEdit" :readonly="checkCanEdit" name="note" id="note" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Note')" />
                    </div>
                </div>
            </div>
            <div v-if="currentTab === 3">
                <UserTermsTab user_type="freelancer" :user_to_edit="freelancer"></UserTermsTab>
            </div>
            <div v-if="currentTab === 4">
                <WorkProfileTab user-type="freelancer" :user="freelancer" :shift-qualifications="shiftQualifications"/>
            </div>
        </div>
        <SuccessModal v-if="showSuccessModal" @close-modal="showSuccessModal = false" :title="$t('Freelancer successfully processed')" :description="$t('The changes have been saved successfully.')" :button="$t('Ok')" />
    </AppLayout>
</template>


<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import {DotsVerticalIcon, PencilAltIcon, TrashIcon} from "@heroicons/vue/outline";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {router, useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import UserTermsTab from "@/Pages/Users/Tabs/UserTermsTab.vue";
import Availability from "@/Pages/Users/Components/Availability.vue";
import UserShiftPlan from "@/Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue";
import WorkProfileTab from "@/Pages/Components/WorkProfileTab.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";

export default {
    name: "Show",
    mixins: [Permissions],
    components: {
        TextareaComponent,
        TextInputComponent,
        BaseMenu,
        FormButton,
        SuccessModal,
        WorkProfileTab,
        UserShiftPlan,
        Availability,
        UserTermsTab,
        PencilAltIcon,
        DotsVerticalIcon,
        TrashIcon,
        AppLayout,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem
    },
    props: [
        'freelancer',
        'shifts',
        'calendarData',
        'dateToShow',
        'wholeWeekDatePeriod',
        'eventsWithTotalPlannedWorkingHours',
        'vacations',
        'dateValue',
        'daysWithEvents',
        'rooms',
        'eventTypes',
        'projects',
        'totalPlannedWorkingHours',
        'vacationSelectCalendar',
        'createShowDate',
        'showVacationsAndAvailabilitiesDate',
        'availabilities',
        'shiftQualifications',
        'freelancer_to_edit_whole_week_date_period_vacations',
        'firstProjectShiftTabId'
    ],
    mounted() {
        this.showSidebar = true;
        setTimeout(() => {
            this.showSidebar = false;
        }, 1000)
    },
    data(){
        return {
            tabs: [
                { id: 1, name: this.$t('Operational plan'), href: '#', current: false, has_permission: this.$can('can plan shifts') || this.hasAdminRole() },
                { id: 2, name: this.$t('Personal data'), href: '#', current: true, has_permission: true },
                { id: 3, name: this.$t('Conditions'), href: '#', current: false, has_permission: this.$can('can edit external users conditions') || this.hasAdminRole() },
                { id: 4, name: this.$t('Work profile'), href: '#', current: false, has_permission: this.$can('can manage workers') || this.hasAdminRole() },
            ],
            showSuccessModal: false,
            currentTab: 2,
            freelancerData: useForm({
                placeholder: 'Freelancer (extern)',
                first_name: this.freelancer.first_name,
                last_name: this.freelancer.last_name,
                position: this.freelancer.position,
                email: this.freelancer.email,
                phone_number: this.freelancer.phone_number,
                street: this.freelancer.street,
                zip_code: this.freelancer.zip_code,
                location: this.freelancer.location,
                note: this.freelancer.note,
            }),
            photoPreview: null,
            showSidebar: false
        }
    },
    computed: {
        checkCanEdit(){
            return !(this.$can('can manage workers') || this.hasAdminRole());
        },
    },
    methods: {
        changeTab(tabId){
            this.tabs.forEach((tab) => {
                tab.current = tab.id === tabId;
                this.currentTab = tabId;
            })
        },
        saveFreelancer(){
            if (this.freelancerData.isDirty){
                this.freelancerData.patch(route('freelancer.update', this.freelancer.id), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.openSuccessModal();

                    }
                })
            }
        },
        openSuccessModal() {
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
        },
        selectNewPhoto(){
            if( this.$can('can manage workers') || this.hasAdminRole()){
                this.$refs.photoInput.click();
            }
        },
        updatePhotoPreview(){
            const photo = this.$refs.photoInput.files[0];


            if (! photo) return;

            const reader = new FileReader();

            reader.onload = (e) => {
                this.photoPreview = e.target.result;
            };

            reader.readAsDataURL(photo);

            router.post(route('freelancer.change.profile-image', this.freelancer.id),{
                profileImage: photo,
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    this.openSuccessModal();

                }
            })
        },
    }
}
</script>
