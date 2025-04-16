<template>
    <AppLayout :title="serviceProvider.provider_name + ' ' + $t('edit')">
        <div class="mt-12 pl-14">
            <div class="flex justify-between items-center">
                <div class="group block flex-shrink-0">
                    <div class="flex items-center">
                        <div>
                            <img class="inline-block h-16 w-16 rounded-full object-cover" :src="serviceProvider.profile_photo_url" alt="" />
                        </div>
                        <div class="ml-3">
                            <h3 class="headline1">
                                {{ serviceProvider.provider_name }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-10">
                <div class="hidden sm:block">
                    <div class="">
                        <nav class="-mb-px flex space-x-8 uppercase xxsDark" aria-label="Tabs">
                            <div v-for="tab in tabs" v-show="tab.has_permission" :key="tab.name" @click="changeTab(tab.id)" :class="[tab.current ? 'border-artwork-buttons-create text-indigo-600 font-bold' : 'border-transparent', 'whitespace-nowrap border-b-2 py-2 px-1 cursor-pointer']" :aria-current="tab.current ? 'page' : undefined">{{ tab.name }}</div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-12 pl-14 mr-40">
            <div v-if="currentTab === 1">
                <UserShiftPlan type="service_provider"
                               :total-planned-working-hours="totalPlannedWorkingHours"
                               :date-value="dateValue"
                               :whole-week-date-period="wholeWeekDatePeriod"
                               :events-with-total-planned-working-hours="eventsWithTotalPlannedWorkingHours"
                               :projects="projects"
                               :event-types="eventTypes"
                               :rooms="rooms"
                               :shift-qualifications="shiftQualifications"
                               :firstProjectShiftTabId="firstProjectShiftTabId"
                               :user-to-edit-id="serviceProvider.id"/>
            </div>
            <div v-if="currentTab === 2">
                <UserTermsTab user_type="service_provider" :user_to_edit="serviceProvider"></UserTermsTab>
            </div>
            <!-- Persönliche Daten -->
            <div v-if="currentTab === 3">
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
                            <img :src="serviceProvider.profile_photo_url" :alt="serviceProvider.provider_name" @click="selectNewPhoto" class="rounded-full h-20 w-20 object-cover cursor-pointer">
                        </div>

                        <!-- New Profile Photo Preview -->
                        <div v-show="photoPreview" class="mt-2" @click="selectNewPhoto">
                                <span
                                    class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                                    :style="'background-image: url(\'' + photoPreview + '\');'"
                                />
                        </div>

                    </div>
                    <div class="col-span-7">
                        <div>
                            <BaseInput v-model="providerData.provider_name" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="first_name" id="first_name" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Company name')" />
                        </div>
                    </div>
                </div>

                <div class="max-w-lg">
                    <VisualFeedback :show-save-success="showSavedSuccess"/>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">
                    <div class="col-span-1">
                        <BaseInput type="email" v-model="providerData.street" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="street" id="street" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Street')" />
                    </div>
                    <div class="col-span-1">
                        <BaseInput type="email" v-model="providerData.zip_code" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="zip_code" id="zip_code" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Zip code')" />
                    </div>
                    <div class="col-span-1">
                        <BaseInput type="email" v-model="providerData.location" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="location" id="location" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Location')" />
                    </div>
                    <div class="col-span-1">
                        <BaseInput type="email" v-model="providerData.email" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="email" id="email" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Email')" />
                    </div>
                    <div class="col-span-1">
                        <BaseInput type="email" v-model="providerData.phone_number" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="phone_number" id="phone_number" :class="checkCanEdit ? 'bg-gray-200' : ''" :label="$t('Phone number')" />
                    </div>
                    <div class="col-span-full">
                        <BaseTextarea rows="4" v-model="providerData.note" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="note" id="note" :label="$t('Note')" :class="checkCanEdit ? 'bg-gray-200' : ''" />
                    </div>
                </div>

                <div class="mt-10 mb-10">
                    <h3 class="headline3">
                        {{ $t('Contact persons')}}
                    </h3>

                    <ul role="list" class="divide-y divide-gray-100 mt-5" v-if="checkCanEdit">
                        <li v-for="contact in serviceProvider.contacts" :key="contact.email" class="flex justify-between gap-x-6 py-5">
                            <div class="flex gap-x-4">
                                <div class="min-w-0 flex">
                                    <p class="text-sm font-semibold leading-6 text-gray-900">{{ contact.first_name }} {{ contact.last_name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                    <span class="w-fit">
                                        {{ contact.phone_number }}
                                    </span>
                                |
                                <span class="w-52">
                                        {{ contact.email }}
                                    </span>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="mt-10">
                        <div v-for="contact in serviceProvider.contacts">
                            <SingleContact :contact="contact"/>
                        </div>
                        <div>
                            <PlusCircleIcon class="w-5 h-5" @click="addContact" />
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="currentTab === 4">
                <WorkProfileTab user-type="serviceProvider"
                                :user="serviceProvider"
                                :shift-qualifications="shiftQualifications"
                />
            </div>
        </div>
        <SuccessModal v-if="showSuccessModal" @close-modal="showSuccessModal = false" :title="$t('Service provider successfully processed')" :description="$t('The changes have been saved successfully.')" button="Ok" />
    </AppLayout>
</template>


<script>
import {defineComponent} from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue";
import {DotsVerticalIcon, PencilAltIcon, PlusCircleIcon, TrashIcon} from "@heroicons/vue/outline";
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption, ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import SingleContact from "@/Pages/ServiceProvider/Components/SingleContact.vue";
import UserTermsTab from "@/Pages/Users/Tabs/UserTermsTab.vue";
import UserShiftPlan from "@/Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import WorkProfileTab from "@/Pages/Components/WorkProfileTab.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import VisualFeedback from "@/Components/Feedback/VisualFeedback.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

export default defineComponent({
    name: "Show",
    mixins: [Permissions],
    components: {
        BaseTextarea,
        BaseInput,
        VisualFeedback,
        ListboxOptions, ListboxOption, Listbox, ListboxLabel, ListboxButton,
        TextareaComponent,
        TextInputComponent,
        BaseMenu,
        FormButton,
        SuccessModal,
        WorkProfileTab,
        BaseSidenav,
        UserShiftPlan,
        UserTermsTab,
        SingleContact,
        PencilAltIcon,
        DotsVerticalIcon,
        TrashIcon,
        AppLayout,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem,
        PlusCircleIcon
    },
    props: [
        'serviceProvider',
        'shifts',
        'dateValue',
        'wholeWeekDatePeriod',
        'eventsWithTotalPlannedWorkingHours',
        'rooms',
        'eventTypes',
        'projects',
        'totalPlannedWorkingHours',
        'shiftQualifications',
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
            showSuccessModal: false,
            tabs: [
                { id: 1, name: this.$t('Operational plan'), href: '#', current: false, has_permission: this.hasAdminRole() },
                { id: 2, name: this.$t('Conditions'), href: '#', current: false, has_permission: this.$can('can edit external users conditions') || this.hasAdminRole() },
                { id: 3, name: this.$t('Company data'), href: '#', current: true, has_permission: true },
                { id: 4, name: this.$t('Work profile'), href: '#', current: false, has_permission: this.$can('can manage workers') || this.hasAdminRole() },
            ],
            currentTab: 3,
            providerData: useForm({
                input: 'Dienstleister (extern)',
                provider_name: this.serviceProvider.provider_name,
                email: this.serviceProvider.email,
                phone_number: this.serviceProvider.phone_number,
                street: this.serviceProvider.street,
                zip_code: this.serviceProvider.zip_code,
                location: this.serviceProvider.location,
                note: this.serviceProvider.note,
                type_of_provider: this.serviceProvider.type_of_provider,
            }),
            types: ['work', 'housing'],
            photoPreview: null,
            showSidebar: false,
            showSavedSuccess: false,
        }
    },
    computed: {
        checkCanEdit(){
            return !(this.$can('can manage workers') || this.hasAdminRole());
        },
    },
    methods: {
        usePage,
        addContact(){
            router.post(route('service-provider.contact.store', this.serviceProvider.id), {
            }, {
                preserveState: true, preserveScroll: true
            })
        },
        changeTab(tabId){
            this.tabs.forEach((tab) => {
                tab.current = tab.id === tabId;
                this.currentTab = tabId;
            })
        },
        saveProvider(){
            if (this.providerData.isDirty) {
                this.providerData.patch(route('service_provider.update', this.serviceProvider.id), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.showSavedSuccess = true
                        setTimeout(() => {
                            this.showSavedSuccess = false
                        }, 3000)
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

            router.post(route('service_provider.change.profile-image', this.serviceProvider.id),{
                profileImage: photo,
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    this.showSavedSuccess = true
                    setTimeout(() => {
                        this.showSavedSuccess = false
                    }, 3000)
                }
            })
        },
    }
})
</script>

<style scoped>

</style>
