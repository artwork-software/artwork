
<template>
    <div class="flex w-full border border-gray-300 rounded-lg">
        <button v-if="this.computedEventsWithoutRoom.length > 1" class="bg-artwork-buttons-create w-6 rounded-l-lg"
                @click="event.opened = !event.opened">
            <IconChevronUp  stroke-width="1.5" v-if="event.opened"
                            class="h-6 w-6 text-white my-auto"></IconChevronUp>
            <IconChevronDown stroke-width="1.5" v-else
                             class="h-6 w-6 text-white my-auto"></IconChevronDown>
        </button>
        <div class="mx-2 mt-2 w-full grid grid-cols-1 md:grid-cols-2 gap-4" v-if="(event.opened || this.computedEventsWithoutRoom.length === 1) && event?.user_id === usePage().props.auth.user.id || isAdmin">
            <div class="flex w-full justify-between col-span-full">
                <div
                    class="flex justify-start my-auto items-center mt-3.5 ml-1 text-error line-through">
                    {{ this.rooms.find(room => room.id === event.declinedRoomId)?.name }}
                </div>
                <div class="flex justify-end">
                    <div v-if="event?.user_id === usePage().props.auth.user.id || isAdmin"
                         class="flex  justify-end">
                        <div class="flex mt-1 mr-2 cursor-pointer" @click="openDeleteEventModal(event)">
                            <IconTrash class="text-primary h-6 w-6 hover:text-artwork-messages-error transition-all duration-150 ease-in-out" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="">
                <div class="h-12 flex w-full truncate p-2" v-if="(event?.user_id === usePage().props.auth.user.id) || !isAdmin">
                    <div>
                        <div class="block w-5 h-5 rounded-full" :style="{'backgroundColor' : this.eventTypes.find(type => type.id === event.eventType.id)?.hex_code }" />
                    </div>
                    <p class="ml-2 headline2">{{ this.eventTypes.find(type => type.id === event.eventType.id).name }}</p>
                </div>
                <Listbox as="div" class="flex h-12 mr-2 relative" v-model="event.eventTypeId" v-else :onchange="checkCollisions()" id="eventType">
                    <ListboxButton class="menu-button mt-5">
                        <div class="flex items-center justify-between w-full">
                            <div class="flex items-center gap-x-2">
                                <div>
                                    <div class="block w-5 h-5 rounded-full" :style="{'backgroundColor' : this.eventTypes.find(type => type.id === event.eventType.id)?.hex_code }" />
                                </div>
                                <span class="truncate items-center flex">
                                    <span>{{this.eventTypes.find(type => type.id === event.eventType.id)?.name }}</span>
                                </span>
                            </div>
                            <span class="pointer-events-none">
                                 <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                            </span>
                        </div>
                    </ListboxButton>
                    <transition leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <ListboxOptions
                            class="absolute w-full z-10 mt-16 bg-artwork-navigation-background shadow-lg max-h-32 pr-2 rounded-lg pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                            <ListboxOption as="template" class="max-h-8"
                                           v-for="eventType in eventTypes"
                                           :key="eventType.name"
                                           :value="eventType.id"
                                           v-slot="{ active, selected }">
                                <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                    <div class="flex">
                                        <div>
                                            <div class="block w-3 h-3 rounded-full" :style="{'backgroundColor' : eventType?.hex_code }" />
                                        </div>
                                        <span
                                            :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                            {{ eventType.name }}
                                        </span>
                                    </div>
                                    <span
                                        :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                          <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success"
                                                     aria-hidden="true"/>
                                    </span>
                                </li>
                            </ListboxOption>
                        </ListboxOptions>
                    </transition>
                </Listbox>
                <p class="text-xs text-red-800">{{ event.error?.eventType?.join('. ') }}</p>
            </div>
            <div>
                <TextInputComponent
                    v-if="this.eventTypes.find(type => type.id === event.eventTypeId)?.individual_name"
                    v-model="event.eventName"
                    id="eventTitle"
                    :label="$t('Event name') + '*'"
                    :disabled="!(event?.user_id !== usePage().props.auth.user.id) || !isAdmin"
                />
                <TextInputComponent v-else
                                    v-model="event.eventName"
                                    id="eventTitle"
                                    :label="$t('Event name')"
                                    :disabled="!(event?.user_id !== usePage().props.auth.user.id) || !isAdmin"
                />
                <p class="text-xs text-red-800">{{ event.error?.eventName?.join('. ') }}</p>
            </div>
            <div class="grid gird-cols-1 gap-x-4 mb-4" v-if="usePage().props.event_status_module">
                <div class="h-full">
                    <Listbox as="div" class="" v-model="selectedEventStatus" id="eventType">
                        <ListboxLabel class="xsLight mb-0">{{ $t('Event Status') }}</ListboxLabel>
                        <ListboxButton class="menu-button">
                            <div class="flex w-full justify-between">
                                <div class="flex items-center gap-x-2">
                                    <div>
                                        <div class="block w-5 h-5 rounded-full" :style="{'backgroundColor' : selectedEventStatus?.color }"/>
                                    </div>
                                    <div class="truncate w-56">
                                        {{ selectedEventStatus?.name }}
                                    </div>
                                </div>
                                <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                            </div>
                        </ListboxButton>

                        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <ListboxOptions class="absolute w-72 z-10 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                <ListboxOption as="template" class="max-h-8"
                                               v-for="status in eventStatuses"
                                               :key="status.name"
                                               :value="status"
                                               v-slot="{ active, selected }">
                                    <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                        <div class="flex">
                                            <div>
                                                <div class="block w-3 h-3 rounded-full"
                                                     :style="{'backgroundColor' : status?.color }"/>
                                            </div>
                                            <span
                                                :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate w-52']">
                                                {{ status.name }}
                                            </span>
                                        </div>
                                        <span
                                            :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <IconCheck stroke-width="1.5" v-if="selected"
                                                                 class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </Listbox>
                    <p class="text-xs text-red-800"
                       v-html="Array.isArray(error?.eventType) ? error.eventType.join('.<br> ') : error?.eventType">
                    </p>
                </div>
                <div>
                </div>
            </div>

            <!--    Properties    -->
            <div class="flex">
                <TagComponent v-for="eventProperty in this.event?.eventProperties"
                              :icon="eventProperty.icon.replace('Icon', '')"
                              :displayed-text="eventProperty.name"
                              hideX="true"
                              property=""/>
            </div>
            <!--    Time    -->
            <div class="col-span-full">
                <SwitchGroup as="div" class="flex items-center">
                    <Switch v-model="event.allDay"
                            @update:modelValue="checkChanges(event)"
                            :class="[event.allDay ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-1 focus:ring-indigo-600 focus:ring-offset-2']">
                                    <span aria-hidden="true"
                                          :class="[event.allDay ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                    </Switch>
                    <SwitchLabel as="span" class="ml-3 text-sm">
                                    <span :class="[event.allDay ? 'xsDark' : 'xsLight', 'text-sm']">
                                        {{$t('Full day')}}
                                    </span>
                    </SwitchLabel>
                </SwitchGroup>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 col-span-full">
                <div class="">
                    <div class="w-full flex">
                        <DateInputComponent
                            v-model="event.startDate"
                            id="startDate"
                            @change="checkChanges(event)"
                            :disabled="!(event?.user_id !== usePage().props.auth.user.id) || !isAdmin"
                            required
                            :label="$t('Start*')"
                            :classes="!event.allDay ? 'border-r-0 rounded-r-none' : ''"
                        />
                        <TimeInputComponent
                            v-model="event.startTime"
                            v-if="!event.allDay"
                            id="changeStartTime"
                            @change="checkChanges(event)"
                            :disabled="!(event?.user_id !== usePage().props.auth.user.id) || !isAdmin"
                            required
                            label="hh:mm"
                            :classes="event.allDay ? '' : 'rounded-l-none'"
                        />
                    </div>
                    <p class="text-xs text-red-800">{{ event.error?.start?.join('. ') }}</p>
                </div>
                <div class="">
                    <div class="w-full flex">
                        <DateInputComponent
                            v-model="event.endDate"
                            id="endDate"
                            @change="checkChanges(event)"
                            :disabled="!(event?.user_id !== usePage().props.auth.user.id) || !isAdmin"
                            required
                            :label="$t('End*')"
                            :classes="!event.allDay ? 'border-r-0 rounded-r-none' : ''"
                        />
                        <TimeInputComponent
                            v-model="event.endTime"
                            v-if="!event.allDay"
                            id="changeEndTime"
                            @change="checkChanges(event)"
                            :disabled="!(event?.user_id !== usePage().props.auth.user.id) || !isAdmin"
                            required
                            label="hh:mm"
                            :classes="event.allDay ? '' : 'rounded-l-none'"
                        />
                    </div>
                    <p class="text-xs text-red-800">{{ event.error?.end?.join('. ') }}</p>
                </div>
            </div>
            <!-- Serien Termin -->
            <div v-if="event?.is_series" class="xsLight mt-2 col-span-full">{{ $t('Event is part of a repeat event') }}</div>
            <div v-if="event?.is_series" class="xsLight mb-2 col-span-full">{{ $t('Cycle: {0} to {1}', {0: event.selectedFrequencyName, 1: convertDateFormat(event.series.end_date) } )}}</div>
            <!--    Room    -->
            <div class="col-span-full">
                <div class=" w-full h-10 cursor-pointer truncate p-2" v-if="!event?.user_id === usePage().props.auth.user.id || !isAdmin" >
                    {{ this.rooms.find(room => room.id === event.roomId)?.name }}
                </div>
                <Listbox as="div" v-model="event.roomId" id="room" v-if="event?.user_id === usePage().props.auth.user.id || isAdmin">
                    <ListboxButton
                        class="menu-button">
                        <div v-if="event.roomId" class="flex-grow text-left">
                            {{ this.rooms.find(room => room.id === event.roomId)?.name }}
                        </div>
                        <div v-else class="flex-grow xsLight text-left subpixel-antialiased">
                            {{$t('Select room')}}*
                        </div>
                        <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                    </ListboxButton>
                    <ListboxOptions
                        class="w-[80%] bg-artwork-navigation-background max-h-32 overflow-y-auto text-sm absolute z-20">
                        <ListboxOption v-for="room in rooms"
                                       class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                       :key="room.name"
                                       :value="room.id"
                                       v-slot="{ active, selected }">
                            <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                {{ room.name }}
                                <IconAlertTriangle
                                    v-if="event.roomCollisionArray[room.id] > 0"
                                    class="h-4 w-4 mx-2" alt="conflictIcon"
                                />
                            </div>
                            <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                        </ListboxOption>
                    </ListboxOptions>
                </Listbox>
                <p class="text-xs text-red-800">{{ event.error?.roomId?.join('. ') }}</p>
            </div>
            <div class="bg-lightBackgroundGray pt-1 pb-4 px-3 col-span-full -mx-2">
                <div class="my-3">
                    <p v-if="event.error?.projectId" class="errorText pb-4">
                        {{ $t(event.error.projectId[0]) }}
                    </p>
                    <input type="checkbox"
                           v-model="showProjectInfo"
                           class="input-checklist">
                    <span :class="[showProjectInfo ? 'xsDark' : 'xsLight', 'text-sm ml-2']">
                        {{$t('Assign event to a project')}}
                    </span>
                </div>
                <!--    Project    -->
                <div v-if="showProjectInfo">
                    <div class="xsLight flex" v-if="!event.creatingProject">
                        {{$t('Currently assigned to:')}}
                        <a v-if="event.projectId"
                           :href="route('projects.tab', {project: event.project.id, projectTab: this.first_project_calendar_tab_id})"
                           class="ml-3 flex xsDark">
                            {{ event.project?.name }}
                        </a>
                        <div v-else class="xsDark ml-2">
                            {{ event.project?.name ?? 'Keinem Projekt' }}
                        </div>
                        <div v-if="event.project?.id && (event?.user_id !== usePage().props.auth.user.id) || !isAdmin" class="flex items-center my-auto">
                            <button type="button"
                                    @click="this.deleteProject(event)">
                                <IconCircleX stroke-width="1.5" class="pl-2 h-6 w-6 hover:text-error text-primary"/>
                            </button>
                        </div>
                    </div>
                    <div class="xsLight" v-if="event.creatingProject">
                        {{$t('The project is created when it is saved.')}}
                    </div>

                    <div class="my-2" v-if="(event?.user_id !== usePage().props.auth.user.id) || !isAdmin">
                        <div class="flex pb-2">
                            <span class="mr-4 text-sm"
                                  :class="[!event.creatingProject ? 'xsDark' : 'xsLight', '']">
                                {{$t('Existing project')}}
                            </span>
                            <label for="project-toggle"
                                   class="inline-flex relative items-center cursor-pointer">
                                <input type="checkbox"
                                       v-model="event.creatingProject"
                                       :disabled="!event.canEdit"
                                       id="project-toggle"
                                       class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 rounded-full
                                peer-checked:after:translate-x-full peer-checked:after:border-white
                                after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                                after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600">
                                </div>
                            </label>
                            <span class="ml-4 text-sm"
                                  :class="[event.creatingProject ? 'xsDark' : 'xsLight', '']">
                                                {{$t('New project')}}
                                            </span>
                            <div v-if="showHints" class="ml-3 flex">
                                <SvgCollection svgName="arrowLeft" class="mt-1"/>
                                <div class="hind text-secondary ml-1 my-auto text-sm">
                                    {{$t('Create a new project at the same time')}}
                                </div>
                            </div>
                        </div>
                        <TextInputComponent type="text"
                               id="projectName"
                               @focusin="event.showProjectSearchResults = true"
                               @keyup="this.projectName = event.projectName"
                               v-model="event.projectName"
                               autocomplete="off"
                               :label="creatingProject ? $t('New project name') : $t('Search project')"
                        />

                        <div
                            v-if="projectSearchResults.length > 0 && !event.creatingProject && event.showProjectSearchResults"
                            class="absolute bg-primary truncate sm:text-sm w-10/12 z-10">
                            <div v-for="(project, index) in projectSearchResults"
                                 :key="index"
                                 @click="onLinkingProject(project, event)"
                                 class="p-4 text-white border-l-4 hover:border-l-success border-l-primary cursor-pointer">
                                {{ project.name }}
                            </div>
                        </div>
                        <p class="text-xs text-red-800">{{ event.error?.projectName?.join('. ') }}</p>
                    </div>
                </div>
                <!--    Description    -->
                <div class="mb-4">
                    <TextareaComponent
                        :label="$t('What do I need to bear in mind for the event?')"
                        id="description"
                        :disabled="!(event?.user_id !== usePage().props.auth.user.id) || !isAdmin"
                        v-model="event.description"
                        rows="4"
                    />
                </div>
                <!-- Attribute Menu -->
                <div>
                    <Menu as="div" class="inline-block text-left w-full relative">
                        <div>
                            <MenuButton class="menu-button">
                                <div class="xsLight subpixel-antialiased flex items-center gap-x-2">
                                    <IconAdjustmentsAlt stroke-width="1.5" class="h-6 w-6" />{{$t('Select appointment properties')}}
                                </div>
                                <IconChevronDown stroke-width="1.5" class="ml-2 h-5 w-5 text-primary float-right" aria-hidden="true"/>
                            </MenuButton>
                        </div>
                        <transition
                            enter-active-class="transition duration-50 ease-out"
                            enter-from-class="transform scale-100 opacity-100"
                            enter-to-class="transform scale-100 opacity-100"
                            leave-active-class="transition duration-75 ease-in"
                            leave-from-class="transform scale-100 opacity-100"
                            leave-to-class="transform scale-95 opacity-0"
                        >
                            <MenuItems
                                class="absolute overflow-y-auto h-24 mt-2 w-full origin-top-left divide-y divide-gray-200 rounded-lg bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                                <div class="w-full rounded-2xl bg-primary border-none mt-2 flex flex-col gap-y-1">
                                    <div v-for="eventProperty in this.event_properties" class="flex flex-row gap-x-1 w-full items-center">
                                        <input v-model="eventProperty.checked"
                                               type="checkbox"
                                               class="checkBoxOnDark"/>
                                        <component :is="eventProperty.icon" class="w-5 h-5 text-white" stroke-width="2"/>
                                        <div :class="[eventProperty.checked ? 'xsWhiteBold' : 'xsLight', 'my-auto']">
                                            {{ eventProperty.name }}
                                        </div>
                                    </div>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
            <div class="flex justify-center w-full col-span-full pb-3" v-if="(event?.user_id === usePage().props.auth.user.id) || isAdmin">
                <button
                    :disabled="event.roomId === null || event.startDate === null || event.endDate === null || (event.startTime === null && !event.allDayEvent) || (event.endTime === null && !event.allDayEvent)"
                    :class="event.roomId === null || event.startDate === null || event.endDate === null || (event.startTime === null && !event.allDayEvent) || (event.endTime === null && !event.allDayEvent) ? 'bg-secondary hover:bg-secondary' : ''"
                    class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover py-2 px-8 rounded-full text-white"
                    @click="updateEvent(event)">
                    {{
                        (isAdmin || selectedRoom?.everyone_can_book) ? $t('Save') : $t('Request occupancy')
                    }}
                </button>
            </div>
        </div>
        <!-- View if not opened Event -->
        <div class="ml-2 w-11/12" v-else>
            <div class=" w-full flex cursor-pointer truncate p-2">
                <div>
                    <div class="block w-10 h-10 rounded-full" :style="{'backgroundColor' : this.eventTypes.find(type => type.id === event.eventTypeId)?.hex_code }" />
                </div>
                <p class="ml-2 headline2 flex items-center">
                    {{ this.eventTypes.find(type => type.id === event.eventTypeId)?.name }}
                </p>
                <div class="flex w-1/2 ml-12 xsDark items-center">
                    {{ event.eventName }}
                </div>
            </div>
            <div class="w-full flex">
                <div class="flex w-1/2 xxsDark items-center my-auto" v-if="event.projectId">
                    {{$t('Project')}}:
                    <a :href="route('projects.tab', {project: event.projectId, projectTab: this.first_project_calendar_tab_id})"
                       class="ml-1 xxsDarkBold items-center flex">
                        {{ event.projectName }}
                    </a>
                </div>
                <div class="flex items-center w-1/2 mb-1">
                    <div class="truncate flex xxsDark max-w-60 mt-1">
                        {{$t('Created by')}}
                        <div class="xxsDarkBold ml-1"> {{ event.created_by.first_name }}
                            {{ event.created_by.last_name }}
                        </div>
                    </div>
                    <img
                        :data-tooltip-target="event.created_by.id"
                        :src="event.created_by.profile_photo_url"
                        :alt="event.created_by.last_name"
                        class="ml-2 ring-white ring-2 rounded-full h-9 w-9 object-cover"/>
                </div>
            </div>
            <div class="my-2">
                <div class="flex" v-if="event.startDate === event.endDate">
                    <div class="xsDark flex">
                        <p v-if="this.rooms.find(room => room.id === event.declinedRoomId)"
                           class="text-error mr-1 line-through">
                            {{ this.rooms.find(room => room.id === event.declinedRoomId)?.name }},
                        </p>
                        {{ event.startDate.toString().substring(10, 8) }}.{{ event.startDate.toString().substring(7, 5) }}.{{ event.startDate.toString().substring(4, 0) }},
                        {{ event.allDay ? 'Ganztägig' : event.startTime + "-" + event.endTime }}
                    </div>
                </div>
                <div v-else>
                    <p v-if="this.rooms.find(room => room.id === event.declinedRoomId)"
                       class="text-error line-through">
                        {{ this.rooms.find(room => room.id === event.declinedRoomId)?.name }},
                    </p>
                    <div class="xsDark">
                        {{ event.startDate.toString().substring(10, 8) }}.{{ event.startDate.toString().substring(7, 5) }}.{{ event.startDate.toString().substring(4, 0) }},
                        {{ event.allDay ? 'Ganztägig' : event.startTime }} -
                        {{ event.endDate.toString().substring(10, 8) }}.{{ event.endDate.toString().substring(7, 5) }}.{{ event.endDate.toString().substring(4, 0) }},
                        {{ event.allDay ? 'Ganztägig' : event.endTime }}
                    </div>
                </div>
            </div>
            <div v-if="event.opened && event.canEdit">
                {{ event.description }}
            </div>
        </div>
    </div>
    <confirmation-component
        v-if="deleteComponentVisible"
        :confirm="$t('Delete')"
        :titel="$t('Delete event')"
        :description="$t('Are you sure you want to put the event {0} in the trash? You can restore it within 30 days.', [event.eventName] )"
        @closed="afterConfirm"/>
</template>

<script>
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Input from "@/Jetstream/Input.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {
    Listbox,
    ListboxButton, ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Switch,
    SwitchGroup,
    SwitchLabel
} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, XCircleIcon, XIcon} from "@heroicons/vue/outline";
import {CheckIcon, ChevronUpIcon, TrashIcon} from "@heroicons/vue/solid";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import {useEvent} from "@/Composeables/Event.js";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import {inject} from "vue";
import {router, useForm, usePage} from "@inertiajs/vue3";

const {getDaysOfEvent, formatEventDateByDayJs} = useEvent();

export default {
name: "SingleEventInEventsWithoutRoom",
    mixins: [Permissions, IconLib],
    components: {
        ListboxLabel,
        TextareaComponent,
        TimeInputComponent,
        DateInputComponent,
        TextInputComponent,
        BaseModal,
        Switch,
        SwitchGroup,
        SwitchLabel,
        Input,
        JetDialogModal,
        XIcon,
        XCircleIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        ChevronDownIcon,
        ChevronUpIcon,
        SvgCollection,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        ConfirmationComponent,
        TagComponent
    },
    props: [
        'eventTypes',
        'rooms',
        'isAdmin',
        'removeNotificationOnAction',
        'first_project_calendar_tab_id',
        'event',
        'computedEventsWithoutRoom',
        'showHints',
        'eventStatuses'
    ],
    data() {
        return {
            startDate: null,
            startTime: null,
            endDate: null,
            endTime: null,
            projectName: null,
            title: null,
            eventName: null,
            eventTypeName: null,
            selectedEventType: this.eventTypes[0],
            selectedProject: null,
            selectedRoom: null,
            error: null,
            creatingProject: false,
            projectSearchResults: [],
            description: null,
            canEdit: false,
            deleteComponentVisible: false,
            eventToDelete: null,
            allDayEvent: false,
            showProjectInfo: this.event.project_id !== null,
            firstCall: true,
            isOption: false,
            frequencies: [
                {
                    id: 1,
                    name: this.$t('Daily')
                },
                {
                    id: 2,
                    name: this.$t('Weekly')
                },
                {
                    id: 3,
                    name: this.$t('Every 2 weeks')
                },
                {
                    id: 4,
                    name: this.$t('Monthly')
                }
            ],
            event_properties: inject('event_properties'),
            selectedEventStatus: this.eventStatuses?.find(status => status.default),
        }
    },
    emits: ['desiresReload'],
    watch: {
        projectName: {
            deep: true,
            handler() {
                if (this.creatingProject || !this.projectName) {
                    this.projectSearchResults = [];
                    return;
                }
                axios.get('/projects/search', {params: {query: this.projectName}})
                    .then(response => this.projectSearchResults = response.data)
            },
        }
    },
    created() {
        this.event_properties.forEach((event_property) => {
            event_property.checked = this.event.eventProperties.some(
                (event_event_properties) => event_event_properties.id === event_property.id
            );
        });
    },
    methods: {
        usePage,
        getTimeOfDate(date) {
            //returns hours and minutes in format HH:mm, if necessary with leading zeros, from given date object
            return ('0' + date.getHours()).slice(-2) + ":" + ('0' + date.getMinutes()).slice(-2);
        },
        getDateOfDate(date) {
            //returns date in format "YYYY-MM-DD" from given date object, with leading zeros
            //make sure to add 1 to the returned month because javascript starts counting from 0, January = 0
            return date.getFullYear() + "-" +
                (date.getMonth() + 1).toString().padStart(2, '0') + '-' +
                date.getDate().toString().padStart(2, '0');
        },
        convertDateFormat(dateString) {
            const parts = dateString.split('-');
            return parts[2] + "." + parts[1] + "." +parts[0];
        },
        getFrequencyName(frequencyId) {
            const matchedFrequency = this.frequencies.find(frequency => frequency.id === frequencyId);

            if (matchedFrequency) {
                return matchedFrequency.name;
            } else {
                return this.$t('No cycle selected');
            }
        },
        onLinkingProject(project) {
            this.event.projectId = project.id;
            this.event.project = project;
            this.event.projectName = '';
            this.projectName = '';
            this.event.showProjectSearchResults = false;
            this.projectSearchResults = [];
        },
        requestReload(desiredRoomId, desiredDays) {
            this.$emit('desiresReload', [desiredRoomId], desiredDays, true);
        },
        /**
         * Format date and time to ISO 8601 with timezone UTC
         *
         * @param date
         * @param time
         * @returns {string|null}
         */
        formatDate(date, time) {
            if (date === null || time === null) return null;
            return (new Date(date + ' ' + time)).toISOString()
        },
        checkChanges(event) {
            this.updateTimes(event);
        },
        /**
         * If the user selects a start, end, and room
         * call the server to get information if there are any collision
         *
         * @returns {Promise<void>}
         */
        async checkCollisions() {
            if (
                this.event.startTime && this.event.startDate && this.event.endTime && this.event.endDate ||
                this.event.allDay && this.event.startDate && this.event.endDate
            ) {
                let startFull = this.formatDate(this.event.startDate, !this.event.allDay ? this.event.startTime : '00:00');
                let endFull = this.formatDate(this.event.endDate, !this.event.allDay ? this.event.endTime : '23:59');

                await axios.post('/collision/room', {
                    params: {
                        start: startFull,
                        end: endFull
                    }
                }).then(response => this.event.roomCollisionArray = response.data);
            }
        },
        updateTimes() {
            if (this.event.startDate) {
                if (!this.event.endDate && this.checkYear(this.event.startDate)) {
                    this.event.endDate = this.event.startDate;
                }
                if (this.event.startTime) {
                    if (!this.event.endTime) {
                        if (this.event.startTime === '23:00') {
                            this.event.endTime = '23:59';
                        } else {
                            let startHours = this.event.startTime.slice(0, 2);
                            if (startHours === '23') {
                                this.event.endTime = '00:' + this.event.startTime.slice(3, 5);
                                let date = new Date();
                                this.event.endDate = new Date(
                                    date.setDate(new Date(this.event.endDate).getDate() + 1)
                                ).toISOString().slice(0, 10);
                            } else {
                                this.event.endTime = this.getNextHourString(this.event.startTime)
                            }
                        }
                    }
                }
            }

            this.validateStartBeforeEndTime(this.event);
            this.checkCollisions(this.event);
        },
        async validateStartBeforeEndTime(event) {
            event.error = null;
            if (event.startDate && event.endDate && event.startTime && event.endTime) {
                let startFull = this.setCombinedTimeString(event.startDate, event.startTime, 'start');
                let endFull = this.setCombinedTimeString(event.endDate, event.endTime, 'end');
                return await axios
                    .post('/events', {start: startFull, end: endFull}, {headers: {'X-Dry-Run': true}})
                    .catch(error => event.error = error.response.data.errors);
            }

        },
        addMinutes(date, minutes) {
            date.setMinutes(date.getMinutes() + minutes);
            return date;
        },
        setCombinedTimeString(date, time, target) {
            let combinedDateString = (date.toString() + ' ' + time);
            const offset = new Date(combinedDateString).getTimezoneOffset()

            if (target === 'start') {
                if (offset === -60) {
                    return new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)
                    ).toISOString().slice(0, 16);
                } else {
                    return new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)
                    ).toISOString().slice(0, 16);
                }
            } else if (target === 'end') {
                if (offset === -60) {
                    return new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)
                    ).toISOString().slice(0, 16);
                } else {
                    return new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)
                    ).toISOString().slice(0, 16);
                }
            }
        },
        getNextHourString(timeString) {
            let hours = timeString.slice(0, 2);
            let minutes = timeString.slice(3, 5);
            if ((Number(hours) + 1) < 10) {
                return '0' + (Number(hours) + 1) + ':' + minutes;
            } else {
                return (Number(hours) + 1) + ':' + minutes;
            }

        },
        deleteProject(event) {
            event.project = null;
            event.projectId = null;
            event.projectName = '';
        },
        /**
         * Creates an event and reloads all events
         *
         * @returns {Promise<*>}
         */
        updateEvent(event) {
            if (this.removeNotificationOnAction && (this.selectedRoom?.everyone_can_book || this.isAdmin)) {
                this.isOption = true;
            }

            const updateEventForm = useForm(this.eventData(event))

            updateEventForm.put(route('events.update', {event: event.id}), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    router.reload({
                        only: ['eventsWithoutRoom']
                    })
                },
                onError: (error) => {
                    event.error = error.response;
                }
            })
        },
        openDeleteEventModal() {
            this.deleteComponentVisible = true;
        },
        afterConfirm(bool) {
            if (!bool) {
                return this.deleteComponentVisible = false;
            }

            router.delete(route('events.delete', {event: this.event.id}), {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({
                        only: ['eventsWithoutRoom']
                    })
                    this.deleteComponentVisible = false;
                },
                onError: (error) => {
                    this.event.error = error.response.data.errors;
                }
            })


            /*axios.delete(route('events.delete', {event: this.event.id}))
                .then(() => {
                    this.requestReload(
                        null,
                        getDaysOfEvent(
                            formatEventDateByDayJs(event.start),
                            formatEventDateByDayJs(event.end)
                        )
                    );
                    this.deleteComponentVisible = false;
                })
                .catch(error => this.event.error = error.response.data.errors);*/
        },
        eventData(event) {
            return {
                title: event.title,
                eventName: event.eventName,
                start: this.formatDate(event.startDate, event.startTime),
                end: this.formatDate(event.endDate, event.endTime),
                roomId: event.roomId,
                description: event.description,
                eventNameMandatory: this.eventTypes.find(eventType => eventType.id === event.eventType.id)?.individual_name,
                projectId: this.showProjectInfo ? event.project.id : null,
                projectName: event.creatingProject ? event.project.name : '',
                eventTypeId: event.eventType.id,
                projectIdMandatory: this.eventTypes.find(eventType => eventType.id === event.eventType.id)?.project_mandatory && !this.creatingProject,
                creatingProject: event.creatingProject,
                isOption: this.isOption,
                allDay: event.allDay,
                eventStatusId: this.selectedEventStatus?.id,
                is_series: event.series ? event.series : false,
                event_properties: this.event_properties
                    .filter((eventProperty) => eventProperty.checked)
                    .map((eventProperty) => eventProperty.id)
            };
        },
    },
}
</script>
