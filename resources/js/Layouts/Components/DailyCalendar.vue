<template>
    <div class="bg-backgroundGray pb-20">
        <div class="flex flex-row" :class="calendarType === 'project' ? 'bg-backgroundGray' : 'bg-white'">
            <div class="flex flex-1 flex-wrap">
                <div class="w-full flex my-auto justify-between">
                    <div class="flex flex-wrap items-center">
                        <div v-if="calendarType !== 'project'" class="flex items-center mb-4 ml-20 mt-10">
                            <h2 class="text-3xl font-black flex">Raumbelegungen</h2>
                            <div class="flex items-center"
                                 v-if="this.$page.props.can.admin_rooms || this.$page.props.is_admin || this.$page.props.can.admin_projects || this.$page.props.can.request_room_occupancy">
                                <AddButton @click="openAddEventModal" text="Neue Belegung" mode="page"/>
                                <div v-if="$page.props.can.show_hints" class="flex mt-2.5">
                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                    <span
                                        class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Frage neue Raumbelegungen an</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex w-full items-center ml-20">
                            <div class="text-xl font-black w-40">
                                {{ this.formattedWeekday }} {{ this.shown_day_formatted.split(' ')[1] }}
                            </div>
                            <div v-if="calendarType === 'main'" class="ml-2 flex items-center">
                                <Link
                                    :href="route('events.daily_management',{wanted_day: new Date(new Date(this.shown_day_local).setDate(new Date(this.shown_day_local).getDate() - 1))})">
                                    <ChevronLeftIcon class="h-5 w-5"/>
                                </Link>
                                <CalendarIcon @click="openChangeDateModal"
                                              class="h-6 w-6 cursor-pointer ml-2 mr-2"/>
                                <Link
                                    :href="route('events.daily_management',{wanted_day: new Date(new Date(this.shown_day_local).setDate(new Date(this.shown_day_local).getDate() + 1))})">
                                    <ChevronRightIcon class="h-5 w-5"/>
                                </Link>
                            </div>
                            <div v-if="calendarType === 'room'" class="ml-2 flex items-center">
                                <Link
                                    :href="route('rooms.show',{wanted_day: new Date(new Date(this.shown_day_local).setDate(new Date(this.shown_day_local).getDate() - 1)),room:this.rooms[0], calendarType: 'daily'})">
                                    <ChevronLeftIcon class="h-5 w-5"/>
                                </Link>
                                <CalendarIcon @click="openChangeDateModal"
                                              class="h-6 w-6 cursor-pointer ml-2 mr-2"/>
                                <Link
                                    :href="route('rooms.show',{wanted_day: new Date(new Date(this.shown_day_local).setDate(new Date(this.shown_day_local).getDate() + 1)),room:this.rooms[0], calendarType: 'daily'})">
                                    <ChevronRightIcon class="h-5 w-5"/>
                                </Link>
                            </div>
                            <div v-if="calendarType === 'project'" class="ml-2 flex items-center">
                                <Link
                                    :href="route('projects.show',{project: projects
                                       [0],openTab:'calendar', wanted_day: new Date(new Date(this.shown_day_local).setDate(new Date(this.shown_day_local).getDate() - 1)),calendarType: 'daily'})">
                                    <ChevronLeftIcon class="h-5 w-5"/>
                                </Link>
                                <CalendarIcon @click="openChangeDateModal"
                                              class="h-6 w-6 cursor-pointer ml-2 mr-2"/>
                                <Link
                                    :href="route('projects.show',{project: projects
                                       [0],openTab:'calendar', wanted_day: new Date(new Date(this.shown_day_local).setDate(new Date(this.shown_day_local).getDate() + 1)), calendarType: 'daily'})">
                                    <ChevronRightIcon class="h-5 w-5"/>
                                </Link>
                            </div>
                            <div class="flex my-auto items-center mt-5 ml-10">
                                <Listbox v-if="this.rooms.length > 1" as="div"
                                         class="sm:col-span-3 mb-8 flex items-center my-auto"
                                         v-model="wantedArea">
                                    <div class="relative">
                                        <ListboxButton
                                            :class="calendarType === 'project' ? 'bg-backgroundGray' : 'bg-white'"
                                            class="flex ml-4 cursor-pointer  relative w-full font-semibold pr-10 py-2 mt-4 text-left cursor-default focus:outline-none focus:ring-0 focus:ring-primary focus:border-primary sm:text-sm">
                                        <span v-if="wantedArea" class="block truncate items-center">
                                            <span>{{ wantedArea.name }}</span>
                                        </span>
                                            <span v-else class="block truncate items-center">
                                            <span>Alle Areale</span>
                                        </span>
                                            <span
                                                class="flex inset-y-0 flex items-center ml-2 pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
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
                                                               v-for="area in areas
                                                                  "
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
                                <Listbox as="div" class="sm:col-span-3 mb-8 flex items-center my-auto"
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
                                                class="flex inset-y-0 flex items-center ml-2 pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
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
                                                class="inset-y-0 flex items-center ml-2 pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
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
                                <div v-for="hour in hours_of_day"
                                     class="w-40 inline-flex mt-1 h-36 w-full uppercase text-secondary subpixel-antialiased">
                                    {{ hour }}
                                </div>
                            </div>
                            <div class="flex">
                                <div v-if="events_without_room.count > 0"
                                     class="inline-flex flex-col pl-3 w-56 bg-error border-r-8 border-white">
                                    <h2 class="px-2 text-white uppercase cursor-pointer subpixel-antialiased mt-4 mb-4 ">
                                        Termine ohne Raum
                                    </h2>
                                    <ol class="h-full w-54 grid grid-cols-1 sm:pr-8"
                                        style="grid-template-rows: 1.75rem repeat(1440, minmax(0, 1fr)) auto">
                                        <li v-for="event in sortedEvents(events_without_room.events)"
                                            class="mt-px flex"
                                            :style="event.minutes_from_day_start !== 0 ? {'grid-row': event.minutes_from_day_start + '/ span ' + event.duration_in_minutes} : {'grid-row': 1 + '/ span ' + event.duration_in_minutes}">
                                            <div v-if="checkEventType(event) && checkAttribute(event)">
                                                <div :class="`border-${event.event_type.svg_name}-400`"
                                                     class="group h-full rounded-lg leading-5 border-l-4">
                                                    <div @click="openDayDetailModal(room,event)"

                                                         :class="[{'stripes': event.occupancy_option }, 'bg-white relative h-full w-40 m-0.5 mr-4 cursor-pointer']">
                                                        <!-- Inhalt einer Terminkachel -->
                                                        <div>
                                                            <!-- Icons -->
                                                            <div class="flex p-1 ml-1 mt-1">
                                                                <img src="/Svgs/IconSvgs/icon_public.svg"
                                                                     v-if="event.audience"
                                                                     class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                                <img src="/Svgs/IconSvgs/icon_loud.svg"
                                                                     v-if="event.is_loud"
                                                                     :class="event.audience ? 'ml-1' : ''"
                                                                     class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                                <div v-if="!event.audience && !event.is_loud"
                                                                     class="h-5 w-5">
                                                                </div>
                                                            </div>
                                                            <div v-if="event.conflicts.length > 0"
                                                                 class="h-5 flex right-0 top-0 bg-error items-center absolute">
                                                                <img src="/Svgs/IconSvgs/icon_warning_white.svg"
                                                                     class="h-4 w-4 ml-1 flex text-white"
                                                                     aria-hidden="true"/>
                                                                <span
                                                                    class="text-white ml-1 flex items-center mr-0.5">
                                                                {{ event.conflicts.length }}
                                                            </span>
                                                            </div>
                                                            <div
                                                                class="text-secondary subpixel-antialiased text-sm ml-2 mt-1">
                                                                {{ event.event_type.name }}
                                                            </div>
                                                            <div>
                                                                <!-- Name of connected Project -->
                                                                <div
                                                                    v-if="event.project_id !== null"
                                                                    class="mt-3 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary">
                                                                    {{
                                                                        projects.find(x => x.id === event.project_id).name
                                                                    }}
                                                                </div>
                                                                <!-- Individual Eventname -->
                                                                <div
                                                                    class="my-1 ml-2 text-xs flex font-lexend text-secondary">
                                                                    {{ event.name }}
                                                                </div>
                                                                <!-- Time of Event -->
                                                                <div
                                                                    class="ml-2 mt-1 text-sm flex text-secondary subpixel-antialiased">
                                                                    {{ event.start_time.substring(11, 16) }} -
                                                                    <div v-if="event.duration_in_minutes > 1439">
                                                                        23:59
                                                                    </div>
                                                                    <div v-else>{{
                                                                            event.end_time.substring(11, 16)
                                                                        }}
                                                                    </div>
                                                                </div>
                                                                <!-- Project Leader Icons -->
                                                                <div class="ml-1 mt-2 flex" v-if="event.project_id">
                                                                    <div
                                                                        v-if="projects
                                                                           .find(x => x.id === event.project_id).project_managers.length <= 3"
                                                                        class="flex shrink-0 mt-2 -mr-3"
                                                                        v-for="user in projects
                                                                           .find(x => x.id === event.project_id).project_managers">
                                                                        <img :data-tooltip-target="user.id"
                                                                             :src="user.profile_photo_url"
                                                                             :alt="user.name"
                                                                             class="shrink-0 ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                                                                        <UserTooltip class="shrink-0" :user="user"/>
                                                                    </div>
                                                                    <div class="flex" v-else>
                                                                        <div class=" mt-2 -mr-3"
                                                                             v-for="user in projects
                                                                                .find(x => x.id === event.project_id).project_managers.slice(0,2)">
                                                                            <img :data-tooltip-target="user.id"
                                                                                 :src="user.profile_photo_url"
                                                                                 :alt="user.name"
                                                                                 class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                                                                            <UserTooltip :user="user"/>
                                                                        </div>
                                                                        <Menu as="div" class="relative mt-3 -mr-3">
                                                                            <div>
                                                                                <MenuButton
                                                                                    class="flex items-center rounded-full focus:outline-none">
                                                                                    <ChevronDownIcon
                                                                                        class="ml-1 flex-shrink-0 h-10 w-10 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                                                                </MenuButton>
                                                                            </div>
                                                                            <transition
                                                                                enter-active-class="transition ease-out duration-100"
                                                                                enter-from-class="transform opacity-0 scale-95"
                                                                                enter-to-class="transform opacity-100 scale-100"
                                                                                leave-active-class="transition ease-in duration-75"
                                                                                leave-from-class="transform opacity-100 scale-100"
                                                                                leave-to-class="transform opacity-0 scale-95">
                                                                                <MenuItems
                                                                                    class="z-40 absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                                                    <MenuItem
                                                                                        v-for="user in projects
                                                                                           .find(x => x.id === event.project_id).project_managers"
                                                                                        v-slot="{ active }">
                                                                                        <Link href="#"
                                                                                              :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                            <img
                                                                                                class="h-9 w-9 rounded-full"
                                                                                                :src="user.profile_photo_url"
                                                                                                alt=""/>
                                                                                            <span class="ml-4">
                                                                                                {{
                                                                                                    user.first_name
                                                                                                }} {{
                                                                                                    user.last_name
                                                                                                }}
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
                                                </div>
                                            </div>
                                        </li>
                                    </ol>
                                </div>

                                <div v-if="this.roomsToShow.length > 0"
                                     v-for="room in roomsToShow.sort((a,b) => a.area_id - b.area_id)"
                                     class="inline-flex flex-col pl-3 shrink-0 bg-backgroundGray"
                                     :class="[room.area_id !== getLastRoom().area_id ? 'border-l-8 border-white' : '', room.events.length === 0 ? 'w-56': '']">
                                    <h2 class="uppercase text-secondary subpixel-antialiased mt-4 mb-4 ">
                                        {{ room.name }}
                                    </h2>
                                    <ol class="h-full grid pr-8 grid-cols-1"
                                        style="grid-template-rows: 1.75rem repeat(1440, minmax(0, 1fr)) auto">
                                        <!-- Listobject on hover when there is no event on that day -->
                                        <li @mouseover="activateHover(room.id)"
                                            @click="openAddEventModal(room.id)"
                                            @mouseout="deactivateHover()"
                                            v-if="room.events.length === 0 && (this.$page.props.can.admin_rooms || this.$page.props.is_admin || this.$page.props.can.admin_projects || this.$page.props.can.request_room_occupancy)"
                                            :class="showAddHoverRoomId === room.id ? 'bg-secondary' : ''"
                                            class="relative mt-px flex hover:bg-secondary cursor-pointer"
                                            style="grid-row: 1 / span 1439">
                                            <button v-show="showAddHoverRoomId === room.id"
                                                    type="button"
                                                    class="m-auto border border-transparent rounded-full shadow-sm text-white bg-primary bg-primaryHover focus:outline-none">
                                                <PlusSmIcon class="h-6 w-6" aria-hidden="true"/>
                                            </button>
                                        </li>


                                        <li v-for="event in sortedEvents(room.events)" class="mt-px flex"
                                            :style="event.minutes_from_day_start !== 0 ? {'grid-row': event.minutes_from_day_start + '/ span ' + event.duration_in_minutes} : {'grid-row': 1 + '/ span ' + event.duration_in_minutes}">
                                            <div v-if="checkEventType(event) && checkAttribute(event)">
                                                <div :style="{'border-color': $svgColors[event.event_type.svg_name]}"
                                                     class="group h-full rounded-lg leading-5 border-l-4">
                                                    <div @click="openDayDetailModal(room,event)"

                                                         :class="[{'stripes': event.occupancy_option }, 'bg-white relative h-full w-40 m-0.5 mr-4 cursor-pointer']">
                                                        <!-- Inhalt einer Terminkachel -->
                                                        <div>
                                                            <!-- Icons -->
                                                            <div class="flex p-1 ml-1 mt-1">
                                                                <img src="/Svgs/IconSvgs/icon_public.svg"
                                                                     v-if="event.audience"
                                                                     class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                                <img src="/Svgs/IconSvgs/icon_loud.svg"
                                                                     v-if="event.is_loud"
                                                                     :class="event.audience ? 'ml-1' : ''"
                                                                     class="h-5 w-5 my-auto text-secondary subpixel-antialiased"/>
                                                                <div v-if="!event.audience && !event.is_loud"
                                                                     class="h-5 w-5">
                                                                </div>
                                                            </div>
                                                            <div
                                                                v-if="event.conflicts.length > 0 || room.events.find(roomEvent => roomEvent.conflicts.includes(event.id))"
                                                                class="h-5 flex right-0 top-0 bg-error items-center absolute">
                                                                <img src="/Svgs/IconSvgs/icon_warning_white.svg"
                                                                     class="h-4 w-4 ml-1 flex text-white"
                                                                     aria-hidden="true"/>
                                                                <span
                                                                    v-if="!room.events.find(roomEvent => roomEvent.conflicts.includes(event.id))"
                                                                    class="text-white ml-1 flex items-center mr-0.5">
                                                                {{ event.conflicts.length }}
                                                                </span>
                                                                <span v-else
                                                                      class="text-white ml-1 flex items-center mr-0.5">
                                                                {{ event.conflicts.length + 1 }}
                                                                </span>
                                                            </div>
                                                            <div v-if="this.calendarType !== 'project'"
                                                                class="text-secondary subpixel-antialiased text-sm ml-2 mt-1">
                                                                {{ event.event_type.name }}
                                                            </div>
                                                            <div>
                                                                <!-- Name of connected Project -->
                                                                <div
                                                                    v-if="event.project_id !== null && this.calendarType !== 'project'"
                                                                    class="mt-3 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary">
                                                                    {{
                                                                        projects.find(x => x.id === event.project_id).name
                                                                    }}
                                                                </div>
                                                                <!-- Individual Eventname -->
                                                                <div v-if="event.name">
                                                                    <div
                                                                        v-if="event.project_id !== null && this.calendarType !== 'project'"
                                                                        class="my-1 ml-2 text-xs flex font-lexend text-secondary">
                                                                        {{ event.name }}
                                                                    </div>
                                                                    <div v-else
                                                                         class="mt-3 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary">
                                                                        {{ event.name }}
                                                                    </div>
                                                                </div>
                                                                <div v-else
                                                                     class="mt-3 ml-2 text-lg flex leading-6 font-bold font-lexend text-primary truncate mr-3">
                                                                    {{ this.event_types.find(x => x.id === event.event_type_id).name }}
                                                                </div>
                                                                <!-- Time of Event -->
                                                                <div
                                                                    class="ml-2 mt-1 text-sm flex text-secondary subpixel-antialiased">
                                                                    <div v-if="event.minutes_from_day_start === 1">
                                                                        00:00 -
                                                                    </div>
                                                                    <div v-else>
                                                                        {{ event.start_time.substring(11, 16) }} -
                                                                    </div>
                                                                    <div v-if="event.duration_in_minutes > 1439">
                                                                        23:59
                                                                    </div>
                                                                    <div v-else>{{
                                                                            event.end_time.substring(11, 16)
                                                                        }}
                                                                    </div>
                                                                </div>
                                                                <!-- Project Leader Icons -->
                                                                <div class="ml-1 mt-2 flex" v-if="event.project_id">
                                                                    <div
                                                                        v-if="projects
                                                                           .find(x => x.id === event.project_id).project_managers.length <= 3"
                                                                        class="flex mt-2 -mr-3"
                                                                        v-for="user in projects
                                                                           .find(x => x.id === event.project_id).project_managers">
                                                                        <img :data-tooltip-target="user.id"
                                                                             :src="user.profile_photo_url"
                                                                             :alt="user.name"
                                                                             class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                                                                        <UserTooltip :user="user"/>
                                                                    </div>
                                                                    <div class="flex" v-else>
                                                                        <div class=" mt-2 -mr-3"
                                                                             v-for="user in projects
                                                                                .find(x => x.id === event.project_id).project_managers.slice(0,2)">
                                                                            <img :data-tooltip-target="user.id"
                                                                                 :src="user.profile_photo_url"
                                                                                 :alt="user.name"
                                                                                 class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                                                                            <UserTooltip :user="user"/>
                                                                        </div>
                                                                        <Menu as="div" class="relative mt-3 -mr-3">
                                                                            <div>
                                                                                <MenuButton
                                                                                    class="flex items-center rounded-full focus:outline-none">
                                                                                    <ChevronDownIcon
                                                                                        class="ml-1 flex-shrink-0 h-10 w-10 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                                                                </MenuButton>
                                                                            </div>
                                                                            <transition
                                                                                enter-active-class="transition ease-out duration-100"
                                                                                enter-from-class="transform opacity-0 scale-95"
                                                                                enter-to-class="transform opacity-100 scale-100"
                                                                                leave-active-class="transition ease-in duration-75"
                                                                                leave-from-class="transform opacity-100 scale-100"
                                                                                leave-to-class="transform opacity-0 scale-95">
                                                                                <MenuItems
                                                                                    class="z-40 absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                                                    <MenuItem
                                                                                        v-for="user in projects
                                                                                           .find(x => x.id === event.project_id).project_managers"
                                                                                        v-slot="{ active }">
                                                                                        <Link href="#"
                                                                                              :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                            <img
                                                                                                class="h-9 w-9 rounded-full"
                                                                                                :src="user.profile_photo_url"
                                                                                                alt=""/>
                                                                                            <span class="ml-4">
                                                                                                {{
                                                                                                    user.first_name
                                                                                                }} {{
                                                                                                    user.last_name
                                                                                                }}
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
                                                </div>
                                            </div>
                                        </li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

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
                <div class="flex">
                    <Listbox as="div" class="flex mt-6 w-1/2 mr-2" v-model="selectedEventType">
                        <ListboxButton
                            class="pl-3 border border-gray-300 w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                            <div class="flex items-center my-auto">
                                <EventTypeIconCollection :height="20" :width="20"
                                                         :iconName="selectedEventType.svg_name"/>
                                <span class="block truncate items-center ml-3 flex">
                                            <span>{{ selectedEventType.name }}</span>
                                </span>
                                <span
                                    class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                            </div>
                        </ListboxButton>

                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <ListboxOptions
                                class="absolute w-72 z-10 mt-10 bg-primary shadow-lg max-h-32 pl-1 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
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
                    </Listbox>

                    <div class="mt-6 flex w-1/2 ml-2" v-if="!selectedEventType.project_mandatory">
                        <input v-if="selectedEventType.individual_name && !selectedProject && !newProjectName"
                               type="text"
                               v-model="addEventForm.name" placeholder="Terminname*"
                               class="text-primary h-10 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 w-full text-sm"/>
                        <input v-else type="text" v-model="addEventForm.name" placeholder="Terminname"
                               class="text-primary h-10 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 w-full text-sm"/>
                    </div>


                </div>

                <div class="flex mt-4 w-full justify-between">
                    <div class="flex">
                        <input v-model="addEventForm.audience"
                               type="checkbox"
                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-buttonBlue border-2 border-gray-300"/>
                        <img src="/Svgs/IconSvgs/icon_public.svg" class="h-5 w-5 ml-2 my-auto mt-1"
                             :class="[addEventForm.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                        <p :class="[addEventForm.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                           class="ml-1.5 mt-1.5 text-xs subpixel-antialiased text-secondary">Publikum</p>
                    </div>
                    <div class="flex">
                        <input v-model="addEventForm.is_loud"
                               type="checkbox"
                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-buttonBlue border-2 border-gray-300"/>
                        <img src="/Svgs/IconSvgs/icon_loud.svg" class="h-5 w-5 ml-2 my-auto"
                             :class="[addEventForm.is_loud ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                        <p :class="[addEventForm.is_loud ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                           class="ml-1.5 mt-1.5 text-xs subpixel-antialiased text-secondary">Es wird laut</p>
                    </div>
                </div>

                <div>
                    <div class="flex items-center mt-4">
                        <span class="mr-4 text-sm"
                              :class="[!creatingProject ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']">
                                Bestehendes Projekt
                            </span>
                        <Switch @click="switchProjectMode()" v-model="creatingProject"
                                :class="[creatingProject ?
                                        'bg-buttonBlue' :
                                        'bg-buttonBlue',
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

                    <div class="my-auto w-full mt-4" v-else>

                        <input v-if="selectedProject === null" id="projectSearch" v-model="project_query" type="text" autocomplete="off"
                               class="text-primary h-10 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 w-full text-sm"
                               placeholder="Zu welchem bestehendem Projekt zuordnen?*"
                               :disabled="this.selectedProject"/>
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
                        <label for="startTime" class="text-xs subpixel-antialiased">Startdatum*</label>
                        <input
                            @blur="validateStartTime(addEventForm)"
                            v-model="addEventForm.start_time" id="startTime"
                            placeholder="Startdatum" type="datetime-local"
                            class="placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 text-primary placeholder-secondary mr-2 w-full"/>
                    </div>
                    <div class="text-secondary ml-2">
                        <label for="endTime" class="text-xs subpixel-antialiased">Enddatum*</label>
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

                <Listbox as="div" class="flex" v-model="selectedRoom">
                    <ListboxButton
                        class="pl-3 border border-gray-300 bg-white w-full relative mt-6 py-2 cursor-pointer focus:outline-none sm:text-sm">
                        <div class="flex items-center my-auto">
                                        <span v-if="selectedRoom" class="block truncate items-center flex mr-2">
                                            <span>{{ selectedRoom.name }}</span>

                                        </span>
                            <span v-if="!selectedRoom"
                                  class="block truncate text-secondary">Raum wählen*</span>
                            <span
                                class="inset-y-0 right-0 absolute flex items-center pr-2 pointer-events-none">
                                            <ChevronDownIcon class="h-5 w-5" aria-hidden="true"/>
                                         </span>
                        </div>
                    </ListboxButton>
                    <transition leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <ListboxOptions
                            class="absolute z-10 mt-16 w-5/6 bg-primary shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
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
                </Listbox>

                <div class="mt-4">
                        <textarea placeholder="Was gibt es bei dem Termin zu beachten?"
                                  v-model="addEventForm.description" rows="4"
                                  class="resize-none shadow-sm p-4 focus:outline-none placeholder-secondary placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 block w-full sm:text-sm border"/>
                </div>
                <div>
                    <div v-if="selectedRoom" @mouseover="showHints()">
                        <div class="flex items-center w-full justify-center"
                             v-if="(selectedRoom.room_admins.find(user => user.id === this.$page.props.user.id) || selectedRoom.everyone_can_book) || this.$page.props.is_admin || this.$page.props.can.admin_rooms">
                            <button :class="[startTimeError || this.addEventForm.start_time === null || this.addEventForm.end_time === null || this.selectedRoom === null || (selectedEventType.project_mandatory && selectedProject === null && newProjectName === '') || ((addEventForm.name === '' && selectedEventType.individual_name) && newProjectName === '' && selectedProject === null) ?
                                    'bg-secondary': 'bg-buttonBlue hover:bg-buttonHover focus:outline-none']"
                                    class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover rounded-3xl"
                                    @click="addEvent(false)"
                                    :disabled="addEventForm.start_time === null || addEventForm.end_time === null || (selectedEventType.project_mandatory && selectedProject === null && newProjectName === '') || ((addEventForm.name === '' && selectedEventType.individual_name) && newProjectName === '' && selectedProject === null) || startTimeError">
                                Belegen
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center w-full justify-center"
                         v-if="!selectedRoom || (!selectedRoom.room_admins.find(user => user.id === this.$page.props.user.id) || !selectedRoom.everyone_can_book) && !$page.props.is_admin"
                         @mouseover="showHints()">
                        <button :class="[startTimeError || this.addEventForm.start_time === null || this.addEventForm.end_time === null || this.selectedRoom === null ||(selectedEventType.project_mandatory && selectedProject === null && newProjectName === '') || (addEventForm.name === '' && newProjectName === '' && selectedProject === null) ?
                                    'bg-secondary': 'bg-buttonBlue hover:bg-buttonHover focus:outline-none']"
                                class="mt-4 px-12 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover rounded-3xl"
                                @click="addEvent(true)"
                                :disabled="addEventForm.start_time === null
                                || addEventForm.end_time === null || (selectedEventType.project_mandatory
                                && selectedProject === null && newProjectName === '') || ((addEventForm.name === '' && selectedEventType.individual_name)
                                && newProjectName === ''
                                && selectedProject === null)
                                || startTimeError">
                            Belegung anfragen
                        </button>
                    </div>
                    <div class="mt-1" v-if="newEventError">
                        <div class="text-error subpixel-antialiased text-sm flex">
                            {{ this.newEventError }}
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <!-- TagesDetail-Modal -->
    <jet-dialog-modal :show="showDayDetailModal" @close="closeDayDetailModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_appointment_new.svg" class="-ml-6 -mt-8"/>
            <XIcon @click="closeDayDetailModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div v-for="(event,index) in wantedEvents" :class="wantedEvents.length -1 === index ? '' : 'border-b-2'"
                 class="mx-4 pb-8">
                <div>
                    <div class="mt-2 flex">
                        <div
                            v-if="event.conflicts.length > 0 || (rooms.find(room => room.id === event.room_id) && rooms.find(room => room.id === event.room_id).events.find(wantedEvent => wantedEvent.conflicts.includes(event.id)))"
                            class="bg-error flex h-8 w-8 mt-6 mr-2">
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
                                                                 :iconName="event_types
                                                                    .find(x => x.id === event.event_type_id).svg_name"/>
                                        <span class="block truncate items-center text-3xl font-black ml-3 flex">
                                            <span>{{
                                                    event_types.find(x => x.id === event.event_type_id).name
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
                                        class="absolute w-full w-72 z-10 mt-1 bg-primary shadow-lg max-h-32 pl-1 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
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
                             class="bg-white w-full relative mt-4 py-2 cursor-pointer focus:outline-none flex items-center">
                            <EventTypeIconCollection :height="24" :width="24"
                                                     :iconName="event_types
                                                        .find(x => x.id === event.event_type_id).svg_name"/>
                            <span class="block truncate items-center text-3xl font-black ml-3 flex">
                                        <span>
                                            {{ event_types.find(x => x.id === event.event_type_id).name }}
                                        </span>
                                    </span>
                        </div>
                        <div class="flex justify-end"
                             v-if="checkProjectPermission(event.project_id,this.$page.props.user.id) || this.$page.props.can.admin_rooms || this.$page.props.is_admin || (this.myRooms ? this.myRooms.length > 0 : false) || event.created_by.id === this.$page.props.user.id">
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
                                                v-if="event.occupancy_option && (rooms.find(room => room.id === event.room_id).room_admins && (rooms.find(room => room.id === event.room_id).room_admins.find(admin => admin.id === this.$page.props.user.id))) || this.$page.props.is_admin || this.$page.props.can.admin_rooms"
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
                                                v-if="event.occupancy_option && (rooms.find(room => room.id === event.room_id).room_admins && (rooms.find(room => room.id === event.room_id).room_admins.find(admin => admin.id === this.$page.props.user.id))) || this.$page.props.is_admin || this.$page.props.can.admin_rooms"
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
                                        :href="route('projects.show',{openTab: 'calendar',wanted_day: new Date(new Date(this.shown_day_local).setDate(new Date(this.shown_day_local).getDate())),project:event.project_id, calendarType: 'daily'})"
                                        class="ml-3 text-md flex font-bold font-lexend text-primary">
                                        {{ projects.find(x => x.id === event.project_id).name }}
                                    </Link>
                                </div>
                            </div>
                            <div v-else class="flex font-lexend text-secondary subpixel-antialiased text-sm">
                                <div>Keinem Projekt zugeordnet</div>
                            </div>
                            <div
                                v-if="checkProjectPermission(event.project_id,this.$page.props.user.id) || this.$page.props.is_admin || this.$page.props.can.admin_rooms || event.created_by.id === this.$page.props.user.id"
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
                                            <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
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
                            <div class="w-full font-black font-lexend text-primary tracking-wide text-xl my-2">
                                {{ event.name }}
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="checkProjectPermission(event.project_id,this.$page.props.user.id) || this.$page.props.is_admin || this.$page.props.can.admin_rooms || event.created_by.id === this.$page.props.user.id"
                        class="flex mt-1">
                        <div class="text-secondary mr-2">
                            <label for="startDate" class="text-xs subpixel-antialiased">Startdatum*</label>
                            <input
                                v-model="event.start_time_dt_local" id="startDate"
                                placeholder="Terminstart" type="datetime-local"
                                class="border-gray-300 text-primary placeholder-secondary mr-2 w-full placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1"/>
                        </div>
                        <div class="text-secondary ml-2">
                            <label for="endDate" class="text-xs subpixel-antialiased">Enddatum*</label>
                            <input
                                v-model="event.end_time_dt_local" id="endDate"
                                placeholder="Zu erledigen bis?" type="datetime-local"
                                class="border-gray-300 text-primary placeholder-secondary w-full placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1"/>
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
                            <img src="/Svgs/IconSvgs/icon_public.svg" class="h-5 w-5 ml-2 my-auto mt-1"
                                 :class="[event.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"/>
                            <p :class="[event.audience ? 'text-primary font-black' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1.5 mt-1.5 text-xs subpixel-antialiased text-secondary">Publikum</p>
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
                            <button :class="[event.start_time === null || event.end_time === null || event.selectedRoom === null ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                    class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold tracking-wider uppercase shadow-sm text-secondaryHover"
                                    @click="updateEvent(event)"
                                    :disabled="event.start_time === null && event.end_time === null">
                                Speichern
                            </button>
                        </div>
                    </div>
                    <div v-else class="subpixel-antialiased mt-4 w-80">
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
                                     <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
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
                                placeholder="Startdatum" type="date"
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
import AddButton from "@/Layouts/Components/AddButton";

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
        AddButton,
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
        UserTooltip,
        PencilAltIcon,
        TrashIcon
    },
    props: ['calendarType', 'hours_of_day', 'myRooms', 'rooms', 'projects', 'event_types', 'areas', 'shown_day_formatted', 'shown_day_local', 'requested_wanted_day', 'start_time_of_new_event', 'end_time_of_new_event', 'events_without_room'],
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
        formattedWeekday: function () {
            switch (this.shown_day_formatted.split(' ')[0]) {
                case 'Monday':
                    return 'MO';
                case 'Tuesday':
                    return 'DI';
                case 'Wednesday':
                    return 'MI';
                case 'Thursday':
                    return 'DO';
                case 'Friday':
                    return 'FR';
                case 'Saturday':
                    return 'SA';
                case 'Sunday':
                    return 'SO';
            }
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
                    Inertia.get(route('projects.show'), {
                        project: this.projects[0],
                        calendarType: 'daily',
                        wanted_day: new Date(new Date(this.shown_day_local).setDate(new Date(this.shown_day_local).getDate() - 1)),
                        start_time: this.addEventForm.start_time
                    }, {only: ['areas'], preserveState: true});
                } else {
                    Inertia.get(route('events.daily_management'), {
                        wanted_day: this.requested_wanted_day,
                        start_time: this.addEventForm.start_time
                    }, {only: ['areas'], preserveState: true});
                }

                 */

            }
        },
        async getEndTimeConflicts() {
            if (this.selectedRoom) {
                await axios.get(`/room/${this.selectedRoom.id}/end_time_conflicts`, {
                    params: {
                        wanted_day: this.requested_wanted_day,
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
                        project: this.projects[0],
                        calendarType: 'daily',
                        wanted_day: new Date(new Date(this.shown_day_local).setDate(new Date(this.shown_day_local).getDate() - 1)),
                        end_time: this.addEventForm.end_time
                    }, {only: ['areas'], preserveState: true});
                } else {
                    Inertia.get(route('events.daily_management'), {
                        end_time: this.addEventForm.end_time
                    }, {only: ['areas'], preserveState: true});
                }

                 */
            }
        },
        sortedEvents: function (events) {
            function compare(a, b) {
                if (b.duration_in_minutes === null) {
                    return -1;
                }
                if (a.duration_in_minutes === null) {
                    return 1;
                }
                if (a.duration_in_minutes < b.duration_in_minutes)
                    return 1;
                if (a.duration_in_minutes > b.duration_in_minutes)
                    return -1;
                return 0;
            }

            return events.sort(compare);
        },

        hasConflict(event_id) {

            for (let conflict of this.wantedDay.conflicts) {
                if (conflict.includes(event_id)) {
                    return true;
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
        checkEventType(event) {
            if (this.wantedEventType) {
                return event.event_type_id === this.wantedEventType.eventTypeId;
            } else {
                return true;
            }
        },
        checkAttribute(event) {
            if (this.wantedAttribute) {
                switch (this.wantedAttribute.id) {
                    case 1:
                        return event.occupancy_option === true;
                    case 2:
                        return event.is_loud === true;
                    case 3:
                        return event.audience === true;
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
        activateHover(roomId) {
            this.showAddHoverRoomId = roomId;
        },
        deactivateHover() {
            this.showAddHoverRoomId = null;
        },
        openDayDetailModal: function (room, event) {
            this.wantedEvents = [];
            this.wantedEvents.push(event);
            if (room) {
                event.conflicts.forEach((conflictEventId) => {
                    this.wantedEvents.push(room.events.find(roomEvent => roomEvent.id === conflictEventId))
                })
                room.events.forEach((roomEvent) => {
                    if (roomEvent.conflicts.includes(event.id)) {
                        this.wantedEvents.push(roomEvent);
                    }
                })
            }
            this.showDayDetailModal = true;
        },
        closeDayDetailModal() {
            this.showDayDetailModal = false;
            this.wantedEvents = null;
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
                        project: this.projects[0].id,
                        openTab: 'calendar',
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
                        project: this.projects[0].id,
                        openTab: 'calendar',
                        calendarType: 'daily'
                    }))
                } else {
                    Inertia.visit(route('events.daily_management', {wanted_day: this.wantedDayDate}))
                }
            }
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
            showAddHoverDate: new Date(this.shown_day_local).setHours(0, 0, 0, 0),
            showAddHoverRoomId: null,
            wantedEventType: null,
            newProjectName: "",
            wantedAttribute: null,
            showDayDetailModal: false,
            wantedEvents: [],
            selectedRoom: null,
            creatingProject: false,
            project_query: "",
            wantedArea: null,
            project_search_results: [],
            startTimeError: null,
            conflictData: null,
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
