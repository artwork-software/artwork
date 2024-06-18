<template>
    <div class="bg-backgroundGray mt-6 pb-6">
        <div class="ml-14 pt-3 pr-14 ">
            <div class="flex justify-between items-center mt-4 stickyHeader p-4">
                <div class="flex items-center gap-6">
                    <div class="flex w-full justify-between">
                        <SwitchGroup as="div" class="flex items-center" v-if="checkCommitted && (this.$can('can commit shifts') || this.hasAdminRole())">
                            <Switch v-model="hasUncommittedShift"
                                    @update:modelValue="updateCommitmentOfShifts"
                                    :class="[!hasUncommittedShift ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                                <span aria-hidden="true"
                                      :class="[!hasUncommittedShift ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                            </Switch>
                            <SwitchLabel as="span" class="ml-3 text-sm">
                                <span class="font-medium text-gray-900">{{ $t('Fixed') }}</span>
                            </SwitchLabel>
                        </SwitchGroup>
                        <div v-if="conflictMessage.length > 0" class="text-red-500">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                <div class="pl-2">
                                    {{ $t('Shift conflict') }}:
                                </div>
                                <div class="flex divide-x divide-red-500">
                                    <div v-for="(conflict, index) in conflictMessage" :class="index < 0 ? 'pr-2' : 'px-2'">
                                        {{ dayjs(conflict.date).format('DD.MM.YYYY') }}, {{ conflict.abbreviation }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div ref="userWindowButton" @click="openUserWindow()">
                            <IconUsers class="h-6 w-6"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-5">
                <transition
                    enter-active-class="transition ease-out duration-100"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95">
                    <div class="z-50 origin-top-right absolute right-10 px-4 py-2 w-80 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none"
                        v-show="userWindow" ref="containerRef">
                        <div class="flex items-center justify-between">
                            <div class="flex gap-4 items-center" @click="openFilter = !openFilter">
                                <IconFilter class="text-white" />
                                <span v-if="openFilter">
                                   <IconChevronDown class="h-5 w-5 text-white"/>
                                </span>
                                <span v-else>
                                    <IconChevronUp class="h-5 w-5 text-white"/>
                                </span>
                            </div>
                            <div class="flex items-center pl-2 py-1">
                                <Switch @click="toggleCompactMode"
                                        :class="[this.$page.props.user.compact_mode ?
                                        'bg-artwork-buttons-create' :
                                        'bg-gray-300',
                                        'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none']">
                                    <span aria-hidden="true"
                                          :class="[this.$page.props.user.compact_mode ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                                </Switch>
                                <div :class="[this.$page.props.user.compact_mode ? 'xsLight text-secondaryHover' : 'xsLight','ml-1']">
                                    {{$t('Compact Mode')}}
                                </div>
                            </div>
                            <div>
                                <IconX class="h-5 w-5 text-white" @click="userWindow = !userWindow"/>
                            </div>
                        </div>
                        <div class="" v-if="openFilter">
                            <div class="my-5">
                                <div class="flex w-full mb-3">
                                    <input v-model="showIntern"
                                           type="checkbox"
                                           class="cursor-pointer h-5 w-5 text-success border-2 border-gray-300 focus:ring-0 active:ring-0"/>
                                    <div :class="[showIntern ? 'xsWhiteBold' : 'xsLight', 'my-auto ml-2']">
                                        {{ $t('Internal employees') }}
                                    </div>
                                </div>
                                <div class="flex w-full mb-3">
                                    <input v-model="showExtern"
                                           type="checkbox"
                                           class="cursor-pointer h-5 w-5 text-success border-2 border-gray-300 focus:ring-0 active:ring-0"/>
                                    <div :class="[showExtern ? 'xsWhiteBold' : 'xsLight', 'my-auto ml-2']">
                                        {{ $t('External employees') }}
                                    </div>
                                </div>
                                <div class="flex w-full mb-3">
                                    <input v-model="showProvider"
                                           type="checkbox"
                                           class="cursor-pointer h-5 w-5 text-success border-2 border-gray-300 focus:ring-0 active:ring-0"/>
                                    <div :class="[showProvider ? 'xsWhiteBold' : 'xsLight', 'my-auto ml-2']">
                                        {{ $t('Service provider') }}
                                    </div>
                                </div>
                                <div class="flex w-full mb-3" v-for="craft in crafts" :key="craft.id">
                                    <input type="checkbox"
                                           class="cursor-pointer h-5 w-5 text-success border-2 border-gray-300 focus:ring-0 active:ring-0"
                                           v-model="activeCraftFilters"
                                           :id="'craft-' + craft.id"
                                           :value="craft.id"
                                    />
                                    <div class="xsLight my-auto ml-2">
                                        {{craft.name}}
                                    </div>
                                </div>
                                <div>
                                    <div>
                                        <label for="account-number" class="block text-xs font-medium leading-6 text-white">
                                            {{ $t('Search') }}
                                        </label>
                                        <div class="relative mt-2 rounded-md shadow-sm">
                                            <input v-model="userSearch" type="text" name="account-number" id="account-number" class="block w-full border-0 py-1.5 pr-10 text-gray-900 ring-0  placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6" />
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                <IconSearch class="h-5 w-5 text-gray-400" aria-hidden="true" v-if="userSearch.length === 0" />
                                                <IconX class="h-5 w-5 text-gray-400 cursor-pointer" aria-hidden="true" v-if="userSearch.length > 0" @click="userSearch = ''"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <CraftFilter is_tiny :crafts="loadedProjectInformation['ShiftTab'].crafts"/>
                            </div>
                            <div class="my-2 h-0.5 w-full bg-[#3A374D]">
                            </div>
                        </div>
                        <div @mousedown="preventContainerDrag" class="max-h-72 shiftUserWindow">
                            <div v-for="craft in searchUserWithCrafts">
                                <div @click="changeCraftVisibility(craft.id)" class="text-xs text-white flex cursor-pointer items-center h-6" v-if="craft.users.length > 0">
                                    {{ craft.name }}
                                    <IconChevronDown
                                        :class="closedCrafts.includes(craft.id) ? '' : 'rotate-180 transform'"
                                        class="h-4 w-4"
                                    />
                                </div>
                                <div v-for="user in craft.users" v-if="!closedCrafts.includes(craft.id)">
                                    <DragElement :item="user.element"
                                                 :plannedHours="user.plannedWorkingHours"
                                                 :expected-hours="user.expectedWorkingHours"
                                                 :type="user.type"
                                                 :color="craft.color"
                                    />
                                </div>
                            </div>

                            <div v-if="searchUserWithoutCrafts.length > 0">
                                <div @click="changeCraftVisibility('noCraft')" class="text-xs text-white flex cursor-pointer items-center h-6">
                                    {{ $t('Without craft assignment')}}
                                    <IconChevronDown
                                        :class="closedCrafts.includes('noCraft') ? '' : 'rotate-180 transform'"
                                        class="h-4 w-4"
                                    />
                                </div>
                                <div v-for="user in searchUserWithoutCrafts" v-if="!closedCrafts.includes('noCraft')">
                                    <DragElement :item="user.element"
                                                 :plannedHours="user.plannedWorkingHours"
                                                 :expected-hours="user.expectedWorkingHours"
                                                 :type="user.type"
                                                 :color="null"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
                <div class="xsDark" v-if="loadedProjectInformation['ShiftTab'].events_with_relevant.length === 0">
                    {{ $t('So far, there are no shift-relevant dates for this project.') }}
                </div>
                <SingleRelevantEvent v-for="event in loadedProjectInformation['ShiftTab'].events_with_relevant"
                                     :crafts="loadedProjectInformation['ShiftTab'].crafts"
                                     :currentUserCrafts="loadedProjectInformation['ShiftTab'].current_user_crafts"
                                     :event="event"
                                     :event-types="headerObject.eventTypes"
                                     :shift-qualifications="loadedProjectInformation['ShiftTab'].shift_qualifications"
                                     @dropFeedback="showDropFeedback"
                                     :shift-time-presets="loadedProjectInformation['ShiftTab'].shift_time_presets"
                />
            </div>
        </div>
    </div>


    <SideNotification v-if="dropFeedback" type="error" :text="dropFeedback" @close="dropFeedback = null"/>
</template>
<script>
import {defineComponent} from 'vue'
import {Menu, MenuButton, MenuItem, MenuItems, Switch, SwitchGroup, SwitchLabel} from '@headlessui/vue'
import {DuplicateIcon, PencilAltIcon} from "@heroicons/vue/outline";
import {DotsVerticalIcon, TrashIcon, XIcon} from "@heroicons/vue/solid";
import SingleShiftEvent from "@/Pages/Projects/Components/SingleRelevantEvent.vue";
import DragElement from "@/Pages/Projects/Components/DragElement.vue";
import SingleRelevantEvent from "@/Pages/Projects/Components/SingleRelevantEvent.vue";
import Input from "@/Jetstream/Input.vue";
import Permissions from "@/Mixins/Permissions.vue";
import {usePage} from "@inertiajs/vue3";
import dayjs from "dayjs";
import SideNotification from "@/Layouts/Components/General/SideNotification.vue";
import IconLib from "@/Mixins/IconLib.vue";
import {router} from "@inertiajs/vue3";
import CraftFilter from "@/Components/Filter/CraftFilter.vue";

export default defineComponent({
    name: "ShiftTab",
    inheritAttrs: false,
    props: [
        'loadedProjectInformation',
        'headerObject',
    ],
    mixins: [Permissions, IconLib],
    components: {
        CraftFilter,
        SideNotification,
        Input,
        SingleRelevantEvent,
        DragElement,
        SingleShiftEvent,
        PencilAltIcon,
        TrashIcon,
        DuplicateIcon,
        DotsVerticalIcon,
        Switch,
        SwitchGroup,
        SwitchLabel,
        Menu,
        MenuItems,
        MenuItem,
        MenuButton,
        XIcon,
    },
    data() {
        return {
            enabled: false,
            userWindow: false,
            openFilter: false,
            showIntern: false,
            showExtern: false,
            showProvider: false,
            activeCraftFilters: [],
            dropFeedback: null,
            closedCrafts: [],
            userSearch: ''
        }
    },
    watch: {
        userSearch: {
            handler(){
                if(this.userSearch.length > 0){
                    this.closedCrafts = [];
                }
            }
        }
    },
    computed: {
        dropUsers(){
            const users = [];
            if (this.loadedProjectInformation['ShiftTab']) {
                // Initialzustand: alle Benutzer anzeigen, wenn keine spezifischen Filter gesetzt sind
                const noFiltersApplied = !this.showIntern && !this.showExtern && !this.showProvider;

                // Filter für interne Benutzer
                if (this.showIntern || noFiltersApplied) {
                    this.loadedProjectInformation['ShiftTab'].users_for_shifts.forEach((user) => {
                        users.push({
                            element: user.user,
                            type: 0,
                            plannedWorkingHours: user.plannedWorkingHours,
                        });
                    });
                }

                // Filter für externe Benutzer (Freelancer)
                if (this.showExtern || noFiltersApplied) {
                    this.loadedProjectInformation['ShiftTab'].freelancers_for_shifts.forEach((freelancer) => {
                        users.push({
                            element: freelancer.freelancer,
                            type: 1,
                            plannedWorkingHours: freelancer.plannedWorkingHours,
                        });
                    });
                }

                // Filter für Dienstleister
                if (this.showProvider || noFiltersApplied) {
                    this.loadedProjectInformation['ShiftTab'].service_providers_for_shifts.forEach((service_provider) => {
                        users.push({
                            element: service_provider.service_provider,
                            type: 2,
                            plannedWorkingHours: service_provider.plannedWorkingHours,
                        })
                    })
                }
            }
            return users;
        },
        conflictMessage(){
            let conflicts = [];

            this.loadedProjectInformation['ShiftTab'].events_with_relevant.forEach(event => {
                event.shifts.forEach(shift => {
                    shift.users.forEach(user => {
                        console.log(user);
                        if(user.formatted_vacation_days?.includes(shift.event_start_day)){
                            conflicts.push({ date: shift.event_start_day, abbreviation: shift.craft.abbreviation })
                        }
                    })
                })
            });

            return conflicts;
        },
        filteredUsers() {
            if (!this.showExtern && !this.showIntern && !this.showProvider && this.activeCraftFilters.length === 0) {
                return this.dropUsers;
            }
            let users = [];
            if (this.showIntern) {
                users = users.concat(this.dropUsers.filter(user => user.type === 0));
            }
            if (this.showExtern) {
                users = users.concat(this.dropUsers.filter(user => user.type === 1));
            }
            if (this.showProvider) {
                users = users.concat(this.dropUsers.filter(user => user.type === 2));
            }
            if (this.activeCraftFilters.length > 0) {
                this.activeCraftFilters.forEach((craftId) => {
                    users = users.concat(
                        this.dropUsers.filter(user => user.element.assigned_craft_ids.includes(craftId))
                    );
                });
                //remove duplicates from users
                users = Array.from(new Set(users));
            }
            return users;
        },
        craftsToDisplay() {
            const users = this.dropUsers;
            if (this.$page.props.user.show_crafts?.length === 0){
                return this.loadedProjectInformation['ShiftTab'].crafts?.map(craft => ({
                    name: craft.name,
                    id: craft.id,
                    users: users.filter(user => user.element.assigned_craft_ids?.includes(craft.id)),
                    color: craft?.color
                }));
            } else {
                return this.loadedProjectInformation['ShiftTab'].crafts?.map(craft => ({
                    name: craft.name,
                    id: craft.id,
                    users: users.filter(user => user.element.assigned_craft_ids?.includes(craft.id)),
                    color: craft?.color
                })).filter(craft => this.$page.props.user.show_crafts?.includes(craft.id));
            }
        },
        usersWithNoCrafts() {
            return this.dropUsers.filter(user =>
                !user.element.assigned_craft_ids || user.element.assigned_craft_ids?.length === 0
            );
        },
        hasUncommittedShift() {
            return this.loadedProjectInformation['ShiftTab']?.events_with_relevant.some(
                event => event.shifts.find(shift => shift.is_committed === false)
            );
        },
        searchUserWithoutCrafts() {
            // check if user Freelancer, User or Service Provider. USer and Freelancer has first and last name, service provider has name and add filter for showIntern, showExtern and showProvider
            return this.usersWithNoCrafts.filter(user => {
                if (user.element.first_name && user.element.last_name) {
                    return user.element.first_name.toLowerCase().includes(this.userSearch.toLowerCase()) ||
                        user.element.last_name.toLowerCase().includes(this.userSearch.toLowerCase());
                } else if (user.element.provider_name) {
                    return user.element.provider_name.toLowerCase().includes(this.userSearch.toLowerCase());
                }
            });
        },
        searchUserWithCrafts() {
            return this.craftsToDisplay.map(craft => ({
                ...craft,
                users: craft.users.filter(user => {
                    if (user.element.first_name && user.element.last_name) {
                        return user.element.first_name.toLowerCase().includes(this.userSearch.toLowerCase()) ||
                            user.element.last_name.toLowerCase().includes(this.userSearch.toLowerCase());
                    } else if (user.element.provider_name) {
                        return user.element.provider_name.toLowerCase().includes(this.userSearch.toLowerCase());
                    }
                })
            }));
        }
    },
    mounted() {
        this.makeContainerDraggable();
    },
    methods: {
        changeCraftVisibility(id) {
            if (this.closedCrafts.includes(id)) {
                this.closedCrafts.splice(this.closedCrafts.indexOf(id), 1);
            } else {
                this.closedCrafts.push(id);
            }
        },
        toggleCompactMode() {
            router.post(route('user.compact.mode.toggle', {user: this.$page.props.user.id}), {
                compact_mode: !this.$page.props.user.compact_mode
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        checkCommitted(){
            return this.loadedProjectInformation['ShiftTab'].events_with_relevant?.length > 0;
        },
        projectMembers: function () {
            let projectMemberArray = [];
            this.headerObject.project.users.forEach(member => {
                    projectMemberArray.push(member.id)
                }
            )
            return projectMemberArray;
        },
        showDropFeedback(feedback) {
            this.dropFeedback = feedback;
            setTimeout(() => {
                this.dropFeedback = null
            }, 2000)
        },
        dayjs,
        usePage,
        updateCommitmentOfShifts() {
            this.$inertia.patch(route('update.shift.commitment'), {
                project_id: this.headerObject.project.id,
                shifts: this.loadedProjectInformation['ShiftTab']?.events_with_relevant.flatMap(
                    event => event.shifts.map(shift => shift.id)
                ),
                is_committed: this.hasUncommittedShift,
                committing_user_id: this.$page.props.user.id
            }, {
                preserveScroll: true
            })
        },
        makeContainerDraggable() {
            const container = this.$refs.containerRef;
            let isDragging = false;
            let offsetX = 0;
            let offsetY = 0;

            container.addEventListener('mousedown', (event) => {
                isDragging = true;
                offsetX = event.clientX - container.offsetLeft;
                offsetY = event.clientY - container.offsetTop;
            });

            document.addEventListener('mousemove', (event) => {
                if (isDragging) {
                    container.style.left = `${event.clientX - offsetX}px`;
                    container.style.top = `${event.clientY - offsetY}px`;
                }
            });

            document.addEventListener('mouseup', () => {
                isDragging = false;
            });
        },
        preventContainerDrag(event) {
            event.stopPropagation();
        },
        openUserWindow() {
            if (this.userWindow) {
                this.userWindow = !this.userWindow;
            } else {
                const container = this.$refs.containerRef;
                const button = this.$refs.userWindowButton;

                // Berechnen Sie den Abstand des Buttons vom oberen Rand des Viewports
                const buttonRect = button.getBoundingClientRect();

                // Berücksichtigen Sie den Scroll-Stand des Fensters
                const scrollX = window.scrollX;
                const scrollY = window.scrollY;

                // Berechnen Sie die neue linke und obere Position des Containers
                const containerLeft = buttonRect.left + scrollX - 330;
                const containerTop = buttonRect.top + scrollY + button.offsetHeight;

                container.style.left = `${containerLeft}px`;
                container.style.top = `${containerTop}px`;
                this.userWindow = !this.userWindow;
            }
        }
    },
})
</script>


<style scoped>

.shiftUserWindow {
    overflow: overlay;
}

.stickyHeader {
    position: sticky;
    position: -webkit-sticky;
    display: block;
    top: 0;
    z-index: 21;
    background-color: #F5F5F3;
}

</style>
