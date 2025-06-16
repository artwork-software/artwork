<template>
    <BaseModal @closed="closeModal" v-if="true">
        <div class="mx-4">
            <!--   Heading   -->
            <div v-if="this.isRoomAdmin || this.hasAdminRole()">
                <ModalHeader
                    :title="this.event?.id ? this.event?.occupancy_option ? $t('Change & confirm occupancy') : this.event?.isPlanning ? $t('Planned Event') : $t('Event') : isPlanning ? $t('Create planned Event') : $t('New room allocation')"
                    :description="$t('Please make sure that you allow for preparation and follow-up time.')"
                />
                <div v-if="event?.id" class="flex items-center mb-4">
                    {{ $t('Created by') }}
                    <div>
                        <UserPopoverTooltip :user="this.event?.created_by"
                                            :id="this.event?.created_by?.id ?? 'deletedUserTooltip'"
                                            height="7"
                                            width="7"
                                            class="ml-2"/>
                    </div>
                </div>
            </div>

            <ModalHeader v-else
                :title="$t('Event')"
            />
            <!--    Form    -->
            <!--    Type and Title    -->
            <div class="grid gird-cols-1 md:grid-cols-2 gap-x-4 mb-4" v-if="canEdit">
                <div class="h-full">
                    <Listbox as="div" class="" v-model="selectedEventType" v-if="canEdit" id="eventType">
                        <!--<ListboxLabel class="xsLight mb-0">{{ $t('Event type') }}</ListboxLabel>-->
                        <ListboxButton class="menu-button-no-padding relative">
                            <div class="truncate">
                                <div class="top-2 left-4 absolute text-gray-500 text-xs">
                                    {{ $t('Event type') }}
                                </div>
                                <div class="pt-6 pb-2 flex items-center gap-x-2">
                                    <div>
                                        <div class="block w-4 h-4 rounded-full" :style="{'backgroundColor' : selectedEventType?.hex_code }"/>
                                    </div>
                                    <div class="truncate">
                                        {{ selectedEventType?.name }}
                                    </div>
                                </div>
                            </div>
                            <IconChevronDown class="h-5 w-5 text-primary" aria-hidden="true"/>
                        </ListboxButton>

                        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <ListboxOptions class="absolute w-72 z-10 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                <ListboxOption as="template" class="max-h-8"
                                               v-for="eventType in sortedEventTypes"
                                               :key="eventType.name"
                                               :value="eventType"
                                               v-slot="{ active, selected }">
                                    <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                        <div class="flex">
                                            <div>
                                                <div class="block w-3 h-3 rounded-full"
                                                     :style="{'backgroundColor' : eventType?.hex_code }"/>
                                            </div>
                                            <span
                                                :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                {{ eventType.name }}
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
                    <div v-if="canEdit">
                        <BaseInput
                            v-model="this.eventName"
                            id="eventTitle"
                            :label="selectedEventType?.individual_name ? $t('Event name') + '*' : $t('Event name')"
                            :disabled="!canEdit"
                        />

                        <p v-if="selectedEventType?.individual_name"
                           class="text-xs text-error"
                            v-html="Array.isArray(error?.eventName) ? error.eventName.join('.<br> ') : error?.eventName">
                        </p>
                    </div>
                </div>
            </div>
            <div class="grid gird-cols-1 md:grid-cols-2 gap-x-4 mb-4" v-if="usePage().props.event_status_module">
                <div class="h-full">
                    <Listbox as="div" class="" v-model="selectedEventStatus" id="eventType" v-if="canEdit">
                        <ListboxButton class="menu-button-no-padding relative">
                            <div class="truncate">
                                <div class="top-2 left-4 absolute text-gray-500 text-xs">
                                    {{ $t('Event Status') }}
                                </div>
                                <div class="pt-6 pb-2 flex items-center gap-x-2">
                                    <div>
                                        <div class="block w-4 h-4 rounded-full" :style="{'backgroundColor' : selectedEventStatus?.color }"/>
                                    </div>
                                    <div class="truncate">
                                        {{ selectedEventStatus?.name }}
                                    </div>
                                </div>
                            </div>
                            <IconChevronDown class="h-5 w-5 text-primary" aria-hidden="true"/>
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

            <!-- Read-only mode (canEdit=false) - Redesigned layout -->
            <div v-if="!canEdit">
                <!-- Event header with type and title -->
                <div class="flex items-center mb-6">
                    <div class="flex-shrink-0 mr-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center" :style="{'backgroundColor' : selectedEventType?.hex_code }"></div>
                    </div>
                    <div>
                        <h2 class="text-xl font-lexend font-semibold">{{ this.eventName }}</h2>
                        <div class="flex items-center mt-1">
                            <span class="text-sm font-medium text-gray-600">{{ selectedEventType?.name }}</span>
                            <span v-if="selectedEventStatus" class="flex items-center ml-4">
                                <div class="w-3 h-3 rounded-full mr-1" :style="{'backgroundColor' : selectedEventStatus?.color }"></div>
                                <span class="text-sm font-medium text-gray-600">{{ selectedEventStatus?.name }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Event details section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left column -->
                    <div>
                        <!-- Date and time information -->
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">{{ $t('Date & Time') }}</h3>
                            <div class="flex items-center">
                                <p class="sDark">
                                    <span v-if="this.startDate === this.endDate">
                                        {{ this.startDate.toString().substring(10, 8) }}.{{
                                            this.startDate.toString().substring(7, 5)
                                        }}.{{ this.startDate.toString().substring(4, 0) }},
                                        {{ this.startTime }} - {{ this.endTime }}
                                    </span>
                                    <span v-else>
                                        {{ this.startDate.toString().substring(10, 8) }}.{{
                                            this.startDate.toString().substring(7, 5)
                                        }}.{{ this.startDate.toString().substring(4, 0) }},
                                        {{ this.startTime }} -
                                        {{ this.endDate.toString().substring(10, 8) }}.{{
                                            this.endDate.toString().substring(7, 5)
                                        }}.{{ this.endDate.toString().substring(4, 0) }},
                                        {{ this.endTime }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Room information -->
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">{{ $t('Room') }}</h3>
                            <div class="flex items-center sDark">
                                <p>{{ this.selectedRoom?.name }}</p>
                            </div>
                        </div>

                        <!-- Project information (if available) -->
                        <div v-if="this.selectedProject?.id" class="mb-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">{{ $t('Project') }}</h3>
                            <div class="flex items-center sDark">
                                <a v-if="canAccessProject()" :href="route('projects.tab', {project: selectedProject.id, projectTab: this.first_project_calendar_tab_id})">
                                    {{ this.selectedProject?.name }}
                                </a>
                                <span v-else class="sDark">
                                    {{ this.selectedProject?.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right column -->
                    <div>
                        <!-- Created by information -->
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">{{ $t('Created by') }}</h3>
                            <div class="flex items-center">
                                <UserPopoverTooltip :user="this.event.created_by"
                                                   :id="this.event.created_by?.id ?? 'deletedUserTooltip'"
                                                   height="7"
                                                   width="7" class="mr-2"/>
                                <p class="sDark">{{ this.event.created_by?.full_name }}</p>
                            </div>
                        </div>

                        <!-- Series information (if applicable) -->
                        <div v-if="event?.is_series" class="mb-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">{{ $t('Repeat Event') }}</h3>
                            <div class="flex items-center">
                                <p class="sDark">
                                    {{ $t('Cycle: {0} to {1}', [selectedFrequency.name, convertDateFormat(seriesEndDate)]) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description section (if available) -->
                <div v-if="this.description" class="mt-6 border-t border-gray-200 pt-4">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">{{ $t('Description') }}</h3>
                    <p class="text-gray-800 whitespace-pre-line">{{ this.description }}</p>
                </div>

                <!-- Event properties section (if available) -->
                <div v-if="event_properties?.filter((eventProperty) => eventProperty.checked)?.length > 0" class="mt-6 border-t border-gray-200 pt-4">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">{{ $t('Properties') }}</h3>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <div v-for="(eventProperty, index) in event_properties.filter((eventProperty) => eventProperty.checked)"
                             class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                            <component :is="eventProperty.icon" class="w-4 h-4 mr-1" />
                            {{ eventProperty.name }}
                        </div>
                    </div>
                </div>

                <!-- Comments section (if available) -->
                <div v-if="showComments && this.event.comments && this.event.comments.length > 0" class="mt-6 border-t border-gray-200 pt-4">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">{{ $t('Comments') }}</h3>
                    <div class="space-y-4">
                        <div v-for="comment in this.event.comments" class="bg-gray-50 rounded-lg p-3">
                            <div class="flex items-center mb-2">
                                <NewUserToolTip :id="comment.id" :user="comment.user" :height="6" :width="6" class="mr-2"></NewUserToolTip>
                                <span class="text-xs text-gray-500">{{ comment.created_at }}</span>
                            </div>
                            <p class="text-gray-800">{{ comment.comment }}</p>
                        </div>
                    </div>
                </div>

                <!-- Rejections section (for planning events) -->
                <div v-if="event && event?.isPlanning && event?.verifications && event?.verifications.some(v => v.rejection_reason !== null)" class="mt-6 border-t border-gray-200 pt-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-500">{{ $t('Rejections') }}</h3>
                        <button @click="showRejections = !showRejections" class="text-gray-400 hover:text-gray-600">
                            <IconChevronDown class="h-5 w-5" :class="showRejections ? 'rotate-180': ''" />
                        </button>
                    </div>
                    <div v-if="showRejections" class="space-y-3">
                        <div v-for="verification in event?.verifications" :key="verification.id" v-if="verification.rejection_reason !== null" class="bg-gray-50 rounded-lg p-3">
                            <div class="flex items-start">
                                <UserPopoverTooltip :user="verification?.verifier" class="mr-3 flex-shrink-0" />
                                <div class="flex-1">
                                    <div class="flex items-center justify-between w-full mb-1">
                                        <h4 class="text-sm font-medium text-gray-800">{{ verification?.verifier?.full_name }}</h4>
                                        <p class="text-xs text-gray-500">{{ verification?.created_at }}</p>
                                    </div>
                                    <p class="text-sm text-gray-700">{{ verification?.rejection_reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
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
                                {{ $t('Full day') }}
                            </span>
                    </SwitchLabel>
                </SwitchGroup>
            </div>
            <div v-if="canEdit" class="grid grid-cols-1 md:grid-cols-2 gap-x-4 pt-3">
                <div>
                    <div class="w-full flex">
                        <BaseInput
                            type="date"
                            id="startDate"
                            @change="checkChanges()"
                            v-model="startDate"
                            label="Start"
                        />

                        <BaseInput
                            type="time"
                            v-model="startTime"
                            id="changeStartTime"
                            v-if="!allDayEvent"
                            @change="checkChanges()"
                            label="Startzeit"
                            :disabled="!canEdit"
                            required
                        />
                    </div>
                    <p class="text-xs text-error"
                       v-html="Array.isArray(error?.start) ? error.start.join('.<br> ') : error?.start"/>
                </div>
                <div>
                    <div class="w-full flex">
                        <BaseInput
                            type="date"
                            v-model="endDate"
                            id="endDate"
                            @change="checkChanges()"
                            label="End"
                        />
                        <BaseInput
                            type="time"
                            v-model="endTime"
                            v-if="!allDayEvent"
                            id="changeEndTime"
                            @change="checkChanges()"
                            label="Endzeit"
                            :disabled="!canEdit"
                            required
                        />
                    </div>
                    <p class="text-xs text-error"
                       v-html="Array.isArray(error?.end) ? error.end.join('.<br> ') : error?.end"/>
                </div>

            </div>
            <div>
                <div class="text-red-500 text-xs" v-show="helpTextLengthRoom.length > 0">
                    {{helpTextLengthRoom }}
                </div>
            </div>
            <!-- Serien Termin -->
            <div v-if="!event && canEdit">
                <SwitchGroup as="div" class="flex items-center mt-3 mb-1">
                    <Switch v-model="series"
                            :class="[series ? 'bg-indigo-600 cursor-pointer' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-1 focus:ring-indigo-600 focus:ring-offset-2']">
                            <span aria-hidden="true"
                                  :class="[series ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                    </Switch>
                    <SwitchLabel as="span" class="ml-3 text-sm">
                            <span :class="[series ? 'xsDark' : 'xsLight', 'text-sm']">
                                {{ $t('Repeat event') }}
                            </span>
                    </SwitchLabel>
                </SwitchGroup>
                <div v-show="series">
                    <div class="grid grid-cols-2 gap-2 mt-4">
                        <Listbox :disabled="event?.is_series" as="div" class="relative" v-model="selectedFrequency">
                            <ListboxButton class="menu-button-no-padding relative">
                                <div class="truncate">
                                    <div class="top-2 left-4 absolute text-gray-500 text-xs">
                                        {{ $t('Frequency') }}
                                    </div>
                                    <div class="pt-6 pb-2 flex items-center gap-x-2">
                                        <div class="truncate">
                                            {{ selectedFrequency.name }}
                                        </div>
                                    </div>
                                </div>
                                <IconChevronDown class="h-5 w-5 text-primary" aria-hidden="true"/>
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
                                                        <IconCheck stroke-width="1.5" class="h-5 w-5"
                                                                   aria-hidden="true"/>
                                                    </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </Listbox>
                        <div>
                            <div class="w-full flex">
                                <BaseInput
                                    type="date"
                                    :disabled="event?.is_series"
                                    v-model="seriesEndDate"
                                    id="endDate"
                                    :label="$t('End date Repeat event')"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else-if="event?.is_series && canEdit" class="xsLight mt-2">{{ $t('Event is part of a repeat event') }}</div>
            <div v-if="event?.is_series && canEdit" class="xsLight mb-2">
                {{ $t('Cycle: {0} to {1}', [selectedFrequency.name, convertDateFormat(seriesEndDate)]) }}
            </div>
            <!--    Room    -->
            <div class="pt-3 mb-4" v-if="canEdit">
                <Listbox as="div" class="relative" v-model="selectedRoom" id="room" v-if="canEdit">
                    <ListboxButton class="menu-button-no-padding relative">
                        <div class="truncate">
                            <div class="top-2 left-4 absolute text-gray-500 text-xs">
                                {{ $t('Room') }}*
                            </div>
                            <div class="pt-6 pb-2 flex items-center gap-x-2">
                                <div v-if="selectedRoom" class="truncate">
                                    {{ selectedRoom?.name }}
                                </div>
                                <div v-else>
                                    {{ $t('Select room') }}
                                </div>
                            </div>
                        </div>
                        <IconChevronDown class="h-5 w-5 text-primary" aria-hidden="true"/>
                    </ListboxButton>
                    <ListboxOptions class="w-full rounded-lg bg-primary max-h-32 overflow-y-auto text-sm absolute z-30">
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
                            <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                       aria-hidden="true"/>
                        </ListboxOption>
                    </ListboxOptions>
                </Listbox>
                <p class="text-xs text-error"
                   v-html=" Array.isArray(error?.roomId) ? error.roomId.join('.<br> ') : error?.roomId"/>
            </div>

            <!--Gray Background Area -->
            <div class="bg-lightBackgroundGray my-4 -mx-8 pt-1 pb-4" v-if="canEdit">
                <div class="px-10">
                    <!--    Project    -->
                    <div v-if="canEdit">
                        <!-- Checkbox to decide if i show this block or not -->
                        <div class="my-3">
                            <input type="checkbox" v-model="showProjectInfo"
                                   class="input-checklist">
                            <span
                                :class="[showProjectInfo ? 'xsDark' : 'xsLight', 'text-sm ml-2']">{{
                                    $t('Assign event to a project')
                                }}</span>
                        </div>
                        <div v-if="showProjectInfo">
                            <div class="xsLight flex" v-if="!this.creatingProject">
                                {{ $t('Currently assigned to:') }}
                                <a v-if="this.selectedProject?.id && canAccessProject()"
                                   :href="route('projects.tab', {project: selectedProject.id, projectTab: this.first_project_calendar_tab_id})"
                                   class="ml-3 flex xsDark">
                                    {{ this.selectedProject?.name }}
                                </a>
                                <span v-else-if="this.selectedProject?.id" class="ml-3 flex xsDark">
                                    {{ this.selectedProject?.name }}
                                </span>
                                <div v-else class="xsDark ml-2">
                                    {{ this.selectedProject?.name ?? 'Keinem Projekt' }}
                                </div>
                                <div v-if="this.selectedProject?.id && this.canEdit"
                                     class="flex items-center my-auto">
                                    <button type="button"
                                            @click="selectedProject = null">
                                        <IconCircleX stroke-width="1.5"
                                                     class="pl-2 h-6 w-6 hover:text-error text-primary"/>
                                    </button>
                                </div>
                            </div>
                            <div class="xsLight" v-if="this.creatingProject">
                                {{ $t('The project is created when it is saved.') }}
                            </div>

                            <div class="my-2" v-if="this.canEdit">
                                <div class="flex pb-2">
                                    <SwitchGroup as="div" class="flex items-center">
                                        <SwitchLabel as="span" class="mr-3 text-sm" :class="creatingProject ? 'font-bold' : 'text-gray-400'">
                                            {{ $t('New project') }}
                                        </SwitchLabel>
                                        <Switch v-model="creatingProject" :class="[creatingProject ? 'bg-artwork-buttons-create' : 'bg-artwork-buttons-create', 'relative inline-flex h-3 w-6 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none']">
                                            <span aria-hidden="true" :class="[!creatingProject  ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                                        </Switch>
                                        <SwitchLabel as="span" class="ml-3 text-sm" :class="!creatingProject? 'font-bold' : 'text-gray-400'">
                                            {{ $t('Existing project') }}
                                        </SwitchLabel>
                                    </SwitchGroup>
                                </div>
                                <div class="relative w-full">
                                    <BaseInput
                                        id="projectName"
                                        :label="creatingProject ? $t('New project name') : $t('Search project')"
                                        v-model="projectName"
                                    />
                                    <div v-if="projectSearchResults.length > 0 && !creatingProject"
                                         class="absolute bg-primary truncate sm:text-sm w-full z-10">
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
                    </div>
                    <p class="text-xs text-error"
                       v-html="Array.isArray(error?.projectId) ? error.projectId.join('.<br> ') : error?.projectId"/>
                    <p class="text-xs text-error"
                       v-html="Array.isArray(error?.projectName) ? error.projectName.join('.<br> ') : error?.projectName"/>
                </div>
            </div>

            <div>
                <!--    Description    -->
                <div class="py-2">
                    <BaseTextarea
                        v-if="canEdit"
                        :label="$t('What do I need to bear in mind for the event?')"
                        id="description"
                        :disabled="!canEdit"
                        v-model="description"
                        rows="4"
                    />
                    <div v-else-if="this.description" class="mt-4 xsDark">
                        {{ this.description }}
                    </div>
                    <div v-if="this.event?.occupancy_option && canEdit">
                        <BaseTextarea
                            :label="$t('Comment on the booking (inquirer will be notified)')"
                            id="adminComment"
                            :disabled="!canEdit"
                            v-model="adminComment"
                            rows="4"
                        />
                    </div>
                    <div v-if="this.event?.occupancy_option && (isRoomAdmin || this.hasAdminRole())" class="flex py-2 items-center">
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
                                {{ $t('Commitments') }}
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
                                {{ $t('Optional commitment') }}
                        </span>
                        </div>
                    </div>
                    <div class="py-2 w-full relative" v-if="optionAccept">
                        <Listbox as="div" v-model="optionString" id="room">
                            <ListboxButton class="menu-button">
                                <div class="flex-grow flex text-left xsDark">
                                    {{ optionString }}
                                </div>
                                <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary"
                                                 aria-hidden="true"/>
                            </ListboxButton>
                            <ListboxOptions class="w-full bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                <ListboxOption v-for="option in options"
                                               class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                               :key="option.name"
                                               :value="option.name"
                                               v-slot="{ active, selected }">
                                    <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                        {{ option.name }}
                                    </div>
                                    <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success"
                                               aria-hidden="true"/>
                                </ListboxOption>
                            </ListboxOptions>
                        </Listbox>
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
                <Menu as="div" class="inline-block text-left w-full" v-if="canEdit && event_properties?.length > 0">
                    <div>
                        <MenuButton class="menu-button">
                            <span class="flex items-center gap-x-2">
                                <IconAdjustmentsAlt stroke-width="1.5" class="h-6 w-6" alt="attributeIcon"/>
                                {{ $t('Select appointment properties') }}
                            </span>
                            <IconChevronDown stroke-width="1.5" class="ml-2 -mr-1 h-5 w-5 float-right" aria-hidden="true"/>
                        </MenuButton>
                    </div>
                    <transition
                        enter-active-class="transition duration-50 ease-out"
                        enter-from-class="transform scale-100 opacity-100"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0">
                        <MenuItems class="absolute overflow-y-auto max-h-44 w-[88%] origin-top-left divide-y divide-gray-200 rounded-lg bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                            <div class="w-full rounded-2xl bg-primary border-none mt-2 flex flex-col gap-y-2">
                                <div v-for="eventProperty in event_properties" class="flex flex-row gap-x-1 w-full items-center">
                                    <input v-model="eventProperty.checked"
                                           :disabled="!canEdit"
                                           type="checkbox"
                                           class="input-checklist-dark"/>
                                    <component :is="eventProperty.icon" class="w-5 h-5 text-white" stroke-width="2"/>
                                    <div :class="[eventProperty.checked ? 'xsWhiteBold' : 'xsLight', 'my-auto']">
                                        {{ eventProperty.name }}
                                    </div>
                                </div>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
                <!--    Properties    -->
                <div v-if="event_properties?.filter((eventProperty) => eventProperty.checked)?.length > 0" class="mt-3 mb-4 flex items-center flex-wrap gap-2">
                    <div v-for="(eventProperty, index) in event_properties.filter((eventProperty) => eventProperty.checked)" class="group block shrink-0 bg-gray-50 w-fit pr-3 rounded-full border border-gray-300">
                        <div class="flex items-center">
                            <div class="rounded-full p-1 size-8 flex items-center justify-center">
                                <component :is="eventProperty.icon" class="inline-block size-4"  />
                            </div>
                            <div class="mx-1">
                                <p class="xxsDark group-hover:text-gray-900">{{ eventProperty.name}}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="event && event?.isPlanning" class="mt-4">
                    <div class="mb-4 flex items-center justify-between cursor-pointer" @click="showRejections = !showRejections">
                        <h3 class="font-lexend">{{ $t('Rejections')}}</h3>
                        <component is="IconChevronDown" stroke-width="1.5" class="h-5 w-5 text-primary" :class="showRejections ? 'rotate-180': ''" aria-hidden="true"/>
                    </div>

                    <div class="space-y-3 group" v-if="showRejections">
                        <div v-for="(verification, index) in event?.verifications" :key="verification.id">
                            <div class="flex w-full" v-if="verification.rejection_reason !== null">
                                <div class="mr-4 shrink-0">
                                    <UserPopoverTooltip :user="verification?.verifier" />
                                </div>
                                <div class="w-full">
                                    <div class="flex items-center justify-between w-full">
                                        <h4 class="text-sm font-lexend">{{ verification?.verifier?.full_name }}</h4>
                                        <p class="text-[9px] text-gray-500">
                                            {{ verification?.created_at }}
                                        </p>
                                    </div>
                                    <p class="mt-0.5 font-lexend text-xs text-gray-700">{{ verification?.rejection_reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action buttons for edit mode -->
            <div v-if="canEdit">
                <div class="flex justify-center w-full py-4" v-if="hasAdminRole() || selectedRoom?.everyone_can_book || roomAdminIds.includes(this.$page.props.auth.user.id) || $can('create events without request')">
                    <FormButton
                        :disabled="!this.selectedRoom || this.selectedRoom === '' || !submit  || endDate > seriesEndDate || series && !seriesEndDate || (this.accept === false && this.optionAccept === false && adminComment === '')"
                        @click="updateOrCreateEvent()"
                        :text="this.event?.occupancy_option ? this.accept ? $t('Commitments') : this.optionAccept ? $t('Optional commitment') : this.adminComment !== '' ? $t('Send message') : $t('Save') : $t('Save')"
                    />
                </div>
                <div class="flex justify-center w-full py-4" v-else>
                    <FormButton
                        :disabled="!this.selectedRoom || this.selectedRoom === '' || !submit || endDate > seriesEndDate || series && !seriesEndDate || !this.$can('request room occupancy')"
                        @click="updateOrCreateEvent(true)"
                        :text="$t('Request occupancy')"
                    />
                </div>
            </div>
            <!-- Close button for read-only mode -->
            <div v-else class="flex justify-center w-full py-4">
                <FormButton
                    @click="closeModal"
                    :text="$t('Close')"
                />
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
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
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
import {CheckIcon, ChevronUpIcon, TrashIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Input from "@/Jetstream/Input.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import ChangeAllSubmitModal from "@/Layouts/Components/ChangeAllSubmitModal.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import dayjs from "dayjs";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {useEvent} from "@/Composeables/Event.js";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import DateInputComponent from "@/Components/Inputs/DateInputComponent.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import {inject} from "vue";
import Button from "@/Jetstream/Button.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

const {getDaysOfEvent} = useEvent();

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


export default {
    name: 'EventComponent',
    mixins: [
        Permissions, IconLib
    ],
    components: {
        BaseTextarea,
        BaseInput,
        Button,
        TextareaComponent,
        TimeInputComponent,
        DateInputComponent,
        TextInputComponent,
        ModalHeader,
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
        const event_properties = inject('event_properties');

        return {
            options,
            event_properties
        }
    },
    data() {
        return {
            submit: true,
            startDate: null,
            startTime: null,
            endDate: null,
            endTime: null,
            oldStartDate: null,
            oldStartTime: null,
            oldEndDate: null,
            oldEndTime: null,
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
            eventStatus: null,
            eventTypeName: null,
            selectedEventType: this.eventTypes[0],
            selectedEventStatus: this.eventStatuses?.find(status => status.default),
            showProjectInfo: this.project ? true : this.calendarProjectPeriod && this.$page.props.auth.user.calendar_settings.time_period_project_id ? true :false,
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
            helpTextLengthRoom: '',
            initialRoomId: null,
            showRejections: false
            //event_properties: event_properties
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
        'first_project_calendar_tab_id',
        'usedInBulkComponent',
        'requiresAxiosRequests',
        'calendarProjectPeriod',
        'eventStatuses',
        'isPlanning',
        'wantedDate'
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
                } else {
                    axios.get('/projects/search', {params: {query: this.projectName}})
                        .then(response => this.projectSearchResults = response.data)
                }
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
                admin => admin.id === this.$page.props.auth.user.id
            ) || false;
        },
        isCreator() {
            return this.event ? this.event.created_by?.id === this.$page.props.auth.user.id : false
        },
        sortedEventTypes() {
            return this.eventTypes.sort((a, b) => a.name.localeCompare(b.name));
        },
    },
    mounted() {
        if (this.wantedDate){
            // set StartDate to wantedDate with time 00:00
            this.startDate = this.wantedDate;
            this.startTime = '09:00';

            // set EndDate to wantedDate with time 23:59
            this.endDate = this.wantedDate;
            this.endTime = '10:00';
        }

        if (this.wantedRoomId) {
            this.selectedRoom = this.rooms.find(room => room.id === this.wantedRoomId);
        } else if (this.event) {
            this.selectedRoom = this.rooms.find(type => type.id === this.event.roomId);
        }
    },
    methods: {
        usePage,
        canAccessProject() {
            // Check if user has read or write rights for all projects
            if (this.$can('view projects') || this.$can('write projects')) {
                return true;
            }

            // Check if user is the creator of the project
            if (this.selectedProject?.creator_id === this.$page.props.auth.user.id) {
                return true;
            }

            // Check if user is a team member of the project
            if (this.selectedProject?.team_members?.some(member => member.id === this.$page.props.auth.user.id)) {
                return true;
            }

            return false;
        },
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
                return;
            }

            if (this.event?.project) {
                //console.log(this.event.project);
                this.selectedProject = {id: this.event.project.id, name: this.event.project.name};
            } else if (this.calendarProjectPeriod && this.$page.props.auth.user.calendar_settings.time_period_project_id){
                this.selectedProject = {id: this.$page.props.auth.user.calendar_settings.time_period_project_id, name: this.$page.props.projectNameOfCalendarProject};
            }

            const start = dayjs(this.event.start);
            const end = dayjs(this.event.end);

            this.startDate = start.format('YYYY-MM-DD');
            this.startTime = start.format('HH:mm');
            this.endDate = end.format('YYYY-MM-DD');
            this.endTime = end.format('HH:mm');
            this.oldStartDate = this.startDate;
            this.oldStartTime = this.startTime;
            this.oldEndDate = this.endDate;
            this.oldEndTime = this.endTime;
            this.title = this.event.title;
            this.eventName = this.event.eventName;
            this.selectedEventStatus = this.eventStatuses.find(status => status.id === this.event?.eventStatus?.id ?? this.event?.eventStatusId);
            this.allDayEvent = this.event.allDay ? this.event.allDay : false;
            if (!this.event.eventType?.id) {
                this.selectedEventType = this.eventTypes[0];
            } else {
                this.selectedEventType = this.eventTypes.find(type => type.id === this.event.eventType.id);
            }

            if (this.event?.eventTypeId) {
                this.selectedEventType = this.eventTypes.find(type => type.id === this.event.eventTypeId);
            }
            this.series = this.event.is_series;
            if (this.series) {
                this.seriesEndDate = this.event.series.end_date;
            }
            this.frequencies.forEach((frequency) => {
                if (frequency.id === this.event.series?.frequency_id) {
                    this.selectedFrequency = frequency;
                }
            });

            if (this.selectedProject?.id) {
                this.showProjectInfo = true;
            }
            if (this.wantedRoomId) {
                this.selectedRoom = this.rooms.find(room => room.id === this.wantedRoomId);
            } else if (this.event) {
                this.selectedRoom = this.rooms.find(type => type.id === this.event.roomId);
            }

            if (this.wantedDate){
                // set StartDate to wantedDate with time 00:00
                this.startDate = this.wantedDate;
                this.startTime = '09:00';

                // set EndDate to wantedDate with time 23:59
                this.endDate = this.wantedDate;
                this.endTime = '10:00';
            }


            this.initialRoomId = this.selectedRoom?.id;
            this.selectedRoom = this.rooms.find(room => room.id === this.event.roomId);
            this.description = this.event.description;

            console.log(this.event);

            this.event_properties?.forEach((event_property) => {
                event_property.checked = this.event?.eventProperties.some(
                    (event_event_properties) => event_event_properties.id === event_property.id
                );
            });

            this.checkCollisions();
        },
        closeModal(closedOnPurpose) {
            if (closedOnPurpose) {
                this.$emit(
                    'closed',
                    closedOnPurpose,
                    //get room ids array to update
                    Array.from(
                        //make ids unique by new Set
                        new Set(
                            [this.initialRoomId, this.selectedRoom?.id].filter(
                                (value) => value !== null && typeof value !== 'undefined'
                            )
                        )
                    ),
                    getDaysOfEvent(
                        this.startDate,
                        this.series === true ? this.seriesEndDate : this.endDate
                    ), getDaysOfEvent(
                        this.oldStartDate,
                        this.oldEndDate
                    )
                );
            } else {
                this.$emit('closed', closedOnPurpose);
            }

            this.startDate = null;
            this.startTime = null;
            this.endDate = null;
            this.endTime = null;
            this.selectedRoom = null;
            this.selectedProject = null;
            this.initialRoomId = null;
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

            if (
                !this.requiresAxiosRequests && (
                    this.usedInBulkComponent ||
                    (
                        this.$page.props.auth.user.calendar_settings.time_period_project_id === this.selectedProject?.id &&
                        this.calendarProjectPeriod
                    )
                )
            ) {
                if (!this.event?.id) {
                    router.post(
                        route('events.store'),
                        this.eventData(),
                        {
                            preserveScroll: true,
                            preserveState: (page) => {
                                //if component exists in response the validation didn't fail and there is no need
                                //to preserveState, if errors are provided from backend preserveState will be set to
                                //false causing modal to stay opened
                                return typeof(page?.component) === 'undefined';
                            },
                            onSuccess: () => {
                                this.closeModal(true);
                            },
                            onError: (response) => {
                                this.error = response;
                            },
                        }
                    );
                } else {
                    router.put(
                        route('events.update', {event: this.event.id}),
                        this.eventData(),
                        {
                            preserveScroll: true,
                            preserveState: (page) => {
                                //if component exists in response the validation didn't fail and there is no need
                                //to preserveState, if errors are provided from backend preserveState will be set to
                                //false causing modal to stay opened
                                return typeof(page?.component) === 'undefined';
                            },
                            onSuccess: () => {
                                this.closeModal(true);
                            },
                            onError: (response) => {
                                this.error = response;
                            },
                        }
                    );
                }
            } else {
                if (!this.event?.id) {
                    return await axios
                        .post('/events', this.eventData())
                        .then(() => this.closeModal(true))
                        .catch(error => this.error = error.response.data.errors);
                } else {
                    return await axios
                        .put('/events/' + this.event?.id, this.eventData())
                        .then(() => {
                            this.closeModal(true);
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
                eventStatusId: this.selectedEventStatus?.id,
                start: this.formatDate(this.startDate, this.startTime),
                end: this.formatDate(this.endDate, this.endTime),
                roomId: this.selectedRoom?.id,
                description: this.description,
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
                usedInBulkComponent: this.usedInBulkComponent,
                showProjectPeriodInCalendar: this.calendarProjectPeriod,
                event_properties: this.event_properties ?this.event_properties
                    .filter((eventProperty) => eventProperty.checked)
                    .map((eventProperty) => eventProperty.id) : [],
                isPlanning: this.event ? this.event.isPlanning : this.isPlanning,
            };
        },
    },
}
</script>
