<template>
    <div class="bg-backgroundGray mt-6 pb-6">
        <div class=" ml-14 pt-3 pr-14">
            <div class="flex justify-between items-center mt-4">
            <div class="">
                <SwitchGroup as="div" class="flex items-center" v-if="eventsWithRelevant?.length > 0 && (this.$can('can commit shifts') || this.hasAdminRole())">
                    <Switch v-model="hasUncommittedShift"
                            @update:modelValue="updateCommitmentOfShifts"
                            :class="[!hasUncommittedShift ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                        <span aria-hidden="true"
                              :class="[!hasUncommittedShift ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                    </Switch>
                    <SwitchLabel as="span" class="ml-3 text-sm">
                        <span class="font-medium text-gray-900">Festgeschrieben</span>
                    </SwitchLabel>
                </SwitchGroup>
            </div>
            <div>
                <div @click="userWindow = !userWindow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24.162" height="17.536" viewBox="0 0 24.162 17.536">
                        <g id="public" transform="translate(-6.127 0.378)">
                            <g id="Gruppe_549" data-name="Gruppe 549" transform="translate(6.877 0.372)">
                                <path id="Pfad_825" data-name="Pfad 825"
                                      d="M42.16,9.549c0,.076,0,.149-.006.224a3.531,3.531,0,0,1-7.044.06c-.009-.095-.013-.189-.013-.284a3.531,3.531,0,0,1,7.063,0Z"
                                      transform="translate(-31.943 -4.187)" fill="none" stroke="#27233c"
                                      stroke-miterlimit="10" stroke-width="1.5"/>
                                <path id="Pfad_826" data-name="Pfad 826"
                                      d="M32.424,28.016a5.84,5.84,0,0,1-2.644-.923,4.775,4.775,0,0,0-3.323,4.38v1.09A1.114,1.114,0,0,0,27.636,33.6h11.01a1.118,1.118,0,0,0,1.183-1.042v-1.09a4.775,4.775,0,0,0-3.323-4.38,5.863,5.863,0,0,1-2.615.92Z"
                                      transform="translate(-26.457 -17.569)" fill="none" stroke="#27233c"
                                      stroke-linecap="round" stroke-miterlimit="10" stroke-width="1.5"/>
                                <path id="Pfad_827" data-name="Pfad 827"
                                      d="M67.612,4.53c0,.076,0,.149-.006.224a3.531,3.531,0,0,1-7.044.06c-.009-.095-.013-.189-.013-.284a3.531,3.531,0,0,1,7.063,0Z"
                                      transform="translate(-48.105 -1)" fill="none" stroke="#27233c"
                                      stroke-miterlimit="10" stroke-width="1.5"/>
                                <path id="Pfad_828" data-name="Pfad 828"
                                      d="M57.679,28.586h8.087a1.118,1.118,0,0,0,1.183-1.042v-1.09a4.774,4.774,0,0,0-3.323-4.379,5.863,5.863,0,0,1-2.615.92l-1.468,0a5.838,5.838,0,0,1-2.644-.923,5.309,5.309,0,0,0-2.364,1.678"
                                      transform="translate(-44.286 -14.382)" fill="none" stroke="#27233c"
                                      stroke-linecap="round" stroke-miterlimit="10" stroke-width="1.5"/>
                            </g>
                        </g>
                    </svg>
                </div>

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
                                <svg xmlns="http://www.w3.org/2000/svg" width="21.496" height="19.496"
                                     viewBox="0 0 21.496 19.496">
                                    <path id="Icon_feather-filter" data-name="Icon feather-filter"
                                          d="M23,4.5H3l8,9.458V20.5l4,2V13.958Z" transform="translate(-2.25 -3.75)"
                                          fill="none" stroke="#fcfcfb" stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="1.5"/>
                                </svg>
                                <span v-if="openFilter">
                                <svg xmlns="http://www.w3.org/2000/svg" width="9.177" height="6.003"
                                     viewBox="0 0 9.177 6.003">
                                    <g id="headline" transform="translate(66.076 -18.911) rotate(180)">
                                          <path id="Pfad_1076" data-name="Pfad 1076" d="M0,0,3.882,3.882,7.763,0"
                                                transform="translate(65.369 -19.618) rotate(180)" fill="none"
                                                stroke="#fcfcfb" stroke-width="2"/>
                                    </g>
                                </svg>
                            </span>
                                <span v-else>
                                <svg xmlns="http://www.w3.org/2000/svg" width="9.177" height="6.003"
                                     viewBox="0 0 9.177 6.003">
                                    <g id="headline" transform="translate(-56.898 24.914)">
                                        <path id="Pfad_1076" data-name="Pfad 1076" d="M0,0,3.882,3.882,7.763,0"
                                              transform="translate(65.369 -19.618) rotate(180)" fill="none"
                                              stroke="#fcfcfb" stroke-width="2"/>
                                    </g>
                                </svg>
                            </span>
                            </div>
                            <div>
                                <XIcon class="h-6 w-6 text-white" @click="userWindow = !userWindow"/>
                            </div>
                        </div>

                        <div class="" v-if="openFilter">
                            <div class="my-5">
                                <div class="flex w-full mb-3">
                                    <input v-model="showIntern"
                                           type="checkbox"
                                           class="cursor-pointer h-5 w-5 text-success border-2 border-gray-300 focus:ring-0 active:ring-0"/>
                                    <div :class="[showIntern ? 'xsWhiteBold' : 'xsLight', 'my-auto ml-2']">
                                        Interne Mitarbeiter*innen
                                    </div>
                                </div>
                                <div class="flex w-full mb-3">
                                    <input v-model="showExtern"
                                           type="checkbox"
                                           class="cursor-pointer h-5 w-5 text-success border-2 border-gray-300 focus:ring-0 active:ring-0"/>
                                    <div :class="[showExtern ? 'xsWhiteBold' : 'xsLight', 'my-auto ml-2']">
                                        Externe Mitarbeiter*innen
                                    </div>
                                </div>
                                <div class="flex w-full">
                                    <input v-model="showProvider"
                                           type="checkbox"
                                           class="cursor-pointer h-5 w-5 text-success border-2 border-gray-300 focus:ring-0 active:ring-0"/>
                                    <div :class="[showProvider ? 'xsWhiteBold' : 'xsLight', 'my-auto ml-2']">
                                        Dienstleister
                                    </div>
                                </div>
                            </div>
                            <div class="my-2 h-0.5 w-full bg-[#3A374D]">

                            </div>
                        </div>

                        <div @mousedown="preventContainerDrag" class="max-h-72 shiftUserWindow">
                            <div v-for="user in filteredUsers">
                                <DragElement :item="user.element" :plannedHours="user.plannedWorkingHours" :expected-hours="user.expectedWorkingHours" :type="user.type"/>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <div class="mt-5">
            <div class="xsDark" v-if="eventsWithRelevant.length === 0">
                Bisher gibt es für dieses Projekt keine schichtrelevanten Termine.
            </div>
            <SingleRelevantEvent v-for="event in eventsWithRelevant" :crafts="crafts" :event="event" :event-types="eventTypes"/>
        </div>
        </div>
    </div>

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

export default defineComponent({
    name: "ShiftTab",
    props: ['eventsWithRelevant', 'crafts', 'users', 'dropUsers', 'eventTypes'],
    mixins: [Permissions],
    components: {
        Input,
        SingleRelevantEvent,
        DragElement,
        SingleShiftEvent,
        PencilAltIcon, TrashIcon, DuplicateIcon, DotsVerticalIcon,
        Switch, SwitchGroup, SwitchLabel, Menu, MenuItems, MenuItem, MenuButton,
        XIcon,
    },
    data() {
        return {
            enabled: false,
            userWindow: false,
            openFilter: false,
            showIntern: false,
            showExtern: false,
            showProvider: false
        }
    },
    computed: {
        filteredUsers() {
            if (!this.showExtern && !this.showIntern && !this.showProvider) {
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
            return users
        },
        hasUncommittedShift() {
            return this.eventsWithRelevant.some(event => event.shifts.find(shift => shift.is_committed === false));
        }
    },
    mounted() {
        console.log(this.eventsWithRelevant)
        this.makeContainerDraggable();
    },
    methods: {
        usePage,
        updateCommitmentOfShifts() {
            this.$inertia.patch(route('update.shift.commitment'), {
                project_id: this.$page.props.project.id,
                shifts: this.eventsWithRelevant.flatMap(event => event.shifts.map(shift => shift.id)),
                is_committed: this.hasUncommittedShift
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

</style>
