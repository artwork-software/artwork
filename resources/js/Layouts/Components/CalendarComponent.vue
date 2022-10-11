<template>

    <div class="mt-10 ml-14">
        <div class="inline-flex mb-5 w-1/2">
            <Menu as="div" class="relative inline-block text-left w-auto">
                <div>
                    <MenuButton
                        class="mt-1 w-72 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white align-middle"
                    >
                        <CalendarIcon class="w-5 h-5 float-left mr-2" />
                        <span class="float-left">{{ this.displayDate }}</span>
                        <ChevronDownIcon
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
                        class="absolute left mt-2 w-52 origin-top-right rounded-sm bg-primary ring-1 ring-black py-2 text-white opacity-100 z-50">
                        <button @click="$refs.vuecal.switchView('day', new Date()); this.selectedDate = new Date()"
                                class="w-full mt-2 text-left pl-2"
                                :class="currentView === 'day' ? 'text-white font-bold border-l-2 border-success' : 'text-secondary border-none'">
                            <label class="text-sm">
                                Heute
                            </label>
                        </button>
                        <button @click="$refs.vuecal.switchView('week')"
                                class="w-full mt-2 text-left pl-2"
                                :class="currentView === 'week' ? 'text-white font-bold border-l-2 border-success' : 'text-secondary border-none'">
                            <label class="text-sm">
                                Woche
                            </label>
                        </button>
                        <button @click="$refs.vuecal.switchView('month')"
                                class="w-full mt-2 text-left pl-2"
                                :class="currentView === 'month' ? 'text-white font-bold border-l-2 border-l-success' : 'text-secondary border-none'">
                            <label class="text-sm">
                                Monat
                            </label>
                        </button>
                        <button @click="$refs.vuecal.switchView('year')"
                                class="w-full mt-2 text-left pl-2"
                                :class="currentView === 'year' ? 'text-white font-bold border-l-2 border-l-success' : 'text-secondary border-none'">
                            <label class="text-sm">
                                Jahr
                            </label>
                        </button>
                    </MenuItems>
                </transition>
            </Menu>
            <button class="ml-2 text-black" @click="$refs.vuecal.previous()">
                <ChevronLeftIcon class="h-5 w-5 text-primary"/>
            </button>
            <button class="ml-2 text-black" @click="$refs.vuecal.next()">
                <ChevronRightIcon class="h-5 w-5 text-primary"/>
            </button>
        </div>

        <div class="inline-flex mb-5 justify-end w-1/2">

            <!-- Calendar Filter -->
            <Menu as="div" class="relative inline-block text-left max-w-80">
                <div>
                    <MenuButton
                        class="mt-1 border border-gray-300 w-full bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                    >
                        <span class="float-left">Filter</span>
                        <ChevronDownIcon
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
                        class="absolute right-0 mt-2 max-w-80 origin-top-right divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                        <div class="inline-flex border-none w-1/5">
                            <button>
                                <FilterIcon class="w-3 mr-1 mt-0.5"/>
                            </button>
                        </div>
                        <div class="inline-flex border-none justify-end w-4/5">
                            <button class="flex" @click="resetCalendarFilter">
                                <XIcon class="w-3 mr-1 mt-0.5"/>
                                <label class="text-xs">Zurücksetzen</label>
                            </button>
                            <button class="flex ml-4" @click="saving = !saving">
                                <DocumentTextIcon class="w-3 mr-1 mt-0.5"/>
                                <label class="text-xs">Speichern</label>
                            </button>
                        </div>
                        <div class="mx-auto w-full max-w-md rounded-2xl bg-primary border-none mt-2">

                            <!-- Save Filter Section -->
                            <Disclosure v-slot="{ open }" v-if="saving" default-open>
                                <DisclosureButton
                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                >
                                    <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Gespeicherte Filter</span>
                                    <ChevronDownIcon
                                        :class="open ? 'rotate-180 transform' : ''"
                                        class="h-4 w-4 mt-0.5 text-white"
                                    />
                                </DisclosureButton>
                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                    <div class="justify-between flex">
                                        <input id="saveFilter" v-model="filterName" type="text" autocomplete="off"
                                               class="shadow-sm placeholder-darkInputText bg-darkInputBg focus:outline-none focus:ring-0 border-secondary focus:border-1 text-sm"
                                               placeholder="Name des Filters"/>
                                        <AddButton text="Speichern" class="text-sm ml-0"
                                                   @click="saveFilter"></AddButton>
                                    </div>
                                    <hr v-if="filters.length > 0" class="border-secondary rounded-full border-1 mt-4 mb-3">
                                    <button class="rounded-full bg-buttonBlue px-5 py-2 align-middle flex mb-1" v-for="filter of filters">
                                        <label @click="applyFilter(filter)" class="text-white">{{ filter.name }}</label>
                                        <XIcon @click="deleteFilter(filter.id)" class="h-3 w-3 text-white ml-1 mt-1"/>
                                    </button>
                                </DisclosurePanel>
                                <hr class="border-secondary rounded-full border-2 mt-2 mb-2">
                            </Disclosure>

                            <!-- Room Filter Section -->
                            <Disclosure v-slot="{ open }">
                                <DisclosureButton
                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                >
                                    <span
                                        :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Räume</span>
                                    <ChevronDownIcon
                                        :class="open ? 'rotate-180 transform' : ''"
                                        class="h-4 w-4 mt-0.5 text-white"
                                    />
                                </DisclosureButton>
                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                    <div v-if="currentView !== 'month'">
                                        <SwitchGroup>
                                            <div class="flex items-center">
                                                <Switch v-model="calendarFilters.showAdjoiningRooms"
                                                        :class="calendarFilters.showAdjoiningRooms ? 'bg-white' : 'bg-darkGray'"
                                                        class="relative inline-flex h-3 w-7 items-center rounded-full">
                                            <span
                                                :class="calendarFilters.showAdjoiningRooms ? 'translate-x-[18px] bg-secondary' : 'translate-x-1/3 bg-white'"
                                                class="inline-block h-2 w-2 transform rounded-full transition"/>
                                                </Switch>
                                                <SwitchLabel class="ml-4 text-xs"
                                                             :class="calendarFilters.showAdjoiningRooms ? 'text-white' : 'text-secondary'">
                                                    Nebenräume anzeigen
                                                </SwitchLabel>
                                            </div>
                                        </SwitchGroup>
                                        <SwitchGroup>
                                            <div class="flex items-center mt-2">
                                                <Switch v-model="calendarFilters.allDayFree"
                                                        :class="calendarFilters.allDayFree ? 'bg-white' : 'bg-darkGray'"
                                                        class="relative inline-flex h-3 w-7 items-center rounded-full">
                                            <span
                                                :class="calendarFilters.allDayFree ? 'translate-x-[18px] bg-secondary' : 'translate-x-1/3 bg-white'"
                                                class="inline-block h-2 w-2 transform rounded-full transition"/>
                                                </Switch>
                                                <SwitchLabel class="ml-4 text-xs"
                                                             :class="calendarFilters.allDayFree ? 'text-white' : 'text-secondary'">
                                                    ganztägig frei
                                                </SwitchLabel>
                                            </div>
                                        </SwitchGroup>

                                        <!--
                                        <Menu as="div" v-if="calendarFilters.allDayFree">
                                            <div>
                                                <MenuButton
                                                    class="p-2 my-4 text-darkInputText bg-darkInputBg border border-secondary flex w-full justify-between">
                                                    <label v-if="currentInterval === ''" class="text-sm">Zeitraum
                                                        auswählen</label>
                                                    <label v-else class="text-sm">{{ currentInterval }}</label>
                                                    <ChevronDownIcon
                                                        class="h-4 w-4 shadow-sm text-white mt-0.5 float-right"></ChevronDownIcon>
                                                </MenuButton>
                                            </div>
                                            <transition enter-active-class="transition ease-out duration-100"
                                                        enter-from-class="transform opacity-0 scale-95"
                                                        enter-to-class="transform opacity-100 scale-100"
                                                        leave-active-class="transition ease-in duration-75"
                                                        leave-from-class="transform opacity-100 scale-100"
                                                        leave-to-class="transform opacity-0 scale-95">
                                                <MenuItems
                                                    class="z-40 origin-top-left absolute overflow-y-auto mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none w-2/3">
                                                    <MenuItem v-for="interval in freeTimeIntervals" v-slot="{ active }">
                                                        <div @click="currentInterval = interval"
                                                             :class="[active ? 'bg-primaryHover text-white' : 'text-secondary',
                                                  'group px-3 py-2 text-sm subpixel-antialiased']">
                                                            {{ interval }}
                                                        </div>
                                                    </MenuItem>
                                                </MenuItems>
                                            </transition>
                                        </Menu> -->
                                    </div>

                                    <hr class="border-gray-500 mt-2 mb-2">
                                    <Disclosure v-slot="{ open }">
                                        <DisclosureButton
                                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                        >
                                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Raumkategorien</span>
                                            <ChevronDownIcon
                                                :class="open ? 'rotate-180 transform' : ''"
                                                class="h-4 w-4 mt-0.5 text-white"
                                            />
                                        </DisclosureButton>
                                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                            <div v-if="roomCategories.length > 0" v-for="category in roomCategories"
                                                 class="flex w-full mb-2">
                                                <input type="checkbox" v-model="category.checked"
                                                @change="this.changeFilterElements(calendarFilters.roomCategories, category)"
                                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                <p :class="[category.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                    {{ category.name }}</p>
                                            </div>
                                            <div v-else class="text-secondary">Noch keine Kategorien angelegt</div>
                                        </DisclosurePanel>
                                    </Disclosure>
                                    <hr class="border-gray-500 mt-2 mb-2">
                                    <Disclosure v-slot="{ open }">
                                        <DisclosureButton
                                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                        >
                                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Areale</span>
                                            <ChevronDownIcon
                                                :class="open ? 'rotate-180 transform' : ''"
                                                class="h-4 w-4 mt-0.5 text-white"
                                            />
                                        </DisclosureButton>
                                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                            <div v-if="areas.length > 0" v-for="area in areas" class="flex w-full mb-2">
                                                <input type="checkbox" v-model="area.checked"
                                                @change="this.changeFilterElements(calendarFilters.areas, area)"
                                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                <p :class="[area.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                    {{ area.label || area.name }}</p>
                                            </div>
                                            <div v-else class="text-secondary">Keine Areale angelegt</div>
                                        </DisclosurePanel>
                                    </Disclosure>
                                    <hr class="border-gray-500 mt-2 mb-2">
                                    <Disclosure v-slot="{ open }">
                                        <DisclosureButton
                                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                        >
                                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Raumeigenschaften</span>
                                            <ChevronDownIcon
                                                :class="open ? 'rotate-180 transform' : ''"
                                                class="h-4 w-4 mt-0.5 text-white"
                                            />
                                        </DisclosureButton>
                                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                            <div v-if="roomAttributes.length > 0" v-for="attribute in roomAttributes"
                                                 class="flex w-full mb-2">
                                                <input type="checkbox" v-model="attribute.checked"
                                                @change="this.changeFilterElements(calendarFilters.roomAttributes, attribute)"
                                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                <p :class="[attribute.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                    {{ attribute.name }}</p>
                                            </div>
                                            <div v-else class="text-secondary">Noch keine Raumeigenschaften angelegt
                                            </div>
                                        </DisclosurePanel>
                                    </Disclosure>
                                    <hr class="border-gray-500 mt-2 mb-2">
                                    <Disclosure v-slot="{ open }">
                                        <DisclosureButton
                                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                        >
                                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Räume</span>
                                            <ChevronDownIcon
                                                :class="open ? 'rotate-180 transform' : ''"
                                                class="h-4 w-4 mt-0.5 text-white"
                                            />
                                        </DisclosureButton>
                                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                            <div v-if="rooms.length > 0" v-for="room in rooms"
                                                 class="flex w-full mb-2">
                                                <input type="checkbox" v-model="room.checked"
                                                @change="this.changeFilterElements(calendarFilters.rooms, room)"
                                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                <p :class="[room.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                    {{ room.label }}</p>
                                            </div>
                                            <div v-else class="text-secondary">Noch keine Räume angelegt</div>
                                        </DisclosurePanel>
                                    </Disclosure>
                                </DisclosurePanel>
                            </Disclosure>

                            <hr class="border-secondary rounded-full border-2 mt-2 mb-2">

                            <!-- Event Filter Section -->
                            <Disclosure v-slot="{ open }">
                                <DisclosureButton
                                    class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm focus:outline-none focus-visible:ring-purple-500"
                                >
                                <span
                                    :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Termine</span>
                                    <ChevronDownIcon
                                        :class="open ? 'rotate-180 transform' : ''"
                                        class="h-4 w-4 mt-0.5 text-white"
                                    />
                                </DisclosureButton>
                                <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                    <hr class="border-gray-500 mt-2 mb-2">
                                    <Disclosure v-slot="{ open }">
                                        <DisclosureButton
                                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500"
                                        >
                                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Termintyp</span>
                                            <ChevronDownIcon
                                                :class="open ? 'rotate-180 transform' : ''"
                                                class="h-4 w-4 mt-0.5 text-white"
                                            />
                                        </DisclosureButton>
                                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                            <div v-for="eventType in types" class="flex w-full mb-2">
                                                <input type="checkbox" v-model="eventType.checked"
                                                @change="this.changeFilterElements(calendarFilters.eventTypes, eventType)"
                                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                <p :class="[eventType.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                    {{ eventType.name }}</p>
                                            </div>
                                        </DisclosurePanel>
                                    </Disclosure>
                                    <hr class="border-gray-500 mt-2 mb-2">
                                    <Disclosure v-slot="{ open }">
                                        <DisclosureButton
                                            class="flex w-full py-2 justify-between rounded-lg bg-primary text-left text-sm focus:outline-none focus-visible:ring-purple-500"
                                        >
                                            <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Termineigenschaften</span>
                                            <ChevronDownIcon
                                                :class="open ? 'rotate-180 transform' : ''"
                                                class="h-4 w-4 mt-0.5 text-white"
                                            />
                                        </DisclosureButton>
                                        <DisclosurePanel class="pt-2 pb-2 text-sm text-white">
                                            <div v-for="eventAttribute in eventAttributes" class="flex w-full mb-2">
                                                <input type="checkbox" v-model="eventAttribute.checked"
                                                @change="this.changeFilterBoolean(eventAttribute.value, eventAttribute.checked)"
                                                       class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                                                <p :class="[eventAttribute.checked ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                                   class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                    {{ eventAttribute.name }}</p>
                                            </div>
                                        </DisclosurePanel>
                                    </Disclosure>
                                </DisclosurePanel>
                            </Disclosure>

                        </div>
                    </MenuItems>
                </transition>
            </Menu>
            <AddButton class="bg-primary hover:bg-secondary text-white"
                       @click="openEventComponent()" text="Neue Belegung"/>
        </div>

        <CalendarFilterTagComponent :calendar-filters="calendarFilters" :event-attributes="eventAttributes"/>
    </div>

    <!--  Calendar  -->
    <div>
        <vue-cal
            ref="vuecal"
            id="vuecal"
            style="height: 600px"
            today-button
            events-on-month-view="short"
            locale="de"
            hide-view-selector
            hide-title-bar
            show-week-numbers

            :click-to-navigate="true"
            :stickySplitLabels="true"
            :disable-views="['years']"
            :events="displayedEvents"
            :split-days="displayedRooms"
            :editable-events="{ title: false, drag: true, resize: false, delete: true, create: true }"
            :snap-to-time="15"
            :selected-date="selectedDate"
            :drag-to-create-threshold="15"
            events-count-on-year-view
            v-model:active-view="currentView"

            @event-drag-create="openEventComponent($event)"
            @event-focus="openEventComponent($event)"

            @ready="fetchEvents"
            @view-change="fetchEvents($event)"
        >
            <template #event="{ event, view }">
                <div class="flex absolute right-0 top-0">
                    <img v-if="event.audience" src="/Svgs/IconSvgs/icon_public.svg" class="h-6 w-6 mx-2" alt="audienceIcon"/>
                    <img v-if="event.isLoud" src="/Svgs/IconSvgs/icon_loud.svg" class="h-6 w-6 mx-1" alt="isLoudIcon"/>
                </div>

                <div class="font-lexend text-primary truncate" v-html="event.title" />
                <span class="truncate" v-if="event.eventName && event.eventName !== event.title"> {{event.eventName}}</span>
                <span class="flex text-xs w-full text-secondary">
                    <span class="items-center mx-auto">{{ event.start.formatTime("HH:mm")}} - {{ event.end.formatTime("HH:mm") }}  </span><br/>
                </span>
            </template>
        </vue-cal>
    </div>
    <!-- Termin erstellen Modal-->
    <event-component
        v-if="createEventComponentIsVisible"
        @closed="onEventComponentClose()"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :event="selectedEvent"
        :isAdmin=" $page.props.is_admin || $page.props.can.admin_rooms"
    />
    <!-- Termin erstellen Modal-->
    <jet-dialog-modal :show="addingEvent" @close="closeAddEventModal">
        <template #content>
            <img alt="Neuer Termin" src="/Svgs/Overlays/illu_appointment_new.svg" class="-ml-6 -mt-8 mb-4"/>
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
                    <Listbox as="div" class="flex mt-6 w-1/2 mr-2" v-model="selectedEventType" v-if="canEdit" :onchange="checkCollisions()" id="eventType">
                        <ListboxButton
                            class="pl-3 border border-gray-300 w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                            <div class="flex items-center my-auto">
                                <EventTypeIconCollection :height="20" :width="20"
                                                         :iconName="selectedEventType?.svg_name"/>
                                <span class="block truncate items-center ml-3 flex">
                                            <span>{{ selectedEventType?.name }}</span>
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
                                               v-for="eventType in eventTypes"
                                               :key="eventType.name"
                                               :value="eventType"
                                               v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                        <EventTypeIconCollection :height="12" :width="12"
                                                                 :iconName="eventType?.svg_name"/>
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
                    <p class="text-xs text-red-800">{{ error?.eventType?.join('. ') }}</p>

                    <div class="mt-6 flex w-1/2 ml-2" v-if="!selectedEventType.project_mandatory">
                        <input v-if="selectedEventType.individual_name && !this.selectedProject && !creatingProject"
                               type="text"
                               v-model="addEventForm.title" placeholder="Terminname*"
                               class="text-primary h-10 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 w-full text-sm"/>
                        <input v-else type="text" v-model="addEventForm.title"
                               :placeholder="[selectedEventType.individual_name ? 'Terminname*' : 'Terminname']"
                               class="text-primary h-10 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 w-full text-sm"/>
                    </div>

                    <p class="text-xs text-red-800">{{ error?.title?.join('. ') }}</p>

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
                        <input type="text" v-model="addEventForm.projectName"
                               placeholder="Projektname von neuem Projekt*"
                               class="text-primary h-10 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full text-sm border-gray-300 "/>
                        <p class="text-xs text-red-800">{{ error?.projectName?.join('. ') }}</p>
                    </div>

                    <div class="my-auto w-full mt-4" v-else>

                        <input v-if="this.selectedProject === null" id="projectSearch" v-model="project_query"
                               type="text"
                               autocomplete="off"
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
                        <p class="text-xs text-red-800">{{ error?.projectId?.join('. ') }}</p>
                    </div>
                </div>

                <div class="flex mt-4 items-center">
                    <div v-if="collision">
                        <div class="bg-error absolute left-0 flex p-1 -mt-2 mr-0.5">
                            <img src="/Svgs/IconSvgs/icon_warning_white.svg"
                                 class="h-8 w-8 p-1 my-auto flex text-white"
                                 aria-hidden="true"/>
                        </div>
                    </div>
                    <div class="text-secondary w-1/2">
                        <label for="eventStartDate" class="text-xs subpixel-antialiased">Startdatum*</label>
                        <div class="w-full">
                            <input v-model="addEventForm.startDate" id="eventStartDate"
                                   @change="updateTimes(addEventForm)"
                                   placeholder="Startdatum*" type="date"
                                   class="border-gray-300 text-primary placeholder-secondary"/>
                            <input
                                v-model="addEventForm.startTime" id="changeStartTime"
                                @change="updateTimes(addEventForm)"
                                placeholder="StartZeit*" type="time"
                                class="border-gray-300 text-primary placeholder-secondary"/>
                        </div>
                    </div>
                    <div class="text-secondary ml-10 w-1/2">
                        <label for="eventEndDate" class="text-xs subpixel-antialiased">Enddatum*</label>
                        <div class="w-full">
                            <input v-model="addEventForm.endDate" id="eventEndDate" @change="updateTimes(addEventForm)"
                                   placeholder="Startdatum*" type="date"
                                   class="border-gray-300 text-primary placeholder-secondary"/>
                            <input
                                v-model="addEventForm.endTime" id="changeEndTime" @change="updateTimes(addEventForm)"
                                placeholder="StartZeit*" type="time"
                                class="border-gray-300 text-primary placeholder-secondary"/>
                        </div>
                    </div>

                </div>
                <p class="text-xs text-red-800">{{ error?.start?.join('. ') }}</p>
                <p class="text-xs text-red-800">{{ error?.end?.join('. ') }}</p>
                <Listbox @change="changeRoom()" as="div" class="flex" v-model="selectedRoom">
                    <ListboxButton
                        class="pl-3 border border-gray-300 bg-white w-full relative mt-6 py-2 cursor-pointer focus:outline-none sm:text-sm">
                        <div class="flex items-center my-auto">
                                        <span v-if="selectedRoom" class="block truncate items-center flex mr-2">
                                            <span>{{ selectedRoom.label }}</span>

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
                            <ListboxOption as="template" class="max-h-8"
                                           v-for="room in rooms"
                                           :key="room.label"
                                           :value="room"
                                           v-slot="{ active, selected }">
                                <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ room.label }}
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

                <p class="text-xs text-red-800">{{ error?.roomId?.join('. ') }}</p>

                <p v-if="collision" class="text-xs text-red-800">
                    Dieser Termin überschneidet sich mit {{ collision }} Terminen im selben Raum.
                    Diese könnten anderen Projekten zugeordnet sein.
                </p>

                <div class="mt-4">
                        <textarea placeholder="Was gibt es bei dem Termin zu beachten?"
                                  v-model="addEventForm.description" rows="4"
                                  class="resize-none shadow-sm p-4 focus:outline-none placeholder-secondary placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 block w-full sm:text-sm border"/>
                </div>
                <div>
                    <div v-if="selectedRoom" @mouseover="showHints()">
                        <div class="flex items-center w-full justify-center"
                             v-if="this.$page.props.is_admin || this.$page.props.can.admin_rooms || (selectedRoom.room_admins.find(user => user.id === this.$page.props.user.id) || selectedRoom.everyone_can_book)">
                            <button :class="[this.addEventForm.start === null || this.addEventForm.end === null || this.selectedRoom === null || (selectedEventType.project_mandatory && selectedProject === null && selectedProject.projectName === '') || ((addEventForm.title === '' && selectedEventType.individual_name) && addEventForm.projectName === '' && selectedProject === null) ?
                                    'bg-secondary': 'bg-buttonBlue hover:bg-buttonHover focus:outline-none']"
                                    class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover rounded-3xl"
                                    @click="updateOrCreateEvent(addEventForm, false)"
                                    :disabled="addEventForm.start === null || addEventForm.end === null || (selectedEventType.project_mandatory && selectedProject === null && addEventForm.projectName === '') || ((addEventForm.title === '' && selectedEventType.individual_name) && addEventForm.projectName === '' && selectedProject === null)">
                                Belegen
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center w-full justify-center"
                         v-if="!selectedRoom || !$page.props.is_admin || (!selectedRoom.room_admins.find(user => user.id === this.$page.props.user.id) || !selectedRoom.everyone_can_book)"
                         @mouseover="showHints()">
                        <button :class="[addEventForm.start === null || addEventForm.end === null || this.selectedRoom === null ||(selectedEventType.project_mandatory && selectedProject === null && addEventForm.projectName === '') || (addEventForm.title === '' && addEventForm.projectName === '' && selectedProject === null) ?
                                    'bg-secondary': 'bg-buttonBlue hover:bg-buttonHover focus:outline-none']"
                                class="mt-4 px-12 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover rounded-3xl"
                                @click="updateOrCreateEvent(addEventForm, true)"
                                :disabled="addEventForm.start === null
                                || addEventForm.end === null || (selectedEventType.project_mandatory
                                && selectedProject === null && addEventForm.projectName === '') || ((addEventForm.title === '' && selectedEventType.individual_name)
                                && addEventForm.projectName
                                && selectedProject === null)">
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
    <!-- Event Detail Modal -->
    <jet-dialog-modal :show="showEventModal" @close="closeEventModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8"/>
            <XIcon @click="closeEventModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div>
                <div class="mt-2 flex items-center w-full">
                    <!-- TODO: KONFLIKTANZEIGE ÜBERARBEITEN -> Aktuell werden Fehler über Collision erkannt -> Funktioniert auch noch nicht wie gewollt -->
                    <div v-if="collision > 0" class="bg-error absolute left-0 flex h-8 w-8 mt-4 mr-2">
                        <img src="/Svgs/IconSvgs/icon_warning_white.svg"
                             class="h-8 w-8 p-1 my-auto flex text-white"
                             aria-hidden="true"/>
                    </div>
                    <Listbox
                        v-if="checkProjectPermission(selectedEvent.projectId,this.$page.props.user.id) || this.$page.props.is_admin || selectedEvent.created_by.id === this.$page.props.user.id"
                        as="div"
                        class="flex w-full" v-model="selectedEvent.eventTypeId">
                        <div class="relative">
                            <ListboxButton
                                class="bg-white w-full relative mt-4 py-2 cursor-pointer focus:outline-none">
                                <div class="flex items-center">
                                    <EventTypeIconCollection :height="24" :width="24"
                                                             :iconName="eventTypes.find(x => x.id === selectedEvent.eventTypeId).svg_name"/>
                                    <span class="block truncate items-center text-3xl font-black ml-3 flex">
                                                <span>
                                                    {{ eventTypes.find(x => x.id === selectedEvent.eventTypeId).name }}
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
                                                   v-for="eventType in eventTypes"
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
                                                 :iconName="eventTypes.find(x => x.id === selectedEvent.eventTypeId).svg_name"/>
                        <span class="block truncate items-center text-3xl font-black ml-3 flex">
                                        <span>
                                            {{ eventTypes.find(x => x.id === selectedEvent.eventTypeId).name }}
                                        </span>
                                    </span>
                    </div>
                    <div class="flex justify-end"
                         v-if="checkProjectPermission(selectedEvent.projectId,this.$page.props.user.id) || this.$page.props.is_admin || this.$page.props.can.admin_rooms || selectedEvent.created_by.id === this.$page.props.user.id">
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
                                        <!-- TODO: WENN ROOM ADMINS EINGEBAUT SIND (admins muss in den rooms props sein) WIEDER NACH OCCUPANCY DAS HIER EINFÜGEN:  && ((rooms.find(room => room.id === selectedEvent.roomId).room_admins.find(admin => admin.id === this.$page.props.user.id) selectedRoom.everyone_can_book) || this.$page.props.is_admin || this.$page.props.can.admin_rooms) -->
                                        <MenuItem
                                            v-if="selectedEvent.occupancy_option && ((rooms.find(room => room.id === selectedEvent.roomId).room_admins.find(admin => admin.id === this.$page.props.user.id) || selectedRoom.everyone_can_book) || this.$page.props.is_admin || this.$page.props.can.admin_rooms)"
                                            v-slot="{ active }">
                                            <a href="#" @click="approveRequest(selectedEvent)"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <PencilAltIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Raumbelegung zusagen
                                            </a>
                                        </MenuItem>
                                        <!-- TODO: HIER AUCH DANN EINFÜGEN WENN ROOM ADMINS MITGEGEBEN WERDEN && ((rooms.find(room => room.id === selectedEvent.roomId).room_admins.find(admin => admin.id === this.$page.props.user.id) || selectedRoom.everyone_can_book) || this.$page.props.is_admin || this.$page.props.can.admin_rooms) -->
                                        <MenuItem
                                            v-if="selectedEvent.occupancy_option && ((rooms.find(room => room.id === selectedEvent.roomId).room_admins.find(admin => admin.id === this.$page.props.user.id) || selectedRoom.everyone_can_book) || this.$page.props.is_admin || this.$page.props.can.admin_rooms)"
                                            v-slot="{ active }">
                                            <a href="#" @click="declineRequest(selectedEvent)"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <PencilAltIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Raumbelegung absagen
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a href="#" @click="openDeleteEventModal(selectedEvent)"
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
                        <div v-if="selectedEvent.projectId !== null" class="flex items-center w-2/3 text-sm">
                            <div class="my-auto flex w-28">Zugeordnet zu</div>
                            <div>
                                <a
                                    :href="route('projects.show', {project: selectedEvent.projectId, openTab: 'calendar'})"
                                    class="ml-3 text-md flex font-bold font-lexend text-primary">
                                    {{ selectedEvent.projectName }}
                                </a>
                            </div>
                        </div>
                        <div v-else class="flex font-lexend text-secondary subpixel-antialiased text-sm">
                            <div>Keinem Projekt zugeordnet</div>
                        </div>
                        <div
                            v-if="checkProjectPermission(selectedEvent.projectId,this.$page.props.user.id) || this.$page.props.can.admin_rooms || this.$page.props.is_admin || (this.myRooms ? this.myRooms.length > 0 : false) || selectedEvent.created_by.id === this.$page.props.user.id"
                            class="w-1/3">
                            <Listbox @change="changeRoom()" as="div" class="flex" v-model="selectedRoom">
                                <ListboxButton
                                    class="pl-3 border border-gray-300 bg-white w-full relative mt-6 py-2 cursor-pointer focus:outline-none sm:text-sm">
                                    <div class="flex items-center my-auto">
                                        <span v-if="selectedRoom" class="block truncate items-center flex mr-2">
                                            <span>{{ selectedRoom.label }}</span>

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
                                        class="absolute z-10 mt-16 bg-primary shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="room in rooms"
                                                       :key="room.label"
                                                       :value="room"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ room.label }}
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

                        </div>
                        <div v-else class="flex items-center my-auto">
                                        <span v-if="selectedEvent.roomId" class="block truncate items-center flex">
                                            <span>{{ rooms.find(x => x.id === selectedEvent.roomId).name }}</span>

                                        </span>
                        </div>
                    </div>
                </div>
                <div class="flex font-lexend text-secondary subpixel-antialiased text-xs my-auto">
                    <div class="my-auto">angelegt von:</div>
                    <img v-if="selectedEvent.created_by.profile_photo_url"
                         :data-tooltip-target="selectedEvent.created_by.id"
                         :src="selectedEvent.created_by.profile_photo_url"
                         :alt="selectedEvent.created_by.name"
                         class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                    <div class="flex ml-2 my-auto">
                        {{ selectedEvent.created_by.first_name }} {{ selectedEvent.created_by.last_name }}
                    </div>
                </div>
                <div>
                    <div class="mt-4 w-full"
                         v-if="checkProjectPermission(selectedEvent.projectId,this.$page.props.user.id) || this.$page.props.is_admin || this.$page.props.can.admin_rooms || selectedEvent.created_by.id === this.$page.props.user.id">
                        <input type="text" v-model="selectedEvent.name"
                               :placeholder="[selectedEventType.individual_name ? 'Terminname' : 'Terminname*']"
                               class="text-primary font-black h-10 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full text-sm border-gray-300 "/>
                    </div>
                    <div v-else>
                        <div class="w-full font-bold font-lexend text-primary tracking-wide text-xl my-2">
                            {{ selectedEvent.name }}
                        </div>
                    </div>
                </div>
                <!-- TODO: wieder created BY || selectedEvent.created_by.id === this.$page.props.user.id -->
                <div
                    v-if="checkProjectPermission(selectedEvent.projectId,this.$page.props.user.id) || this.$page.props.is_admin || this.$page.props.can.admin_rooms || selectedEvent.created_by.id === this.$page.props.user.id"
                    class="flex mt-4">
                    <div class="text-secondary w-1/2">
                        <label for="eventStartDate" class="text-xs subpixel-antialiased">Startdatum*</label>
                        <div class="w-full">
                            <input v-model="selectedEvent.startDate" id="changeEventStartDate"
                                   placeholder="Startdatum*" type="date"
                                   class="border-gray-300 text-primary placeholder-secondary"/>
                            <input
                                v-model="selectedEvent.startTime" id="eventStartTime"
                                placeholder="StartZeit*" type="time"
                                class="border-gray-300 text-primary placeholder-secondary"/>
                        </div>
                    </div>
                    <div class="text-secondary ml-10 w-1/2">
                        <label for="eventEndDate" class="text-xs subpixel-antialiased">Enddatum*</label>
                        <div class="w-full">
                            <input v-model="selectedEvent.endDate" id="changeEventEndDate"
                                   placeholder="Startdatum*" type="date"
                                   class="border-gray-300 text-primary placeholder-secondary"/>
                            <input
                                v-model="selectedEvent.endTime" id="eventEndTime"
                                placeholder="StartZeit*" type="time"
                                class="border-gray-300 text-primary placeholder-secondary"/>
                        </div>
                    </div>
                </div>
                <div v-else class="mt-4 subpixel-antialiased">
                    {{
                        selectedEvent.start.split('-')[2].split(' ')[0]
                    }}.{{
                        selectedEvent.start.toLocaleString().split('-')[1]
                    }}.{{ selectedEvent.start.toLocaleString().split('-')[0] }},
                    {{ selectedEvent.start.split('-')[2].split(' ')[1] }} -
                    {{
                        selectedEvent.end.split('-')[2].split(' ')[0]
                    }}.{{
                        selectedEvent.end.toLocaleString().split('-')[1]
                    }}.{{ selectedEvent.end.toLocaleString().split('-')[0] }},
                    {{ selectedEvent.end.split('-')[2].split(' ')[1] }}
                </div>
                <div
                    v-if="checkProjectPermission(selectedEvent.projectId,this.$page.props.user.id) || this.$page.props.can.admin_rooms || this.$page.props.is_admin || (this.myRooms ? this.myRooms.length > 0 : false) || selectedEvent.created_by.id === this.$page.props.user.id">
                    <div class="mt-4">
                            <textarea placeholder="Was gibt es bei dem Termin zu beachten?"
                                      v-model="selectedEvent.description" rows="4"
                                      class="resize-none font-black shadow-sm placeholder-secondary p-4 placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 block w-full sm:text-sm border"/>
                    </div>
                    <div class="flex items-center w-full justify-center">
                        <button
                            v-if="!rooms.find(room => room.id === selectedEvent.roomId) || rooms.find(room => room.id === selectedEvent.roomId) && (rooms.find(room => room.id === selectedEvent.roomId).room_admins.find(user => user.id === this.$page.props.user.id) || this.$page.props.is_admin) "
                            :class="[selectedEvent.start === null || selectedEvent.end === null || selectedEvent.selectedRoom === null ?
                                    'bg-secondary': 'bg-buttonBlue hover:bg-buttonHover focus:outline-none']"
                            class="mt-4 px-12 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover rounded-3xl"
                            @click="updateOrCreateEvent(selectedEvent,false)"
                            :disabled="selectedEvent.start === null && selectedEvent.end === null">
                            Speichern
                        </button>
                        <div class="items-center"
                             v-if="rooms.find(room => room.id === selectedEvent.roomId) ?  ((!rooms.find(room => room.id === selectedEvent.roomId).room_admins.find(user => user.id === this.$page.props.user.id) || selectedRoom.everyone_can_book) && !this.$page.props.is_admin) : false">
                            <button :class="[selectedEvent.start === null || selectedEvent.end === null || selectedEvent.selectedRoom === null ?
                                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                    class="mt-4 px-12 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover rounded-3xl"
                                    @click="updateOrCreateEvent(selectedEvent,true)"
                                    :disabled="selectedEvent.start === null || selectedEvent.end === null || selectedEvent.selectedRoom === null">
                                Raum anfragen
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="subpixel-antialiased mt-4">
                    {{ selectedEvent.description }}
                </div>
            </div>
        </template>
    </jet-dialog-modal>
    <!-- Event löschen Modal -->
    <jet-dialog-modal :show="deletingEvent" @close="closeDeleteEventModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="font-black font-lexend text-primary text-3xl my-2">
                    Event löschen
                </div>
                <XIcon @click="closeDeleteEventModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-error subpixel-antialiased">
                    Bist du sicher, dass du {{ selectedEvent.title }} aus dem
                    System löschen möchtest?
                </div>
                <div class="flex justify-between mt-6">
                    <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="deleteEvent">
                        Löschen
                    </button>
                    <div class="flex my-auto">
                            <span @click="closeDeleteEventModal()"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                    </div>
                </div>
            </div>

        </template>

    </jet-dialog-modal>
    <!-- Event Detail Modal -->
    <jet-dialog-modal :show="false" @close="selectedEvent = null">
        <template #content>
            <img src="/Svgs/Overlays/illu_appointment_new.svg" class="-ml-6 -mt-8 mb-4" alt="calendar-icon"/>
            <div class="mx-4">
                <div class="flex justify-between">
                    <div class="font-black font-lexend text-primary tracking-wide text-3xl my-2">
                        Event Details
                    </div>
                    <button @click="deleteEvent"
                            v-if="selectedEvent.id"
                            class="text-white bg-red-800 hover:bg-red-600 rounded-lg py-2 px-4 m-3">
                        Löschen
                    </button>
                </div>
                <XIcon @click="selectedEvent = null"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                       aria-hidden="true"/>
            </div>

            <form @submit.prevent="updateOrCreateEvent(selectedEvent, false)">

                <div class="my-5 w-full border-gray-200 border-b-2"></div>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <label for="title" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Termin Title
                    </label>
                    <input id="title"
                           type="text"
                           class="rounded focus:border-indigo-300 w-full md:w-1/2"
                           v-model="selectedEvent.title"/>
                </div>
                <p class="text-xs text-red-800">{{ error?.title?.join('. ') }}</p>

                <div class="text-secondary text-xs">
                    Bitte beachte, dass du Vor- und Nachbereitungszeit einplanst.
                </div>

                <div class="form-group flex justify-between my-2">
                    <label for="start" class="form-label w-full md:w-1/2 text-gray-700 mt-3">Start</label>
                    <input id="start"
                           @change="checkCollisions"
                           type="datetime-local"
                           v-model="selectedEvent.start"
                           class="rounded focus:border-indigo-300 w-full md:w-1/2">
                </div>
                <p class="text-xs text-red-800">{{ error?.start?.join('. ') }}</p>

                <div class="form-group flex justify-between my-2">
                    <label for="end" class="form-label w-full md:w-1/2 text-gray-700 mt-3">Ende</label>
                    <input id="end"
                           @change="checkCollisions"
                           type="datetime-local"
                           v-model="selectedEvent.end"
                           class="rounded focus:border-indigo-300 w-full md:w-1/2">
                </div>
                <p class="text-xs text-red-800">{{ error?.end?.join('. ') }}</p>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <label for="roomId" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Raum
                    </label>
                    <select id="roomId"
                            @change="checkCollisions"
                            class="rounded focus:border-indigo-300 w-full md:w-1/2"
                            v-model="selectedEvent.roomId">
                        <option v-for="room in rooms" :value="room.id">
                            {{ room.label }}
                        </option>
                    </select>
                </div>
                <p class="text-xs text-red-800">{{ error?.roomId?.join('. ') }}</p>

                <p v-if="collision" class="text-xs text-red-800">
                    Dieser Termin überschneidet sich mit {{ collision }} Terminen im selben Raum.
                    Diese könnten anderen Projekten zugeordnet sein.
                </p>

                <div class="my-5 w-full border-gray-200 border-b-2"></div>

                <div class="form-group flex justify-between my-2">
                    <label for="audience" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Wird Publikum anwesend sein?
                    </label>
                    <input type="checkbox"
                           id="audience"
                           value="audience"
                           class="w-10 h-10 rounded-full focus:border-indigo-300"
                           v-model="selectedEvent.audience">
                </div>
                <p class="text-xs text-red-800">{{ error?.audience?.join('. ') }}</p>

                <div class="form-group flex justify-between my-2">
                    <label for="isLoud" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Könnte es laut werden?
                    </label>
                    <input type="checkbox"
                           id="isLoud"
                           value="isLoud"
                           class="w-10 h-10 rounded-full focus:border-indigo-300"
                           v-model="selectedEvent.isLoud">
                </div>
                <p class="text-xs text-red-800">{{ error?.isLoud?.join('. ') }}</p>

                <div class="my-5 w-full border-gray-200 border-b-2"></div>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <label for="projectId" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Bestehendem Projekt zuordnen
                    </label>
                    <select id="projectId"
                            class="rounded focus:border-indigo-300 w-full md:w-1/2"
                            v-model="selectedEvent.projectId">
                        <option :value="null">
                            Neues Projekt erstellen
                        </option>
                        <option v-for="project in projects" :value="project.id">
                            {{ project.label }}
                        </option>
                    </select>
                </div>
                <p class="text-xs text-red-800">{{ error?.projectId?.join('. ') }}</p>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <label for="projectName" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Name für ein neues Projekt
                    </label>
                    <input id="projectName"
                           type="text"
                           class="rounded focus:border-indigo-300 w-full md:w-1/2 disabled:opacity-25"
                           :disabled="selectedEvent.projectId"
                           v-model="selectedEvent.projectName"/>
                </div>
                <p class="text-xs text-red-800">{{ error?.projectName?.join('. ') }}</p>

                <div class="text-secondary text-xs">
                    Der Name wird nur gespeichert, wenn du ein neues Projekt auswählst
                </div>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <label for="type" class="form-label w-full md:w-1/2 text-gray-700 mt-3">Termin Typ</label>
                    <select id="type"
                            class="rounded focus:border-indigo-300 w-full md:w-1/2"
                            v-model="selectedEvent.eventTypeId">
                        <option v-for="type in types" :value="type.id">
                            <EventTypeIconCollection :height="12" :width="12" :iconName="type?.img"/>
                            {{ type.label }}
                        </option>
                    </select>
                </div>
                <p class="text-xs text-red-800">{{ error?.eventType?.join('. ') }}</p>

                <div class="my-5 w-full border-gray-200 border-b-2"></div>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <label for="description" class="form-label w-full md:w-1/2 text-gray-700 mt-3">
                        Weitere Termin informationen:
                    </label>
                    <textarea id="description"
                              type="text"
                              class="rounded focus:border-indigo-300 w-full md:w-1/2"
                              v-model="selectedEvent.description"></textarea>
                </div>
                <p class="text-xs text-red-800">{{ error?.description?.join('. ') }}</p>

                <div class="my-5 w-full border-gray-200 border-b-2"></div>

                <div class="form-group flex flex-col md:flex-row justify-between my-2">
                    <button type="submit"
                            class="text-white bg-primary hover:bg-blue-800 rounded-lg py-2 px-4 m-3 w-full">
                        Speichern
                    </button>
                </div>
            </form>

        </template>
    </jet-dialog-modal>
</template>

<script>

import VueCal from 'vue-cal'
import 'vue-cal/dist/vuecal.css'
import JetDialogModal from "@/Jetstream/DialogModal";
import {
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    DocumentTextIcon,
    DotsVerticalIcon,
    FilterIcon,
    PencilAltIcon,
    TrashIcon,
    XCircleIcon,
    XIcon,
    CalendarIcon

} from '@heroicons/vue/outline';
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
    Listbox,
    ListboxButton,
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
import {CheckIcon, ChevronUpIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import {Link, useForm} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton";
import EventComponent from "@/Layouts/Components/EventComponent";
import CalendarFilterComponent from "@/Layouts/Components/CalendarFilterComponent";
import CalendarFilterTagComponent from "@/Layouts/Components/CalendarFilterTagComponent";
import Button from "@/Jetstream/Button";

export default {
    name: 'CalendarComponent',
    components: {
        Button,
        CalendarFilterTagComponent,
        CalendarFilterComponent,
        CalendarIcon,
        FilterIcon,
        SwitchLabel,
        SwitchGroup,
        Disclosure,
        DisclosureButton,
        DisclosurePanel,
        VueCal,
        JetDialogModal,
        XIcon,
        DocumentTextIcon,
        XCircleIcon,
        EventTypeIconCollection,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        ChevronDownIcon,
        ChevronUpIcon,
        ChevronLeftIcon,
        ChevronRightIcon,
        SvgCollection,
        CheckIcon,
        Switch,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        AddButton,
        Link,
        EventComponent,
    },
    props: ['project', 'room', 'initialView', 'eventTypes'],
    data() {
        return {
            displayDate: '',
            filters: [],
            filterName: '',
            selectedDate: null,
            calendarFilters: {
                rooms: [],
                areas: [],
                eventTypes: [],
                roomAttributes: [],
                roomCategories: [],
                isLoud: null,
                isNotLoud: null,
                hasAudience: null,
                hasNoAudience: null,
                adjoiningNoAudience: null,
                adjoiningNotLoud: null,
                allDayFree: null,
                showAdjoiningRooms: null
            },
            saving: false,
            roomFilters: {
                showAdjoiningRooms: false,
                onlyFreeRooms: false
            },
            eventAttributes: {
                isLoud: {
                    name: 'laut',
                    value: 'isLoud',
                    checked: false
                },
                isNotLoud: {
                    name: 'nicht laut',
                    value: 'isNotLoud',
                    checked: false
                },
                adjoiningNotLoud: {
                    name: 'ohne laute Nebenveranstaltung',
                    value: 'adjoiningNotLoud',
                    checked: false
                },
                hasAudience: {
                    name: 'mit Publikum',
                    value: 'hasAudience',
                    checked: false
                },
                hasNoAudience: {
                    name: 'ohne Publikum',
                    value: 'hasNoAudience',
                    checked: false
                },
                adjoiningNoAudience: {
                    name: 'ohne Nebenveranstaltung mit Publikum',
                    value: 'adjoiningNoAudience',
                    checked: false
                },
            },
            types: [],
            showFreeRooms: false,
            showAdjoiningRooms: false,
            myRooms: [],
            newEventError: null,
            assignProject: true,
            addingEvent: false,
            selectedRoom: null,
            selectedProject: null,
            project_query: "",
            project_search_results: [],
            creatingProject: false,
            selectedEventType: this.eventTypes[0],
            events: [],
            displayedEvents: [],
            areaFilter: [],
            roomFilter: [],
            typeFilter: [],
            rooms: [],
            areas: [],
            displayedRooms: [],
            projects: [],
            selectedEvent: null,
            error: null,
            collision: 0,
            eventsSince: null,
            eventsUntil: null,
            showEventModal: false,
            deletingEvent: false,
            currentView: this.initialView ?? 'week',
            roomCategories: [],
            roomAttributes: [],
            eventComponentIsVisible: false,
            createEventComponentIsVisible: false,
            addEventForm: useForm({
                title: '',
                startDate: null,
                startTime: null,
                start: null,
                endDate: null,
                endTime: null,
                end: null,
                description: '',
                occupancy_option: false,
                isLoud: false,
                audience: false,
                roomId: null,
                projectId: null,
                eventTypeId: null,
                projectName: null,
                user_id: this.$page.props.user.id,
            }),
        }
    },
    created() {
        if (!HTMLElement.prototype.scrollTo) HTMLElement.prototype.scrollTo = function ({ top }) { this.scrollTop = top }
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
    methods: {
        applyFilter(filter) {
            this.calendarFilters = filter;
            this.changeChecked(this.rooms, 'rooms')
            this.changeChecked(this.areas, 'areas')
            this.changeChecked(this.roomCategories, 'roomCategories')
            this.changeChecked(this.roomAttributes, 'roomAttributes')
            this.changeChecked(this.types, 'eventTypes')

            Object.entries(this.eventAttributes).forEach(entry => {
                Object.entries(this.calendarFilters).forEach(filterEntry => {
                    if(entry[1].value === filterEntry) {
                        entry[1].checked = true
                    }
                })
            })

            this.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil,
            });
        },
        changeChecked(array, filterName) {
            array.forEach(object => {
                if(this.calendarFilters[`${filterName}`].some(filterObj => filterObj.id === object.id)) {
                    object.checked = true
                }
            })
        },
        async saveFilter() {
            const filterIds = this.getFilterIds();
            await axios.post('/filters', { name: this.filterName, calendarFilters: filterIds})
            await axios.get('/filters')
                .then(response => {
                    this.filters = response.data;
                })
        },
        async deleteFilter(id) {
            await axios.delete(`/filters/${id}`)
            this.resetCalendarFilter();
            await axios.get('/filters')
                .then(response => {
                    this.filters = response.data
                })
        },
        async changeFilterElements(filterArray, element) {
            if (element.checked) {
                filterArray.push(element)
            } else {
                // entry[0] is the key, e.g. room_categories. entry[1] is the array corresponding to the key.
                Object.entries(this.calendarFilters).forEach(entry => {
                    if (Array.isArray(entry[1]) && entry[1].length > 0) {

                        if (entry[1].filter(obj => obj.id === element.id)) {
                            this.calendarFilters[entry[0]] = filterArray.filter(elem => element.id !== elem.id)
                        }
                    }
                })
            }
            await this.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil,
            });
        },
        changeFilterBoolean(filter, variable) {
            this.calendarFilters[`${filter}`] = variable
            this.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil,
            });
        },
        resetCalendarFilter() {
            this.addFilterableVariable(this.rooms, false);
            this.addFilterableVariable(this.areas, false);
            this.addFilterableVariable(this.roomCategories, false);
            this.addFilterableVariable(this.roomAttributes, false);
            this.addFilterableVariable(this.types, false);
            Object.entries(this.calendarFilters).forEach(entry => {
                if (Array.isArray(entry[1])) {
                    entry[1].length = 0;
                } else {
                    this.calendarFilters[entry[0]] = false;
                }
            })

            Object.entries(this.eventAttributes).forEach(entry => {
                entry[1].checked = false;
            })


            this.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil
            });
        },
        resetArrayChecked() {

        },
        resetBooleanChecked() {

        },
        openEventComponent(event = null) {
            if (event === null) {
                this.selectedEvent = null;
                this.createEventComponentIsVisible = true;
                return;
            }

            if (!event.id) {
                event = {
                    start: event?.start,
                    end: event?.end,
                    projectId: this.project?.id,
                    projectName: this.project?.name,
                    roomId: event.roomId,
                }
            }

            this.selectedEvent = event;
            this.createEventComponentIsVisible = true;
        },

        onEventComponentClose() {
            this.createEventComponentIsVisible = false;
            this.fetchEvents({startDate: this.eventsSince, endDate: this.eventsUntil});
        },
        /**
         * Fetch the events from server
         * initialise possible rooms, types and projects
         *
         * @param view
         * @param {Date} startDate
         * @param {Date} endDate
         * @returns {Promise<void>}
         */
        async fetchEvents({view = null, startDate = null, endDate = null}) {

            this.currentView = view ?? this.currentView ?? 'week';

            this.scrollToNine();
            this.setDisplayDate(this.currentView, startDate)

            this.eventsSince = startDate ?? this.eventsSince;
            this.eventsUntil = endDate ?? this.eventsUntil;

            const filters = this.getFilterIds();

            console.log(filters);

            await axios
                .get('/events/', {
                    params: {
                        calendarFilters: filters,
                        start: this.eventsSince,
                        end: this.eventsUntil,
                        projectId: this.project?.id,
                        roomId: this.roomId,
                    }
                })
                .then(response => {
                    this.events = response.data.events
                    this.projects = response.data.projects
                    this.filters = response.data.calendarFilters

                    if (this.rooms.length === 0) {
                        this.rooms = response.data.rooms
                        this.displayedRooms = this.rooms;
                        this.addFilterableVariable(this.rooms, false)
                    }
                    if (this.areas.length === 0) {
                        this.areas = response.data.areas
                        this.addFilterableVariable(this.areas, false)
                    }
                    if (this.roomCategories.length === 0) {
                        this.roomCategories = response.data.roomCategories
                        this.addFilterableVariable(this.roomCategories, false)
                    }
                    if (this.roomAttributes.length === 0) {
                        this.roomAttributes = response.data.roomAttributes
                        this.addFilterableVariable(this.roomAttributes, false)
                    }
                    if (this.types.length === 0) {
                        this.types = this.eventTypes
                        this.addFilterableVariable(this.types, false)
                    }

                    // color coding of rooms
                    //this.events.map(event => event.class = colors[event.split % colors.length])

                    // fix timezone to current local
                    this.events.map(event => event.start = new Date(event.start))
                    this.events.map(event => event.end = new Date(event.end))

                    this.displayedEvents = this.events;

                    console.log(this.calendarFilters.rooms);
                    this.displayedRooms = (this.calendarFilters.rooms.length > 0 ? this.calendarFilters.rooms : this.rooms)
                });
        },
        getFilterIds() {
            let filterIds = {};
            filterIds.roomCategoryIds = this.calendarFilters.roomCategories.map(elem => elem.id);
            filterIds.roomAttributeIds = this.calendarFilters.roomAttributes.map(elem => elem.id);
            filterIds.roomIds = this.calendarFilters.rooms.map(elem => elem.id);
            filterIds.areaIds = this.calendarFilters.areas.map(elem => elem.id);
            filterIds.eventTypeIds = this.calendarFilters.eventTypes.map(elem => elem.id);
            filterIds.isLoud = this.calendarFilters.isLoud
            filterIds.isNotLoud = this.calendarFilters.isNotLoud
            filterIds.hasAudience = this.calendarFilters.hasAudience
            filterIds.hasNoAudience = this.calendarFilters.hasNoAudience
            filterIds.adjoiningNoAudience = this.calendarFilters.adjoiningNoAudience
            filterIds.adjoiningNotLoud = this.calendarFilters.adjoiningNotLoud
            filterIds.allDayFree = this.calendarFilters.allDayFree
            filterIds.showAdjoiningRooms = this.calendarFilters.showAdjoiningRooms
            return filterIds;
        },
        openEventModal(event) {
            console.log(event);
            this.selectedRoom = this.rooms.find((x) => x.id === event.roomId);
            if (event.title !== '') {
                const offset = new Date(event.start).getTimezoneOffset()
                console.log(offset);
                this.selectedEvent = event;
                this.checkCollisions();
                let startDate = new Date(new Date(event.start).setMinutes(new Date(event.start).getMinutes() - offset))
                this.selectedEvent.start = startDate;
                this.selectedEvent.startDate = startDate.toISOString().slice(0, 10);
                this.selectedEvent.startTime = startDate.toISOString().slice(11, 16);
                let endDate = new Date(new Date(event.end).setMinutes(new Date(event.end).getMinutes() - offset))
                this.selectedEvent.end = endDate;
                this.selectedEvent.endDate = endDate.toISOString().slice(0, 10);
                this.selectedEvent.endTime = endDate.toISOString().slice(11, 16);
                this.showEventModal = true;
            } else {
                this.checkCollisions();
                this.openAddEventModal(event);
            }
        },
        closeEventModal() {
            this.showEventModal = false;
            this.selectedEvent = null;
            this.fetchEvents({startDate: this.eventsSince, endDate: this.eventsUntil})
        },
        openAddEventModal(event = null) {

            this.error = null;

            if (event !== null) {
                const offset = new Date(event.start).getTimezoneOffset()
                let startDate = new Date(new Date(event.start).setMinutes(new Date(event.start).getMinutes() - offset))
                this.addEventForm.start = startDate;
                this.addEventForm.startDate = startDate.toISOString().slice(0, 10);
                this.addEventForm.startTime = startDate.toISOString().slice(11, 16);
                let endDate = new Date(new Date(event.end).setMinutes(new Date(event.end).getMinutes() - offset))
                this.addEventForm.end = endDate;
                this.addEventForm.endDate = endDate.toISOString().slice(0, 10);
                this.addEventForm.endTime = endDate.toISOString().slice(11, 16);
            }
            if (this.project) {
                this.addEventForm.projectId = this.projectId;
                this.selectedProject = this.project;
            }
            if (this.room) {
                this.addEventForm.roomId = this.room.id;
                this.selectedRoom = this.rooms.find((x) => x.id === this.room.id);
            }
            this.addingEvent = true;
        },
        closeAddEventModal() {
            this.addingEvent = false;
            this.assignProject = false;
            this.selectedProject = null;
            this.newProjectName = '';
            this.creatingProject = false;
            this.addEventForm.eventType = null;
            this.addEventForm.title = '';
            this.addEventForm.start = null;
            this.addEventForm.startDate = null;
            this.addEventForm.startTime = null;
            this.addEventForm.endDate = null;
            this.addEventForm.endTime = null;
            this.addEventForm.end = null;
            this.addEventForm.description = '';
            this.addEventForm.occupancy_option = false;
            this.addEventForm.is_loud = false;
            this.addEventForm.audience = false;
            this.selectedRoom = null;
            this.addEventForm.projectId = null;
            this.addEventForm.projectName = null;
            this.selectedEventType = this.eventTypes[0];
            this.newEventError = null;
            this.fetchEvents({startDate: this.eventsSince, endDate: this.eventsUntil})
        },
        showHints() {
            if (this.selectedRoom === undefined || this.selectedRoom === null) {
                this.newEventError = 'Wähle zuerst einen Raum aus.';
            } else if (this.addEventForm.start === undefined) {
                this.newEventError = 'Wähle zuerst eine Startzeit aus.';
            } else if (this.addEventForm.end === undefined) {
                this.newEventError = 'Wähle zuerst eine Endzeit aus.';
            } else if (this.selectedEventType.project_mandatory && this.selectedProject === null && this.addEventForm.projectName === '') {
                this.newEventError = 'Gib zuerst einen Projektnamen an.';
            } else if (this.assignProject && (this.selectedProject === null && this.addEventForm.projectName === '')) {
                this.newEventError = 'Gib zuerst einen Projektnamen ein';
            } else if ((this.addEventForm.title === '' && this.selectedEventType.individual_name)
                && this.newProjectName === ''
                && this.selectedProject === null) {
                this.newEventError = 'Gib zuerst einen Terminnamen an.';
            } else {
                this.newEventError = ''
            }
        },
        checkProjectPermission(wantedProjectId, userId) {
            // TODO: Hier den projecten auch die project_admins mitgeben und dann den Code wieder reinnehmen
            if (wantedProjectId) {
                return (this.projects.find(project => project.id === wantedProjectId).project_admins.find(admin => admin.id === userId) || this.projects.find(project => project.id === wantedProjectId).project_managers.find(admin => admin.id === userId)) || this.$page.props.is_admin
            } else {
                return false;
            }

        },
        updateTimes(event) {
            if (event.startDate) {
                if (!event.endDate) {
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
                                event.endDate = new Date(date.setDate(new Date(event.endDate).getDate() + 1)).toISOString().slice(0, 10);
                                this.setCombinedTimeString(event.endDate, event.endTime, 'end', event);
                            } else {
                                event.endTime = this.getNextHourString(event.startTime)
                            }
                        }
                    }
                    this.setCombinedTimeString(event.startDate, event.startTime, 'start', event);
                } else {
                    this.setCombinedTimeString(event.startDate, '00:00', 'start', event);
                }
            }
            if (event.endDate) {
                if (event.endTime) {
                    this.setCombinedTimeString(event.endDate, event.endTime, 'end', event);
                } else {
                    this.setCombinedTimeString(event.endDate, '23:59', 'end', event);
                }

            }

            this.validateStartBeforeEndTime(event);

            this.checkCollisions();
        },
        async validateStartBeforeEndTime(event) {

            this.error = null;
            if (event.start && event.end) {
                return await axios
                    .post('/events', {start: event.start, end: event.end}, {headers: {'X-Dry-Run': true}})
                    .catch(error => this.error = error.response.data.errors);
            }

        },
        setCombinedTimeString(date, time, target, event) {
            let combinedDateString = (date.toString() + ' ' + time);
            const offset = new Date(combinedDateString).getTimezoneOffset()

            if (target === 'start') {
                if (offset === -60) {
                    event.start = new Date(new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)).toISOString().slice(0, 16);
                } else {
                    event.start = new Date(new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)).toISOString().slice(0, 16);
                }
            } else if (target === 'end') {
                if (offset === -60) {
                    event.end = new Date(new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 60)).toISOString().slice(0, 16);
                } else {
                    event.end = new Date(new Date(combinedDateString).setMinutes(new Date(combinedDateString).getMinutes() + 120)).toISOString().slice(0, 16);
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
        deleteSelectedProject() {
            this.selectedProject = null;
            this.addEventForm.projectId = null;
        },
        changeRoom() {
            if (this.selectedEvent) {
                this.selectedEvent.roomId = this.selectedRoom.id;
            } else {
                this.addEventForm.roomId = this.selectedRoom.id;
            }
            this.checkCollisions();
        },
        addProjectToEvent(project) {
            this.selectedProject = project;
            this.addEventForm.projectId = project.id;
            this.project_query = ""
        },
        switchProjectMode() {
            this.addEventForm.projectName = '';
            this.addEventForm.projectId = null;
        },

        scrollToNine() {
            const calendar = document.querySelector('#vuecal .vuecal__bg')
            calendar.scrollTo({ top: 9 * 40, behavior: 'smooth' })
        },

        addFilterableVariable(dataArray, boolean) {
            dataArray.forEach(element => element.checked = boolean);
        },

        setDisplayDate(view, startDate) {

            if(view === 'day') {
                const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
                this.displayDate = startDate.toLocaleDateString('de-DE', options)
            }
            else if(view === 'week') {
                let beginOfYear = new Date(startDate.getFullYear(), 0, 1);
                let days = Math.floor((startDate - beginOfYear) /
                    (24 * 60 * 60 * 1000));

                let weekNumber = Math.ceil(days / 7);
                this.displayDate = 'Woche - KW ' + weekNumber
            }
            else if(view === 'month') {
                this.displayDate = "Monat - " + startDate.toLocaleDateString('de-DE', { month: 'long', year: 'numeric'})
            }
            else {
                this.displayDate = "Jahr - " + startDate.toLocaleDateString('de-DE', { year: 'numeric'})
            }
        },

        /**
         * Filter Events to decide what to display
         */
        filterEvents() {
            // TODO filter events
            // filter events
            this.displayedEvents = this.events.filter(event =>
                (this.areaFilter.length === 0 || this.areaFilter.find(area => area.id === event.areaId))
                && (this.typeFilter.length === 0 || this.typeFilter.find(type => type.id === event.eventTypeId))
                && (this.roomFilter.length === 0 || this.roomFilter.find(room => room.id === event.roomId))
            )

            this.displayedRooms = this.rooms.filter(room => this.displayedEvents.find(event => event.roomId === room.id))
        },

        /**
         * If the user selects a start, end, and room
         * call the server to get information if there are any collision
         *
         * @returns {Promise<void>}
         */
        async checkCollisions() {

            console.log(this.selectedEvent)

            if (!(this.selectedEvent.start && this.selectedEvent.end && this.selectedEvent.roomId)) {
                this.collision = 0
                return;
            }

            await axios
                .get('/events/collision', {
                    params: {
                        start: this.selectedEvent.start,
                        end: this.selectedEvent.end,
                        roomId: this.selectedEvent.roomId,
                        eventId: this.selectedEvent.id,
                    }
                })
                .then(response => {
                    this.collision = response.data
                });
        },

        /**
         * If the user wants to add a new event by dragging
         * open Modal and fill basic information
         *
         * @param event
         */
        selectEvent(event = null) {
            if (event === null) {
                this.selectedEvent = {
                    projectId: this.projectId,
                    roomId: this.roomId,
                }
                return
            }

            /**
             * Reformat the given JavaScript Date to a ISO 8601 that will work with Input Type dateTime-local
             * Unfortunately the JavaScript toISOString does not convert the timezone,
             * To keep the timezone the offset is subtracted
             * Then the ISOString can be generated, but requires the removal of the trailing Z.
             * Then the Format will work for the HTML Input Type dateTime-local.
             *
             * @example 2021-03-10T01:50:55+0200 => 2021-03-09T23:50:55 (german timezone)
             */
            event.start = event.start.subtractMinutes(event.start.getTimezoneOffset()).toISOString().slice(0, -1)
            event.end = event.end.subtractMinutes(event.end.getTimezoneOffset()).toISOString().slice(0, -1)

            // created by drag and drop
            if (!event.id) {
                this.selectedEvent = {
                    start: event.start,
                    end: event.end,
                    projectId: this.projectId,
                    roomId: event.split ? event.split : this.roomId,
                }
                this.checkCollisions()
                return
            }

            this.selectedEvent = event
            this.collision = event.collisionCount
        },

        /**
         * Updates or creates an event and reloads all events
         *
         * @param event
         * @param isOption
         * @returns {Promise<*>}
         */
        async updateOrCreateEvent(event, isOption) {
            event.eventTypeId = this.selectedEventType.id;
            event.roomId = this.selectedRoom.id;
            event.isOption = isOption;
            if (event.id) {

                return await axios
                    .put(`/events/${event.id}`, event)
                    .then(response => this.closeEventModal())
                    .catch(error => this.error = error.response.data.errors);
            }
            return await axios
                .post('/events', event)
                .then(response => {
                    this.closeAddEventModal();
                    console.log("Res: ")
                    console.log(response)
                })
                .catch(error => this.error = error.response.data.errors);
        },
        async approveRequest(event) {
            event.isOption = false;
            return await axios.put(`/events/${event.id}`, event)
                .then(response => console.log(response))
                .catch(error => this.error = error.response.data.errors);
        },
        async declineRequest(event) {
            event.roomId = null;
            return await axios.put(`/events/${event.id}`, event)
                .then(response => this.selectedRoom = response)
                .catch(error => this.error = error.response.data.errors);
        },
        openDeleteEventModal() {
            this.deletingEvent = true;
        },
        closeDeleteEventModal() {
            this.deletingEvent = false;
        },
        async deleteEvent() {
            return await axios
                .delete(`/events/${this.selectedEvent.id}`)
                .then(response => {
                    this.closeEventModal();
                    this.closeDeleteEventModal();
                });
        }
    }
}
</script>

<style>
/* Styling of Vue Cal */

.vuecal__no-event {
    display: none;
}

.vuecal__flex .vuecal__weekdays-headings {
    background-color: #D8D7DE;
}

.vuecal__flex .vuecal__week-numbers {
    background-color: #D8D7DE;
}

.vuecal__flex .vuecal__split-days-headers {
    background-color: #828190;
}

.vuecal__flex .vuecal__week-number-cell {
    color: #27233C;
    font-size: 0.81rem;
}

.vuecal__week-numbers .vuecal__week-number-cell {
    opacity: 1;
}

.vuecal--month-view .vuecal__cell {
    height: 95px;
}

.vuecal--month-view .vuecal__cell-content {
    justify-content: flex-start;
    height: 100%;
    align-items: flex-end;
    overflow-y: auto;
}

.vuecal--month-view .vuecal__cell-date {
    padding: 4px;
}
.vuecal--month-view .vuecal__event {
    padding-top: 0px;

}

.vuecal--month-view .vuecal__no-event {
    display: none;
}

.vuecal__event {
    font-size: 0.75rem; /* 14px */
    line-height: 1.25rem; /* 20px */
    margin-top: 3px;
    padding-top: 22px;
    background-color: white;
}
.vuecal__event-title{
    font-weight: 600;
    font-size: 0.75rem;
    letter-spacing: 0em;
    line-height: 20px;
    color: #27233c;
}
.vuecal__event-time{
    font-size: 12px;
    letter-spacing: -0.01em;
    line-height: 18px;
    color: #a7a6b1;
}
.vuecal__title {
    font-size: 2rem; /* 14px */
    line-height: 1.25rem; /* 20px */
    margin-top: 3px;
    padding-top: 22px;
    background-color: white;
}

.vuecal__view-btn {
    font-size: 1rem; /* 16px */
    line-height: 1.5rem; /* 24px */
}

.vuecal__title-bar {
    background-color: #ffffff;
    font-size: 1rem; /* 16px */
    line-height: 1.5rem; /* 24px */
}

.vuecal__split-days-headers {
    font-size: 0.75rem; /* 12px */
    line-height: 1rem; /* 16px */
    color: #ffffff
}

.vuecal__flex.weekday-label {
    font-size: 0.81rem; /* 13px */
    line-height: 1rem; /* 16px */
    color: #27233C;
    border-radius: 12px;
}

.vuecal__cell--today .vuecal__cell--current {
    background-color: rgba(48, 23, 173, 0.02) !important;
}

.vuecal__cell--selected {
    background-color: rgba(48, 23, 173, 0.02) !important;
}
.vuecal__cell-split{
    border: 1px solid #D8D7DE;
}



/* Custom Event Type Colors */

.vuecal__event.occupancy_option {
    background-image: url('data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/PjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuX0tudFciIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHdpZHRoPSIxNyIgaGVpZ2h0PSIxNyIgcGF0dGVyblRyYW5zZm9ybT0icm90YXRlKDQ1KSI+PGxpbmUgeDE9IjAiIHk9IjAiIHgyPSIwIiB5Mj0iMTciIHN0cm9rZT0iI0YzRjRGNiIgc3Ryb2tlLXdpZHRoPSI2Ii8+PC9wYXR0ZXJuPjwvZGVmcz4gPHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNwYXR0ZXJuX0tudFcpIiBvcGFjaXR5PSIxIi8+PC9zdmc+')
}
.vuecal__event.eventType0 {
    border: solid #A7A6B1;
    border-width: 0px 0px 0px 3px;
}

.vuecal__event.eventType1 {
    border: solid #641a54;
    border-width: 0px 0px 0px 3px;
}

.vuecal__event.eventType2 {
    border: solid #da3f87;
    border-width: 0px 0px 0px 3px;
}
.vuecal__event.eventType3 {
    border: solid #eb7a3d;
    border-width: 0px 0px 0px 3px;
}
.vuecal__event.eventType4 {
    border: solid #f1b640;
    border-width: 0px 0px 0px 3px;
}
.vuecal__event.eventType5 {
    border: solid #86c554;
    border-width: 0px 0px 0px 3px;
}
.vuecal__event.eventType6 {
    border: solid #2eaa63;
    border-width: 0px 0px 0px 3px;
}
.vuecal__event.eventType7 {
    border: solid #3dc3cb;
    border-width: 0px 0px 0px 3px;
}
.vuecal__event.eventType8 {
    border: solid #168fc3;
    border-width: 0px 0px 0px 3px;
}
.vuecal__event.eventType9 {
    border: solid #4d908e;
    border-width: 0px 0px 0px 3px;
}
.vuecal__event.eventType10 {
    border: solid #21485c;
    border-width: 0px 0px 0px 3px;
}
</style>
