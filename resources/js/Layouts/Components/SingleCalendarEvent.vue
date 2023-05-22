<template>
    <div :class="[event.class, textStyle]" :style="{ width: width + 'px', height: totalHeight * zoomFactor + 'px' }"
         class="px-1 py-0.5 rounded-lg relative group">
        <div v-if="zoomFactor > 0.4"
             class="absolute w-full h-full rounded-lg group-hover:block flex justify-center align-middle items-center"
             :class="event.clicked ? 'block bg-green-200/50' : 'hidden bg-indigo-500/50'">
            <div class="flex justify-center items-center h-full gap-2" v-if="!multiEdit">
                <button type="button" @click="openEditEventModal(event)"
                        class="rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                </button>
                <button v-if="isRoomAdmin || isCreator || this.hasAdminRole()" @click="openAddSubEventModal" v-show="event.eventTypeId === 1" type="button"
                        class="rounded-full bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
                <button v-if="isRoomAdmin || isCreator || this.hasAdminRole()" type="button" @click="showDeclineEventModal = true"
                        class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <button v-if="isRoomAdmin || isCreator || this.hasAdminRole()" @click="openConfirmModal(event.id, 'main')" type="button"
                        class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                </button>
            </div>
            <div v-else class="flex justify-center items-center h-full gap-2">
                <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                        <input v-model="event.clicked" id="candidates" aria-describedby="candidates-description"
                               name="candidates" type="checkbox"
                               class="h-5 w-5 border-gray-300 text-green-400 focus:ring-green-600"/>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-1 py-0.5 ">
            <div :style="textStyle" :class="[zoomFactor === 1 ? 'eventHeader' : '', 'font-bold']"
                 class="flex justify-between ">
                <div v-if="!project" class="flex items-center">
                    <div v-if="event.eventTypeAbbreviation" class="mr-1">
                        {{ event.eventTypeAbbreviation }}:
                    </div>
                    <div :style="{ width: width - (64 * zoomFactor) + 'px'}" class=" truncate">
                        {{ event.title }}
                    </div>
                    <div v-if="$page.props.user.calendar_settings.project_status">
                        <div v-if="event.project?.state?.color"
                             :class="[event.project.state.color,zoomFactor <= 0.8 ? 'border-2' : 'border-4']"
                             class="rounded-full">
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div :style="{ width: width - (55 * zoomFactor) + 'px'}" class=" truncate">
                        {{ event.eventTypeName }}
                    </div>
                </div>
                <!-- Icon -->
                <div v-if="event.audience"
                     class="flex">
                    <svg :class="event.class" xmlns="http://www.w3.org/2000/svg" :width="22 * zoomFactor"
                         :height="11 * zoomFactor"
                         viewBox="0 0 19.182 10.124">
                        <g id="Gruppe_555" data-name="Gruppe 555" transform="translate(0.128 0.128)">
                            <g id="Gruppe_549" data-name="Gruppe 549" transform="translate(0.372 0.372)">
                                <path id="Pfad_825" data-name="Pfad 825"
                                      d="M39.116,8.027c0,.043,0,.085,0,.128a2.009,2.009,0,0,1-4.008.034c-.005-.054-.007-.108-.007-.162a2.009,2.009,0,0,1,4.019,0Z"
                                      transform="translate(-28.015 -4.977)" fill="none" stroke-miterlimit="10"
                                      stroke-width="1"/>
                                <path id="Pfad_826" data-name="Pfad 826"
                                      d="M29.852,27.618a3.323,3.323,0,0,1-1.5-.525,2.717,2.717,0,0,0-1.891,2.492v.62a.634.634,0,0,0,.671.593h6.265a.636.636,0,0,0,.673-.593v-.62a2.717,2.717,0,0,0-1.891-2.492,3.336,3.336,0,0,1-1.488.523Z"
                                      transform="translate(-21.17 -21.674)" fill="none" stroke-linecap="round"
                                      stroke-miterlimit="10" stroke-width="1"/>
                                <path id="Pfad_827" data-name="Pfad 827"
                                      d="M64.568,3.008c0,.043,0,.085,0,.128a2.009,2.009,0,0,1-4.008.034c-.005-.054-.007-.108-.007-.162a2.009,2.009,0,0,1,4.019,0Z"
                                      transform="translate(-48.181 -1)" fill="none" stroke-miterlimit="10"
                                      stroke-width="1"/>
                                <path id="Pfad_828" data-name="Pfad 828"
                                      d="M56.324,25.779h4.6a.636.636,0,0,0,.673-.593v-.62a2.716,2.716,0,0,0-1.891-2.492,3.336,3.336,0,0,1-1.488.523l-.836,0a3.322,3.322,0,0,1-1.5-.525,3.021,3.021,0,0,0-1.345.955"
                                      transform="translate(-43.416 -17.697)" fill="none" stroke-linecap="round"
                                      stroke-miterlimit="10" stroke-width="1"/>
                                <path id="Pfad_829" data-name="Pfad 829"
                                      d="M13.659,3.008c0,.043,0,.085,0,.128a2.009,2.009,0,0,1-4.008.034c-.005-.054-.007-.108-.007-.162a2.009,2.009,0,0,1,4.019,0Z"
                                      transform="translate(-7.846 -1)" fill="none" stroke-miterlimit="10"
                                      stroke-width="1"/>
                                <path id="Pfad_830" data-name="Pfad 830"
                                      d="M8.137,23.127a3,3,0,0,0-1.419-1.053,3.337,3.337,0,0,1-1.487.523l-.836,0a3.323,3.323,0,0,1-1.5-.525A2.716,2.716,0,0,0,1,24.566v.62a.634.634,0,0,0,.671.593H6.189"
                                      transform="translate(-1 -17.697)" fill="none" stroke-linecap="round"
                                      stroke-miterlimit="10" stroke-width="1"/>
                            </g>
                        </g>
                    </svg>
                </div>
            </div>
            {{ room }}
            <div class="flex">
                <!-- Time -->
                <div class="flex" :style="textStyle"
                     :class="[zoomFactor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']">
                <span
                    v-if="new Date(event.start).toDateString() === new Date(event.end).toDateString() && !project && !atAGlance"
                    class="items-center">{{
                        new Date(event.start).formatTime("HH:mm")
                    }} - {{ new Date(event.end).formatTime("HH:mm") }}
                </span>
                    <span class="flex w-full" v-else>
                    <span class="items-center">
                        <span class="text-error">
                        {{ new Date(event.start).toDateString() !== new Date(event.end).toDateString() ? '!' : '' }}
                        </span>
                        {{
                            new Date(event.start).format("DD.MM. HH:mm")
                        }} - {{ new Date(event.end).format("DD.MM. HH:mm") }}
                    </span>
                </span>
                </div>
                <div v-if="event.option_string && $page.props.user.calendar_settings.options" class="flex items-center">
                    <div
                        v-if="!atAGlance && new Date(event.start).toDateString() === new Date(event.end).toDateString()"
                        class="flex eventTime font-medium subpixel-antialiased" :style="textStyle">
                        , {{ event.option_string }}
                    </div>
                    <div class="flex eventTime font-medium subpixel-antialiased ml-0.5" v-else>
                        ({{ event.option_string.charAt(7) }})
                    </div>
                </div>

            </div>
            <!-- repeating Event -->
            <div :style="textStyle" :class="[zoomFactor === 1 ? 'eventText' : '', 'font-semibold']"
                 v-if="$page.props.user.calendar_settings.repeating_events && event.is_series"
                 class="uppercase flex items-center">
                <svg class="mx-1" xmlns="http://www.w3.org/2000/svg" width="8.664" height="10.838"
                     viewBox="0 0 8.664 10.838">
                    <g id="Icon_feather-repeat" data-name="Icon feather-repeat" transform="translate(-3.85 -0.581)">
                        <path id="Pfad_1366" data-name="Pfad 1366" d="M25.5,1.5l1.829,1.829L25.5,5.158"
                              transform="translate(-15.465 0)"/>
                        <path id="Pfad_1367" data-name="Pfad 1367"
                              d="M4.5,10.243V9.329A1.741,1.741,0,0,1,6.136,7.5h5.727" transform="translate(0 -4.436)"/>
                        <path id="Pfad_1368" data-name="Pfad 1368" d="M6.329,26.158,4.5,24.329,6.329,22.5"
                              transform="translate(0 -15.658)"/>
                        <path id="Pfad_1369" data-name="Pfad 1369"
                              d="M11.864,19.5v.914a1.741,1.741,0,0,1-1.636,1.829H4.5" transform="translate(0 -13.307)"/>
                    </g>
                </svg>
                Wiederholungstermin
            </div>
            <!-- User-Icons -->
            <div class="-ml-3 mb-0.5 w-full" v-if="$page.props.user.calendar_settings.project_management">
                <div v-if="event.projectLeaders && !project && zoomFactor >= 0.8"
                     class="mt-1 ml-5 flex flex-wrap">
                    <div class="flex flex-wrap flex-row -ml-1.5"
                         v-for="user in event.projectLeaders?.slice(0,3)">
                        <img :src="user.profile_photo_url" alt=""
                             class="mx-auto shrink-0 flex object-cover rounded-full"
                             :class="['h-' + 5 * zoomFactor, 'w-' + 5 * zoomFactor]">
                    </div>
                    <div v-if="event.projectLeaders.length >= 4" class="my-auto">
                        <Menu as="div" class="relative">
                            <MenuButton class="flex rounded-full focus:outline-none">
                                <div
                                    :class="'h-5 w-5'"
                                    class="-ml-1.5 flex-shrink-0 flex items-center my-auto font-semibold rounded-full shadow-sm text-white bg-black">
                                    <p class="">
                                        +{{ event.projectLeaders.length - 3 }}
                                    </p>
                                </div>
                            </MenuButton>
                            <transition enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
                                <MenuItems
                                    class="absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                    <MenuItem v-for="user in event.projectLeaders" v-slot="{ active }">
                                        <Link href="#"
                                              :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <img :class="'h-5 w-5'"
                                                 class="rounded-full"
                                                 :src="user.profile_photo_url"
                                                 alt=""/>
                                            <span class="ml-4">
                                                {{ user.first_name }} {{ user.last_name }}
                                                </span>
                                        </Link>
                                    </MenuItem>
                                </MenuItems>
                            </transition>
                        </Menu>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-show="event.subEvents.length > 0">
        <div v-for="subEvent in event.subEvents" class="mb-1">
            <div class="w-full relative group rounded-lg border-l-[6px] border-[#A7A6B115]">
                <div
                    class="absolute w-full h-full rounded-lg bg-indigo-500/50 hidden group-hover:block flex justify-center align-middle items-center">
                    <div class="flex justify-center items-center h-full gap-2">
                        <button @click="editSubEvent(subEvent)" type="button"
                                class="rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                            </svg>
                        </button>
                        <button v-if="isRoomAdmin || isCreator || this.hasAdminRole()" type="button"
                                class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <button v-if="isRoomAdmin || isCreator || this.hasAdminRole()" @click="openConfirmModal(subEvent.id, 'sub')" type="button"
                                class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div :class="[subEvent.class]" class="px-1 py-0.5 rounded-r-lg">
                    <div :style="textStyle" :class="[zoomFactor === 1 ? 'eventHeader' : '', 'font-bold']"
                         class="flex justify-between">
                        <div class="flex items-center">
                            {{ subEvent.title }}
                        </div>
                        <!-- Icons -->
                        <div v-if="subEvent.audience"
                             class="flex">
                            <svg :class="subEvent.class" xmlns="http://www.w3.org/2000/svg" :width="22 * zoomFactor"
                                 :height="11 * zoomFactor"
                                 viewBox="0 0 19.182 10.124">
                                <g id="Gruppe_555" data-name="Gruppe 555" transform="translate(0.128 0.128)">
                                    <g id="Gruppe_549" data-name="Gruppe 549" transform="translate(0.372 0.372)">
                                        <path id="Pfad_825" data-name="Pfad 825"
                                              d="M39.116,8.027c0,.043,0,.085,0,.128a2.009,2.009,0,0,1-4.008.034c-.005-.054-.007-.108-.007-.162a2.009,2.009,0,0,1,4.019,0Z"
                                              transform="translate(-28.015 -4.977)" fill="none" stroke-miterlimit="10"
                                              stroke-width="1"/>
                                        <path id="Pfad_826" data-name="Pfad 826"
                                              d="M29.852,27.618a3.323,3.323,0,0,1-1.5-.525,2.717,2.717,0,0,0-1.891,2.492v.62a.634.634,0,0,0,.671.593h6.265a.636.636,0,0,0,.673-.593v-.62a2.717,2.717,0,0,0-1.891-2.492,3.336,3.336,0,0,1-1.488.523Z"
                                              transform="translate(-21.17 -21.674)" fill="none" stroke-linecap="round"
                                              stroke-miterlimit="10" stroke-width="1"/>
                                        <path id="Pfad_827" data-name="Pfad 827"
                                              d="M64.568,3.008c0,.043,0,.085,0,.128a2.009,2.009,0,0,1-4.008.034c-.005-.054-.007-.108-.007-.162a2.009,2.009,0,0,1,4.019,0Z"
                                              transform="translate(-48.181 -1)" fill="none" stroke-miterlimit="10"
                                              stroke-width="1"/>
                                        <path id="Pfad_828" data-name="Pfad 828"
                                              d="M56.324,25.779h4.6a.636.636,0,0,0,.673-.593v-.62a2.716,2.716,0,0,0-1.891-2.492,3.336,3.336,0,0,1-1.488.523l-.836,0a3.322,3.322,0,0,1-1.5-.525,3.021,3.021,0,0,0-1.345.955"
                                              transform="translate(-43.416 -17.697)" fill="none" stroke-linecap="round"
                                              stroke-miterlimit="10" stroke-width="1"/>
                                        <path id="Pfad_829" data-name="Pfad 829"
                                              d="M13.659,3.008c0,.043,0,.085,0,.128a2.009,2.009,0,0,1-4.008.034c-.005-.054-.007-.108-.007-.162a2.009,2.009,0,0,1,4.019,0Z"
                                              transform="translate(-7.846 -1)" fill="none" stroke-miterlimit="10"
                                              stroke-width="1"/>
                                        <path id="Pfad_830" data-name="Pfad 830"
                                              d="M8.137,23.127a3,3,0,0,0-1.419-1.053,3.337,3.337,0,0,1-1.487.523l-.836,0a3.323,3.323,0,0,1-1.5-.525A2.716,2.716,0,0,0,1,24.566v.62a.634.634,0,0,0,.671.593H6.189"
                                              transform="translate(-1 -17.697)" fill="none" stroke-linecap="round"
                                              stroke-miterlimit="10" stroke-width="1"/>
                                    </g>
                                </g>
                            </svg>
                        </div>
                    </div>
                    <!-- Time -->
                    <div :style="textStyle"
                         :class="[zoomFactor === 1 ? 'eventTime' : '', 'font-medium subpixel-antialiased']"
                         class="flex">
                        <span :style="textStyle"
                              v-if="new Date(subEvent.start).toDateString() === new Date(subEvent.end).toDateString()"
                              class="items-center">{{
                                new Date(subEvent.start).formatTime("HH:mm")
                            }} - {{ new Date(subEvent.end).formatTime("HH:mm") }}
                        </span>
                        <span class="flex w-full" v-else>
                            <span class="items-center">
                                {{
                                    new Date(subEvent.start).format("DD.MM. HH:mm")
                                }} - {{ new Date(subEvent.end).format("DD.MM. HH:mm") }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <AddSubEventModal
        v-if="showAddSubEventModal"
        @close="closeAddSubModal"
        :event="event"
        :event-types="eventTypes"
        :sub-event-to-edit="subEventToEdit"
    />

    <ConfirmDeleteModal
        v-if="deleteComponentVisible"
        :title="deleteTitle"
        :description="deleteDescription"
        @closed="closeConfirmModal"
        @delete="deleteEvent"
    />

    <DeclineEventModal
        :request-to-decline="event"
        :event-types="eventTypes"
        @closed="showDeclineEventModal = false"
        v-if="showDeclineEventModal"
    />


</template>

<script>
import Button from "@/Jetstream/Button";
import {PlusCircleIcon} from '@heroicons/vue/outline'
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import AddSubEventModal from "@/Layouts/Components/AddSubEventModal.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmEventRequestComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {Link} from "@inertiajs/inertia-vue3";
import DeclineEventModal from "@/Layouts/Components/DeclineEventModal.vue";
import Permissions from "@/mixins/Permissions.vue";


export default {
    mixins: [Permissions],
    name: "SingleCalendarEvent",
    components: {
        DeclineEventModal,
        AddButton,
        EventComponent,
        ConfirmDeleteModal,
        ConfirmationComponent,
        Menu, MenuItem, MenuItems, MenuButton, UserTooltip, Button, PlusCircleIcon, AddSubEventModal, NewUserToolTip,
        Link
    },
    props: ['event', 'eventTypes', 'height', 'width', 'zoomFactor', 'fullHeight', 'project', 'multiEdit', 'atAGlance', 'rooms'],
    emits: ['openEditEventModal'],
    computed: {
        textStyle() {
            const fontSize = `calc(${this.zoomFactor} * 0.75rem)`;
            const lineHeight = `calc(${this.zoomFactor} * 1rem)`;
            return {
                fontSize,
                lineHeight,
            };
        },
        totalHeight() {
            let height = 42;
            // ProjectStatus is in same row as name -> no extra height needed
            if (this.$page.props.user.calendar_settings.project_status) height += 0;
            //Options are in same row as time -> no extra height needed
            if (this.$page.props.user.calendar_settings.options) height += 0;
            if (this.$page.props.user.calendar_settings.project_management) height += 17;
            if (this.$page.props.user.calendar_settings.repeating_events) height += 20;
            if (this.$page.props.user.calendar_settings.work_shifts) height += 18;
            return height;
        },
        isRoomAdmin() {
            return this.rooms?.find(room => room.id === this.event.roomId)?.admins.some(admin => admin.id === this.$page.props.user.id) || false;
        },
        isCreator(){
            return this.event?.created_by.id === this.$page.props.user.id
        },
        roomCanBeBookedByEveryone(){
            return this.rooms?.find(room => room.id === this.event.roomId).everyone_can_book
        }

    },
    data() {
        return {
            showDeclineEventModal: false,
            showAddSubEventModal: false,
            deleteComponentVisible: false,
            eventToDelete: null,
            type: '',
            deleteTitle: '',
            deleteDescription: '',
            createEventComponentIsVisible: false,
            selectedEvent: null,
            wantedSplit: null,
            subEventToEdit: null
        }
    },
    methods: {
        openEditEventModal(event) {
            this.$emit('openEditEventModal', event);
        },
        closeAddSubModal() {
            this.showAddSubEventModal = false;
        },
        openAddSubEventModal() {
            this.subEventToEdit = null;
            this.showAddSubEventModal = true;
        },
        closeConfirmModal() {
            this.deleteComponentVisible = false;
        },
        openConfirmModal(eventId, type) {
            if (type === 'main') {
                this.type = type;
                this.deleteTitle = 'Termin Löschen?';
                this.deleteDescription = 'Bist du sicher, dass du die ausgewählten Belegungen in den Papierkorb legen möchtest? Sämtliche Untertermine werden ebenfalls gelöscht.';
            } else {
                this.type = type;
                this.deleteTitle = 'Untertermin Löschen?';
                this.deleteDescription = 'Bist du sicher, dass du die ausgewählten Belegungen in den Papierkorb legen möchtest?';
            }
            this.eventToDelete = eventId
            this.deleteComponentVisible = true;
        },
        deleteEvent() {
            if (this.type === 'main') {
                this.$inertia.delete(route('events.delete', this.eventToDelete), {
                    preserveScroll: true,
                    preserveState: true
                })
            }
            if (this.type === 'sub') {
                this.$inertia.delete(route('subEvent.delete', this.eventToDelete), {
                    preserveScroll: true,
                    preserveState: true
                })
            }
            this.deleteComponentVisible = false;
        },
        editSubEvent(subEvent) {
            this.subEventToEdit = subEvent;
            this.showAddSubEventModal = true;
        }
    },
    watch: {
        multiEdit: {
            handler() {
                if (!this.multiEdit) {
                    this.event.clicked = false;
                }
            }
        }
    }
}
</script>

<style scoped>

.occupancy_option_eventType0 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #d2d2d2 8px);
}

.occupancy_option_eventType1 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #e0abd1 8px);
}

.occupancy_option_eventType2 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #f6b9d4 8px);
}

.occupancy_option_eventType3 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, rgb(227, 185, 162) 8px);
}

.occupancy_option_eventType4 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #e5c386 8px);
}

.occupancy_option_eventType5 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #cce5ac 8px);
}

.occupancy_option_eventType6 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #b0e8c5 8px);
}

.occupancy_option_eventType7 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #a9dde1 8px);
}

.occupancy_option_eventType8 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #a0cfe5 8px);
}

.occupancy_option_eventType9 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #a6dcda 8px);
}

.occupancy_option_eventType10 {
    background: repeating-linear-gradient(-45deg, transparent, transparent 6px, #ffffff 6px, #a2cbe0 8px);
}

.eventType0 {
    background-color: #A7A6B115;
    stroke: #7F7E88;
    color: #7F7E88
}

.eventType1 {
    background-color: #641a5415;
    stroke: #631D53;
    color: #631D53
}

.eventType2 {
    background-color: #da3f8715;
    stroke: #D84387;
    color: #D84387
}

.eventType3 {
    background-color: #eb7a3d15;
    stroke: #E97A45;
    color: #E97A45
}

.eventType4 {
    background-color: #f1b64015;
    stroke: #CB8913;
    color: #CB8913
}

.eventType5 {
    background-color: #86c55415;
    stroke: #648928;
    color: #648928
}

.eventType6 {
    background-color: #2eaa6315;
    stroke: #35A965;
    color: #35A965
}

.eventType7 {
    background-color: #3dc3cb15;
    stroke: #35ACB2;
    color: #35ACB2
}

.eventType8 {
    background-color: #168fc315;
    stroke: #2290C1;
    color: #2290C1
}

.eventType9 {
    background-color: #4d908e15;
    stroke: #50908E;
    color: #50908E
}

.eventType10 {
    background-color: #21485C15;
    stroke: #23485B;
    color: #23485B
}
</style>
