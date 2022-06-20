<template>
    <app-layout title="Event Management">
        <div class="py-4">
            <div class="max-w-screen-lg mb-40 my-12 flex flex-row ml-20 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex flex-wrap items-center">
                            <div class="flex items-center mb-4">
                            <h2 class="text-3xl font-black flex">Raumbelegungen</h2>
                            <button @click="openAddEventModal" type="button"
                                    class="flex my-auto ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                            </button>
                            <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                <span
                                    class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Frage neue Raumbelegungen an</span>
                            </div>
                            </div>
                            <div class="flex w-full my-4 items-center">
                                <div class="text-xl font-black">
                                {{formattedMonth}}
                                {{ rooms[0].days_in_month[0].date_local.substring(0,4)}}
                                </div>

                                <div class="ml-2 flex items-center">
                                    <Link :href="route('events.monthly_management',{month_start: new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) -2, 1, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'April' ? 60 : formattedMonth === 'November' ? -60 : 0) ),month_end:new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) -1, 1, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'April' ? 60 : formattedMonth === 'November' ? -60 : 0) )})">
                                    <ChevronLeftIcon class="h-5 w-5" />
                                    </Link>
                                    <CalendarIcon class="h-6 w-6" />
                                    <Link :href="route('events.monthly_management',{month_start: new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7), 1, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'März' ? -60 : formattedMonth === 'Oktober' ? 60 : 0) ),month_end:new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) - (-1), 1, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'März' ? -60 : formattedMonth === 'Oktober' ? 60 : 0) )})">
                                    <ChevronRightIcon class="h-5 w-5" />
                                    </Link>
                                </div>
                                <Listbox as="div" class="sm:col-span-3 mb-8 flex items-center my-auto" v-model="wantedArea">
                                    <div class="relative">
                                        <ListboxButton
                                            class="ml-4 cursor-pointer bg-white relative w-full font-semibold pr-20 py-2 mt-4 text-left cursor-default focus:outline-none focus:ring-0 focus:ring-primary focus:border-primary sm:text-sm">
                                        <span v-if="wantedArea" class="block truncate items-center">
                                            <span>{{ wantedArea.name }}</span>
                                        </span>
                                        <span v-else class="block truncate items-center">
                                            <span>Alle Areale</span>
                                        </span>
                                            <span
                                                class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </span>
                                        </ListboxButton>

                                        <transition leave-active-class="transition ease-in duration-100"
                                                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                                            <ListboxOptions
                                                class="absolute cursor-pointer z-10 mt-1 w-full bg-primary shadow-lg max-h-32 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                                <ListboxOption as="template" class="max-h-8" key="Alle Areale" :value="null" v-slot="{active, selected}">
                                                    <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        Alle Areale
                                                    </span>
                                                        <span
                                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                                    </li>
                                                </ListboxOption>
                                                <ListboxOption as="template" class="max-h-8"
                                                               v-for="area in areas.data"
                                                               :key="area.name"
                                                               :value="area"
                                                               v-slot="{ active, selected }">
                                                    <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ area.name }}
                                                    </span>
                                                        <span
                                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                                    </li>
                                                </ListboxOption>
                                            </ListboxOptions>
                                        </transition>
                                    </div>
                                </Listbox>
                            </div>
                            <div class="bg-stone-50 w-full flex">
                                <div class="mt-14 w-48">
                                    <div v-for="day in days_this_month"
                                         class="w-40 inline-flex mt-1 h-36 w-full text-lg text-secondary subpixel-antialiased">
                                        {{ day.date_formatted }}
                                    </div>
                                </div>
                                <div class="flex">
                                    <div v-if="this.roomsToShow.length > 0" v-for="room in roomsToShow" class="inline-flex flex-col">
                                        <h2 class="text-lg text-secondary subpixel-antialiased mt-4 mb-2">
                                            {{ room.name }}
                                        </h2>
                                        <div v-for="day in room.days_in_month">
                                            <div @click="openDayDetailModal(day)" v-if="day.events.length > 0"
                                                 :class="[{'stripes': day.events[0].occupancy_option }, 'bg-white m-0.5 h-36 mr-4 border border-gray-100 cursor-pointer']">
                                                <!-- If only 1 event on that day-->
                                                <div v-if="day.events.length === 1">
                                                    <!-- Icons -->
                                                    <div class="flex p-1 ml-1 mt-1">
                                                        <UserGroupIcon v-if="day.events[0].audience"
                                                                       class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                        <VolumeUpIcon v-if="day.events[0].is_loud"
                                                                      :class="day.events[0].audience ? 'ml-1' : ''"
                                                                      class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                        <div v-if="!day.events[0].audience && !day.events[0].is_loud"
                                                             class="h-5 w-5">

                                                        </div>
                                                    </div>
                                                    <div>
                                                        <!-- Individual Eventname -->
                                                        <div v-if="day.events[0].project_id === null"
                                                             class="mt-1 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary">
                                                            {{ day.events[0].name }}
                                                        </div>
                                                        <!-- Name of connected Project -->
                                                        <div
                                                            v-else
                                                            class="mt-1 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary">
                                                            {{
                                                                projects.data.find(x => x.id === day.events[0].project_id).name
                                                            }}
                                                        </div>
                                                        <!-- Time of Event -->
                                                        <div class="ml-2 text-sm text-secondary subpixel-antialiased">
                                                            {{ getTimespan(day)[0].toString().substring(16,21) }}
                                                            - {{ getTimespan(day)[1].toString().substring(16,21) }}
                                                        </div>

                                                        <!-- EventType -->
                                                        <div class="mt-8 ml-2 mb-1">
                                                            <EventTypeIconCollection :height="20" :width="20"
                                                                                     :iconName="this.event_types.data.find(x => x.id === day.events[0].event_type_id).svg_name"/>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else-if="day.events.length > 1">
                                                    <div class="flex p-1 ml-1 mt-1">
                                                        <UserGroupIcon v-if="day.events.some(x => x.audience === true)"
                                                                       class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                        <VolumeUpIcon v-if="day.events.some(x => x.is_loud === true)"
                                                                      :class="day.events.some(x => x.audience === true) ? 'ml-1' : ''"
                                                                      class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                        <div v-else class="h-5 w-5">
                                                            <!-- placeholder for design purposes -->
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="mt-2 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary">
                                                        {{ day.events.length }} Projekte
                                                    </div>

                                                    <div class="ml-2 text-sm text-secondary subpixel-antialiased">
                                                        {{ getTimespan(day)[0].toString().substring(16,21) }}
                                                        - {{ getTimespan(day)[1].toString().substring(16,21) }}
                                                    </div>

                                                    <div class="mt-8 ml-2 mb-1 flex">
                                                        <div class="my-auto -mr-1.5 ring-white ring-2 rounded-full"
                                                             v-for="eventType in this.getEventTypes(day.events)">
                                                            <EventTypeIconCollection
                                                                class="rounded-full ring-2 ring-white" :height="20"
                                                                :width="20"
                                                                :iconName="eventType.svg_name"/>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                            <div @mouseover="activateHover(day.date_local, room.id)"
                                                 @click="openAddEventModal(room.id)"
                                                 @mouseout="deactivateHover()" v-else
                                                 class="m-0.5 h-36 mr-4 w-44 flex cursor-pointer"
                                                 :class="showAddHoverDate === day.date_local && showAddHoverRoomId === room.id ? 'bg-secondary' : ''">
                                                <button
                                                    v-show="showAddHoverDate === day.date_local && showAddHoverRoomId === room.id"
                                                    type="button"
                                                    class="m-auto border border-transparent rounded-full shadow-sm text-white bg-primary bg-primaryHover focus:outline-none">
                                                    <PlusSmIcon class="h-6 w-6" aria-hidden="true"/>
                                                </button>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
                {{ rooms }}

            </div>
        </div>
        <!-- Termin erstellen Modal-->
        <jet-dialog-modal :show="addingEvent" @close="closeAddEventModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Neue Raumbelegung
                    </div>
                    <XIcon @click="closeAddEventModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary subpixel-antialiased">
                        Bitte beachte, dass du Vor- und Nachbereitungszeit einplanst.
                    </div>
                    <div v-if="$page.props.can.show_hints" class="mt-6 ml-4 flex">
                        <SvgCollection svgName="arrowLeft" class="mt-3 ml-2 flex-shrink-0"/>
                        <span
                            class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Hier kannst du die Art des Termins definieren. ihn einem Projekt zuordnen und weitere Infos mit deinem Team teilen. Anschließend kannst du dafür die Raumbelegung anfragen.</span>
                    </div>
                    <div class="flex justify-between">
                        <Listbox as="div" class="flex" v-model="selectedEventType">
                            <div class="relative">
                                <ListboxButton
                                    class="bg-white w-56 relative mt-6 font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                    <div class="flex items-center my-auto">
                                        <EventTypeIconCollection :height="20" :width="20"
                                                                 :iconName="selectedEventType.svg_name"/>
                                        <span class="block truncate items-center ml-3 flex">
                                            <span>{{ selectedEventType.name }}</span>
                                </span>
                                        <span
                                            class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                </span>
                                    </div>
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-56 z-10 mt-1 bg-primary shadow-lg max-h-32 pl-1 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="eventType in event_types.data"
                                                       :key="eventType.name"
                                                       :value="eventType"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <EventTypeIconCollection :height="20" :width="20"
                                                                         :iconName="eventType.svg_name"/>
                                                <span
                                                    :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ eventType.name }}
                                                    </span>
                                                <span
                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                        <Listbox as="div" class="flex" v-model="selectedRoom">
                            <div class="relative">
                                <ListboxButton
                                    class="bg-white w-56 relative mt-6 font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                    <div class="flex items-center my-auto">
                                        <span v-if="selectedRoom" class="block truncate items-center flex">
                                            <span>{{ selectedRoom.name }}</span>

                                        </span>
                                        <span v-if="!selectedRoom"
                                              class="block truncate">Raum definieren*</span>
                                        <span
                                            class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                            <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                         </span>
                                    </div>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-56 z-10 mt-1 bg-primary shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                        <div v-for="area in areas.data">
                                            <p class="text-secondary mt-1 text-sm uppercase ml-3 subpixel-antialiased cursor-pointer">
                                                {{ area.name }}</p>
                                            <ListboxOption as="template" class="max-h-8"
                                                           v-for="room in area.rooms"
                                                           :key="room.name"
                                                           :value="room"
                                                           v-slot="{ active, selected }">
                                                <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <span
                                                    :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ room.name }}
                                                    </span>
                                                    <span
                                                        :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                                </li>
                                            </ListboxOption>
                                        </div>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                    </div>
                    <div class="flex mt-6">
                        <input v-model="assignProject"
                               type="checkbox"
                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                        <p :class="[assignProject ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                           class="ml-4 my-auto text-sm">Termin einem Projekt zuordnen</p>
                    </div>
                    <div v-if="assignProject">
                        <div class="flex items-center mt-4">
                            <Switch v-model="creatingProject"
                                    :class="[creatingProject ?
                                        'bg-success' :
                                        'bg-gray-300',
                                        'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none']">
                                <span aria-hidden="true"
                                      :class="[creatingProject ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                            </Switch>
                            <span class="ml-4 text-sm"
                                  :class="[creatingProject ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']">
                                Neues Projekt
                            </span>
                            <div v-if="$page.props.can.show_hints" class="ml-3 flex">
                                <SvgCollection svgName="arrowLeft" class="mt-1 flex-shrink-0"/>
                                <span
                                    class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Lege gleichzeitig ein neues Projekt an</span>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap" v-if="!creatingProject">
                            <div class="my-auto w-full" v-if="this.selectedProject === null">
                                <input id="projectSearch" v-model="project_query" type="text" autocomplete="off"
                                       @focusout="project_query = ''"
                                       class="text-primary h-10 focus:border-black border-2 w-full text-sm border-gray-300 "
                                       placeholder="Zu welchem bestehendem Projekt zuordnen?*"
                                       :disabled="this.selectedProject"/>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0">
                                    <div v-if="project_search_results.length > 0 && project_query.length > 0"
                                         class="absolute z-10 w-full max-h-60 bg-primary shadow-lg
                                         text-base ring-1 ring-black ring-opacity-5
                                         overflow-auto focus:outline-none sm:text-sm">
                                        <div class="border-gray-200">
                                            <div v-for="(project, index) in project_search_results" :key="index"
                                                 class="flex items-center cursor-pointer">
                                                <div class="flex-1 text-sm py-4">
                                                    <p @click="addProjectToEvent(project)"
                                                       class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                        {{ project.name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </transition>
                            </div>

                            <div>

                            <span v-if="this.selectedProject !== null"
                                  class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <span class="flex">
                                    Aktuell zugeordnet zu: {{ this.selectedProject.name }}
                                </span>
                            </div>
                            <button type="button" @click="deleteSelectedProject()">
                                <span class="sr-only">User aus Team entfernen</span>
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                            </button>
                            </span>
                            </div>
                        </div>
                        <div class="flex mt-2" v-if="creatingProject">
                            <input type="text" v-model="newProjectName"
                                   placeholder="Projektname von neuem Projekt*"
                                   class="text-primary h-10 focus:border-black border-2 w-full text-sm border-gray-300 "/>
                        </div>
                    </div>
                    <div class="mt-4">
                        <input type="text" v-model="addEventForm.name" placeholder="Terminname"
                               class="text-primary h-10 focus:border-black border-2 w-full text-sm border-gray-300 "/>
                    </div>
                    <div class="flex mt-4">
                        <div class="text-secondary mr-2">
                            <label for="startTime">Terminstart*</label>
                            <input
                                v-model="addEventForm.start_time" id="startTime"
                                placeholder="Terminstart" type="datetime-local"
                                class="border-gray-300 text-primary placeholder-secondary mr-2 w-full"/>
                        </div>
                        <div class="text-secondary ml-2">
                            <label for="endTime">Terminende*</label>
                            <input
                                v-model="addEventForm.end_time" id="endTime"
                                placeholder="Zu erledigen bis?" type="datetime-local"
                                class="border-gray-300 text-primary placeholder-secondary w-full"/>
                        </div>
                    </div>
                    <div class="flex mt-4 items-center justify-between">
                        <div class="flex">
                            <input v-model="addEventForm.occupancy_option"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <AdjustmentsIcon class="h-5 w-5 ml-2 my-auto"
                                             :class="[addEventForm.occupancy_option ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                            <p :class="[addEventForm.occupancy_option ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1 my-auto text-sm">Belegungsoption</p>
                        </div>
                        <div class="flex items-center justify-between">
                            <input v-model="addEventForm.audience"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <UserGroupIcon class="h-5 w-5 ml-2 my-auto"
                                           :class="[addEventForm.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                            <p :class="[addEventForm.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1 my-auto text-sm">Publikum</p>
                        </div>
                        <div class="flex justify-between">
                            <input v-model="addEventForm.is_loud"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <VolumeUpIcon class="h-5 w-5 ml-2 my-auto"
                                          :class="[addEventForm.is_loud ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                            <p :class="[addEventForm.is_loud ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1 my-auto text-sm">Es wird laut</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <textarea placeholder="Was gibt es bei dem Termin zu beachten?"
                                  v-model="addEventForm.description" rows="4"
                                  class="resize-none shadow-sm placeholder-secondary p-4 focus:ring-black focus:border-black border-2 block w-full sm:text-sm border border-gray-300"/>
                    </div>
                    <div>
                        <button :class="[this.addEventForm.start_time === null || this.addEventForm.end_time === null || this.selectedRoom === null || (addEventForm.name === '' && newProjectName === '' && selectedProject === null) ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="addEvent"
                                :disabled="addEventForm.start_time === null && addEventForm.end_time === null || (addEventForm.name === '' && newProjectName === '' && selectedProject === null)">
                            Belegen
                        </button>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- TagesDetail-Modal -->
        <jet-dialog-modal :show="showDayDetailModal" @close="closeDayDetailModal">
            <template #content>
                <XIcon @click="closeDayDetailModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                       aria-hidden="true"/>
                <div v-for="event in wantedDay.events" class="mx-4 border-b-2 pb-8">

                    <div class="mt-2">
                        <Listbox as="div" class="flex" v-model="event.event_type_id">
                            <div class="relative">
                                <ListboxButton
                                    class="bg-white w-full relative mt-4 py-2 cursor-pointer focus:outline-none">
                                    <div class="flex items-center">
                                        <EventTypeIconCollection :height="24" :width="24"
                                                                 :iconName="event_types.data.find(x => x.id === event.event_type_id).svg_name"/>
                                        <span class="block truncate items-center text-2xl font-black ml-3 flex">
                                            <span>{{
                                                    event_types.data.find(x => x.id === event.event_type_id).name
                                                }}</span>
                                        </span>
                                        <span
                                            class="ml-2 inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-6 w-6 text-primary font-black" aria-hidden="true"/>
                                </span>
                                    </div>
                                </ListboxButton>

                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-full z-10 mt-1 bg-primary shadow-lg max-h-32 pl-1 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="eventType in event_types.data"
                                                       :key="eventType.name"
                                                       :value="eventType.id"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <EventTypeIconCollection :height="20" :width="20"
                                                                         :iconName="eventType.svg_name"/>
                                                <span
                                                    :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ eventType.name }}
                                                    </span>
                                                <span
                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </div>
                        </Listbox>
                        <div v-if="event.name !== null"
                             class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">

                        </div>

                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <div v-if="event.project_id !== null" class="flex items-center">
                                <div>Zugeordnet zu</div>
                                <div>
                                    <Link
                                        :href="route('projects.show',{project: event.project_id})"
                                        class="ml-3 text-lg flex font-bold font-lexend text-primary">
                                        {{ projects.data.find(x => x.id === event.project_id).name }}
                                    </Link>
                                </div>
                            </div>
                            <div class="w-1/3">
                                <Listbox as="div" class="flex items-center my-auto w-full " v-model="event.room_id">
                                    <div class="relative w-full">
                                        <ListboxButton
                                            class="bg-white w-full relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                            <div class="flex items-center my-auto">
                                        <span v-if="event.room_id" class="block truncate items-center flex">
                                            <span>{{ allRooms.find(x => x.id === event.room_id).name }}</span>

                                        </span>
                                                <span v-if="!event.room_id"
                                                      class="block truncate">Raum definieren*</span>
                                                <span
                                                    class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                            <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                         </span>
                                            </div>
                                        </ListboxButton>
                                        <transition leave-active-class="transition ease-in duration-100"
                                                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                                            <ListboxOptions
                                                class="absolute z-10 mt-1 bg-primary shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                                <div v-for="area in areas.data">
                                                    <p class="text-secondary mt-1 text-sm uppercase ml-3 subpixel-antialiased cursor-pointer">
                                                        {{ area.name }}</p>
                                                    <ListboxOption as="template" class="max-h-8"
                                                                   v-for="room in area.rooms"
                                                                   :key="room.name"
                                                                   :value="room.id"
                                                                   v-slot="{ active, selected }">
                                                        <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <span
                                                    :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ room.name }}
                                                    </span>
                                                            <span
                                                                :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                                        </li>
                                                    </ListboxOption>
                                                </div>
                                            </ListboxOptions>
                                        </transition>
                                    </div>
                                </Listbox>
                            </div>
                        </div>
                    </div>
                    <div class="flex mt-4">
                        <div class="text-secondary mr-2">
                            <label for="startDate">Terminstart*</label>
                            <input
                                v-model="event.start_time_dt_local" id="startDate"
                                placeholder="Terminstart" type="datetime-local"
                                class="border-gray-300 text-primary placeholder-secondary mr-2 w-full"/>
                        </div>
                        <div class="text-secondary ml-2">
                            <label for="endDate">Terminende*</label>
                            <input
                                v-model="event.end_time_dt_local" id="endDate"
                                placeholder="Zu erledigen bis?" type="datetime-local"
                                class="border-gray-300 text-primary placeholder-secondary w-full"/>
                        </div>
                    </div>
                    <div class="flex mt-4 items-center">
                        <div class="flex items-center">
                            <input v-model="event.audience"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <UserGroupIcon class="h-5 w-5 ml-2 my-auto"
                                           :class="[event.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                            <p :class="[event.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1 my-auto text-sm">Publikum</p>
                        </div>
                        <div class="flex ml-12">
                            <input v-model="event.is_loud"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <VolumeUpIcon class="h-5 w-5 ml-2 my-auto"
                                          :class="[event.is_loud ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                            <p :class="[event.is_loud ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1 my-auto text-sm">Es wird laut</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <textarea placeholder="Was gibt es bei dem Termin zu beachten?"
                                  v-model="event.description" rows="4"
                                  class="resize-none shadow-sm placeholder-secondary p-4 focus:ring-black focus:border-black border-2 block w-full sm:text-sm border border-gray-300"/>
                    </div>
                    <div>
                        <button :class="[event.start_time === null || event.end_time === null || event.selectedRoom === null ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="updateEvent(event)"
                                :disabled="event.start_time === null && event.end_time === null">
                            Speichern
                        </button>
                    </div>


                </div>
            </template>
        </jet-dialog-modal>
    </app-layout>
</template>
<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    AdjustmentsIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    DotsVerticalIcon,
    UserGroupIcon,
    VolumeUpIcon,
    XIcon,
} from '@heroicons/vue/outline'
import {CalendarIcon, CheckIcon, ChevronUpIcon, PlusSmIcon, XCircleIcon} from '@heroicons/vue/solid'

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
    Switch
} from '@headlessui/vue'
import Button from "@/Jetstream/Button";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import JetSecondaryButton from "@/Jetstream/SecondaryButton";
import Checkbox from "@/Layouts/Components/Checkbox";
import {Link, useForm} from "@inertiajs/inertia-vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";


export default defineComponent({
    components: {
        ListboxLabel,
        SvgCollection,
        Button,
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        JetSecondaryButton,
        ChevronDownIcon,
        ChevronUpIcon,
        Checkbox,
        XIcon,
        XCircleIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        Link,
        EventTypeIconCollection,
        AdjustmentsIcon,
        UserGroupIcon,
        VolumeUpIcon,
        Switch,
        ChevronLeftIcon,
        ChevronRightIcon,
        CalendarIcon,

    },
    props: ['optional_events', 'event_types', 'areas', 'month_events', 'day_events', 'projects', 'rooms', 'days_this_month'],
    computed: {
        allRooms: function () {
            let allRoomsArray = [];
            this.areas.data.forEach((area) => {
                area.rooms.forEach((room) => {
                    allRoomsArray.push(room);
                })
            })
            return allRoomsArray;
        },
        formattedMonth: function (){
            switch(this.rooms[0].days_in_month[0].date_local.slice(5,7)){
                case '01':
                    return 'Januar';
                case '02':
                    return 'Februar';
                case '03':
                    return 'März';
                case '04':
                    return 'April';
                case '05':
                    return 'Mai';
                case '06':
                    return 'Juni';
                case '07':
                    return 'Juli';
                case '08':
                    return 'August';
                case '09':
                    return 'September';
                case '10':
                    return 'Oktober';
                case '11':
                    return 'November';
                case '12':
                    return 'Dezember';
            }
        },
        roomsToShow: function () {
            let roomsCopy = this.rooms.slice();
            if(this.wantedArea){
                return roomsCopy.filter(room => room.area_id === this.wantedArea.id)
            }
            return  this.rooms
        },
    },
    methods: {
        openAddEventModal(roomId) {
            this.addingEvent = true;
            if (this.showAddHoverDate !== null) {
                const startDate = new Date(this.showAddHoverDate);
                startDate.setMinutes(startDate.getMinutes() + 120);
                this.addEventForm.start_time = startDate.toISOString().slice(0, 16);

                const endDate = new Date(this.showAddHoverDate);
                endDate.setMinutes(endDate.getMinutes() + 1559);
                this.addEventForm.end_time = endDate.toISOString().slice(0, 16);
            }
            if (roomId !== null) {
                this.areas.data.forEach((area) => {
                    area.rooms.forEach((room) => {
                        if (room.id === roomId) {
                            this.selectedRoom = room;
                        }
                    })

                })
            }
        },
        getTimespan(day) {
            let startOfDayInMillis = new Date(day.date_local).getTime();
            let endOfDayInMillis = new Date(new Date(day.date_local).setMinutes(1439)).getTime();
            let earliestStart = new Date(day.events[0].start_time_dt_local).getTime() < startOfDayInMillis ? startOfDayInMillis : new Date(day.events[0].start_time_dt_local).getTime();
            let latestEnd = new Date(day.events[0].end_time_dt_local).getTime() > endOfDayInMillis ? endOfDayInMillis : new Date(day.events[0].end_time_dt_local).getTime() > endOfDayInMillis;
            day.events.forEach((event) => {
                let startTimeInMillis = new Date(event.start_time_dt_local).getTime();
                if (startTimeInMillis < earliestStart) {
                    if (startTimeInMillis < startOfDayInMillis) {
                        earliestStart = startOfDayInMillis;
                    } else {
                        earliestStart = startTimeInMillis;
                    }
                }
                let endTimeInMillis = new Date(event.end_time_dt_local).getTime();
                if (endTimeInMillis > latestEnd) {
                    if (endTimeInMillis > endOfDayInMillis) {
                        latestEnd = endOfDayInMillis;
                    } else {
                        latestEnd = endTimeInMillis;
                    }
                }
            })
            let earliestStartDate = new Date(earliestStart);
            let latestEndDate = new Date(latestEnd);
            return [earliestStartDate, latestEndDate]
        },
        getEventTypes(events) {
            let eventTypesToDisplay = [];
            events.forEach((event) => {
                let wantedEventType = this.event_types.data.find(x => x.id === event.event_type_id);
                if (!eventTypesToDisplay.includes(wantedEventType)) {
                    eventTypesToDisplay.push(wantedEventType);
                }
            })
            return eventTypesToDisplay;
        },
        closeAddEventModal() {
            this.addingEvent = false;
            this.addEventForm.eventType = null;
            this.addEventForm.name = '';
            this.addEventForm.start_time = null;
            this.addEventForm.end_time = null;
            this.addEventForm.description = '';
            this.addEventForm.occupancy_option = false;
            this.addEventForm.is_loud = false;
            this.addEventForm.audience = false;
            this.selectedRoom = null;
            this.addEventForm.project = null;
            this.selectedEventType = this.event_types.data[0];
        },
        addEvent() {
            this.addEventForm.event_type_id = this.selectedEventType.id;
            this.addEventForm.room_id = this.selectedRoom.id;
            if (this.assignProject) {
                if (this.creatingProject) {
                    this.addEventForm.project_name = this.newProjectName;
                } else if (this.selectedProject != null) {
                    this.addEventForm.project_id = this.selectedProject.id;
                }
            }

            this.addEventForm.post(route('events.store'), {});
            this.closeAddEventModal();
        },
        addProjectToEvent(project) {
            this.selectedProject = project;
            this.project_query = ""
        },
        deleteSelectedProject() {
            this.selectedProject = null;
        },
        activateHover(date, roomId) {
            this.showAddHoverDate = date;
            this.showAddHoverRoomId = roomId;
        },
        deactivateHover() {
            this.showAddHoverDate = null;
            this.showAddHoverRoomId = null;
        },
        openDayDetailModal(wantedDay) {
            this.wantedDay = wantedDay;
            this.showDayDetailModal = true;
        },
        closeDayDetailModal() {
            this.showDayDetailModal = false;
            this.wantedDay = null;
        },
        updateEvent(event) {
            if (event.name !== null) {
                this.updateEventForm.name = event.name;
            }
            this.updateEventForm.description = event.description;
            this.updateEventForm.start_time = event.start_time_dt_local;
            this.updateEventForm.end_time = event.end_time_dt_local;
            this.updateEventForm.occupancy_option = event.occupancy_option;
            this.updateEventForm.audience = event.audience;
            this.updateEventForm.is_loud = event.is_loud;
            this.updateEventForm.event_type_id = event.event_type_id;
            this.updateEventForm.room_id = event.room_id;
            this.updateEventForm.user_id = event.user_id;
            this.updateEventForm.project_id = event.project_id;
            this.updateEventForm.patch(route('events.update', {event: event.id}));
            this.closeDayDetailModal();
        }
    },
    watch: {
        project_query: {
            handler() {
                if (this.project_query.length > 0) {
                    axios.get('/projects/search', {
                        params: {query: this.project_query}
                    }).then(response => {
                        this.project_search_results = response.data
                    })
                }
            },
            deep: true
        }
    },
    data() {
        return {
            addingEvent: false,
            selectedEventType: this.event_types.data[0],
            assignProject: false,
            selectedProject: null,
            showAddHoverDate: null,
            showAddHoverRoomId: null,
            newProjectName: "",
            showDayDetailModal: false,
            wantedDay: null,
            selectedRoom: null,
            creatingProject: false,
            project_query: "",
            wantedArea: null,
            project_search_results: [],
            addEventForm: useForm({
                name: '',
                start_time: null,
                end_time: null,
                description: '',
                occupancy_option: false,
                is_loud: false,
                audience: false,
                room_id: null,
                project_id: null,
                event_type_id: null,
                project_name: null,
                user_id: this.$page.props.user.id,
            }),
            updateEventForm: useForm({
                name: '',
                start_time: null,
                end_time: null,
                description: '',
                occupancy_option: false,
                is_loud: false,
                audience: false,
                room_id: null,
                project_id: null,
                event_type_id: null,
                project_name: null,
                user_id: this.$page.props.user.id,
            }),

        }
    },
})
</script>
