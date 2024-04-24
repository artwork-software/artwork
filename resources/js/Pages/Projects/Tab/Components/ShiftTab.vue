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
                    <div
                        class="z-40 origin-top-right absolute right-10 px-4 py-2 w-80 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none"
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
                            </div>
                            <div class="my-2 h-0.5 w-full bg-[#3A374D]">
                            </div>
                        </div>
                        <div @mousedown="preventContainerDrag" class="max-h-72 shiftUserWindow">
                            <div v-for="user in filteredUsers">
                                <DragElement :item="user.element"
                                             :plannedHours="user.plannedWorkingHours"
                                             :expected-hours="user.expectedWorkingHours"
                                             :type="user.type"
                                />
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
import Permissions from "@/mixins/Permissions.vue";
import {usePage} from "@inertiajs/inertia-vue3";
import dayjs from "dayjs";
import SideNotification from "@/Layouts/Components/General/SideNotification.vue";
import IconLib from "@/mixins/IconLib.vue";

export default defineComponent({
    name: "ShiftTab",
    props: [
        'loadedProjectInformation',
        'headerObject',
    ],
    mixins: [Permissions, IconLib],
    components: {
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
            dropFeedback: null
        }
    },
    computed: {
        dropUsers(){
            const users = [];

            if (this.loadedProjectInformation['ShiftTab']) {
                this.loadedProjectInformation['ShiftTab'].users_for_shifts.forEach((user) => {
                    users.push({
                        element: user.user,
                        type: 0,
                        plannedWorkingHours: user.plannedWorkingHours,
                    })
                })
            }

            if (this.loadedProjectInformation['ShiftTab']) {
                this.loadedProjectInformation['ShiftTab'].freelancers_for_shifts.forEach((freelancer) => {
                    users.push({
                        element: freelancer.freelancer,
                        type: 1,
                        plannedWorkingHours: freelancer.plannedWorkingHours,
                    })
                })
            }

            if (this.loadedProjectInformation['ShiftTab']) {
                this.loadedProjectInformation['ShiftTab'].service_providers_for_shifts.forEach((service_provider) => {
                    users.push({
                        element: service_provider.service_provider,
                        type: 2,
                        plannedWorkingHours: service_provider.plannedWorkingHours,
                    })
                })
            }

            return users;
        },
        conflictMessage(){
            let conflicts = [];

            this.loadedProjectInformation['ShiftTab'].events_with_relevant.forEach(event => {
                event.shifts.forEach(shift => {
                    shift.users.forEach(user => {
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
        hasUncommittedShift() {
            return this.loadedProjectInformation['ShiftTab']?.events_with_relevant.some(
                event => event.shifts.find(shift => shift.is_committed === false)
            );
        }
    },
    mounted() {
        this.makeContainerDraggable();
    },
    methods: {
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
::-webkit-scrollbar {
    width: 16px;
}

::-webkit-scrollbar-track {
    background-color: transparent;
}

::-webkit-scrollbar-thumb {
    background-color: #A7A6B170;
    border-radius: 16px;
    border: 6px solid transparent;
    background-clip: content-box;
}

::-webkit-scrollbar-thumb:hover {
    background-color: #a8bbbf;
}
.stickyHeader {
    position: sticky;
    position: -webkit-sticky;
    display: block;
    top: 60px;
    z-index: 21;
    background-color: #F5F5F3;
}

</style>
