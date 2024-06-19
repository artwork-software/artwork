<template>
    <BaseModal @closed="closeModal" v-if="true" :modal-image="!this.event?.id ? '/Svgs/Overlays/illu_appointment_new.svg' : '/Svgs/Overlays/illu_appointment_edit.svg'">
            <div class="mx-4">
                <!--   Heading   -->
                <div v-if="this.isRoomAdmin || this.hasAdminRole()">
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            {{
                                this.event?.id ? this.event?.occupancy_option ? $t('Change & confirm occupancy') : $t('Event') : $t('New room allocation')
                            }}
                        </div>
                    </h1>
                    <h2 v-if="!this.event?.id" class="xsLight my-3">
                        {{ $t('Please make sure that you allow for preparation and follow-up time.')}}
                    </h2>
                    <div v-else class="flex items-center">
                        {{ $t('Created by')}}
                        <div v-if="this.event.created_by">
                            <UserPopoverTooltip :user="this.event.created_by" :id="this.event.created_by.id" height="7"
                                                width="7" class="ml-2"/>
                        </div>
                        <div class="xsLight ml-3" v-else>
                            {{ $t('deleted User')}}
                        </div>
                    </div>
                </div>
                <div v-else class="flex-grow headline1">
                    {{ $t('Event')}}
                </div>
                <!--    Form    -->
                <!--    Type and Title    -->
                <div class="flex py-2 my-3">
                    <div class="w-1/2">
                        <div class=" w-full flex cursor-pointer truncate"
                             v-if="!canEdit || this.disableEventTypeSelector">
                            <div>
                                <div class="block w-10 h-10 rounded-full" :style="{'backgroundColor' : selectedEventType?.hex_code }" />
                            </div>
                            <p class="ml-2 flex items-center text-lg font-lexend font-semibold">
                                {{ selectedEventType?.name }}
                            </p>
                        </div>
                        <Listbox as="div" class="flex h-12 mr-2" v-model="selectedEventType"
                                 v-if="canEdit && !this.disableEventTypeSelector"
                                 id="eventType">
                            <ListboxButton
                                class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                <div class="flex items-center my-auto">
                                    <div>
                                        <div class="block w-5 h-5 rounded-full" :style="{'backgroundColor' : selectedEventType?.hex_code }" />
                                    </div>
                                    <span class="block truncate items-center ml-3 flex">
                                            <span>{{ selectedEventType?.name }}</span>
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
                                    class="absolute w-72 z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-for="eventType in eventTypes"
                                                   :key="eventType.name"
                                                   :value="eventType"
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
                        <p class="text-xs text-red-800">{{ error?.eventType?.join('. ') }}</p>
                    </div>

                    <div class="w-1/2" v-if="canEdit">
                        <input type="text"
                               v-model="this.eventName"
                               id="eventTitle"
                               :placeholder="selectedEventType?.individual_name ? $t('Event name') + '*' : $t('Event name')"
                               :disabled="!canEdit"
                               class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                        <p v-if="selectedEventType?.individual_name" class="text-xs text-red-800">
                            {{ error?.eventName?.join('. ') }}</p>
                    </div>
                    <div v-else class="flex w-1/2 ml-12 items-center">
                        {{ this.eventName }}
                    </div>
                </div>

                <div v-if="!canEdit" class="flex w-full">
                    <div class="w-1/2 flex items-center my-auto" v-if="this.selectedProject?.id">
                        {{$t('assigned to')}}: <a
                        :href="route('projects.tab', {project: selectedProject.id, projectTab: this.first_project_calendar_tab_id})"
                        class="ml-3 mt-1 text-sm items-center flex font-bold font-lexend text-primary">
                        {{ this.selectedProject?.name }}
                    </a>
                    </div>
                    <div v-if="this.event.created_by" class="flex items-center w-1/2">
                        <p class="truncate xsLight subpixel-antialiased max-w-60">
                            {{ $t('Created by')}}
                            <UserPopoverTooltip :user="this.event.created_by" :id="this.event.created_by.id" height="9"
                                                width="9" class="ml-2"/>
                        </p>
                    </div>
                </div>
                <div v-if="!canEdit" class="my-2">
                    <div v-if="this.startDate === this.endDate">
                        {{ this.selectedRoom?.name }},
                        {{ this.startDate.toString().substring(10, 8) }}.{{
                            this.startDate.toString().substring(7, 5)
                        }}.{{ this.startDate.toString().substring(4, 0) }},
                        {{ this.startTime }} - {{ this.endTime }}
                    </div>
                    <div v-else>
                        {{ this.selectedRoom?.name }},
                        {{ this.startDate.toString().substring(10, 8) }}.{{
                            this.startDate.toString().substring(7, 5)
                        }}.{{ this.startDate.toString().substring(4, 0) }},
                        {{ this.startTime }} -
                        {{ this.endDate.toString().substring(10, 8) }}.{{
                            this.endDate.toString().substring(7, 5)
                        }}.{{ this.endDate.toString().substring(4, 0) }},
                        {{ this.endTime }}
                    </div>
                </div>

                <!--    Time    -->
                <div v-if="canEdit" class="w-full">
                    <SwitchGroup as="div" class="flex items-center">
                        <Switch v-model="this.allDayEvent"
                                @update:modelValue="checkChanges"
                                :class="[this.allDayEvent ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-1 focus:ring-indigo-600 focus:ring-offset-2']">
                            <span aria-hidden="true"
                                  :class="[this.allDayEvent ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                        </Switch>
                        <SwitchLabel as="span" class="ml-3 text-sm">
                            <span :class="[this.allDayEvent ? 'xsDark' : 'xsLight', 'text-sm']">
                                {{$t('Full day')}}
                            </span>
                        </SwitchLabel>
                    </SwitchGroup>
                </div>
                <div v-if="canEdit" class="flex pb-1 flex-col sm:flex-row align-baseline">
                    <div class="sm:w-1/2 mr-1">
                        <label for="startDate" class="xxsLight">{{ $t('Start*') }}</label>
                        <div class="w-full flex">
                            <input v-model="startDate"
                                   id="startDate"
                                   @change="checkChanges()"
                                   type="date"
                                   :disabled="!canEdit"
                                   required
                                   class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
                            <input v-model="startTime"
                                   id="changeStartTime"
                                   v-if="!allDayEvent"
                                   @change="checkChanges()"
                                   type="time"
                                   :disabled="!canEdit"
                                   required
                                   class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none"/>
                        </div>
                        <p class="text-xs text-red-800">{{ error?.start?.join('. ') }}</p>
                    </div>
                    <div class="sm:w-1/2 ml-1">
                        <label for="endDate" class="xxsLight">{{$t('End*')}}</label>
                        <div class="w-full flex">
                            <input v-model="endDate"
                                   id="endDate"
                                   @change="checkChanges()"
                                   type="date"
                                   required
                                   :disabled="!canEdit"
                                   class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none flex-grow"/>
                            <input v-model="endTime"
                                   v-if="!allDayEvent"
                                   id="changeEndTime"
                                   @change="checkChanges()"
                                   type="time"
                                   required
                                   :disabled="!canEdit"
                                   class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none"/>
                        </div>
                        <p class="text-xs text-red-800">{{ error?.end?.join('. ') }}</p>
                    </div>

                </div>
                <div>
                    <div class="text-red-500 text-xs" v-show="helpTextLength.length > 0">{{ helpTextLength }}</div>
                </div>
                <div>
                    <div class="text-red-500 text-xs" v-show="helpTextLengthRoom.length > 0">{{
                            helpTextLengthRoom
                        }}
                    </div>
                </div>
                <!-- Serien Termin -->
                <div v-if="!event">
                    <SwitchGroup as="div" class="flex items-center my-3">
                        <Switch v-model="series"
                                :class="[series ? 'bg-indigo-600 cursor-pointer' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-1 focus:ring-indigo-600 focus:ring-offset-2']">
                            <span aria-hidden="true"
                                  :class="[series ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                        </Switch>
                        <SwitchLabel as="span" class="ml-3 text-sm">
                            <span :class="[series ? 'xsDark' : 'xsLight', 'text-sm']">
                                {{ $t('Repeat event')}}
                            </span>
                        </SwitchLabel>
                    </SwitchGroup>
                    <div v-show="series">
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <Listbox :disabled="event?.is_series" as="div" v-model="selectedFrequency">
                                <div class="relative mt-2">
                                    <ListboxButton
                                        class="w-full h-10 border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow">
                                        <span class="block truncate">{{ selectedFrequency.name }}</span>
                                        <span
                                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                             <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                        </span>
                                    </ListboxButton>

                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions
                                            class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                            <ListboxOption as="template" v-for="frequency in frequencies"
                                                           :key="frequency.id" :value="frequency"
                                                           v-slot="{ active, selected }">
                                                <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                    <span
                                                        :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{
                                                            frequency.name
                                                        }}</span>

                                                    <span v-if="selected"
                                                          :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                        <IconCheck stroke-width="1.5" class="h-5 w-5" aria-hidden="true"/>
                                                    </span>
                                                </li>
                                            </ListboxOption>
                                        </ListboxOptions>
                                    </transition>
                                </div>
                            </Listbox>
                            <div class="mt-2">
                                <div class="w-full flex">
                                    <input
                                        :disabled="event?.is_series"
                                        v-model="seriesEndDate"
                                        id="endDate"
                                        :type="seriesEndDate ? 'date' : 'text'"
                                        :placeholder="$t('End date Repeat event')"
                                        required
                                        @focus="input => input.target.type = 'date'"
                                        class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none flex-grow"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else-if="event?.is_series" class="xsLight mt-2">{{ $t('Event is part of a repeat event')}}</div>
                <div v-if="event?.is_series" class="xsLight mb-2">
                    {{ $t('Cycle: {0} to {1}', { 0: selectedFrequency.name, 1: convertDateFormat(seriesEndDate)})}}
                </div>
                <!--    Room    -->
                <div class="pt-1 mb-4" v-if="canEdit">
                    <Listbox as="div" v-model="selectedRoom" id="room" v-if="canEdit && selectedRoom">
                        <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                            <div class="flex-grow flex text-left xsDark">
                                {{ selectedRoom?.name }}
                            </div>
                            <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                        </ListboxButton>
                        <ListboxOptions class="w-5/6 bg-primary max-h-32 overflow-y-auto text-sm absolute z-30">
                            <ListboxOption v-for="room in this.rooms"
                                           class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                           :key="room.name"
                                           :value="room"
                                           v-slot="{ active, selected }">
                                <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                    {{ room.name }}
                                    <IconAlertTriangle stroke-width="1.5" v-if="this.roomCollisionArray[room.id] > 0"
                                                       class="h-4 w-4 mx-2" aria-hidden="true"/>
                                </div>
                                <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                            </ListboxOption>
                        </ListboxOptions>
                    </Listbox>
                    <Listbox as="div" v-model="selectedRoom" id="room" v-else>
                        <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                            <div class="flex-grow xsLight text-left subpixel-antialiased">
                                {{$t('Select room')}}*
                            </div>
                            <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                        </ListboxButton>
                        <ListboxOptions class="w-5/6 bg-primary max-h-32 overflow-y-auto text-sm absolute z-30">
                            <ListboxOption v-for="room in rooms"
                                           class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between"
                                           :key="room.name"
                                           :value="room"
                                           v-slot="{ active, selected }">
                                <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                    {{ room.name }}
                                    <img
                                        v-if="this.roomCollisionArray[room.id] > 0"
                                        src="/Svgs/IconSvgs/icon_warning_white.svg"
                                        class="h-4 w-4 mx-2" alt="conflictIcon"
                                    />
                                </div>
                                <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                            </ListboxOption>
                        </ListboxOptions>
                    </Listbox>
                    <p class="text-xs text-red-800">{{ error?.roomId?.join('. ') }}</p>
                </div>

                <!--Gray Background Area -->
                <div class="bg-lightBackgroundGray -mx-10 pt-1 pb-4">
                    <div class="px-10">
                        <!--    Project    -->
                        <div v-if="canEdit">
                            <!-- Checkbox to decide if i show this block or not -->
                            <div class="my-3">
                                <input type="checkbox" v-model="showProjectInfo"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300">
                                <span :class="[showProjectInfo ? 'xsDark' : 'xsLight', 'text-sm ml-2']">{{$t('Assign event to a project')}}</span>
                            </div>
                            <div v-if="showProjectInfo">
                                <div class="xsLight flex" v-if="!this.creatingProject">
                                    {{$t('Currently assigned to:')}}
                                    <a v-if="this.selectedProject?.id"
                                       :href="route('projects.tab', {project: selectedProject.id, projectTab: this.first_project_calendar_tab_id})"
                                       class="ml-3 flex xsDark">
                                        {{ this.selectedProject?.name }}
                                    </a>
                                    <div v-else class="xsDark ml-2">
                                        {{ this.selectedProject?.name ?? 'Keinem Projekt' }}
                                    </div>
                                    <div v-if="this.selectedProject?.id && this.canEdit"
                                         class="flex items-center my-auto">
                                        <button type="button"
                                                @click="selectedProject = null">
                                            <IconCircleX stroke-width="1.5" class="pl-2 h-6 w-6 hover:text-error text-primary"/>
                                        </button>
                                    </div>
                                </div>
                                <div class="xsLight" v-if="this.creatingProject">
                                    {{ $t('The project is created when it is saved.') }}
                                </div>

                                <div class="my-2" v-if="this.canEdit">
                                    <div class="flex pb-2">
                            <span class="mr-4 "
                                  :class="[!creatingProject ? 'xsDark' : 'xsLight',]">
                                {{$t('Existing project')}}
                            </span>
                                        <div class="flex">
                                            <label for="project-toggle"
                                                   class="inline-flex relative items-center cursor-pointer">
                                                <input type="checkbox"
                                                       v-model="creatingProject"
                                                       :disabled="!canEdit"
                                                       id="project-toggle"
                                                       class="sr-only peer">
                                                <div class="w-9 h-5 bg-gray-200 rounded-full
                            peer-checked:after:translate-x-full peer-checked:after:border-white
                            after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                            after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-artwork-buttons-create">
                                                </div>
                                            </label>
                                            <span class="ml-4 text-sm"
                                                  :class="[creatingProject ? 'xsDark' : 'xsLight']">
                                {{$t('New project')}}
                            </span>
                                            <div v-if="showHints" class="ml-3 flex">
                                                <SvgCollection svgName="arrowLeft" class="mt-1"/>
                                                <div class=" ml-1 my-auto hind">
                                                    {{ $t('Create a new project at the same time')}}
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <input type="text"
                                           :placeholder="creatingProject ? $t('New project name') : $t('Search project')"
                                           v-model="projectName"
                                           class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                                    <div v-if="projectSearchResults.length > 0 && !creatingProject"
                                         class="absolute bg-primary truncate sm:text-sm w-10/12">
                                        <div v-for="(project, index) in projectSearchResults"
                                             :key="index"
                                             @click="chooseProject(project)"
                                             class="p-4 xsWhiteBold border-l-4 hover:border-l-success border-l-primary cursor-pointer">
                                            {{ project.name }}
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-red-800">{{ error?.projectId?.join('. ') }}</p>
                        <p class="text-xs text-red-800">{{ error?.projectName?.join('. ') }}</p>
                        <!--    Description    -->
                        <div class="py-2">
                    <textarea v-if="canEdit" :placeholder="$t('What do I need to bear in mind for the event?')"
                              id="description"
                              :disabled="!canEdit"
                              v-model="description"
                              rows="4"
                              class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>
                            <div v-else-if="this.description" class="mt-4 xsDark">
                                {{ this.description }}
                            </div>
                            <div v-if="this.event?.occupancy_option && canEdit">
                        <textarea v-if="canEdit" :placeholder="$t('Comment on the booking (inquirer will be notified)')"
                                  id="adminComment"
                                  :disabled="!canEdit"
                                  v-model="adminComment"
                                  rows="4"
                                  class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>
                            </div>
                            <div v-if="this.event?.occupancy_option && (isRoomAdmin || this.hasAdminRole()) "
                                 class="flex py-2 items-center">
                                <label for="accept-toggle" class="inline-flex relative items-center cursor-pointer">
                                    <input type="checkbox"
                                           v-model="accept"
                                           :disabled="!canEdit"
                                           @change="toggleAccept('accept')"
                                           id="accept-toggle"
                                           class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 rounded-full
                            peer-checked:after:translate-x-full peer-checked:after:border-white
                            after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                            after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-artwork-buttons-create">
                                    </div>
                                </label>
                                <span class="ml-2 text-sm"
                                      :class="[accept ? 'xsDark' : 'xsLight']">
                                {{ $t('Commitments')}}
                        </span>
                                <div class="ml-12 flex items-center">
                                    <label for="optionAccept-toggle"
                                           class="inline-flex relative items-center cursor-pointer">
                                        <input type="checkbox"
                                               v-model="optionAccept"
                                               :disabled="!canEdit"
                                               @change="toggleAccept('option')"
                                               id="optionAccept-toggle"
                                               class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 rounded-full
                            peer-checked:after:translate-x-full peer-checked:after:border-white
                            after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                            after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-artwork-buttons-create">
                                        </div>
                                    </label>
                                    <span class="ml-2 text-sm"
                                          :class="[optionAccept ? 'xsDark' : 'xsLight']">
                                {{ $t('Optional commitment')}}
                        </span>
                                </div>
                            </div>
                            <div class="py-2 w-full" v-if="optionAccept">
                                <Listbox as="div" v-model="optionString" id="room">
                                    <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                                        <div class="flex-grow flex text-left xsDark">
                                            {{ optionString }}
                                        </div>
                                        <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </ListboxButton>
                                    <ListboxOptions class="w-5/6 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                        <ListboxOption v-for="option in options"
                                                       class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                                       :key="option.name"
                                                       :value="option.name"
                                                       v-slot="{ active, selected }">
                                            <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                                {{ option.name }}
                                            </div>
                                            <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </Listbox>
                            </div>
                        </div>
                        <div v-if="!this.$can('request room occupancy')">
                            <div class="errorText">
                                {{$t('You do not have the permission to request a room reservation.')}}
                            </div>
                        </div>
                        <div v-if="showComments" class="my-6" v-for="comment in this.event.comments">
                            <div class="flex justify-between">
                                <div class="flex items-center">
                                    <NewUserToolTip :id="comment.id" :user="comment.user" :height="8"
                                                    :width="8"></NewUserToolTip>
                                    <div class="ml-2 text-secondary">
                                        {{ comment.created_at }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 mr-14 subpixel-antialiased text-primary">
                                {{ comment.comment }}
                            </div>
                        </div>
                        <!-- Attribute Menu -->
                        <Menu as="div" class="inline-block text-left w-full" v-if="canEdit">
                            <div>
                                <MenuButton
                                    class="h-12 inputMain w-full bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white "
                                >

                            <span class="float-left flex xsLight subpixel-antialiased">
                                <IconAdjustmentsAlt stroke-width="1.5"
                                class="mr-2 h-6 w-6"
                                alt="attributeIcon"/>{{$t('Select appointment properties')}}</span>
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
                                    class="absolute overflow-y-auto h-24 mt-2 w-[88%] origin-top-left divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                                    <div class="mx-auto w-full rounded-2xl bg-primary border-none mt-2">
                                        <div class="flex w-full mb-4">
                                            <input v-model="audience"
                                                   :disabled="!canEdit"
                                                   type="checkbox"
                                                   class="checkBoxOnDark"/>
                                            <img src="/Svgs/IconSvgs/icon_public.svg" class="h-6 w-6 mx-2"
                                                 alt="audienceIcon"/>

                                            <div :class="[audience ? 'xsWhiteBold' : 'xsLight', 'my-auto']">
                                                {{$t('With audience')}}
                                            </div>
                                        </div>
                                        <div class="flex w-full mb-2">
                                            <input v-model="isLoud"
                                                   :disabled="!canEdit"
                                                   type="checkbox"
                                                   class="checkBoxOnDark"/>
                                            <div :class="[isLoud ? 'xsWhiteBold' : 'xsLight', 'my-auto mx-2']">
                                                {{ $t('It gets loud')}}
                                            </div>
                                        </div>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                        <!--    Properties    -->
                        <div class="flex py-2">
                            <div v-if="audience">
                                <TagComponent icon="audience" :displayed-text="$t('With audience')" hideX="true"></TagComponent>
                            </div>
                            <div v-if="isLoud">
                                <TagComponent :displayed-text="$t('It gets loud')" hideX="true"></TagComponent>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="canEdit">
                    <div class="flex justify-center w-full py-4"
                         v-if="(isAdmin || selectedRoom?.everyone_can_book || roomAdminIds.includes(this.$page.props.user.id))">
                        <FormButton
                            :disabled="this.selectedRoom === null || !submit  || endDate > seriesEndDate || series && !seriesEndDate || (this.accept === false && this.optionAccept === false && adminComment === '')"
                            @click="updateOrCreateEvent()"
                            :text="this.event?.occupancy_option ? this.accept ? $t('Commitments') : this.optionAccept ? $t('Optional commitment') : this.adminComment !== '' ? $t('Send message') : $t('Save') : $t('Save')"
                        />
                    </div>
                    <div class="flex justify-center w-full py-4" v-else>
                        <FormButton
                            :disabled="this.selectedRoom === null || !submit || endDate > seriesEndDate || series && !seriesEndDate || !this.$can('request room occupancy')"
                            @click="updateOrCreateEvent(true)"
                            :text="$t('Request occupancy')"
                        />
                    </div>
                </div>
            </div>
    </BaseModal>

    <!-- Event löschen Modal -->
    <confirmation-component
        v-if="deleteComponentVisible"
        :confirm="$t('Delete')"
        :titel="$t('Delete event?')"
        :description="$t('Are you sure you want to put the event {0} in the trash? You can restore it within 30 days.', [this.event.title ?? ''])"
        @closed="afterConfirm"/>

    <ChangeAllSubmitModal
        v-if="showSeriesEdit"
        @closed="closeSeriesEditModal"
        @all="saveAllSeriesEvents"
        @single="singleSaveEvent"
    />
</template>


<script>
import IconLib from "@/Mixins/IconLib.vue";

const options = [
    {
        name: 'Option 1',
    },
    {
        name: 'Option 2',
    },
    {
        name: 'Option 3',
    },
    {
        name: 'Option 4',
    },
];

import JetDialogModal from "@/Jetstream/DialogModal.vue";
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
    ListboxLabel,
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
import {
    CheckIcon,
    ChevronUpIcon,
    TrashIcon
} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Input from "@/Jetstream/Input.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {useForm} from "@inertiajs/vue3";
import ChangeAllSubmitModal from "@/Layouts/Components/ChangeAllSubmitModal.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import dayjs from "dayjs";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'EventComponent',
    mixins: [
        Permissions, IconLib
    ],
    components: {
        BaseModal,
        FormButton,
        UserPopoverTooltip,
        NewUserToolTip,
        ChangeAllSubmitModal,
        ListboxLabel,
        SwitchLabel,
        Switch,
        SwitchGroup,
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
        TagComponent,
        InputComponent,
    },
    setup() {
        return {
            options,
        }
    },
    data() {
        return {
            submit: true,
            helpTextLength: '',
            startDate: null,
            startTime: null,
            endDate: null,
            endTime: null,
            isLoud: false,
            audience: false,
            showSeriesEdit: false,
            allSeriesEvents: false,
            frequencies: [
                {
                    id: 1,
                    name: 'Täglich'
                },
                {
                    id: 2,
                    name: 'Wöchentlich'
                },
                {
                    id: 3,
                    name: 'Alle 2 Wochen'
                },
                {
                    id: 4,
                    name: 'Monatlich'
                }
            ],
            series: false,
            seriesEndDate: null,
            selectedFrequency: {
                id: 2,
                name: 'Wöchentlich'
            },
            projectName: null,
            title: null,
            isOption: null,
            eventName: null,
            eventTypeName: null,
            selectedEventType: this.eventTypes[0],
            showProjectInfo: this.project ? true : false,
            allDayEvent: false,
            selectedProject: null,
            selectedRoom: null,
            error: null,
            creatingProject: false,
            projectSearchResults: [],
            description: null,
            canEdit: null,
            declinedRoomId: null,
            deleteComponentVisible: false,
            adminComment: '',
            optionString: null,
            accept: true,
            optionAccept: false,
            disableEventTypeSelector: false,
            selectedOption: options[0].name,
            roomCollisionArray: this.roomCollisions,
            answerRequestForm: useForm({
                accepted: false,
            }),
            collisionRoomForm: this.$inertia.form({
                _method: 'POST',
                start: null,
                end: null,
                roomId: null
            }),
            helpTextLengthRoom: ''
        }
    },
    props: [
        'showHints',
        'eventTypes',
        'rooms',
        'isAdmin',
        'event',
        'project',
        'wantedRoomId',
        'roomCollisions',
        'showComments',
        'first_project_calendar_tab_id'
    ],
    emits: ['closed'],
    watch: {
        selectedRoom: {
            deep: true,
            handler() {
                this.checkChanges()
            }
        },
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
        },
        event: {
            immediate: true,
            deep: true,
            handler: function () {
                this.openModal()
            },
        },
    },
    computed: {
        roomAdminIds() {
            let adminIds = [];
            this.selectedRoom?.room_admins?.forEach(admin => {
                adminIds.push(admin.id);
            })
            return adminIds;
        },
        isRoomAdmin() {
            return this.rooms.find(room => room.id === this.event?.roomId)?.admins.some(
                admin => admin.id === this.$page.props.user.id
            ) || false;
        },
        isCreator() {
            return this.event ? this.event.created_by?.id === this.$page.props.user.id : false
        },
    },
    methods: {
        convertDateFormat(dateString) {
            const parts = dateString.split('-');
            return parts[2] + "." + parts[1] + "." + parts[0];
        },
        checkButtonDisabled() {
            if (this.series) {
                if (this.seriesEndDate) {
                    const eventEndDate = new Date(this.endFull);
                    const endDateSeries = new Date(this.seriesEndDate);
                    return endDateSeries < eventEndDate;
                }
                return true;
            }
            return false;
        },
        openModal() {
            this.canEdit = (!this.event?.id) || this.isCreator || this.isRoomAdmin || this.hasAdminRole();
            if (!this.event) {
                if (this.project) {
                    this.selectedProject = {id: this.project.id, name: this.project.name};
                }
                return;
            }

            const start = dayjs(this.event.start);
            const end = dayjs(this.event.end);

            this.startDate = start?.format('YYYY-MM-DD');
            this.startTime = start?.format('HH:mm');
            this.endDate = end?.format('YYYY-MM-DD');
            this.endTime = end?.format('HH:mm');
            this.isLoud = this.event.isLoud;
            this.audience = this.event.audience;
            this.title = this.event.title;
            this.eventName = this.event.eventName;
            this.allDayEvent = this.event.allDay ? this.event.allDay : false;
            if (!this.event.eventTypeId) {
                this.selectedEventType = this.eventTypes[0];
            } else {
                this.selectedEventType = this.eventTypes.find(type => type.id === this.event.eventTypeId);
                if (this.selectedEventType.id === 1) {
                    this.disableEventTypeSelector = true;
                }
            }
            this.series = this.event.is_series;
            if (this.series) {
                this.seriesEndDate = this.event.series.end_date;
            }
            this.frequencies.forEach((frequency) => {
                if (frequency.id === this.event.series?.frequency_id) {
                    this.selectedFrequency = frequency;
                }
            })
            this.selectedProject = {id: this.event.projectId, name: this.event.projectName};
            if (this.selectedProject.id) {
                this.showProjectInfo = true;
            }
            if (this.wantedRoomId) {
                this.selectedRoom = this.rooms.find(room => room.id === this.wantedRoomId);
            } else if (this.event) {
                this.selectedRoom = this.rooms.find(type => type.id === this.event.roomId);
            }

            this.description = this.event.description;

            this.checkCollisions();
        },
        closeModal(bool) {
            this.startDate = null;
            this.startTime = null;
            this.endDate = null;
            this.endTime = null;
            this.selectedRoom = null;
            this.selectedProject = null;
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
        checkChanges() {
            if (this.selectedRoom) {
                if (this.selectedRoom.temporary) {
                    const startFull = this.formatDate(this.startDate, this.startTime);
                    const endFull = this.formatDate(this.endDate, this.endTime);
                    const start = dayjs(startFull);
                    const end = dayjs(endFull);

                    const roomStartTime = dayjs(this.selectedRoom.start_date);
                    const roomEndTime = dayjs(this.selectedRoom.end_date);
                    if (start < roomStartTime) {
                        this.helpTextLengthRoom = 'Der Terminstart liegt vor dem Beginn des temporären Raumes.';
                        this.submit = false;
                    } else if (end > roomEndTime) {
                        this.helpTextLengthRoom = 'Das Terminende liegt nach dem Ende des temporären Raumes';
                        this.submit = false;
                    } else {
                        this.helpTextLengthRoom = '';
                        this.submit = true;
                    }
                }
            }

            this.updateTimes(this.event);
        },
        /**
         * If the user selects a start, end, and room
         * call the server to get information if there are any collision
         *
         * @returns {Promise<void>}
         */
        async checkCollisions() {
            if (
                this.startTime && this.startDate && this.endTime && this.endDate ||
                this.allDayEvent && this.startDate && this.endDate
            ) {
                let startFull = this.formatDate(this.startDate, this.startTime ?? '00:00');
                let endFull = this.formatDate(this.endDate, this.endTime ?? '23:59');
                await axios.post('/collision/room', {
                    params: {
                        start: startFull,
                        end: endFull
                    }
                }).then(response => this.roomCollisionArray = response.data);
            }
        },
        checkYear(date) {
            return (parseInt(date.split('-')[0]) > 1900);
        },
        updateTimes() {
            if (this.startDate) {
                if (!this.endDate && this.checkYear(this.startDate)) {
                    this.endDate = this.startDate;
                }
                if (this.startTime) {
                    if (!this.endTime) {
                        if (this.startTime === '23:00') {
                            this.endTime = '23:59';
                        } else {
                            let startHours = this.startTime.slice(0, 2);
                            if (startHours === '23') {
                                this.endTime = '00:' + this.startTime.slice(3, 5);
                                let date = new Date();
                                this.endDate = new Date(
                                    date.setDate(new Date(this.endDate).getDate() + 1)
                                ).toISOString().slice(0, 10);
                            } else {
                                this.endTime = this.getNextHourString(this.startTime)
                            }
                        }
                    }
                }
            }

            this.validateStartBeforeEndTime();
            this.checkCollisions();
            this.checkEventTimeLength()
        },
        async validateStartBeforeEndTime() {
            this.error = null;
            if (this.startDate && this.endDate && this.startTime && this.endTime) {
                this.setCombinedTimeString(this.startDate, this.startTime, 'start');
                this.setCombinedTimeString(this.endDate, this.endTime, 'end');
                return await axios
                    .post('/events', {start: this.startFull, end: this.endFull}, {headers: {'X-Dry-Run': true}})
                    .catch(error => this.error = error.response.data.errors);
            }
        },
        checkEventTimeLength() {
            // check if event min 30min
            if (this.startFull && this.endFull) {
                const date = new Date(this.startFull);
                const minimumEnd = this.addMinutes(date, 30);
                if (minimumEnd <= new Date(this.endFull)) {
                    this.helpTextLength = '';
                } else {
                    this.helpTextLength = 'Der Termin darf nicht kürzer als 30 Minuten sein';
                }
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
                    this.startFull = new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)
                    ).toISOString().slice(0, 16);
                } else {
                    this.startFull = new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)
                    ).toISOString().slice(0, 16);
                }
            } else if (target === 'end') {
                if (offset === -60) {
                    this.endFull = new Date(
                        new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)
                    ).toISOString().slice(0, 16);
                } else {
                    this.endFull = new Date(
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
        /**
         * Creates an event and reloads all events
         *
         * @returns {Promise<*>}
         */
        async updateOrCreateEvent(isOption = false) {
            this.isOption = isOption;

            if (this.allDayEvent) {
                // Set startTime to "00:00" and endTime to "23:59" for all-day event
                this.startTime = "00:00";
                this.endTime = "23:59";
            }

            if (this.accept === false && this.optionAccept === false) {
                this.isOption = true;
            }

            if (!this.event?.id) {
                return await axios
                    .post('/events', this.eventData())
                    .then(() => this.closeModal(true))
                    .catch(error => this.error = error.response.data.errors);
            } else {
                if (this.eventData().is_series) {
                    return await axios
                        .put('/events/' + this.event?.id, this.eventData())
                        .then(() => {
                            this.closeModal(true);
                            this.closeSeriesEditModal();
                            this.showSeriesEdit = true;
                            this.$emit('closed', true);
                        })
                        .catch(error => this.error = error.response.data.errors);
                } else {
                    return await axios
                        .put('/events/' + this.event?.id, this.eventData())
                        .then(() => {
                            this.closeModal(true);
                            this.closeSeriesEditModal();
                        })
                        .catch(error => this.error = error.response.data.errors);
                }
            }
        },
        async singleSaveEvent() {
            return await axios
                .put('/events/' + this.event?.id, this.eventData())
                .then(() => {
                    this.closeModal(true);
                    this.closeSeriesEditModal()
                })
                .catch(error => this.error = error.response.data.errors);
        },
        async saveAllSeriesEvents() {
            this.allSeriesEvents = true;
            return await axios
                .put('/events/' + this.event?.id, this.eventData())
                .then(() => {
                    this.closeModal(true);
                    this.closeSeriesEditModal()
                })
                .catch(error => this.error = error.response.data.errors);
        },
        closeSeriesEditModal() {
            this.showSeriesEdit = false;
        },
        async afterConfirm(bool) {
            if (!bool) return this.deleteComponentVisible = false;

            return await axios
                .delete(`/events/${this.event.id}`)
                .then(() => this.closeModal(true));
        },
        async approveRequest(event) {
            this.answerRequestForm.accepted = true;
            this.answerRequestForm.put(route('events.accept', {event: event.id}));
            this.closeModal(true)
        },
        async declineRequest(event) {
            this.answerRequestForm.accepted = false;
            this.answerRequestForm.put(route('events.accept', {event: event}));
            this.closeModal(true)
        },
        chooseProject(project) {
            this.selectedProject = project;
            this.projectName = '';
        },
        toggleAccept(type) {
            if (type === 'option') {
                if (this.optionAccept) {
                    this.accept = false;
                    this.optionString = options[0].name;
                }
            } else {
                if (this.accept) {
                    this.optionAccept = false;
                    this.optionString = null;
                }
            }
        },
        eventData() {
            return {
                title: this.title,
                eventName: this.eventName,
                start: this.formatDate(this.startDate, this.startTime),
                end: this.formatDate(this.endDate, this.endTime),
                roomId: this.selectedRoom?.id,
                description: this.description,
                audience: this.audience ? this.audience : false,
                isLoud: this.isLoud ? this.isLoud : false,
                isOption: this.isOption,
                eventNameMandatory: this.selectedEventType?.individual_name,
                projectId: this.showProjectInfo ? this.selectedProject?.id : null,
                projectName: this.showProjectInfo ? this.creatingProject ? this.projectName : '' : '',
                eventTypeId: this.selectedEventType?.id,
                projectIdMandatory: this.selectedEventType?.project_mandatory && !this.creatingProject,
                creatingProject: this.showProjectInfo ? this.creatingProject : false,
                declinedRoomId: this.declinedRoomId,
                is_series: this.series ? this.series : false,
                seriesFrequency: this.selectedFrequency.id,
                seriesEndDate: this.seriesEndDate,
                allSeriesEvents: this.allSeriesEvents,
                adminComment: this.adminComment,
                optionString: this.optionAccept ? this.optionString : null,
                accept: this.accept,
                optionAccept: this.optionAccept,
                allDay: this.allDayEvent,
            };
        },
    },
}
</script>
