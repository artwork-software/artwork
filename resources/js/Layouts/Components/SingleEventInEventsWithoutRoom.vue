
<template>
    <div class="flex w-full border border-gray-200 rounded-lg bg-white shadow-sm">
        <button v-if="computedEventsWithoutRoom.length > 1" class="bg-artwork-buttons-create w-6 rounded-l-lg"
                @click="event.opened = !event.opened">
            <IconChevronUp  stroke-width="1.5" v-if="event.opened"
                            class="h-6 w-6 text-white my-auto"></IconChevronUp>
            <IconChevronDown stroke-width="1.5" v-else
                             class="h-6 w-6 text-white my-auto"></IconChevronDown>
        </button>

        <!-- EDIT MODE -->
        <div class="mx-4 mt-4 w-full space-y-6" v-if="(event.opened || computedEventsWithoutRoom.length === 1) && (event?.user_id === page.props.auth.user.id || isAdmin)">

            <!-- Top Meta + Previously Declined Room -->
            <div class="flex flex-col gap-2">
                <div class="flex w-full justify-between items-center">
                    <div v-if="event.declinedRoomId" class="flex items-center gap-2 text-[12px] text-red-600">
                        <span>{{ $t('Previously declined from') }}:</span>
                        <span class="font-medium line-through">{{ rooms.find(room => room.id === event.declinedRoomId)?.name }}</span>
                    </div>
                    <div v-if="event?.user_id === page.props.auth.user.id || isAdmin" class="flex justify-end">
                        <button class="text-zinc-400 hover:text-red-600 transition-colors duration-150"
                                @click="openDeleteEventModal(event)"
                                type="button">
                            <IconTrash class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
            <!-- Basics -->
            <section class="border border-zinc-200 rounded-lg bg-white shadow-sm">
                <header class="flex items-center gap-2 px-4 py-3 border-b border-zinc-100">
                    <span class="inline-block w-2 h-2 rounded-full bg-indigo-400"></span>
                    <h3 class="text-sm font-medium text-zinc-900">{{ $t('Basics') }}</h3>
                </header>

                <div class="p-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Event Type -->
                        <div>
                            <div v-if="!(event?.user_id === page.props.auth.user.id || isAdmin)"
                                 class="flex items-center gap-2 px-3 py-4 border border-zinc-200 rounded-md bg-zinc-50">
                                <div class="w-5 h-5 rounded-full" :style="{'backgroundColor': eventTypes.find(type => type.id === event.eventType.id)?.hex_code}"></div>
                                <span class="text-sm font-medium text-zinc-900">{{ eventTypes.find(type => type.id === event.eventType.id)?.name }}</span>
                            </div>
                            <Listbox v-else v-model="event.eventTypeId" @update:model-value="checkCollisions()">
                                <ListboxLabel class="block text-[13px] font-medium text-zinc-700 mb-1">{{ $t('Event type') }}</ListboxLabel>
                                <ListboxButton class="relative w-full cursor-pointer rounded-md border border-zinc-200 bg-white py-4 pl-3 pr-10 text-left shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    <div class="flex items-center gap-2">
                                        <div class="w-5 h-5 rounded-full" :style="{'backgroundColor': eventTypes.find(type => type.id === event.eventType.id)?.hex_code}"></div>
                                        <span class="block truncate text-sm text-zinc-900">{{ eventTypes.find(type => type.id === event.eventType.id)?.name }}</span>
                                    </div>
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                        <IconChevronDown class="h-5 w-5 text-zinc-400" aria-hidden="true" />
                                    </span>
                                </ListboxButton>
                                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm">
                                    <ListboxOption v-for="eventType in eventTypes" :key="eventType.id" :value="eventType.id" v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-indigo-600 text-white' : 'text-zinc-900', 'relative cursor-pointer select-none py-2 pl-3 pr-9']">
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full" :style="{'backgroundColor': eventType.hex_code}"></div>
                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ eventType.name }}</span>
                                            </div>
                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <IconCheck class="h-5 w-5" aria-hidden="true" />
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </Listbox>
                            <p class="mt-1 text-xs text-red-600" v-if="event.error?.eventType">{{ event.error.eventType.join('. ') }}</p>
                        </div>

                        <!-- Event Name -->
                        <div>
                            <label for="eventTitle" class="block text-[13px] font-medium text-zinc-700 mb-1">
                                {{ eventTypes.find(type => type.id === event.eventTypeId)?.individual_name ? $t('Event name') + '*' : $t('Event name') }}
                            </label>
                            <input
                                type="text"
                                id="eventTitle"
                                v-model="event.eventName"
                                :disabled="!(event?.user_id === page.props.auth.user.id || isAdmin)"
                                class="block w-full rounded-md border border-zinc-200 px-3 py-4 text-sm text-zinc-900 placeholder-zinc-500 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-zinc-50 disabled:text-zinc-500"
                                :placeholder="$t('Enter event name')"
                            />
                            <p class="mt-1 text-xs text-red-600" v-if="event.error?.eventName">{{ event.error.eventName.join('. ') }}</p>
                        </div>

                        <!-- Event Status -->
                        <div v-if="page.props.event_status_module">
                            <Listbox v-model="selectedEventStatus">
                                <ListboxLabel class="block text-[13px] font-medium text-zinc-700 mb-1">{{ $t('Event Status') }}</ListboxLabel>
                                <ListboxButton class="relative w-full cursor-pointer rounded-md border border-zinc-200 bg-white py-4 pl-3 pr-10 text-left shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    <div class="flex items-center gap-2">
                                        <div class="w-5 h-5 rounded-full" :style="{'backgroundColor': selectedEventStatus?.color}"></div>
                                        <span class="block truncate text-sm text-zinc-900">{{ selectedEventStatus?.name }}</span>
                                    </div>
                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                        <IconChevronDown class="h-5 w-5 text-zinc-400" aria-hidden="true" />
                                    </span>
                                </ListboxButton>
                                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm">
                                    <ListboxOption v-for="status in eventStatuses" :key="status.id" :value="status" v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-indigo-600 text-white' : 'text-zinc-900', 'relative cursor-pointer select-none py-2 pl-3 pr-9']">
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full" :style="{'backgroundColor': status.color}"></div>
                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ status.name }}</span>
                                            </div>
                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <IconCheck class="h-5 w-5" aria-hidden="true" />
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </Listbox>
                        </div>
                    </div>
                </div>
            </section>

            <!--    Properties    -->
            <div class="flex">
                <TagComponent v-for="eventProperty in event?.eventProperties"
                              :icon="eventProperty.icon.replace('Icon', '')"
                              :displayed-text="eventProperty.name"
                              hideX="true"
                              property=""/>
            </div>
            <!-- Date & Time -->
            <section class="border border-zinc-200 rounded-lg bg-white shadow-sm">
                <header class="flex items-center gap-2 px-4 py-3 border-b border-zinc-100">
                    <span class="inline-block w-2 h-2 rounded-full bg-sky-400"></span>
                    <h3 class="text-sm font-medium text-zinc-900">{{ $t('Date & Time') }}</h3>
                </header>

                <div class="p-4 space-y-4">
                    <label class="inline-flex items-center gap-2">
                        <input
                            type="checkbox"
                            v-model="event.allDay"
                            @change="checkChanges(event)"
                            :disabled="!(event?.user_id === usePage().props.auth.user.id || isAdmin)"
                            class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-600"
                        />
                        <span class="text-[13px] text-zinc-700">{{ $t('Full day') }}</span>
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex gap-2 items-end">
                            <div class="flex-1">
                                <label for="startDate" class="block text-[13px] font-medium text-zinc-700 mb-1">{{ $t('Start') }}</label>
                                <input
                                    type="date"
                                    id="startDate"
                                    v-model="event.startDate"
                                    @change="checkChanges(event)"
                                    :disabled="!(event?.user_id === usePage().props.auth.user.id || isAdmin)"
                                    class="block w-full rounded-md border border-zinc-200 px-3 py-4 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-zinc-50 disabled:text-zinc-500"
                                />
                            </div>
                            <div v-if="!event.allDay" class="flex-1">
                                <label for="startTime" class="block text-[13px] font-medium text-zinc-700 mb-1">{{ $t('Start time') }}</label>
                                <input
                                    type="time"
                                    id="startTime"
                                    v-model="event.startTime"
                                    @change="checkChanges(event)"
                                    :disabled="!(event?.user_id === usePage().props.auth.user.id || isAdmin)"
                                    class="block w-full rounded-md border border-zinc-200 px-3 py-4 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-zinc-50 disabled:text-zinc-500"
                                />
                            </div>
                        </div>
                        <div class="flex gap-2 items-end">
                            <div class="flex-1">
                                <label for="endDate" class="block text-[13px] font-medium text-zinc-700 mb-1">{{ $t('End') }}</label>
                                <input
                                    type="date"
                                    id="endDate"
                                    v-model="event.endDate"
                                    @change="checkChanges(event)"
                                    :disabled="!(event?.user_id === usePage().props.auth.user.id || isAdmin)"
                                    class="block w-full rounded-md border border-zinc-200 px-3 py-4 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-zinc-50 disabled:text-zinc-500"
                                />
                            </div>
                            <div v-if="!event.allDay" class="flex-1">
                                <label for="endTime" class="block text-[13px] font-medium text-zinc-700 mb-1">{{ $t('End time') }}</label>
                                <input
                                    type="time"
                                    id="endTime"
                                    v-model="event.endTime"
                                    @change="checkChanges(event)"
                                    :disabled="!(event?.user_id === usePage().props.auth.user.id || isAdmin)"
                                    class="block w-full rounded-md border border-zinc-200 px-3 py-4 text-sm text-zinc-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-zinc-50 disabled:text-zinc-500"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <p class="text-xs text-red-600" v-if="event.error?.start">{{ event.error.start.join('. ') }}</p>
                        <p class="text-xs text-red-600" v-if="event.error?.end">{{ event.error.end.join('. ') }}</p>
                    </div>

                    <div v-if="event?.is_series" class="mt-4 pt-4 border-t border-zinc-100">
                        <p class="text-xs text-zinc-600 mb-1">{{ $t('Event is part of a repeat event') }}</p>
                        <p class="text-xs text-zinc-600">{{ $t('Cycle: {0} to {1}', [event.selectedFrequencyName, convertDateFormat(event.series.end_date)]) }}</p>
                    </div>
                </div>
            </section>
            <!-- Room -->
            <section class="border border-zinc-200 rounded-lg bg-white shadow-sm">
                <header class="flex items-center gap-2 px-4 py-3 border-b border-zinc-100">
                    <span class="inline-block w-2 h-2 rounded-full bg-rose-400"></span>
                    <h3 class="text-sm font-medium text-zinc-900">{{ $t('Room') }}</h3>
                </header>

                <div class="p-4">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="text-xs text-zinc-600">{{ $t('Pick a room for this event.') }}</span>
                        <div v-if="selectedRoom && event.roomCollisionArray?.[selectedRoom.id] > 0" class="text-[12px] text-amber-600">
                            {{ $t('{0} potential conflicts detected', [event.roomCollisionArray[selectedRoom.id]]) }}
                        </div>
                    </div>

                    <div v-if="!(event?.user_id === page.props.auth.user.id || isAdmin)"
                         class="flex items-center gap-2 px-3 py-4 border border-zinc-200 rounded-md bg-zinc-50">
                        <span class="text-sm font-medium text-zinc-900">{{ rooms.find(room => room.id === event.roomId)?.name }}</span>
                    </div>

                    <Listbox v-else v-model="event.roomId">
                        <ListboxButton class="relative w-full cursor-pointer rounded-md border border-zinc-200 bg-white py-4 pl-3 pr-10 text-left shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <span v-if="event.roomId" class="block truncate text-sm text-zinc-900">
                                {{ rooms.find(room => room.id === event.roomId)?.name }}
                            </span>
                            <span v-else class="block truncate text-sm text-zinc-500">
                                {{ $t('Select room') }}*
                            </span>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                <IconChevronDown class="h-5 w-5 text-zinc-400" aria-hidden="true" />
                            </span>
                        </ListboxButton>
                        <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm">
                            <ListboxOption v-for="room in rooms" :key="room.id" :value="room.id" v-slot="{ active, selected }">
                                <li :class="[active ? 'bg-indigo-600 text-white' : 'text-zinc-900', 'relative cursor-pointer select-none py-2 pl-3 pr-9']">
                                    <div class="flex items-center justify-between">
                                        <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ room.name }}</span>
                                        <div class="flex items-center gap-2">
                                            <IconAlertTriangle
                                                v-if="event.roomCollisionArray && event.roomCollisionArray[room.id] > 0"
                                                :class="[active ? 'text-white' : 'text-amber-600', 'h-4 w-4']"
                                            />
                                            <IconCheck v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'h-5 w-5']" aria-hidden="true" />
                                        </div>
                                    </div>
                                </li>
                            </ListboxOption>
                        </ListboxOptions>
                    </Listbox>

                    <p class="mt-1 text-xs text-red-600" v-if="event.error?.roomId">{{ event.error.roomId.join('. ') }}</p>
                </div>
            </section>
            <!-- Project -->
            <section class="border border-zinc-200 rounded-lg bg-white shadow-sm">
                <header class="flex items-center gap-2 px-4 py-3 border-b border-zinc-100">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
                    <h3 class="text-sm font-medium text-zinc-900">{{ $t('Project') }}</h3>
                </header>

                <div class="p-4 space-y-4">
                    <p class="text-xs text-red-600" v-if="event.error?.projectId">{{ $t(event.error.projectId[0]) }}</p>

                    <!-- Aktivierung -->
                    <label for="showProjectInfo" class="flex items-center gap-2 cursor-pointer">
                        <input id="showProjectInfo" type="checkbox" v-model="showProjectInfo" class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-600" />
                        <span class="text-[13px] text-zinc-700">{{ $t('Assign event to a project') }}</span>
                    </label>

                    <div v-if="showProjectInfo" class="space-y-4">
                        <!-- Chip-Ansicht: nur anzeigen, wenn ein bestehendes Projekt ausgewählt ist -->
                        <div v-if="!event.creatingProject && event.project?.id" class="flex items-center gap-2 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2">
                            <div class="min-w-0">
                                <a
                                    :href="route('projects.tab', { project: event.project.id, projectTab: first_project_calendar_tab_id })"
                                    class="text-sm font-medium text-indigo-600 hover:underline"
                                    :title="event.project?.name"
                                >
                                    {{ event.project?.name }}
                                </a>
                            </div>
                            <div v-if="event?.user_id === usePage().props.auth.user.id || isAdmin" class="flex items-center gap-1.5 shrink-0">
                                <button type="button" class="text-zinc-400 hover:text-red-600 transition-colors" @click="deleteProject(event)">
                                    <IconCircleX class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <div v-if="event?.user_id === usePage().props.auth.user.id || isAdmin" class="space-y-3">
                            <!-- Segment: Bestehend / Neu -->
                            <div class="flex rounded-lg border border-zinc-200 p-1" role="tablist">
                                <button
                                    type="button"
                                    class="flex-1 rounded-md px-3 py-2 text-[13px] font-medium transition-colors"
                                    :class="!event.creatingProject ? 'bg-indigo-600 text-white shadow-sm' : 'text-zinc-700 hover:text-zinc-900'"
                                    @click="event.creatingProject = false"
                                >
                                    {{ $t('Existing project') }}
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 rounded-md px-3 py-2 text-[13px] font-medium transition-colors"
                                    :class="event.creatingProject ? 'bg-indigo-600 text-white shadow-sm' : 'text-zinc-700 hover:text-zinc-900'"
                                    @click="event.creatingProject = true"
                                >
                                    {{ $t('New project') }}
                                </button>
                            </div>

                            <!-- Inhalt: Bestehend = Suche / Neu = freies Input -->
                            <div>
                                <!-- Bestehendes Projekt: Suche -->
                                <div v-if="!event.creatingProject && !event.project?.id">
                                    <label for="projectSearch" class="block text-[13px] font-medium text-zinc-700 mb-1">{{ $t('Search for projects') }}</label>
                                    <input
                                        type="text"
                                        id="projectSearch"
                                        v-model="event.projectName"
                                        @focusin="event.showProjectSearchResults = true"
                                        @keyup="projectName = event.projectName"
                                        autocomplete="off"
                                        class="block w-full rounded-md border border-zinc-200 px-3 py-4 text-sm text-zinc-900 placeholder-zinc-500 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        :placeholder="$t('Type to search projects...')"
                                    />
                                    <LastedProjects
                                        :limit="10"
                                        @select="onLinkingProject"
                                    />
                                    <div
                                        v-if="projectSearchResults.length > 0 && !event.creatingProject && event.showProjectSearchResults"
                                        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm"
                                    >
                                        <div v-for="(project, index) in projectSearchResults"
                                             :key="index"
                                             @click="onLinkingProject(project, event)"
                                             class="relative cursor-pointer select-none py-2 pl-3 pr-9 hover:bg-indigo-600 hover:text-white"
                                        >
                                            {{ project.name }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Neues Projekt: freies Feld -->
                                <div v-if="event.creatingProject">
                                    <label for="projectName" class="block text-[13px] font-medium text-zinc-700 mb-1">{{ $t('New project name') }}</label>
                                    <input
                                        type="text"
                                        id="projectName"
                                        v-model="event.projectName"
                                        class="block w-full rounded-md border border-zinc-200 px-3 py-4 text-sm text-zinc-900 placeholder-zinc-500 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        :placeholder="$t('e.g. Kitchen Miller – Renovation')"
                                    />
                                </div>
                            </div>

                            <div v-if="showHints && event.creatingProject" class="flex items-center gap-2 text-xs text-zinc-600">
                                <SvgCollection svgName="arrowLeft" class="h-4 w-4" />
                                <span>{{ $t('Create a new project at the same time') }}</span>
                            </div>
                        </div>

                        <!-- Hinweise/Fehler -->
                        <p class="text-xs text-zinc-600" v-if="event.creatingProject">
                            {{ $t('The project will be created when saving the event.') }}
                        </p>
                        <p class="text-xs text-red-600" v-if="event.error?.projectName">{{ event.error.projectName.join('. ') }}</p>
                    </div>
                </div>
            </section>

            <!-- Notes -->
            <section class="border border-zinc-200 rounded-lg bg-white shadow-sm">
                <header class="flex items-center gap-2 px-4 py-3 border-b border-zinc-100">
                    <span class="inline-block w-2 h-2 rounded-full bg-violet-400"></span>
                    <h3 class="text-sm font-medium text-zinc-900">{{ $t('Notes') }}</h3>
                </header>

                <div class="p-4">
                    <label for="description" class="block text-[13px] font-medium text-zinc-700 mb-1">{{ $t('What do I need to bear in mind for the event?') }}</label>
                    <textarea
                        id="description"
                        v-model="event.description"
                        :disabled="!(event?.user_id === usePage().props.auth.user.id || isAdmin)"
                        rows="4"
                        class="block w-full rounded-md border border-zinc-200 px-3 py-4 text-sm text-zinc-900 placeholder-zinc-500 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:bg-zinc-50 disabled:text-zinc-500"
                        :placeholder="$t('Add any notes or special requirements for this event...')"
                    ></textarea>
                </div>
            </section>
            <!-- Properties -->
            <section v-if="event_properties?.length > 0" class="border border-zinc-200 rounded-lg bg-white shadow-sm">
                <header class="flex items-center gap-2 px-4 py-3 border-b border-zinc-100">
                    <span class="inline-block w-2 h-2 rounded-full bg-cyan-400"></span>
                    <h3 class="text-sm font-medium text-zinc-900">{{ $t('Properties') }}</h3>
                </header>

                <div class="p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label
                            v-for="ep in event_properties"
                            :key="ep.id"
                            class="flex items-center gap-2 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 transition hover:bg-white"
                        >
                            <input type="checkbox" v-model="ep.checked" class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-600" />
                            <component :is="ep.icon" class="w-3.5 h-3.5" />
                            <span class="text-[13px]" :class="ep.checked ? 'font-medium text-zinc-900' : 'text-zinc-700'">{{ ep.name }}</span>
                        </label>
                    </div>

                    <div v-if="checkedEventProperties.length" class="mt-3 flex flex-wrap items-center gap-1.5">
                        <span
                            v-for="(ep, i) in checkedEventProperties"
                            :key="ep.id ?? i"
                            class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[12.5px] text-zinc-800"
                        >
                           <component :is="ep.icon" class="w-3.5 h-3.5" />
                          <span>{{ ep.name }}</span>
                        </span>
                    </div>
                </div>
            </section>

            <!-- Save Button -->
            <div class="flex items-center justify-end gap-2 pt-6 border-t border-zinc-100" v-if="(event?.user_id === usePage().props.auth.user.id) || isAdmin">
                <button
                    :disabled="!event.roomId || !event.startDate || !event.endDate || (!event.startTime && !event.allDay) || (!event.endTime && !event.allDay)"
                    :class="!event.roomId || !event.startDate || !event.endDate || (!event.startTime && !event.allDay) || (!event.endTime && !event.allDay)
                        ? 'bg-zinc-300 text-zinc-500 cursor-not-allowed'
                        : 'bg-indigo-600 hover:bg-indigo-700 text-white'"
                    class="rounded-md px-6 py-2.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    @click="updateEvent(event)"
                >
                    {{ (isAdmin || (selectedRoom && selectedRoom.everyone_can_book)) ? $t('Save') : $t('Request occupancy') }}
                </button>
            </div>
        </div>
        <!-- View if not opened Event -->
        <div class="ml-2 w-11/12" v-else>
            <div class=" w-full flex cursor-pointer truncate p-2">
                <div>
                    <div class="block w-10 h-10 rounded-full" :style="{'backgroundColor' : eventTypes.find(type => type.id === event.eventTypeId)?.hex_code }" />
                </div>
                <p class="ml-2 headline2 flex items-center">
                    {{ eventTypes.find(type => type.id === event.eventTypeId)?.name }}
                </p>
                <div class="flex w-1/2 ml-12 xsDark items-center">
                    {{ event.eventName }}
                </div>
            </div>
            <div class="w-full flex">
                <div class="flex w-1/2 xxsDark items-center my-auto" v-if="event.projectId">
                    {{$t('Project')}}:
                    <a :href="route('projects.tab', {project: event.projectId, projectTab: first_project_calendar_tab_id})"
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
                        <p v-if="rooms.find(room => room.id === event.declinedRoomId)"
                           class="text-error mr-1 line-through">
                            {{ rooms.find(room => room.id === event.declinedRoomId)?.name }},
                        </p>
                        {{ event.startDate.toString().substring(10, 8) }}.{{ event.startDate.toString().substring(7, 5) }}.{{ event.startDate.toString().substring(4, 0) }},
                        {{ event.allDay ? 'Ganztägig' : event.startTime + "-" + event.endTime }}
                    </div>
                </div>
                <div v-else>
                    <p v-if="rooms.find(room => room.id === event.declinedRoomId)"
                       class="text-error line-through">
                        {{ rooms.find(room => room.id === event.declinedRoomId)?.name }},
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

<script setup>
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Input from "@/Jetstream/Input.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {
    IconChevronUp,
    IconChevronDown,
    IconTrash,
    IconCheck,
    IconCircleX,
    IconAlertTriangle
} from "@tabler/icons-vue";
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
import {computed, inject, onMounted, ref, watch} from "vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import LastedProjects from "@/Artwork/LastedProjects.vue";

// Props definition
const props = defineProps([
    'eventTypes',
    'rooms',
    'isAdmin',
    'removeNotificationOnAction',
    'first_project_calendar_tab_id',
    'event',
    'computedEventsWithoutRoom',
    'showHints',
    'eventStatuses'
]);

// Emits definition
const emit = defineEmits(['desiresReload']);

// Composables
const {getDaysOfEvent, formatEventDateByDayJs} = useEvent();
const page = usePage();

// Injected dependencies
const event_properties = inject('event_properties');

// Reactive form using useForm from Inertia
const form = useForm({
    title: props.event.title,
    eventName: props.event.eventName,
    start: null, // Will be computed
    end: null, // Will be computed
    roomId: props.event.roomId,
    description: props.event.description,
    eventNameMandatory: false, // Will be computed
    projectId: null, // Will be computed
    projectName: props.event.projectName || '',
    eventTypeId: props.event.eventType?.id,
    projectIdMandatory: false, // Will be computed
    creatingProject: false,
    isOption: false,
    allDay: props.event.allDay,
    eventStatusId: null, // Will be computed
    is_series: props.event.series ? props.event.series : false,
    event_properties: [] // Will be computed
});

// Reactive state
const startDate = ref(null);
const startTime = ref(null);
const endDate = ref(null);
const endTime = ref(null);
const projectName = ref(null);
const title = ref(null);
const eventName = ref(null);
const eventTypeName = ref(null);
const selectedEventType = ref(props.eventTypes[0]);
const selectedProject = ref(null);
const selectedRoom = ref(null);
const error = ref(null);
const creatingProject = ref(false);
const projectSearchResults = ref([]);
const description = ref(null);
const canEdit = ref(false);
const deleteComponentVisible = ref(false);
const eventToDelete = ref(null);
const allDayEvent = ref(false);
const showProjectInfo = ref(props.event.project_id !== null);
const firstCall = ref(true);
const isOption = ref(false);
const selectedEventStatus = ref(props.eventStatuses?.find(status => status.default));

// Frequencies data
const frequencies = ref([
    {
        id: 1,
        name: 'Daily' // Will use $t in template
    },
    {
        id: 2,
        name: 'Weekly' // Will use $t in template
    },
    {
        id: 3,
        name: 'Every 2 weeks' // Will use $t in template
    },
    {
        id: 4,
        name: 'Monthly' // Will use $t in template
    }
]);

// Computed properties
const checkedEventProperties = computed(() => {
    return (event_properties ?? []).filter(p => p.checked);
});

// Methods (converted to functions)
const getTimeOfDate = (date) => {
    //returns hours and minutes in format HH:mm, if necessary with leading zeros, from given date object
    return ('0' + date.getHours()).slice(-2) + ":" + ('0' + date.getMinutes()).slice(-2);
};

const getDateOfDate = (date) => {
    //returns date in format "YYYY-MM-DD" from given date object, with leading zeros
    //make sure to add 1 to the returned month because javascript starts counting from 0, January = 0
    return date.getFullYear() + "-" +
        (date.getMonth() + 1).toString().padStart(2, '0') + '-' +
        date.getDate().toString().padStart(2, '0');
};

const convertDateFormat = (dateString) => {
    const parts = dateString.split('-');
    return parts[2] + "." + parts[1] + "." + parts[0];
};

const getFrequencyName = (frequencyId) => {
    const matchedFrequency = frequencies.value.find(frequency => frequency.id === frequencyId);

    if (matchedFrequency) {
        return matchedFrequency.name;
    } else {
        return 'No cycle selected'; // Will use $t in template
    }
};

const onLinkingProject = (project) => {
    props.event.projectId = project.id;
    props.event.project = project;
    props.event.projectName = '';
    projectName.value = '';
    props.event.showProjectSearchResults = false;
    projectSearchResults.value = [];
};

const requestReload = (desiredRoomId, desiredDays) => {
    emit('desiresReload', [desiredRoomId], desiredDays, true);
};

/**
 * Format date and time to ISO 8601 with timezone UTC
 *
 * @param date
 * @param time
 * @returns {string|null}
 */
const formatDate = (date, time) => {
    if (date === null || time === null) return null;
    return (new Date(date + ' ' + time)).toISOString();
};

const checkChanges = (event) => {
    updateTimes(event);
};

/**
 * If the user selects a start, end, and room
 * call the server to get information if there are any collision
 *
 * @returns {Promise<void>}
 */
const checkCollisions = async () => {
    if (
        props.event.startTime && props.event.startDate && props.event.endTime && props.event.endDate ||
        props.event.allDay && props.event.startDate && props.event.endDate
    ) {
        let startFull = formatDate(props.event.startDate, !props.event.allDay ? props.event.startTime : '00:00');
        let endFull = formatDate(props.event.endDate, !props.event.allDay ? props.event.endTime : '23:59');

        await axios.post('/collision/room', {
            params: {
                start: startFull,
                end: endFull
            }
        }).then(response => props.event.roomCollisionArray = response.data);
    }
};

const updateTimes = () => {
    if (props.event.startDate) {
        if (!props.event.endDate && checkYear(props.event.startDate)) {
            props.event.endDate = props.event.startDate;
        }
        if (props.event.startTime) {
            if (!props.event.endTime) {
                if (props.event.startTime === '23:00') {
                    props.event.endTime = '23:59';
                } else {
                    let startHours = props.event.startTime.slice(0, 2);
                    if (startHours === '23') {
                        props.event.endTime = '00:' + props.event.startTime.slice(3, 5);
                        let date = new Date();
                        props.event.endDate = new Date(
                            date.setDate(new Date(props.event.endDate).getDate() + 1)
                        ).toISOString().slice(0, 10);
                    } else {
                        props.event.endTime = getNextHourString(props.event.startTime);
                    }
                }
            }
        }
    }

    validateStartBeforeEndTime(props.event);
    checkCollisions(props.event);
};

const validateStartBeforeEndTime = async (event) => {
    event.error = null;
    if (event.startDate && event.endDate && event.startTime && event.endTime) {
        let startFull = setCombinedTimeString(event.startDate, event.startTime, 'start');
        let endFull = setCombinedTimeString(event.endDate, event.endTime, 'end');
        return await axios
            .post('/events', {start: startFull, end: endFull}, {headers: {'X-Dry-Run': true}})
            .catch(error => event.error = error.response.data.errors);
    }
};

const addMinutes = (date, minutes) => {
    date.setMinutes(date.getMinutes() + minutes);
    return date;
};

const setCombinedTimeString = (date, time, target) => {
    let combinedDateString = (date.toString() + ' ' + time);
    const offset = new Date(combinedDateString).getTimezoneOffset();

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
};

const getNextHourString = (timeString) => {
    let hours = timeString.slice(0, 2);
    let minutes = timeString.slice(3, 5);
    if ((Number(hours) + 1) < 10) {
        return '0' + (Number(hours) + 1) + ':' + minutes;
    } else {
        return (Number(hours) + 1) + ':' + minutes;
    }
};

const deleteProject = (event) => {
    event.project = null;
    event.projectId = null;
    event.projectName = '';
};

/**
 * Creates an event and reloads all events using Inertia form
 */
const updateEvent = (event) => {
    if (props.removeNotificationOnAction && (selectedRoom.value?.everyone_can_book || props.isAdmin)) {
        isOption.value = true;
    }

    // Update form data with current values
    form.title = event.title;
    form.eventName = event.eventName;
    form.start = formatDate(event.startDate, event.startTime);
    form.end = formatDate(event.endDate, event.endTime);
    form.roomId = selectedRoom.value?.id || event.roomId;
    form.description = event.description;

    // Use consistent eventTypeId reference and proper boolean conversion
    const currentEventType = props.eventTypes.find(eventType => eventType.id === event.eventTypeId);
    form.eventNameMandatory = Boolean(currentEventType?.individual_name);
    form.projectIdMandatory = Boolean(currentEventType?.project_mandatory);

    // Handle project data correctly - send existing projectId if available, regardless of showProjectInfo
    form.projectId = event.project?.id || null;
    // Send projectName for existing projects or new project creation
    form.projectName = event.creatingProject ? event.projectName : (event.project?.name || '');
    form.eventTypeId = event.eventType?.id;
    form.creatingProject = Boolean(event.creatingProject);
    form.isOption = isOption.value;
    form.allDay = event.allDay;
    form.eventStatusId = selectedEventStatus.value?.id;
    form.is_series = event.series ? event.series : false;
    form.event_properties = event_properties
        .filter((eventProperty) => eventProperty.checked)
        .map((eventProperty) => eventProperty.id);

    console.log('Form data:', form.data());
    form.put(route('events.update', {event: event.id}), {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            console.log('Event updated successfully');
            router.reload({
                only: ['eventsWithoutRoom']
            });
        },
        onError: (error) => {
            event.error = error.response;
        }
    });
};

const openDeleteEventModal = () => {
    deleteComponentVisible.value = true;
};

const afterConfirm = (bool) => {
    if (!bool) {
        return deleteComponentVisible.value = false;
    }

    router.delete(route('events.delete', {event: props.event.id}), {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({
                only: ['eventsWithoutRoom']
            });
            deleteComponentVisible.value = false;
        },
        onError: (error) => {
            props.event.error = error.response.data.errors;
        }
    });
};

// Watchers
watch(projectName, {
    deep: true,
    handler() {
        if (creatingProject.value || !projectName.value) {
            projectSearchResults.value = [];
            return;
        }
        axios.get('/projects/search', {params: {query: projectName.value}})
            .then(response => projectSearchResults.value = response.data);
    },
});

watch(() => props.event.roomId, {
    handler(newRoomId) {
        // Sync selectedRoom with event.roomId to maintain consistency
        selectedRoom.value = props.rooms.find(room => room.id === newRoomId) || null;
    },
    immediate: true
});

// Lifecycle hooks
onMounted(() => {
    event_properties.forEach((event_property) => {
        event_property.checked = props.event.eventProperties.some(
            (event_event_properties) => event_event_properties.id === event_property.id
        );
    });
});

// Helper function for checkYear - validates if date has a reasonable year
const checkYear = (date) => {
    if (!date) return false;
    const year = new Date(date).getFullYear();
    const currentYear = new Date().getFullYear();
    // Allow dates from 10 years ago to 10 years in the future
    return year >= (currentYear - 10) && year <= (currentYear + 10);
};

// Expose methods and data that might be used in template
defineExpose({
    usePage,
    getTimeOfDate,
    getDateOfDate,
    convertDateFormat,
    getFrequencyName,
    onLinkingProject,
    requestReload,
    formatDate,
    checkChanges,
    checkCollisions,
    updateTimes,
    validateStartBeforeEndTime,
    addMinutes,
    setCombinedTimeString,
    getNextHourString,
    deleteProject,
    updateEvent,
    openDeleteEventModal,
    afterConfirm,
    checkedEventProperties,
    selectedRoom,
    selectedEventStatus,
    showProjectInfo,
    projectSearchResults,
    deleteComponentVisible,
    frequencies,
    event_properties
});
</script>
