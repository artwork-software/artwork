<template>
    <div v-if="rooms.length > 0" class="bg-backgroundGray pb-20">
        <div :class="calendarType === 'project' ? 'bg-backgroundGray' : 'bg-white' " class="flex flex-row">
            <div class="flex flex-1 flex-wrap">
                <div class="w-full flex my-auto justify-between">
                    <div class="flex flex-wrap items-center">
                        <div v-if="calendarType !== 'project'" class="flex items-center mb-4 ml-20 mt-10">
                            <h2 class="flex font-black font-lexend text-primary tracking-wide text-3xl">
                                Raumbelegungen</h2>
                            <div class="flex items-center"
                                 v-if="this.$page.props.can.admin_rooms || this.$page.props.is_admin || this.$page.props.can.admin_projects || this.$page.props.can.request_room_occupancy">
                                <button @click="openAddEventModal" type="button"
                                        class="flex mt-2 ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                    <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                                </button>
                                <div v-if="$page.props.can.show_hints" class="flex mt-2.5">
                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                    <span
                                        class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Frage neue Raumbelegungen an</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex w-full items-center ml-20">
                            <div v-if="calendarType !== 'project'" class="text-xl leading-6 font-bold font-lexend text-primary w-40">
                                {{ formattedMonth }}
                                {{ rooms[0].days_in_month[0].date_local.substring(0, 4) }}
                            </div>
                            <div class="text-xl leading-6 font-bold font-lexend text-primary w-56 flex items-center" v-else>
                                {{ this.first_start }} - {{ this.last_end }}
                            </div>
                            <div v-if="calendarType === 'main'" class="ml-2 flex items-center">
                                <Link
                                    :href="route('events.monthly_management',{month_start: new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) -2, 1, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'April' ? 60 : formattedMonth === 'November' ? -60 : 0) ),month_end:new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) -1, 0, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'April' ? 60 : formattedMonth === 'November' ? -60 : 0) )})">
                                    <ChevronLeftIcon class="h-5 w-5"/>
                                </Link>
                                <CalendarIcon @click="openChangeDateModal"
                                              class="h-6 w-6 cursor-pointer ml-2 mr-2"/>
                                <Link
                                    :href="route('events.monthly_management',{month_start: new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7), 1, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'März' ? -60 : formattedMonth === 'Oktober' ? 60 : 0) ),month_end:new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) - (-1), 0, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'März' ? -60 : formattedMonth === 'Oktober' ? 60 : 0) )})">
                                    <ChevronRightIcon class="h-5 w-5"/>
                                </Link>
                            </div>
                            <div v-if="calendarType === 'room'" class="ml-2 flex items-center">
                                <Link
                                    :href="route('rooms.show',{month_start: new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) -2, 1, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'April' ? 60 : formattedMonth === 'November' ? -60 : 0) ),month_end:new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) -1, 0, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'April' ? 60 : formattedMonth === 'November' ? -60 : 0) ), room:this.rooms[0], calendarType: 'monthly'})">
                                    <ChevronLeftIcon class="h-5 w-5"/>
                                </Link>
                                <CalendarIcon @click="openChangeDateModal"
                                              class="h-6 w-6 cursor-pointer ml-2 mr-2"/>
                                <Link
                                    :href="route('rooms.show',{month_start: new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7), 1, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'März' ? -60 : formattedMonth === 'Oktober' ? 60 : 0) ),month_end:new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) - (-1), 0, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'März' ? -60 : formattedMonth === 'Oktober' ? 60 : 0) ),room:this.rooms[0], calendarType: 'monthly'})">
                                    <ChevronRightIcon class="h-5 w-5"/>
                                </Link>
                            </div>
                            <div v-if="calendarType === 'project'" class="flex items-center">
                                <!--
                                This would be the Calendar+ Time change buttons, but now that we only show project timerange we dont need it
                                <Link
                                    :href="route('projects.show',{project: project_id,openTab:'calendar', month_start: new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) -2, 1, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'April' ? 60 : formattedMonth === 'November' ? -60 : 0) ),month_end:new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) -1, 0, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'April' ? 60 : formattedMonth === 'November' ? -60 : 0) ), project:this.projects[0], calendarType: 'monthly'})">
                                    <ChevronLeftIcon class="h-5 w-5"/>
                                </Link>
                                <CalendarIcon @click="openChangeDateModal"
                                              class="h-6 w-6 cursor-pointer ml-2 mr-2"/>
                                <Link
                                    :href="route('projects.show',{project: project_id,openTab:'calendar', month_start: new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7), 1, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'März' ? -60 : formattedMonth === 'Oktober' ? 60 : 0) ),month_end:new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) - (-1), 0, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'März' ? -60 : formattedMonth === 'Oktober' ? 60 : 0) ), project:this.projects[0], calendarType: 'monthly'})">
                                    <ChevronRightIcon class="h-5 w-5"/>
                                </Link>
                                -->
                            </div>
                            <div class="flex my-auto items-center ml-6 mt-5">
                                <Listbox v-if="this.rooms.length > 1" as="div"
                                         class="sm:col-span-3 mb-8 flex mr-4 items-center my-auto"
                                         v-model="wantedArea">
                                    <div class="relative">
                                        <ListboxButton
                                            :class="calendarType === 'project' ? 'bg-backgroundGray' : 'bg-white'"
                                            class=" flex ml-4 cursor-pointer relative w-full font-semibold pr-10 py-2 mt-4 text-left cursor-default focus:outline-none focus:ring-0 focus:ring-primary focus:border-primary sm:text-sm">
                                        <span v-if="wantedArea" class="block truncate items-center">
                                            <span>{{ wantedArea.name }}</span>
                                        </span>
                                            <span v-else class="block truncate items-center">
                                            <span>Alle Areale</span>
                                        </span>
                                            <span
                                                class="ml-2 inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </span>
                                        </ListboxButton>

                                        <transition leave-active-class="transition ease-in duration-100"
                                                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                                            <ListboxOptions
                                                class="absolute cursor-pointer z-10 mt-1 w-full bg-primary shadow-lg max-h-32 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                                <ListboxOption as="template" class="max-h-8" key="Alle Areale"
                                                               :value="null" v-slot="{active, selected}">
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
                                                               v-for="area in areas"
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
                                <Listbox as="div" class="sm:col-span-3 mb-8 mr-4 flex items-center my-auto"
                                         v-model="wantedEventType">
                                    <div class="relative">
                                        <ListboxButton
                                            :class="calendarType === 'project' ? 'bg-backgroundGray' : 'bg-white'"
                                            class="flex cursor-pointer relative w-full font-semibold pr-10 py-2 mt-4 text-left cursor-default focus:outline-none focus:ring-0 focus:ring-primary focus:border-primary sm:text-sm">
                                        <span v-if="wantedEventType" class="block truncate items-center">
                                            <span>{{ wantedEventType.name }}</span>
                                        </span>
                                            <span v-else class="block truncate items-center">
                                            <span>Alle Terminarten</span>
                                        </span>
                                            <span
                                                class="inset-y-0 ml-2 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </span>
                                        </ListboxButton>

                                        <transition leave-active-class="transition ease-in duration-100"
                                                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                                            <ListboxOptions
                                                class="absolute cursor-pointer z-10 mt-1 w-full bg-primary shadow-lg max-h-32 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                                <ListboxOption as="template" class="max-h-8"
                                                               key="Alle Raumbelegungen"
                                                               :value="null" v-slot="{active, selected}">
                                                    <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        Alle Terminarten
                                                    </span>
                                                        <span
                                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                                    </li>
                                                </ListboxOption>
                                                <ListboxOption as="template" class="max-h-8"
                                                               v-for="filter in eventTypeFilters"
                                                               :key="filter.name"
                                                               :value="filter"
                                                               v-slot="{ active, selected }">
                                                    <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ filter.name }}
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
                                <Listbox as="div" class="sm:col-span-3 mb-8 flex items-center my-auto"
                                         v-model="wantedAttribute">
                                    <div class="relative">
                                        <ListboxButton
                                            :class="calendarType === 'project' ? 'bg-backgroundGray' : 'bg-white'"
                                            class="flex cursor-pointer relative w-full font-semibold pr-10 py-2 mt-4 text-left cursor-default focus:outline-none focus:ring-0 focus:ring-primary focus:border-primary sm:text-sm">
                                        <span v-if="wantedAttribute" class="block truncate items-center">
                                            <span>{{ wantedAttribute.name }}</span>
                                        </span>
                                            <span v-else class="block truncate items-center">
                                            <span>Alle Eigenschaften</span>
                                        </span>
                                            <span
                                                class="ml-2 inset-y-0flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </span>
                                        </ListboxButton>

                                        <transition leave-active-class="transition ease-in duration-100"
                                                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                                            <ListboxOptions
                                                class="absolute cursor-pointer z-10 mt-1 w-full bg-primary shadow-lg max-h-32 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                                <ListboxOption as="template" class="max-h-8"
                                                               key="Alle Eigenschaften"
                                                               :value="null" v-slot="{active, selected}">
                                                    <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        Alle Eigenschaften
                                                    </span>
                                                        <span
                                                            :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                      <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                                    </li>
                                                </ListboxOption>
                                                <ListboxOption as="template" class="max-h-8"
                                                               v-for="filter in attributeFilters"
                                                               :key="filter.name"
                                                               :value="filter"
                                                               v-slot="{ active, selected }">
                                                    <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ filter.name }}
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
                                <div v-if="calendarType === 'project'" class="flex items-center my-auto mt-4">
                                    <div class="flex items-center"
                                         v-if="this.$page.props.can.admin_rooms || this.$page.props.is_admin || this.$page.props.can.admin_projects || this.$page.props.can.request_room_occupancy">
                                        <button @click="openAddEventModal" type="button"
                                                class="flex items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                            <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                                        </button>
                                        <div v-if="$page.props.can.show_hints" class="flex mt-2.5">
                                            <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                            <span
                                                class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Frage neue Raumbelegungen an</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-backgroundGray w-full flex pl-20">
                            <div class="mt-16 w-36">
                                <div @click="goToDailyCalendar(index)" v-for="(day,index) in days_this_month"
                                     class="cursor-pointer w-40 inline-flex mt-1 h-36 w-full text-secondary subpixel-antialiased">
                                    {{ day.date_formatted }}
                                </div>
                            </div>
                            <div class="flex">
                                <!-- Events without Room Column -->
                                <div class="bg-error border-r-8 border-white" v-if="events_without_room.count > 0">
                                    <div
                                        class="px-2 text-white uppercase cursor-pointer subpixel-antialiased mt-4 mb-4 ">
                                        Termine ohne Raum
                                    </div>
                                    <div v-for="day in events_without_room.days_in_month">
                                        <div @click="openDayDetailModal(day)"
                                             v-if="day.events.length > 0 && checkEventType(day.events) && checkAttribute(day.events)"
                                             :class="[{'stripes': day.events[0].occupancy_option }, 'bg-white m-0.5 h-36 mx-4 border border-gray-100 cursor-pointer']">
                                            <!-- If only 1 event on that day-->
                                            <div v-if="day.events.length === 1">

                                                <!-- Icons -->
                                                <div class="flex p-1 ml-1 mt-1">
                                                    <img src="/Svgs/IconSvgs/icon_public.svg"
                                                         v-if="day.events[0].audience"
                                                         class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                    <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="day.events[0].is_loud"
                                                         :class="day.events[0].audience ? 'ml-1' : ''"
                                                         class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                    <div v-if="!day.events[0].audience && !day.events[0].is_loud"
                                                         class="h-5 w-5">

                                                    </div>
                                                </div>
                                                <div>

                                                    <!-- Name of connected Project -->
                                                    <div
                                                        v-if="day.events[0].project_id !== null"
                                                        class="mt-1 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary truncate">
                                                        {{
                                                            projects.find(x => x.id === day.events[0].project_id).name
                                                        }}
                                                    </div>
                                                    <!-- Individual Eventname -->
                                                    <div v-if="day.events[0].name">
                                                        <div v-if="day.events[0].project_id !== null"
                                                             class="my-1 ml-2 text-xs flex font-lexend text-secondary truncate">
                                                            {{ day.events[0].name }}
                                                        </div>
                                                        <div v-else
                                                             class="mt-3 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary truncate">
                                                            {{ day.events[0].name }}
                                                        </div>
                                                    </div>
                                                    <!-- Time of Event -->
                                                    <div class="ml-2 text-sm text-secondary subpixel-antialiased">
                                                        {{ getTimespan(day)[0].toString().substring(16, 21) }}
                                                        - {{ getTimespan(day)[1].toString().substring(16, 21) }}
                                                    </div>

                                                    <!-- EventType -->
                                                    <div
                                                        :class="day.events[0].project_id !== null ? day.events[0].name ? 'mt-2' : 'mt-6' : 'mt-6'"
                                                        class="ml-2 mb-1">
                                                        <EventTypeIconCollection :height="20" :width="20"
                                                                                 :iconName="this.event_types.find(x => x.id === day.events[0].event_type_id).svg_name"/>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else-if="day.events.length > 1" class="relative">

                                                <div class="flex p-1 ml-1 mt-1">
                                                    <img src="/Svgs/IconSvgs/icon_public.svg"
                                                         v-if="day.events.some(x => x.audience === true)"
                                                         class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                    <img src="/Svgs/IconSvgs/icon_loud.svg"
                                                         v-if="day.events.some(x => x.is_loud === true)"
                                                         :class="day.events.some(x => x.audience === true) ? 'ml-1' : ''"
                                                         class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                    <div v-else class="h-5 w-5">
                                                        <!-- placeholder for design purposes -->
                                                    </div>
                                                    <div v-if="day.conflicts.length > 0"
                                                         class="h-5 flex right-0 top-0 bg-error items-center absolute">
                                                        <img src="/Svgs/IconSvgs/icon_warning_white.svg"
                                                             class="h-4 w-4 ml-1 flex text-white"
                                                             aria-hidden="true"/>
                                                        <span class="text-white ml-1 flex items-center mr-0.5">
                                                                {{ day.conflicts.length }}
                                                            </span>
                                                    </div>
                                                </div>

                                                <div
                                                    class="mt-2 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary">
                                                    {{ day.events.length }} Termine
                                                </div>

                                                <div class="ml-2 text-sm text-secondary subpixel-antialiased">
                                                    {{ getTimespan(day)[0].toString().substring(16, 21) }}
                                                    - {{ getTimespan(day)[1].toString().substring(16, 21) }}
                                                </div>

                                                <div class="mt-6 ml-2 flex">
                                                    <div class="-mr-1.5 ring-white ring-2 rounded-full"
                                                         v-for="eventType in this.getEventTypes(day.events)">
                                                        <EventTypeIconCollection
                                                            class="rounded-full ring-2 ring-white" :height="20"
                                                            :width="20"
                                                            :iconName="eventType.svg_name"/>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        <div v-else
                                             class="m-0.5 h-36 mr-4 w-44 flex">
                                        </div>
                                    </div>

                                </div>
                                <div v-if="this.roomsToShow.length > 0"
                                     v-for="room in roomsToShow.sort((a,b) => a.area_id - b.area_id)"
                                     class="inline-flex flex-col pl-3"
                                     :class="room.area_id !== getLastRoom().area_id ? 'border-l-8 border-white' : ''">
                                    <Link
                                        :href="route('rooms.show',{room: room.id,month_start: new Date((new Date).getFullYear(),(new Date).getMonth(),1,0,120),month_end:new Date((new Date).getFullYear(),(new Date).getMonth() + 1,2), calendarType: 'monthly'})"
                                        class="uppercase text-secondary cursor-pointer subpixel-antialiased mt-4 mb-4 ">
                                        {{ room.name }}
                                    </Link>
                                    <div v-for="day in room.days_in_month">
                                        <div @click="openDayDetailModal(day)"
                                             v-if="day.events.length > 0 && checkEventType(day.events) && checkAttribute(day.events)"
                                             :class="[{'stripes': day.events[0].occupancy_option }, 'bg-white m-0.5 h-36 w-44 mr-4 border border-gray-100 cursor-pointer']">
                                            <!-- If only 1 event on that day-->
                                            <div
                                                v-if="day.events.length === 1">

                                                <!-- Icons -->
                                                <div class="flex p-1 ml-1 mt-1">
                                                    <img src="/Svgs/IconSvgs/icon_public.svg"
                                                         v-if="day.events[0].audience"
                                                         class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                    <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="day.events[0].is_loud"
                                                         :class="day.events[0].audience ? 'ml-1' : ''"
                                                         class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                    <div v-if="!day.events[0].audience && !day.events[0].is_loud"
                                                         class="h-5 w-5">

                                                    </div>
                                                </div>
                                                <div>
                                                    <!-- Name of connected Project -->
                                                    <div
                                                        v-if="day.events[0].project_id !== null && projects.find(x => x.id === day.events[0].project_id) && this.calendarType !== 'project'"
                                                        class="mt-1 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary truncate mr-3">
                                                        {{
                                                            projects.find(x => x.id === day.events[0].project_id).name
                                                        }}
                                                    </div>
                                                    <!-- Individual Eventname -->
                                                    <div v-if="day.events[0].name">
                                                        <div
                                                            v-if="day.events[0].project_id !== null && this.calendarType !== 'project'"
                                                            class="my-1 ml-2 text-xs flex font-lexend text-secondary truncate mr-3">
                                                            {{ day.events[0].name }}
                                                        </div>
                                                        <div v-else
                                                             class="mt-3 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary truncate mr-3">
                                                            {{ day.events[0].name }}
                                                        </div>
                                                    </div>
                                                    <div v-else
                                                         class="mt-3 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary truncate mr-3">
                                                        {{ this.event_types.find(x => x.id === day.events[0].event_type_id).name }}
                                                    </div>
                                                    <!-- Time of Event -->
                                                    <div class="ml-2 text-sm text-secondary subpixel-antialiased">
                                                        {{ getTimespan(day)[0].toString().substring(16, 21) }}
                                                        - {{ getTimespan(day)[1].toString().substring(16, 21) }}
                                                    </div>

                                                    <!-- EventType -->
                                                    <div
                                                        :class="day.events[0].project_id !== null ? day.events[0].name ? 'mt-2' : 'mt-6' : 'mt-6'"
                                                        class="ml-2 mb-1">
                                                        <EventTypeIconCollection :height="20" :width="20"
                                                                                 :iconName="this.event_types.find(x => x.id === day.events[0].event_type_id).svg_name"/>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else-if="day.events.length > 1" class="relative">

                                                <div class="flex p-1 ml-1 mt-1">
                                                    <img src="/Svgs/IconSvgs/icon_public.svg"
                                                         v-if="day.events.some(x => x.audience === true)"
                                                         class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                    <img src="/Svgs/IconSvgs/icon_loud.svg"
                                                         v-if="day.events.some(x => x.is_loud === true)"
                                                         :class="day.events.some(x => x.audience === true) ? 'ml-1' : ''"
                                                         class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                    <div v-else class="h-5 w-5">
                                                        <!-- placeholder for design purposes -->
                                                    </div>
                                                    <div
                                                        class="h-5 flex right-0 top-0 bg-error items-center absolute">
                                                        <img src="/Svgs/IconSvgs/icon_warning_white.svg"
                                                             class="h-4 w-4 ml-1 flex text-white"
                                                             aria-hidden="true"/>
                                                        <span class="text-white ml-1 flex items-center mr-0.5">
                                                                {{ day.conflicts.length }}
                                                            </span>
                                                    </div>
                                                </div>

                                                <div
                                                    class="mt-2 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary">
                                                    {{ day.events.length }} Termine
                                                </div>

                                                <div class="ml-2 text-sm text-secondary subpixel-antialiased">
                                                    {{ getTimespan(day)[0].toString().substring(16, 21) }}
                                                    - {{ getTimespan(day)[1].toString().substring(16, 21) }}
                                                </div>

                                                <div class="mt-6 ml-2 flex">
                                                    <div class="-mr-1.5 ring-white ring-2 rounded-full"
                                                         v-for="eventType in this.getEventTypes(day.events)">
                                                        <EventTypeIconCollection
                                                            class="rounded-full ring-2 ring-white" :height="20"
                                                            :width="20"
                                                            :iconName="eventType.svg_name"/>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <div
                                            v-else-if="day.events.length < 1 && (this.$page.props.can.admin_rooms || this.$page.props.is_admin || this.$page.props.can.admin_projects || this.$page.props.can.request_room_occupancy)">
                                            <div @mouseover="activateHover(day.date_local, room.id)"
                                                 @click="openAddEventModal(room.id)"
                                                 @mouseout="deactivateHover()"
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
                                        <div v-else class="h-36 w-44">

                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
    <div v-else>
        <div class="flex items-center ml-14 mt-6"
             v-if="this.$page.props.can.admin_rooms || this.$page.props.is_admin || this.$page.props.can.admin_projects || this.$page.props.can.request_room_occupancy">
            <button @click="openAddEventModal" type="button"
                    class="flex mt-2 ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
            </button>
            <div v-if="$page.props.can.show_hints" class="flex mt-2.5">
                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                <span
                    class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Frage neue Raumbelegungen an</span>
            </div>
        </div>
        <div class="ml-20 mt-10 text-secondary subpixel-antialiased text-xs">
            Bisher wurden keine Termine für dieses Projekt angelegt.
        </div>
    </div>

    <!-- Termin erstellen Modal-->
    <jet-dialog-modal :show="addingEvent" @close="closeAddEventModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_appointment_new.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="font-black font-lexend text-primary tracking-wide text-3xl my-2">
                    Neue Raumbelegung
                </div>
                <XIcon @click="closeAddEventModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-secondary subpixel-antialiased">
                    Bitte beachte, dass du Vor- und Nachbereitungszeit einplanst.
                </div>
                <div v-if="$page.props.can.show_hints" class="mt-6 flex">
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
                                        class="ml-2 inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                                </div>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions
                                    class="absolute w-56 z-10 mt-1 bg-primary shadow-lg max-h-32 pl-1 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-for="eventType in event_types"
                                                   :key="eventType.name"
                                                   :value="eventType"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <EventTypeIconCollection :height="20" :width="20"
                                                                     :iconName="eventType.svg_name"/>
                                            <span
                                                :class="[selected ? 'font-bold text-white' : 'font-normal', 'ml-4 block truncate']">
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
                                <div class="flex items-center my-auto justify-end">
                                        <span v-if="selectedRoom" class="block truncate items-center flex mr-2">
                                            <span>{{ selectedRoom.name }}</span>

                                        </span>
                                    <span v-if="!selectedRoom"
                                          class="block truncate">Raum definieren*</span>
                                    <span
                                        class="inset-y-0 flex items-center pr-2 pointer-events-none">
                                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                         </span>
                                </div>
                            </ListboxButton>
                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions
                                    class="absolute w-56 z-10 mt-1 bg-primary shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                    <div v-for="(area,index) in areas">
                                        <p :class="index === 0 ? 'mt-1': 'mt-4'"
                                           class="text-secondary text-sm uppercase ml-3 subpixel-antialiased cursor-pointer">
                                            {{ area.name }}</p>
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="room in area.rooms"
                                                       :key="room.name"
                                                       :value="room"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ room.name }}
                                                    </span>
                                                <span
                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                        <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                   aria-hidden="true"/>

                                                        <img src="/Svgs/IconSvgs/icon_warning_white.svg"
                                                             v-if="(room.conflicts_start_time.length > 0 || room.conflicts_end_time.length > 0) && (addEventForm.start_time !== null || addEventForm.end_time !== null)"
                                                             class="h-4 w-4 ml-1 flex text-error"
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
                <div class="flex mt-6" v-if="!selectedEventType.project_mandatory">
                    <input v-model="assignProject"
                           type="checkbox"
                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                    <p :class="[assignProject ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                       class="ml-4 my-auto text-sm">Termin einem Projekt zuordnen</p>
                </div>
                <div v-if="assignProject || selectedEventType.project_mandatory">
                    <div class="flex items-center mt-4">
                        <Switch @click="newProjectName = ''" v-model="creatingProject"
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
                    <div class="flex mt-4" v-if="creatingProject">
                        <input type="text" v-model="newProjectName"
                               placeholder="Projektname von neuem Projekt*"
                               class="text-primary h-10 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full text-sm border-gray-300 "/>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap"
                     v-if="(assignProject || selectedEventType.project_mandatory) && !creatingProject">
                    <div class="my-auto w-full" v-if="this.selectedProject === null">
                        <input id="projectSearch" v-model="project_query" type="text" autocomplete="off"
                               class="text-primary h-10 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 w-full text-sm"
                               placeholder="Zu welchem bestehendem Projekt zuordnen?*"
                               :disabled="this.selectedProject"/>
                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <div v-if="project_search_results.length > 0 && project_query.length > 0"
                                 class="absolute z-10 inset-x-0 mx-10 max-h-60 bg-primary shadow-lg
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
                <div class="mt-4" v-if="!selectedEventType.project_mandatory">
                    <input v-if="selectedEventType.individual_name && !selectedProject && !newProjectName" type="text"
                           v-model="addEventForm.name" placeholder="Terminname*"
                           class="text-primary h-10 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 w-full text-sm"/>
                    <input v-else type="text" v-model="addEventForm.name" placeholder="Terminname"
                           class="text-primary h-10 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 w-full text-sm"/>
                </div>
                <div class="flex mt-4 items-center">
                    <div v-if="conflictData">
                        <div v-if="conflictData.length > 0" class="bg-error absolute left-0 flex p-1 -mt-2 mr-0.5">
                            <img src="/Svgs/IconSvgs/icon_warning_white.svg"
                                 class="h-8 w-8 p-1 my-auto flex text-white"
                                 aria-hidden="true"/>
                        </div>
                    </div>
                    <div class="text-secondary mr-2">
                        <label for="startTime" class="text-xs subpixel-antialiased">Terminstart*</label>
                        <input
                            @blur="validateStartTime(addEventForm)"
                            v-model="addEventForm.start_time" id="startTime"
                            placeholder="Terminstart" type="datetime-local"
                            class="placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 text-primary placeholder-secondary mr-2 w-full"/>
                    </div>
                    <div class="text-secondary ml-2">
                        <label for="endTime" class="text-xs subpixel-antialiased">Terminende*</label>
                        <input
                            @blur="validateEndTime(addEventForm)"
                            v-model="addEventForm.end_time" id="endTime"
                            placeholder="Zu erledigen bis?" type="datetime-local"
                            class="placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 text-primary placeholder-secondary w-full"/>
                    </div>
                </div>

                <div class="mt-1" v-if="conflictData !== null">
                    <div v-if="this.conflictData.length === 1 && this.conflictData[0].event_type"
                         class="text-error subpixel-antialiased text-sm flex">
                        Dieser Termin kollidiert mit "{{ this.conflictData[0].event_type.name }}"
                        <div class="flex ml-1" v-if="this.conflictData[0].project"> von
                            <Link
                                :href="route('projects.show',{project: this.conflictData[0].project.id, month_start: new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7), 1, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'März' ? -60 : formattedMonth === 'Oktober' ? 60 : 0) ),month_end:new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) - (-1), 0, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'März' ? -60 : formattedMonth === 'Oktober' ? 60 : 0) ), calendarType: 'monthly'})"
                                class="font-black flex cursor-pointer ml-1">
                                {{ this.conflictData[0].project.name }}
                            </Link>
                        </div>
                        <div class="flex ml-2" v-if="this.conflictData[0].event_name !== null">
                            mit Namen "{{ this.conflictData[0].event_name }}"
                        </div>
                    </div>
                    <div class="text-error subpixel-antialiased text-sm flex" v-else-if="this.conflictData.length > 1">
                        Dieser Termin kollidiert mit {{ this.conflictData.length }} anderen Terminen.
                    </div>
                </div>

                <div class="mt-1" v-if="startTimeError">
                    <div class="text-error subpixel-antialiased text-sm flex">
                        {{ startTimeError.start_time }}
                    </div>
                </div>
                <div class="flex mt-4 items-center">
                    <div class="flex items-center">
                        <input v-model="addEventForm.audience"
                               type="checkbox"
                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                        <img src="/Svgs/IconSvgs/icon_public.svg" class="h-5 w-5 ml-2 my-auto mt-1"
                             :class="[addEventForm.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                        <p :class="[addEventForm.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                           class="ml-1.5 mt-1.5 text-xs subpixel-antialiased text-secondary">Publikum</p>
                    </div>
                    <div class="flex ml-12">
                        <input v-model="addEventForm.is_loud"
                               type="checkbox"
                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                        <img src="/Svgs/IconSvgs/icon_loud.svg" class="h-5 w-5 ml-2 my-auto"
                             :class="[addEventForm.is_loud ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                        <p :class="[addEventForm.is_loud ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                           class="ml-1.5 mt-1.5 text-xs subpixel-antialiased text-secondary">Es wird laut</p>
                    </div>
                </div>
                <div class="mt-4">
                        <textarea placeholder="Was gibt es bei dem Termin zu beachten?"
                                  v-model="addEventForm.description" rows="4"
                                  class="resize-none shadow-sm p-4 focus:outline-none placeholder-secondary placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 block w-full sm:text-sm border"/>
                </div>
                <div>
                    <div v-if="selectedRoom">
                        <div
                            v-if="selectedRoom.room_admins.find(user => user.id === this.$page.props.user.id) || this.$page.props.is_admin || this.$page.props.can.admin_rooms">
                            <button :class="[startTimeError || this.addEventForm.start_time === null || this.addEventForm.end_time === null || this.selectedRoom === null || (selectedEventType.project_mandatory && selectedProject === null && newProjectName === '') || ((addEventForm.name === '' && selectedEventType.individual_name) && newProjectName === '' && selectedProject === null) ?
                                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                    class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                    @click="addEvent(false)"
                                    :disabled="addEventForm.start_time === null || addEventForm.end_time === null || (selectedEventType.project_mandatory && selectedProject === null && newProjectName === '') || ((addEventForm.name === '' && selectedEventType.individual_name) && newProjectName === '' && selectedProject === null) || startTimeError">
                                Belegen
                            </button>
                        </div>
                    </div>
                    <div
                        v-if="!selectedRoom || !selectedRoom.room_admins.find(user => user.id === this.$page.props.user.id) && !$page.props.is_admin">
                        <button :class="[startTimeError || this.addEventForm.start_time === null || this.addEventForm.end_time === null || this.selectedRoom === null ||(selectedEventType.project_mandatory && selectedProject === null && newProjectName === '') || (addEventForm.name === '' && newProjectName === '' && selectedProject === null) ?
                                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-4 flex items-center px-12 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="addEvent(true)"
                                :disabled="addEventForm.start_time === null
                                || addEventForm.end_time === null || (selectedEventType.project_mandatory
                                && selectedProject === null && newProjectName === '') || ((addEventForm.name === '' && selectedEventType.individual_name)
                                && newProjectName === ''
                                && selectedProject === null)
                                || startTimeError">
                            Raum anfragen
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <!-- TagesDetail-Modal -->
    <jet-dialog-modal :show="showDayDetailModal" @close="closeDayDetailModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8"/>
            <XIcon @click="closeDayDetailModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div v-for="(event,index) in wantedDay.events"
                 :class="wantedDay.events.length -1 === index ? '' : 'border-b-2'" class="mx-4 pb-8">
                <div>
                    <div class="mt-2 flex items-center w-full">
                        <div v-if="hasConflict(event.id)" class="bg-error absolute left-0 flex h-8 w-8 mt-4 mr-2">
                            <img src="/Svgs/IconSvgs/icon_warning_white.svg"
                                 class="h-8 w-8 p-1 my-auto flex text-white"
                                 aria-hidden="true"/>
                        </div>
                        <Listbox
                            v-if="checkProjectPermission(event.project_id,this.$page.props.user.id) || this.$page.props.is_admin || event.created_by.id === this.$page.props.user.id"
                            as="div"
                            class="flex w-full" v-model="event.event_type_id">
                            <div class="relative">
                                <ListboxButton
                                    class="bg-white w-full relative mt-4 py-2 cursor-pointer focus:outline-none">
                                    <div class="flex items-center">
                                        <EventTypeIconCollection :height="24" :width="24"
                                                                 :iconName="event_types.find(x => x.id === event.event_type_id).svg_name"/>
                                        <span class="block truncate items-center text-3xl font-black ml-3 flex">
                                                <span>
                                                    {{ event_types.find(x => x.id === event.event_type_id).name }}
                                                </span>
                                            </span>
                                        <span
                                            class="ml-2 inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                                <ChevronDownIcon class="h-6 w-6 text-primary font-black"
                                                                 aria-hidden="true"/>
                                            </span>
                                    </div>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-full z-10 mt-1 bg-primary shadow-lg max-h-32 pl-1 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="eventType in event_types"
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
                        <div v-else
                             class="bg-white w-full relative mt-4 py-2 focus:outline-none flex items-center">
                            <EventTypeIconCollection :height="24" :width="24"
                                                     :iconName="event_types.find(x => x.id === event.event_type_id).svg_name"/>
                            <span class="block truncate items-center text-3xl font-black ml-3 flex">
                                        <span>
                                            {{ event_types.find(x => x.id === event.event_type_id).name }}
                                        </span>
                                    </span>
                        </div>
                        <div class="flex justify-end"
                             v-if="checkProjectPermission(event.project_id,this.$page.props.user.id) || this.$page.props.is_admin || this.$page.props.can.admin_rooms || event.created_by.id === this.$page.props.user.id">
                            <Menu as="div" class="my-auto w-full relative">
                                <div class="flex justify-end">
                                    <MenuButton
                                        class="flex mt-4">
                                        <DotsVerticalIcon class="flex flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                          aria-hidden="true"/>
                                    </MenuButton>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="origin-top-right absolute z-40 right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                        <div class="py-1">
                                            <MenuItem
                                                v-if="event.occupancy_option && (rooms.find(room => room.id === event.room_id).room_admins.find(admin => admin.id === this.$page.props.user.id) || this.$page.props.is_admin || this.$page.props.can.admin_rooms)"
                                                v-slot="{ active }">
                                                <a href="#" @click="approveRequest(event)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <PencilAltIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Raumbelegung zusagen
                                                </a>
                                            </MenuItem>
                                            <MenuItem
                                                v-if="event.occupancy_option && (rooms.find(room => room.id === event.room_id).room_admins.find(admin => admin.id === this.$page.props.user.id) || this.$page.props.is_admin || this.$page.props.can.admin_rooms)"
                                                v-slot="{ active }">
                                                <a href="#" @click="declineRequest(event)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <PencilAltIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Raumbelegung absagen
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a href="#" @click="deleteEvent(event.id)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <TrashIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Termin löschen
                                                </a>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center justify-between">
                            <div v-if="event.project_id !== null" class="flex items-center w-2/3 text-sm">
                                <div class="my-auto flex w-28">Zugeordnet zu</div>
                                <div>
                                    <Link
                                        :href="route('projects.show',{project: event.project_id,openTab: 'calendar', month_start: new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7), 1, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'März' ? -60 : formattedMonth === 'Oktober' ? 60 : 0) ),month_end:new Date(rooms[0].days_in_month[0].date_local.substring(0,4),rooms[0].days_in_month[0].date_local.substring(5,7) - (-1), 0, 0,0 - new Date(rooms[0].days_in_month[0].date_local).getTimezoneOffset() - (formattedMonth === 'März' ? -60 : formattedMonth === 'Oktober' ? 60 : 0) ), calendarType: 'monthly'})"
                                        class="ml-3 text-md flex font-bold font-lexend text-primary">
                                        {{ projects.find(x => x.id === event.project_id).name }}
                                    </Link>
                                </div>
                            </div>
                            <div v-else class="flex font-lexend text-secondary subpixel-antialiased text-sm">
                                <div>Keinem Projekt zugeordnet</div>
                            </div>
                            <div
                                v-if="checkProjectPermission(event.project_id,this.$page.props.user.id) || this.$page.props.can.admin_rooms || this.$page.props.is_admin || (this.myRooms ? this.myRooms.length > 0 : false) || event.created_by.id === this.$page.props.user.id"
                                class="w-1/3">
                                <Listbox as="div" class="flex items-center my-auto w-full " v-model="event.room_id">
                                    <div class="relative w-full">
                                        <ListboxButton
                                            class="bg-white w-full relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                            <div class="flex items-center my-auto justify-end">
                                        <span v-if="event.room_id" class="block truncate items-center flex">
                                            <span>{{ allRooms.find(x => x.id === event.room_id).name }}</span>

                                        </span>
                                                <span v-if="!event.room_id"
                                                      class="block truncate">Raum definieren*</span>
                                                <span
                                                    class="ml-2 inset-y-0 flex items-center pr-2 pointer-events-none">
                                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                         </span>
                                            </div>
                                        </ListboxButton>
                                        <transition leave-active-class="transition ease-in duration-100"
                                                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                                            <ListboxOptions
                                                class="absolute z-10 mt-1 bg-primary shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                                <div v-for="(area,index) in areas">
                                                    <p :class="index === 0 ? 'mt-1': 'mt-4'"
                                                       class="text-secondary text-sm uppercase ml-3 subpixel-antialiased cursor-pointer">
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
                            <div v-else class="flex items-center my-auto">
                                        <span v-if="event.room_id" class="block truncate items-center flex">
                                            <span>{{ allRooms.find(x => x.id === event.room_id).name }}</span>

                                        </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex font-lexend text-secondary subpixel-antialiased text-xs my-auto">
                        <div class="my-auto">angelegt von:</div>
                        <img v-if="event.created_by.profile_photo_url"
                             :data-tooltip-target="event.created_by.id"
                             :src="event.created_by.profile_photo_url"
                             :alt="event.created_by.name"
                             class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                        <div class="flex ml-2 my-auto">
                            {{ event.created_by.first_name }} {{ event.created_by.last_name }}
                        </div>
                    </div>
                    <div>
                        <div class="mt-4 w-full"
                             v-if="checkProjectPermission(event.project_id,this.$page.props.user.id) || this.$page.props.is_admin || this.$page.props.can.admin_rooms || event.created_by.id === this.$page.props.user.id">
                            <input type="text" v-model="event.name" placeholder="Terminname"
                                   class="text-primary font-black h-10 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full text-sm border-gray-300 "/>
                        </div>
                        <div v-else>
                            <div class="w-full font-bold font-lexend text-primary tracking-wide text-xl my-2">
                                {{ event.name }}
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="checkProjectPermission(event.project_id,this.$page.props.user.id) || this.$page.props.is_admin || this.$page.props.can.admin_rooms || event.created_by.id === this.$page.props.user.id"
                        class="flex mt-4">
                        <div class="text-secondary mr-2">
                            <label for="startDate" class="text-xs subpixel-antialiased">Terminstart*</label>
                            <input
                                v-model="event.start_time_dt_local" id="startDate"
                                placeholder="Terminstart" type="datetime-local"
                                class="border-gray-300 text-primary placeholder-secondary mr-2 w-full"/>
                        </div>
                        <div class="text-secondary ml-2">
                            <label for="endDate" class="text-xs subpixel-antialiased">Terminende*</label>
                            <input
                                v-model="event.end_time_dt_local" id="endDate"
                                placeholder="Zu erledigen bis?" type="datetime-local"
                                class="border-gray-300 text-primary placeholder-secondary w-full"/>
                        </div>
                    </div>
                    <div v-else class="mt-4 subpixel-antialiased">
                        {{
                            event.start_time.split('-')[2].split(' ')[0]
                        }}.{{
                            event.start_time.toLocaleString().split('-')[1]
                        }}.{{ event.start_time.toLocaleString().split('-')[0] }},
                        {{ event.start_time.split('-')[2].split(' ')[1] }} -
                        {{
                            event.end_time.split('-')[2].split(' ')[0]
                        }}.{{
                            event.end_time.toLocaleString().split('-')[1]
                        }}.{{ event.end_time.toLocaleString().split('-')[0] }},
                        {{ event.end_time.split('-')[2].split(' ')[1] }}
                    </div>
                    <div
                        v-if="checkProjectPermission(event.project_id,this.$page.props.user.id) || this.$page.props.is_admin || event.created_by.id === this.$page.props.user.id"
                        class="flex mt-4 items-center">
                        <div class="flex items-center">
                            <input v-model="event.audience"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <img src="/Svgs/IconSvgs/icon_public.svg" class="h-5 w-5 ml-2 mt-1"
                                 :class="[event.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                            <p :class="[event.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1.5 mt-1.5 text-sm text-xs subpixel-antialiased text-secondary">Publikum</p>
                        </div>
                        <div class="flex ml-12">
                            <input v-model="event.is_loud"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <img src="/Svgs/IconSvgs/icon_loud.svg" class="h-5 w-5 ml-2 my-auto"
                                 :class="[event.is_loud ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                            <p :class="[event.is_loud ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1.5 mt-1.5 text-xs subpixel-antialiased text-secondary">Es wird laut</p>
                        </div>
                    </div>
                    <div
                        v-if="checkProjectPermission(event.project_id,this.$page.props.user.id) || this.$page.props.can.admin_rooms || this.$page.props.is_admin || (this.myRooms ? this.myRooms.length > 0 : false) || event.created_by.id === this.$page.props.user.id">
                        <div class="mt-4">
                            <textarea placeholder="Was gibt es bei dem Termin zu beachten?"
                                      v-model="event.description" rows="4"
                                      class="resize-none font-black shadow-sm placeholder-secondary p-4 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 block w-full sm:text-sm border"/>
                        </div>
                        <div>
                            <button
                                v-if="!rooms.find(room => room.id === event.room_id) || rooms.find(room => room.id === event.room_id) && (rooms.find(room => room.id === event.room_id).room_admins.find(user => user.id === this.$page.props.user.id) || this.$page.props.is_admin)"
                                :class="[event.start_time === null || event.end_time === null || event.selectedRoom === null ?
                                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase tracking-wider shadow-sm text-secondaryHover"
                                @click="updateEvent(event,false)"
                                :disabled="event.start_time === null && event.end_time === null">
                                Speichern
                            </button>
                        </div>
                        <div
                            v-if="rooms.find(room => room.id === event.room_id) ? (!rooms.find(room => room.id === event.room_id).room_admins.find(user => user.id === this.$page.props.user.id) && !this.$page.props.is_admin) : false">
                            <button :class="[event.start_time === null || event.end_time === null || event.selectedRoom === null ?
                                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                    class="mt-4 flex items-center px-12 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                    @click="updateEvent(event,true)"
                                    :disabled="event.start_time === null || event.end_time === null || event.selectedRoom === null">
                                Raum anfragen
                            </button>
                        </div>
                    </div>
                    <div v-else class="subpixel-antialiased mt-4">
                        {{ event.description }}
                    </div>
                </div>


            </div>
        </template>
    </jet-dialog-modal>
    <!-- Datum ändern Modal -->
    <jet-dialog-modal :show="showChangeDateModal" @close="closeChangeDateModal">
        <template #content>
            <div class="mx-4">
                <div class="font-black font-lexend text-primary text-3xl my-2">
                    Zeitrahmen auswählen
                </div>
                <XIcon @click="closeChangeDateModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div>
                    <Listbox as="div" class="sm:col-span-3 mb-8 flex mr-4 items-center my-auto"
                             v-model="wantedDateType">
                        <div class="relative">
                            <ListboxButton
                                class="cursor-pointer bg-white relative w-full font-semibold pr-20 py-2 mt-4 text-left cursor-default focus:outline-none focus:ring-0 focus:ring-primary focus:border-primary sm:text-sm">
                                        <span v-if="wantedDateType" class="block truncate items-center">
                                            <span>{{ wantedDateType.name }}</span>
                                        </span>
                                <span v-else class="block truncate items-center">
                                            <span>Ansicht auswählen</span>
                                        </span>
                                <span
                                    class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </span>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions
                                    class="absolute cursor-pointer z-10 mt-1 w-full bg-primary shadow-lg max-h-32 rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-for="dateType in this.dateTypes"
                                                   :key="dateType.name"
                                                   :value="dateType"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ dateType.name }}
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
                <div v-if="wantedDateType.id === 1">
                    <div class="flex mt-4">
                        <div class="text-secondary mr-2">
                            <label for="changeStartDate">Start-Datum</label>
                            <input
                                v-model="wantedStartDate" id="changeStartDate"
                                placeholder="Terminstart" type="date"
                                class="border-gray-300 text-primary placeholder-secondary mr-2 w-full"/>
                        </div>
                        <div class="text-secondary ml-2">
                            <label for="changeEndDate">End-Datum</label>
                            <input
                                v-model="wantedEndDate" id="changeEndDate"
                                placeholder="Zu erledigen bis?" type="date"
                                class="border-gray-300 text-primary placeholder-secondary w-full"/>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <Datepicker v-model="wantedDayDate" locale="de" inline autoApply
                                :enableTimePicker="false"></Datepicker>
                </div>
                <div class="flex justify-between mt-6">
                    <button :class="[wantedDateType.id === 1 && (wantedStartDate === null || wantedEndDate === null) ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']" class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="changeWantedDate()">
                        Anzeigen
                    </button>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>
<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'

import {
    AdjustmentsIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    DotsVerticalIcon,
    XIcon,
    PencilAltIcon,
    TrashIcon
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
import {Inertia} from "@inertiajs/inertia";
import UserTooltip from "@/Layouts/Components/UserTooltip";

const attributeFilters = [
    {name: 'Nur Anfragen', id: 1},
    {name: 'Nur laute Termine', id: 2},
    {name: 'Nur Termine mit Publikum', id: 3}
]

const dateTypes = [
    {name: 'Monatsansicht', id: 1},
    {name: 'Tagesansicht', id: 2}
]
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
        Switch,
        ChevronLeftIcon,
        ChevronRightIcon,
        CalendarIcon,
        Datepicker,
        PencilAltIcon,
        TrashIcon,
        UserTooltip

    },
    props: ['first_start','last_end','calendarType', 'event_types', 'areas', 'month_events', 'projects', 'myRooms', 'rooms', 'days_this_month', 'events_without_room', 'requested_start_time', 'requested_end_time', 'start_time_of_new_event', 'end_time_of_new_event', 'project_id'],
    computed: {
        allRooms: function () {
            let allRoomsArray = [];
            this.areas.forEach((area) => {
                area.rooms.forEach((room) => {
                    allRoomsArray.push(room);
                })
            })
            return allRoomsArray;
        },
        formattedMonth: function () {
            switch (this.rooms[0].days_in_month[0].date_local.slice(5, 7)) {
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
        eventTypeFilters: function () {
            let filters = [];
            this.event_types.forEach((eventType) => {
                filters.push({eventTypeId: eventType.id, name: eventType.name});
            })
            return filters;
        },
        roomsToShow: function () {
            let roomsCopy = this.rooms.slice();
            if (this.wantedArea) {
                return roomsCopy.filter(room => room.area_id === this.wantedArea.id)
            }
            return this.rooms

        },
    },
    methods: {
        checkProjectPermission(wantedProjectId, userId) {
            if (wantedProjectId) {
                return (this.projects.find(project => project.id === wantedProjectId).project_admins.find(admin => admin.id === userId) || this.projects.find(project => project.id === wantedProjectId).project_managers.find(admin => admin.id === userId)) || this.$page.props.is_admin
            } else {
                return false;
            }

        },
        async getStartTimeConflicts() {
            if (this.selectedRoom) {

                await axios.get(`/room/${this.selectedRoom.id}/start_time_conflicts`, {
                    params: {
                        start_time: this.addEventForm.start_time
                    }
                }).then(response => {
                    if (this.conflictData === null) {
                        if (response.data !== null && response.data.length !== 0) {
                            this.conflictData = response.data;
                        }
                    } else {
                        if (response.data !== null && response.data.length !== 0) {
                            this.conflictData.push(response.data);
                        }
                    }
                });

            } else {
                /* hier gibt es einen Fehler wegen falschem Parameter für die Route -> soll überarbeitet werden, deswegen erstmal kein fix
                Code hier ist dazu da um in der Listbox zur Auswahl des Raumes richtig anzuzeigen,ob der Raum zu den gewählten Zeitpunkten belegt ist.
                if (this.calendarType === 'project') {
                    console.log(this.project_id);
                    Inertia.get(route('projects.show'), {
                        project: this.project_id,
                        calendarType: 'monthly',
                        month_start: this.requested_start_time,
                        month_end: this.requested_end_time,
                        start_time: this.addEventForm.start_time
                    }, {only: ['areas'], preserveState: true});
                } else {
                    Inertia.get(route('events.monthly_management'), {
                        month_start: this.requested_start_time,
                        month_end: this.requested_end_time,
                        start_time: this.addEventForm.start_time
                    }, {only: ['areas'], preserveState: true});
                }
                 */
            }
        },
        validateStartBeforeEndTime(form) {

            if (form.start_time && form.end_time) {
                Inertia.post(route('events.store'), {
                    start_time: form.start_time,
                    end_time: form.end_time,
                }, {
                    headers: {'X-Dry-Run': 'true'},
                    onError: (errors) => {
                        this.startTimeError = errors
                    },
                    onSuccess: () => {
                        this.startTimeError = null
                    }
                })
            }
        },
        async validateStartTime(form) {
            await this.getStartTimeConflicts()

            this.validateStartBeforeEndTime(form)
        },
        async validateEndTime(form) {
            await this.getEndTimeConflicts()

            this.validateStartBeforeEndTime(form)
        },
        async getEndTimeConflicts() {
            if (this.selectedRoom) {

                await axios.get(`/room/${this.selectedRoom.id}/end_time_conflicts`, {
                    params: {
                        end_time: this.addEventForm.end_time
                    }
                }).then(response => {
                    if (this.conflictData === null) {
                        if (response.data !== null && response.data.length !== 0) {
                            this.conflictData = response.data;
                        }
                    } else {
                        if (response.data !== null && response.data.length !== 0) {
                            this.conflictData.push(response.data);
                        }
                    }
                });

            } else {
                /* hier gibt es einen Fehler wegen falschem Parameter für die Route -> soll überarbeitet werden, deswegen erstmal kein fix
                   Code hier ist dazu da um in der Listbox zur Auswahl des Raumes richtig anzuzeigen,ob der Raum zu den gewählten Zeitpunkten belegt ist.
                if (this.calendarType === 'project') {
                    Inertia.get(route('projects.show'), {
                        project: this.project_id,
                        calendarType: 'monthly',
                        month_start: this.requested_start_time,
                        month_end: this.requested_end_time,
                        end_time: this.addEventForm.end_time
                    }, {only: ['areas'], preserveState: true});
                } else {
                    Inertia.get(route('events.monthly_management'), {
                        month_start: this.requested_start_time,
                        month_end: this.requested_end_time,
                        end_time: this.addEventForm.end_time
                    }, {only: ['areas'], preserveState: true});
                }
                 */
            }
        },
        hasConflict(event_id) {
            if (this.wantedDay.conflicts) {
                for (let conflict of this.wantedDay.conflicts) {
                    if (conflict.includes(event_id)) {
                        return true;
                    }
                }
            }
            return false;
        },
        getLastRoom() {
            let firstRoom = true;
            let lastRoom = this.roomsToShow[this.lastRoomIndex];
            if (this.lastRoomIndex === 0) {
                if (!firstRoom) {
                    this.lastRoomIndex++;
                    return lastRoom
                } else {
                    firstRoom = false;
                    return lastRoom
                }

            } else {
                this.lastRoomIndex++
                return lastRoom;
            }
        },
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
            if (this.calendarType === 'project') {
                this.assignProject = true;
                this.selectedProject = this.projects[0];
            }
            if (roomId !== null) {
                this.areas.forEach((area) => {
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
                let wantedEventType = this.event_types.find(x => x.id === event.event_type_id);
                if (!eventTypesToDisplay.includes(wantedEventType)) {
                    eventTypesToDisplay.push(wantedEventType);
                }
            })
            return eventTypesToDisplay;
        },
        checkEventType(events) {
            if (this.wantedEventType) {
                return events.filter(event => event.event_type_id === this.wantedEventType.eventTypeId).length > 0;
            } else {
                return true;
            }
        },
        checkAttribute(events) {
            if (this.wantedAttribute) {
                switch (this.wantedAttribute.id) {
                    case 1:
                        return events.filter(event => event.occupancy_option === true).length > 0;
                    case 2:
                        return events.filter(event => event.is_loud === true).length > 0;
                    case 3:
                        return events.filter(event => event.audience === true).length > 0;
                }
            } else {
                return true;
            }
        },
        closeAddEventModal() {
            this.addingEvent = false;
            this.assignProject = false;
            this.selectedProject = null;
            this.newProjectName = '';
            this.creatingProject = false;
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
            this.addEventForm.project_name = null;
            this.conflictData = null;
            this.selectedEventType = this.event_types[0];
            this.startTimeError = null;
        },
        addEvent(isOption) {
            this.addEventForm.event_type_id = this.selectedEventType.id;
            this.addEventForm.room_id = this.selectedRoom.id;
            this.addEventForm.occupancy_option = isOption;
            if (this.assignProject || this.selectedEventType.project_mandatory) {
                if (this.creatingProject) {
                    this.addEventForm.project_name = this.newProjectName;
                } else if (this.selectedProject != null) {
                    this.addEventForm.project_id = this.selectedProject.id;
                }
            }

            this.addEventForm.post(route('events.store'), {preserveScroll: true});

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
        updateEvent(event, isOption = false) {
            if (event.name !== null) {
                this.updateEventForm.name = event.name;
            }
            this.updateEventForm.description = event.description;
            this.updateEventForm.start_time = event.start_time_dt_local;
            this.updateEventForm.end_time = event.end_time_dt_local;
            this.updateEventForm.occupancy_option = isOption;
            this.updateEventForm.audience = event.audience;
            this.updateEventForm.is_loud = event.is_loud;
            this.updateEventForm.event_type_id = event.event_type_id;
            this.updateEventForm.room_id = event.room_id;
            this.updateEventForm.user_id = event.user_id;
            this.updateEventForm.project_id = event.project_id;
            this.updateEventForm.patch(route('events.update', {preserveScroll: true, event: event.id},));
            this.closeDayDetailModal();
        },
        openChangeDateModal() {
            this.showChangeDateModal = true;
        },
        closeChangeDateModal() {
            this.showChangeDateModal = false;
        },
        changeWantedDate() {
            if (this.wantedDateType.id === 1) {
                if (this.calendarType === 'room') {
                    Inertia.visit(route('rooms.show', {
                        month_start: this.wantedStartDate,
                        month_end: this.wantedEndDate,
                        room: this.rooms[0],
                        calendarType: 'monthly'
                    }))
                } else if (this.calendarType === 'project') {
                    Inertia.visit(route('projects.show', {
                        month_start: this.wantedStartDate,
                        month_end: this.wantedEndDate,
                        openTab: 'calendar',
                        project: this.project_id,
                        calendarType: 'monthly'
                    }))
                } else {
                    Inertia.visit(route('events.monthly_management', {
                        month_start: this.wantedStartDate,
                        month_end: this.wantedEndDate
                    }))
                }
            } else {
                if (this.calendarType === 'room') {
                    Inertia.visit(route('rooms.show', {
                        wanted_day: this.wantedDayDate,
                        room: this.rooms[0],
                        calendarType: 'daily'
                    }))
                } else if (this.calendarType === 'project') {
                    Inertia.visit(route('projects.show', {
                        wanted_day: this.wantedDayDate,
                        openTab: 'calendar',
                        project: this.project_id,
                        calendarType: 'daily'
                    }))
                } else {
                    Inertia.visit(route('events.daily_management', {wanted_day: this.wantedDayDate}))
                }
            }
        },
        goToDailyCalendar(index) {
            this.wantedDateType.id = 2;
            this.wantedDayDate = new Date(new Date(this.roomsToShow[0].days_in_month[index].date_local).setHours(new Date(this.roomsToShow[0].days_in_month[index].date_local).getHours() + 2));
            this.changeWantedDate();
        },
        deleteEvent(eventId) {
            Inertia.delete(`/events/${eventId}`);
            this.closeDayDetailModal();
        },
        approveRequest(event) {
            if (event.name !== null) {
                this.updateEventForm.name = event.name;
            }
            this.updateEventForm.description = event.description;
            this.updateEventForm.start_time = event.start_time_dt_local;
            this.updateEventForm.end_time = event.end_time_dt_local;
            this.updateEventForm.occupancy_option = false;
            this.updateEventForm.audience = event.audience;
            this.updateEventForm.is_loud = event.is_loud;
            this.updateEventForm.event_type_id = event.event_type_id;
            this.updateEventForm.room_id = event.room_id;
            this.updateEventForm.user_id = event.user_id;
            this.updateEventForm.project_id = event.project_id;
            this.updateEventForm.patch(route('events.update', {event: event.id}));
            this.closeDayDetailModal();
        },
        declineRequest(event) {
            if (event.name !== null) {
                this.updateEventForm.name = event.name;
            }
            this.updateEventForm.description = event.description;
            this.updateEventForm.start_time = event.start_time_dt_local;
            this.updateEventForm.end_time = event.end_time_dt_local;
            this.updateEventForm.occupancy_option = event.false;
            this.updateEventForm.audience = event.audience;
            this.updateEventForm.is_loud = event.is_loud;
            this.updateEventForm.event_type_id = event.event_type_id;
            this.updateEventForm.room_id = null;
            this.updateEventForm.user_id = event.user_id;
            this.updateEventForm.project_id = event.project_id;
            this.updateEventForm.patch(route('events.update', {event: event.id}));
            this.closeDayDetailModal();
        }
    },
    watch: {
        selectedRoom() {
            if (this.addEventForm.start_time) {
                this.getStartTimeConflicts()
            }

            if (this.addEventForm.end_time) {
                this.getEndTimeConflicts()
            }
        },
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
            selectedEventType: this.event_types[0],
            lastRoomIndex: 0,
            showChangeDateModal: false,
            wantedDateType: dateTypes[0],
            wantedStartDate: null,
            wantedEndDate: null,
            wantedDayDate: null,
            assignProject: this.calendarType === 'project',
            selectedProject: this.calendarType === 'project' ? this.projects[0] : null,
            showAddHoverDate: null,
            showAddHoverRoomId: null,
            wantedEventType: null,
            newProjectName: "",
            wantedAttribute: null,
            showDayDetailModal: false,
            wantedDay: null,
            selectedRoom: null,
            creatingProject: false,
            project_query: "",
            wantedArea: null,
            conflictData: null,
            startTimeError: null,
            project_search_results: [],
            addEventForm: useForm({
                name: '',
                start_time: this.start_time_of_new_event,
                end_time: this.end_time_of_new_event,
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
    setup() {
        return {
            attributeFilters,
            dateTypes
        }
    }
})
</script>
