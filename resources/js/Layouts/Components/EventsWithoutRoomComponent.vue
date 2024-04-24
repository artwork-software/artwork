<template>
    <jet-dialog-modal :show="true" @close="closeModal(false)">
        <template #content>
            <img alt="Terminkonflikt" src="/Svgs/Overlays/illu_appointment_warning.svg" class="-ml-6 -mt-8 mb-4"/>
            <IconX stroke-width="1.5" @click="closeModal()" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--    Heading    -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            {{$t('Events without room')}}
                        </div>
                    </h1>
                    <h2 class="xsLight">
                        {{$t('These room booking requests have been rejected by the room admin. Cancel the appointments or move them to another room.')}}
                    </h2>
                </div>
                <!--    Form    -->
                <div class="flex my-8 " v-for="event in this.computedEventsWithoutRoom">
                    <div class="flex w-full border border-2 border-gray-300">
                        <button v-if="this.computedEventsWithoutRoom.length > 1" class="bg-buttonBlue w-6"
                                @click="event.opened = !event.opened">
                            <IconChevronUp  stroke-width="1.5" v-if="event.opened"
                                           class="h-6 w-6 text-white my-auto"></IconChevronUp>
                            <IconChevronDown stroke-width="1.5" v-else
                                             class="h-6 w-6 text-white my-auto"></IconChevronDown>
                        </button>
                        <div class="mx-2 mt-2 w-full" v-if="(event.opened || this.computedEventsWithoutRoom.length === 1) && event.canEdit">
                            <div class="flex w-full justify-between">
                                <div
                                    class="flex justify-start my-auto items-center mt-3.5 ml-1 text-error line-through">
                                    {{ this.rooms.find(room => room.id === event.declinedRoomId)?.name }}
                                </div>
                                <div class="flex justify-end">

                                    <div v-if="event?.canDelete"
                                         class="flex  justify-end">
                                        <div class="flex mt-1 mr-2 cursor-pointer" @click="openDeleteEventModal(event)">
                                            <img class="bg-buttonBlue hover:bg-buttonHover h-8 w-8 p-1 rounded-full"
                                                 src="/Svgs/IconSvgs/icon_trash_white.svg"/>
                                        </div>
                                    </div>
                                    <div v-else>
                                        <div v-if="event.audience || event.isLoud"
                                             class="flex justify-end mt-6">
                                            <img v-if="event.audience" src="/Svgs/IconSvgs/icon_public.svg"
                                                 class="h-6 w-6 mx-2"
                                                 alt="audienceIcon"/>
                                            <img v-if="event.isLoud" src="/Svgs/IconSvgs/icon_adjustments.svg"
                                                 class="h-5 w-5 mx-2"
                                                 alt="attributeIcon"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--    Type and Title    -->
                            <div class="flex py-2">

                                <div class="w-1/2">
                                    <div class="h-12 flex w-full truncate p-2" v-if="!event.canEdit">
                                        <div>
                                            <div class="block w-5 h-5 rounded-full" :style="{'backgroundColor' : this.eventTypes.find(type => type.id === event.eventTypeId)?.hex_code }" />
                                        </div>
                                        <p class="ml-2 headline2">
                                            {{ this.eventTypes.find(type => type.id === event.eventTypeId).name }}</p>

                                    </div>

                                    <Listbox as="div" class="flex h-12 mr-2" v-model="event.eventTypeId"
                                             v-if="event.canEdit"
                                             :onchange="checkCollisions(event)" id="eventType">
                                        <ListboxButton
                                            class="pl-3 border-2 border-gray-300 w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                            <div class="flex items-center my-auto">
                                                <div>
                                                    <div class="block w-5 h-5 rounded-full" :style="{'backgroundColor' : this.eventTypes.find(type => type.id === event.eventTypeId)?.hex_code }" />
                                                </div>
                                                <span class="block truncate items-center ml-3 flex">
                                            <span>{{
                                                    this.eventTypes.find(type => type.id === event.eventTypeId)?.name
                                                }}</span>
                                </span>
                                                <span
                                                    class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                                            </div>
                                        </ListboxButton>

                                        <transition leave-active-class="transition ease-in duration-100"
                                                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                                            <ListboxOptions
                                                class="absolute w-64 z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
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

                                <div class="w-1/2">
                                    <input
                                        v-if="this.eventTypes.find(type => type.id === event.eventTypeId)?.individual_name"
                                        type="text"
                                        v-model="event.eventName"
                                        id="eventTitle"
                                        :placeholder="$t('Event name') + '*'"
                                        :disabled="!event.canEdit"
                                        class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    <input v-else type="text"
                                           v-model="event.eventName"
                                           id="eventTitle"
                                           :placeholder="$t('Event name')"
                                           :disabled="!event.canEdit"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                                    <p class="text-xs text-red-800">{{ event.error?.eventName?.join('. ') }}</p>
                                </div>

                            </div>
                            <!--    Properties    -->
                            <div class="flex py-2">
                                <div v-if="event.audience">
                                    <TagComponent icon="audience" :displayed-text="$t('With audience')"
                                                  hideX="true"></TagComponent>
                                </div>
                                <div v-if="event.isLoud">
                                    <TagComponent :displayed-text="$t('It gets loud')" hideX="true"></TagComponent>
                                </div>
                            </div>

                            <!--    Time    -->
                            <SwitchGroup as="div" class="flex items-center">
                                <Switch v-model="event.allDay"
                                        @update:modelValue="checkChanges(event)"
                                        :class="[event.allDay ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-1 focus:ring-indigo-600 focus:ring-offset-2']">
                                    <span aria-hidden="true"
                                          :class="[event.allDay ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                                </Switch>
                                <SwitchLabel as="span" class="ml-3 text-sm">
                                    <span :class="[event.allDay ? 'xsDark' : 'xsLight', 'text-sm']">
                                        {{$t('Full day')}}
                                    </span>
                                </SwitchLabel>
                            </SwitchGroup>
                            <div class="flex py-1 flex-col sm:flex-row align-baseline">
                                <div class="sm:w-1/2">
                                    <label for="startDate" class="xsLight">{{$t('Start*')}}</label>
                                    <div class="w-full flex">
                                        <input v-model="event.startDate"
                                               id="startDate"
                                               @change="checkChanges(event)"
                                               type="date"
                                               :disabled="!event.canEdit"
                                               required
                                               class="border-gray-300 border-2  disabled:border-none flex-grow"/>
                                        <input v-model="event.startTime"
                                               v-if="!event.allDay"
                                               id="changeStartTime"
                                               @change="checkChanges(event)"
                                               type="time"
                                               :disabled="!event.canEdit"
                                               required
                                               class="border-gray-300 border-2  disabled:border-none"/>
                                    </div>
                                    <p class="text-xs text-red-800">{{ event.error?.start?.join('. ') }}</p>
                                </div>
                                <div class="sm:w-1/2">
                                    <label for="endDate" class="xsLight">{{ $t('End*') }}</label>
                                    <div class="w-full flex">
                                        <input v-model="event.endDate"
                                               id="endDate"
                                               @change="checkChanges(event)"
                                               type="date"
                                               required
                                               :disabled="!event.canEdit"
                                               class="border-gray-300 border-2 disabled:border-none flex-grow"/>
                                        <input v-model="event.endTime"
                                               v-if="!event.allDay"
                                               id="changeEndTime"
                                               @change="checkChanges(event)"
                                               type="time"
                                               required
                                               :disabled="!event.canEdit"
                                               class="border-gray-300 border-2 disabled:border-none"/>
                                    </div>
                                    <p class="text-xs text-red-800">{{ event.error?.end?.join('. ') }}</p>
                                </div>
                            </div>
                            <div class="text-xs text-red-800" v-if="event.helpTextLength">{{ event.helpTextLength }}</div>
                            <!-- Serien Termin -->
                            <div v-if="event?.is_series" class="xsLight mt-2">{{ $t('Event is part of a repeat event') }}</div>
                            <div v-if="event?.is_series" class="xsLight mb-2">{{ $t('Cycle: {0} to {1}', {0: event.selectedFrequencyName, 1: convertDateFormat(event.series.end_date) } )}}</div>
                            <!--    Room    -->
                            <div class="py-1">
                                <div class=" w-full h-10 cursor-pointer truncate p-2" v-if="!event.canEdit">
                                    {{ this.rooms.find(room => room.id === event.roomId)?.name }}
                                </div>
                                <Listbox as="div" v-model="event.roomId" id="room" v-if="event.canEdit">
                                    <ListboxButton
                                        class="border-2 border-gray-300 w-full h-10 cursor-pointer truncate flex p-2">
                                        <div v-if="event.roomId" class="flex-grow text-left">
                                            {{ this.rooms.find(room => room.id === event.roomId)?.name }}
                                        </div>
                                        <div v-else class="flex-grow xsLight text-left subpixel-antialiased">
                                            {{$t('Select room')}}*
                                        </div>
                                        <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </ListboxButton>
                                    <ListboxOptions
                                        class="w-[80%] bg-primary max-h-32 overflow-y-auto text-sm absolute z-20">
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
                            <div class="bg-lightBackgroundGray pt-1 pb-4 px-3">
                                <div class="my-3">
                                    <input type="checkbox"
                                           v-model="event.showProjectInfo"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300">
                                    <span :class="[event.showProjectInfo ? 'xsDark' : 'xsLight', 'text-sm ml-2']">{{$t('Assign event to a project')}}</span>
                                </div>
                                <!--    Project    -->
                                <div v-if="event.showProjectInfo">
                                    <div class="xsLight flex" v-if="!event.creatingProject">
                                        {{$t('Currently assigned to:')}}
                                        <a v-if="event.projectId"
                                           :href="route('projects.tab', {project: event.projectId, projectTab: this.first_project_calendar_tab_id})"
                                           class="ml-3 flex xsDark">
                                            {{ event.project?.name }}
                                        </a>
                                        <div v-else class="xsDark ml-2">
                                            {{ event.project?.name ?? 'Keinem Projekt' }}
                                        </div>
                                        <div v-if="event.project?.id && event.canEdit" class="flex items-center my-auto">
                                            <button type="button"
                                                    @click="this.deleteProject(event)">
                                                <IconCircleX stroke-width="1.5" class="pl-2 h-6 w-6 hover:text-error text-primary"/>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="xsLight" v-if="event.creatingProject">
                                       {{$t('The project is created when it is saved.')}}
                                    </div>

                                    <div class="my-2" v-if="event.canEdit">
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
                                        <input type="text"
                                               id="projectName"
                                               @focusin="event.showProjectSearchResults = true"
                                               @keyup="this.projectName = event.projectName"
                                               v-model="event.projectName"
                                               autocomplete="off"
                                               :placeholder="creatingProject ? $t('New project name') : $t('Search project')"
                                               class="h-10 border-2 focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                                        <div
                                            v-if="projectSearchResults.length > 0 && !event.creatingProject && event.showProjectSearchResults"
                                            class="absolute bg-primary truncate sm:text-sm w-10/12">
                                            <div v-for="(project, index) in projectSearchResults"
                                                 :key="index"
                                                 @click="onLinkingProject(project, event)"
                                                 class="p-4 text-white border-l-4 hover:border-l-success border-l-primary cursor-pointer">
                                                {{ project.name }}
                                            </div>
                                        </div>

                                        <p class="text-xs text-red-800">{{ event.error?.projectId?.join('. ') }}</p>
                                        <p class="text-xs text-red-800">{{ event.error?.projectName?.join('. ') }}</p>
                                    </div>
                                </div>
                                <!--    Description    -->
                                <div class="py-2">
                                <textarea :placeholder="$t('What do I need to bear in mind for the event?')"
                                          id="description"
                                          :disabled="!event.canEdit"
                                          v-model="event.description"
                                          rows="4"
                                          class="border-gray-300 border-2 resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1"/>
                                </div>
                                <!-- Attribute Menu -->
                                <Menu as="div" class="inline-block text-left w-full">
                                    <div>
                                        <MenuButton
                                            class="h-12 border-2 border-gray-300 w-full bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white "
                                        >
                                            <span class="float-left flex xsLight subpixel-antialiased">
                                                <IconAdjustmentsAlt stroke-width="1.5"
                                                class="mr-2"/>{{$t('Select appointment properties')}}</span>
                                            <IconChevronDown stroke-width="1.5"
                                                class="ml-2 -mr-1 h-5 w-5 text-primary float-right"
                                                aria-hidden="true"
                                            />
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
                                            class="absolute overflow-y-auto h-24 mt-2 w-[80%] origin-top-left divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                                            <div class="mx-auto w-full rounded-2xl bg-primary border-none mt-2">
                                                <div class="flex w-full mb-4">
                                                    <input v-model="event.audience"
                                                           :disabled="!event.canEdit"
                                                           type="checkbox"
                                                           class="checkBoxOnDark"/>
                                                    <IconUsersGroup stroke-width="1.5" class="h-6 w-6 mx-2"
                                                         alt="audienceIcon"/>

                                                    <div
                                                        :class="[event.audience ? 'text-white' : 'text-secondary', 'subpixel-antialiased']">
                                                        {{$t('With audience')}}
                                                    </div>
                                                </div>
                                                <div class="flex w-full mb-2">
                                                    <input v-model="event.isLoud"
                                                           :disabled="!event.canEdit"
                                                           type="checkbox"
                                                           class="checkBoxOnDark"/>
                                                    <div
                                                        :class="[event.isLoud ? 'text-white' : 'text-secondary', 'subpixel-antialiased mx-2']">
                                                        {{$t('It gets loud')}}
                                                    </div>
                                                </div>
                                            </div>
                                        </MenuItems>
                                    </transition>
                                </Menu>
                            </div>
                            <div class="flex justify-center w-full py-4" v-if="event.canEdit">
                                <button
                                    :disabled="event.roomId === null || event.startDate === null || event.endDate === null || (event.startTime === null && !event.allDayEvent) || (event.endTime === null && !event.allDayEvent)"
                                    :class="event.roomId === null || event.startDate === null || event.endDate === null || (event.startTime === null && !event.allDayEvent) || (event.endTime === null && !event.allDayEvent) ? 'bg-secondary hover:bg-secondary' : ''"
                                    class="bg-buttonBlue hover:bg-indigo-600 py-2 px-8 rounded-full text-white"
                                    @click="updateOrCreateEvent(event)"
                                >
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
                </div>
            </div>
        </template>
    </jet-dialog-modal>

    <!-- Event löschen Modal -->
    <confirmation-component
        v-if="deleteComponentVisible"
        :confirm="$t('Delete')"
        :titel="$t('Delete event')"
        :description="$t('Are you sure you want to put the event {0} in the trash? You can restore it within 30 days.', [eventToDelete.eventName] )"
        @closed="afterConfirm"/>

</template>

<script>

import JetDialogModal from "@/Jetstream/DialogModal";
import {
    ChevronDownIcon,
    DotsVerticalIcon,
    PencilAltIcon,
    XCircleIcon,
    XIcon
} from '@heroicons/vue/outline';
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems, Switch, SwitchGroup, SwitchLabel
} from "@headlessui/vue";
import {
    CheckIcon,
    ChevronUpIcon,
    TrashIcon
} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import Input from "@/Jetstream/Input";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent";
import TagComponent from "@/Layouts/Components/TagComponent";
import Permissions from "@/mixins/Permissions.vue";
import {Inertia} from "@inertiajs/inertia";
import IconLib from "@/mixins/IconLib.vue";

export default {
    name: 'EventsWithoutRoomComponent',
    mixins: [Permissions, IconLib],
    components: {
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
    data() {
        return {
            startDate: null,
            startTime: null,
            endDate: null,
            endTime: null,
            isLoud: false,
            audience: false,
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
            showProjectInfo: false,
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
            ]
        }
    },
    props: [
        'showHints',
        'eventTypes',
        'rooms',
        'isAdmin',
        'eventsWithoutRoom',
        'removeNotificationOnAction',
        'first_project_calendar_tab_id'
    ],
    emits: ['closed'],
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
    computed: {
        computedEventsWithoutRoom: function () {
            this.eventsWithoutRoom.forEach((event) => {
                const dateStart = new Date(event.start);
                event.startDate = this.getDateOfDate(dateStart);
                event.startTime = this.getTimeOfDate(dateStart);

                const dateEnd = new Date(event.end);
                event.endDate = this.getDateOfDate(dateEnd);
                event.endTime = this.getTimeOfDate(dateEnd);

                event.creatingProject = false;
                //setting show project info for every event on first rendering
                if (this.firstCall) {
                    event.showProjectInfo = (event.projectId !== null);
                    event.selectedFrequencyName = this.getFrequencyName(event.series?.frequency_id);
                }
            })
            this.firstCall = false;
            return this.eventsWithoutRoom;
        },
    },
    methods: {
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
        onLinkingProject(project, event) {
            event.projectId = project.id;
            event.project = project;
            event.projectName = '';
            this.projectName = '';
            event.showProjectSearchResults = false;
            this.projectSearchResults = [];
        },
        closeModal(bool) {
            this.$emit('closed', bool);
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
        async checkCollisions(event) {
            if (
                event.startTime && event.startDate && event.endTime && event.endDate ||
                event.allDay && event.startDate && event.endDate
            ) {
                let startFull = this.formatDate(event.startDate, !event.allDay ? event.startTime : '00:00');
                let endFull = this.formatDate(event.endDate, !event.allDay ? event.endTime : '23:59');

                await axios.post('/collision/room', {
                    params: {
                        start: startFull,
                        end: endFull
                    }
                }).then(response => event.roomCollisionArray = response.data);
            }
        },
        updateTimes(event) {
            if (event.startDate) {
                if (!event.endDate && this.checkYear(event.startDate)) {
                  event.endDate = event.startDate;
                }
                if (event.startTime) {
                    if (!event.endTime) {
                          if (event.startTime === '23:00') {
                            event.endTime = '23:59';
                          } else {
                              let startHours = event.startTime.slice(0, 2);
                              if (startHours === '23') {
                                  event.endTime = '00:' + event.startTime.slice(3, 5);
                                  let date = new Date();
                                  event.endDate = new Date(
                                      date.setDate(new Date(event.endDate).getDate() + 1)
                                  ).toISOString().slice(0, 10);
                              } else {
                                  event.endTime = this.getNextHourString(event.startTime)
                              }
                        }
                    }
                }
            }

            this.validateStartBeforeEndTime(event);
            this.checkCollisions(event);
            this.checkEventTimeLength(event);
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
        checkEventTimeLength(event) {
            if (event.allDay) {
                event.helpTextLength = '';
                return;
            }
            // check if event min 30min
            let startFull = new Date(event.startDate + ' ' + event.startTime);
            let endFull = new Date(event.endDate + ' ' + event.endTime);

            const minimumEnd = this.addMinutes(startFull, 30);
            if (minimumEnd <= endFull) {
                event.helpTextLength = '';
            } else {
                event.helpTextLength = 'Der Termin darf nicht kürzer als 30 Minuten sein';
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
        async updateOrCreateEvent(event) {
            if (this.removeNotificationOnAction && (this.selectedRoom?.everyone_can_book || this.isAdmin)) {
                this.isOption = true;
            }
            return await axios
                .put('/events/' + event?.id, this.eventData(event))
                .then(() => this.closeModal(this.removeNotificationOnAction === true))
                .catch(error => event.error = error.response.data.errors);
        },
        openDeleteEventModal(event) {
            this.eventToDelete = event;
            this.deleteComponentVisible = true;
        },
        async afterConfirm(bool) {
            if (!bool) return this.deleteComponentVisible = false;

            Inertia.delete(`/events/${this.eventToDelete.id}`, {
              onFinish: () => {
                this.closeModal();
              }
            })
        },
        eventData(event) {
            return {
                title: event.title,
                eventName: event.eventName,
                start: this.formatDate(event.startDate, event.startTime),
                end: this.formatDate(event.endDate, event.endTime),
                roomId: event.roomId,
                description: event.description,
                audience: event.audience,
                isLoud: event.isLoud,
                eventNameMandatory: this.eventTypes.find(eventType => eventType.id === event.eventTypeId)?.individual_name,
                projectId: event.showProjectInfo ? event.projectId : null,
                projectName: event.creatingProject ? event.projectName : '',
                eventTypeId: event.eventTypeId,
                projectIdMandatory: this.eventTypes.find(eventType => eventType.id === event.eventTypeId)?.project_mandatory && !this.creatingProject,
                creatingProject: event.creatingProject,
                isOption: this.isOption,
                allDay: event.allDay,
                is_series: event.series ? event.series : false
            };
        },
    },
}
</script>
