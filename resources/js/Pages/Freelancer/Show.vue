<template>
    <AppLayout title="Freelancer">
        <div class="max-w-screen-lg mt-12 ml-14 mr-40">
            <div class="flex justify-between w-full items-center">
                <div class="group block flex-shrink-0">
                    <div class="flex items-center">
                        <div>
                            <img class="inline-block h-16 w-16 rounded-full object-cover" :src="freelancer.profile_image" alt="" />
                        </div>
                        <div class="ml-3">
                            <h3 class="headline1">
                                {{ freelancer.name }}
                            </h3>
                        </div>
                    </div>
                </div>
                <div>
                    <Menu as="div" class="my-auto relative">
                        <div>
                            <MenuButton class="flex">
                                <DotsVerticalIcon class="mr-3 ml-6 flex-shrink-0 h-6 w-6 text-gray-600 my-auto bg-buttonBlue/10 rounded-full" aria-hidden="true"/>
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="origin-top-right absolute p-4 mr-4 mt-2 w-80 shadow-lg bg-primary focus:outline-none">
                                <div>

                                    <MenuItem v-slot="{ active }">

                                        <a href="#" @click="openChangeTeamsModal"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Teamzugehörigkeit bearbeiten
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="deleteFromAllDepartments"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Nutzer*in aus allen Teams entfernen
                                        </a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>

            <div class="mt-10">
                <div class="hidden sm:block">
                    <div class="">
                        <nav class="-mb-px flex space-x-8 uppercase xxsDark" aria-label="Tabs">
                            <div v-for="tab in tabs" :key="tab.name" @click="changeTab(tab.id)" :class="[tab.current ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent', 'whitespace-nowrap border-b-2 py-2 px-1 cursor-pointer']" :aria-current="tab.current ? 'page' : undefined">{{ tab.name }}</div>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="mt-10">
                <!-- Einsatzplan -->
                <div v-if="currentTab === 1">
                    <UserShiftPlan :total-planned-working-hours="totalPlannedWorkingHours" type="freelancer" :date-value="dateValue"
                                   :days-with-events="daysWithEvents"
                                   :projects="projects" :event-types="eventTypes" :rooms="rooms"
                                   :vacations="vacations"></UserShiftPlan>
                    <Availability type="freelancer" :calendar-data="calendarData" :date-to-show="dateToShow"
                                  :user="freelancer" :vacations="vacations"/>
                </div>
                <!-- Persönliche Daten -->
                <div v-if="currentTab === 2">
                    <!-- Profilbild, Name, Nachname -->
                    <div class="grid grid-cols-1 sm:grid-cols-8 gap-4 flex items-center">
                        <div class="col-span-1">
                            <input
                                ref="photoInput"
                                type="file"
                                class="hidden"
                                @change="updatePhotoPreview"
                            >

                            <!-- Current Profile Photo -->
                            <div v-show="! photoPreview" class="mt-2">
                                <img :src="freelancer.profile_image" :alt="freelancer.first_name"  @click="selectNewPhoto" class="rounded-full h-20 w-20 object-cover cursor-pointer">
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
                            <label for="first_name" class="xxsLight">Vorname</label>
                            <div>
                                <input type="text" v-model="freelancerData.first_name" :disabled="checkCanEdit" :readonly="checkCanEdit" name="first_name" id="first_name" class="block w-full border-b-2 border-transparent border-b-gray-200 py-1.5 text-gray-900 ring-0 ring-inset placeholder:text-gray-400 sm:text-sm sm:leading-6" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Vorname" />
                            </div>
                        </div>
                        <div class="col-span-4">
                            <label for="last_name" class="xxsLight">Nachname</label>
                            <div>
                                <input type="text" v-model="freelancerData.last_name" :disabled="checkCanEdit" :readonly="checkCanEdit" name="last_name" id="last_name" class="block w-full border-b-2 border-transparent border-b-gray-200 py-1.5 text-gray-900 ring-0 ring-inset placeholder:text-gray-400 sm:text-sm sm:leading-6" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Nachname" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">
                        <div class="col-span-1">
                            <input type="text" readonly class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8 bg-gray-200" placeholder="Freelancer" disabled value="Freelancer" />
                        </div>
                        <div class="col-span-1">
                            <input type="text" v-model="freelancerData.position" :disabled="checkCanEdit" :readonly="checkCanEdit" name="position" id="position" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Position" />
                        </div>
                        <div class="col-span-1">
                            <input type="email" v-model="freelancerData.email" :disabled="checkCanEdit" :readonly="checkCanEdit" name="email" id="email" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Email" />
                        </div>
                        <div class="col-span-1">
                            <input type="email" v-model="freelancerData.phone_number" :disabled="checkCanEdit" :readonly="checkCanEdit" name="phone_number" id="phone_number" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Telefonnummer" />
                        </div>
                        <div class="col-span-1">
                            <input type="email" v-model="freelancerData.street" :disabled="checkCanEdit" :readonly="checkCanEdit" name="street" id="street" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Straße" />
                        </div>
                        <div class="col-span-1"></div>
                        <div class="col-span-1">
                            <input type="email" v-model="freelancerData.zip_code" :disabled="checkCanEdit" :readonly="checkCanEdit" name="zip_code" id="zip_code" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="PLZ" />
                        </div>
                        <div class="col-span-1">
                            <input type="email" v-model="freelancerData.location" :disabled="checkCanEdit" :readonly="checkCanEdit" name="location" id="location" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Ort" />
                        </div>
                        <div class="col-span-full">
                            <textarea rows="4" v-model="freelancerData.note" :disabled="checkCanEdit" :readonly="checkCanEdit" name="note" id="note" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" :class="checkCanEdit ? 'bg-gray-200' : ''" placeholder="Notiz" />
                        </div>
                    </div>

                    <AddButton class="mt-5 !ml-0" text="Änderung Speichern" :disabled="checkCanEdit" :readonly="checkCanEdit" type="secondary" @click="saveFreelancer" />
                </div>
                <div v-if="currentTab === 3">
                    <UserTermsTab user_type="freelancer" :user_to_edit="freelancer"></UserTermsTab>
                </div>

            </div>
            <BaseSidenav :show="showSidebar" @toggle="this.showSidebar =! this.showSidebar" >
                <UserSidebar :user="freelancer" type="freelancer"  />
            </BaseSidenav>
        </div>


    </AppLayout>
</template>


<script>
import {defineComponent} from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue";
import {DotsVerticalIcon, PencilAltIcon, TrashIcon} from "@heroicons/vue/outline";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {useForm} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {Inertia} from "@inertiajs/inertia";
import Permissions from "@/mixins/Permissions.vue";
import UserTermsTab from "@/Pages/Users/Tabs/UserTermsTab.vue";
import Availability from "@/Pages/Users/Components/Availability.vue";
import UserShiftPlan from "@/Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import UserSidebar from "@/Pages/Users/Components/UserSidebar.vue";

export default defineComponent({
    name: "Show",
    mixins: [Permissions],
    components: {
        UserSidebar, BaseSidenav,
        UserShiftPlan, Availability,
        UserTermsTab,
        AddButton,
        PencilAltIcon, DotsVerticalIcon, TrashIcon,
        AppLayout, Menu, MenuButton, MenuItems, MenuItem
    },
    props: [
        'freelancer',
        'shifts',
        'calendarData',
        'dateToShow',
        'vacations',
        'dateValue',
        'daysWithEvents',
        'rooms',
        'eventTypes',
        'projects',
        'totalPlannedWorkingHours'
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
                { id: 1, name: 'Einsatzplan', href: '#', current: false },
                { id: 2, name: 'Persönliche Daten', href: '#', current: true },
                { id: 3, name: 'Konditionen', href: '#', current: false },

            ],
            currentTab: 2,
            freelancerData: useForm({
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
            this.freelancerData.patch(route('freelancer.update', this.freelancer.id), {
                preserveState: true,
                preserveScroll: true
            })
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

            Inertia.post(route('freelancer.change.profile-image', this.freelancer.id),{
                profileImage: photo,
            }, {
                preserveScroll: true,
                preserveState: true
            })
        },
    }
})
</script>

<style scoped>

</style>
