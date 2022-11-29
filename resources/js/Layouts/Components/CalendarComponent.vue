<template>

    <div class="mt-10 ml-12 flex justify-between items-center w-[95%]">
        <div class="inline-flex mb-5">
            <Menu v-slot="{ open }" as="div" class="relative inline-block text-left w-auto">
                <div>
                    <MenuButton  id="menuButton"
                        class="-mt-1 w-72 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white align-middle"
                    >
                        <CalendarIcon class="w-5 h-5 float-left mr-2"/>
                        <span class="float-left xsDark">{{ this.displayDate }}</span>
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
                        <button @click="$refs.vuecal.switchView('day', new Date()); this.selectedDate = new Date();"
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
            <button class="ml-2 -mt-2 text-black" @click="$refs.vuecal.previous()">
                <ChevronLeftIcon class="h-5 w-5 text-primary"/>
            </button>
            <button class="ml-2 -mt-2 text-black" @click="$refs.vuecal.next()">
                <ChevronRightIcon class="h-5 w-5 text-primary"/>
            </button>


        </div>
        <div class="ml-5 flex errorText items-center cursor-pointer mb-5 w-48" @click="openEventsWithoutRoomComponent()"
             v-if="eventsWithoutRoom.length > 0">

            <ExclamationIcon class="h-6  mr-2"/>
            {{
                eventsWithoutRoom.length
            }}{{ eventsWithoutRoom.length === 1 ? ' Termin ohne Raum!' : ' Termine ohne Raum!' }}
        </div>
        <div class=" inline-flex mb-5 justify-end">

            <!-- Calendar Filter -->
            <Menu as="div" class="relative inline-block flex items-center text-left">
                <div class="">
                    <MenuButton
                        class="w-52 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                    >
                        <span class="float-left xsDark">Filter</span>
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
                        class="w-80 absolute right-0 top-12 origin-top-right divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                        <!-- <div class="inline-flex border-none w-1/5">
                            <button>
                                <FilterIcon class="w-3 mr-1 mt-0.5"/>
                            </button>
                        </div> -->
                        <div class="inline-flex border-none justify-end w-full">
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
                            <Disclosure v-slot="{ open }" default-open>
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
                                    <div v-if="saving">
                                        <div class="flex">
                                            <input id="saveFilter" v-model="filterName" type="text"
                                                   class="shadow-sm placeholder-darkInputText bg-darkInputBg focus:outline-none focus:ring-0 border-secondary focus:border-1 text-sm"
                                                   placeholder="Name des Filters"/>
                                            <button
                                                class="rounded-full bg-buttonBlue cursor-pointer px-5 py-2 align-middle flex mb-1 ml-2">
                                                <label @click="saveFilter" class="cursor-pointer text-white text-xs">Speichern</label>
                                            </button>
                                            <!-- <AddButton text="Speichern" class="text-sm ml-0"
                                                       @click="saveFilter"></AddButton> -->
                                        </div>
                                        <hr class="border-gray-500 mt-4 mb-4">
                                    </div>
                                    <button
                                        class="rounded-full bg-buttonBlue cursor-pointer px-5 py-2 align-middle flex mb-1"
                                        v-for="filter of filters">
                                        <label @click="applyFilter(filter)"
                                               class="cursor-pointer text-white">{{ filter.name }}</label>
                                        <XIcon @click="deleteFilter(filter.id)" class="h-3 w-3 text-white ml-1 mt-1"/>
                                    </button>
                                    <p v-if="filters.length === 0" class="text-secondary py-1">Noch keine Filter
                                        gespeichert</p>
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
                                    <div v-if="currentView !== 'month' && currentView !== 'year'">
                                        <SwitchGroup>
                                            <div class="flex items-center">
                                                <Switch v-model="roomFilters.showAdjoiningRooms"
                                                        @click="this.changeFilterBoolean('showAdjoiningRooms', roomFilters.showAdjoiningRooms); this.changeDisplayedRooms()"
                                                        :class="roomFilters.showAdjoiningRooms ? 'bg-white' : 'bg-darkGray'"
                                                        class="relative inline-flex h-3 w-7 items-center rounded-full">
                                            <span
                                                :class="roomFilters.showAdjoiningRooms ? 'translate-x-[18px] bg-secondary' : 'translate-x-1/3 bg-white'"
                                                class="inline-block h-2 w-2 transform rounded-full transition"/>
                                                </Switch>
                                                <SwitchLabel class="ml-4 text-xs"
                                                             :class="roomFilters.showAdjoiningRooms ? 'text-white' : 'text-secondary'">
                                                    Nebenräume anzeigen
                                                </SwitchLabel>
                                            </div>
                                        </SwitchGroup>
                                        <SwitchGroup v-if="currentView === 'day'">
                                            <div class="flex items-center mt-2">
                                                <Switch v-model="roomFilters.allDayFree"
                                                        @click="this.changeFilterBoolean('allDayFree', roomFilters.allDayFree);"
                                                        :class="roomFilters.allDayFree ? 'bg-white' : 'bg-darkGray'"
                                                        class="relative inline-flex h-3 w-7 items-center rounded-full">
                                            <span
                                                :class="roomFilters.allDayFree ? 'translate-x-[18px] bg-secondary' : 'translate-x-1/3 bg-white'"
                                                class="inline-block h-2 w-2 transform rounded-full transition"/>
                                                </Switch>
                                                <SwitchLabel class="ml-4 text-xs"
                                                             :class="roomFilters.allDayFree ? 'text-white' : 'text-secondary'">
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
                                                       @change="this.changeFilterElements(calendarFilters.roomCategories, 'roomCategories', category)"
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
                                                       @change="this.changeFilterElements(calendarFilters.areas,'areas', area);"
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
                                                       @change="this.changeFilterElements(calendarFilters.roomAttributes,'roomAttributes', attribute);"
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
                                                       @change="this.changeFilterElements(calendarFilters.rooms,'rooms', room)"
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
                                                       @change="this.changeFilterElements(calendarFilters.eventTypes,'eventTypes', eventType)"
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
            <AddButton class="bg-primary hover:bg-secondary text-white resize-none"
                       @click="openEventComponent()" text="Neue Belegung"/>
        </div>


    </div>
    <div class="ml-12">
        <CalendarFilterTagComponent
            class="flex"
            :calendar-filters="calendarFilters"
            :event-attributes="eventAttributes"
            :room-filters="roomFilters"
            :events-since="eventsSince"
            :events-until="eventsUntil"
        />
    </div>
    <!--  Calendar  -->
    <div class="pl-3 overflow-x-scroll">
        <vue-cal
            ref="vuecal"
            id="vuecal"
            style="height: 70rem; max-height: calc(100vh - 280px); min-width: 100%; width: fit-content;"
            today-button
            :time-cell-height=120
            events-on-month-view="short"
            locale="de"
            hide-view-selector
            show-week-numbers
            :hideTitleBar="currentView !== 'year'"

            sticky-split-labels
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
            <template #title="{ title, view }">
                <div :class="currentView === 'year' ? 'ml-24' : ''" class="mb-6">
                    {{ title }}
                </div>
            </template>
            <template #today-button>
                <div class="flex w-24 xsDark text-buttonBlue" v-if="currentView === 'year'">
                    aktuelles Jahr
                </div>
            </template>
            <template #weekday-heading="{ heading, view }">
                <div v-if="currentView === 'week'">
                    {{ heading.label }}, {{ heading.date.format("DD.MM.YYYY") }}
                </div>
            </template>
            <template #week-number-cell=" weekNumber, view" >
                <div>
                KW {{weekNumber.week}}
                </div>
            </template>
            <template #split-label="{ split, view }">
                <Link class="text-base font-bold" :href="route('rooms.show',{room: split.id})">
                    {{ split.label }}
                </Link>
            </template>
            <template #event="{ event, view}">
                <div class="text-left mt-3 cursor-pointer">
                    <div v-if="currentView !== 'month' && (event.audience || event.isLoud)"
                         class="flex absolute left-0 top-0">
                        <img v-if="event.audience" src="/Svgs/IconSvgs/icon_public.svg" class="h-6 w-6 mx-2"
                             alt="audienceIcon"/>
                        <img v-if="event.isLoud" src="/Svgs/IconSvgs/icon_adjustments.svg" class="h-5 w-5 mx-2"
                             alt="attributeIcon"/>
                    </div>
                    <div v-if="!project" class="xsDark truncate mx-1">
                        {{ event.title }}
                    </div>

                    <div v-else class="xsDark truncate mx-1">
                        {{ this.eventTypes.find(eventType => eventType.id === event.eventTypeId)?.name }}
                    </div>
                    <div v-if="currentView !== 'month'" class="mx-1">

                        <div v-if="!project">
                        <span class="truncate xxsLight truncate"
                              v-if="event.eventName && event.eventName !== event.title"> {{ event.eventName }}</span>
                        </div>
                        <div v-else class="truncate xxsLight truncate">
                            {{ event.eventName }}
                        </div>
                        <span class="flex w-full xxsLight">


                        <span v-if="event.start.getDay() === event.end.getDay()"
                              class="items-center xxsLight">{{ event.start.formatTime("HH:mm") }} - {{
                                event.end.formatTime("HH:mm")
                            }}
                        </span>
                        <span class="flex w-full xxsLight" v-else>
                            <span class="items-center">
                                {{ event.start.format("DD.MM.YYYY HH:mm") }} - {{
                                    event.end.format("DD.MM.YYYY HH:mm")
                                }}
                            </span>

                        </span><br/>
                    </span>
                        <!-- only show profile pics when event is longer than 90 minutes => has enough space to display -->
                        <div v-if="event.endTimeMinutes - event.startTimeMinutes >= 90">
                        <div class="mt-3 -ml-3">
                            <div v-if="event.projectLeaders && !project"
                                 class="mt-1 flex flex-wrap w-full">
                                <div class="-mr-3 flex flex-wrap flex-row"
                                     v-for="user in event.projectLeaders?.slice(0,3)">
                                    <img :data-tooltip-target="user.id"
                                         :class="currentView === 'month'? 'h-7 w-7' : 'h-9 w-9'"
                                         class="rounded-full ring-2 ring-white object-cover"
                                         :src="user.profile_photo_url"
                                         alt=""/>
                                    <UserTooltip :user="user"/>
                                </div>
                                <div v-if="event.projectLeaders.length >= 4" class="my-auto">
                                    <Menu as="div" class="relative">
                                        <div>
                                            <MenuButton class="flex rounded-full focus:outline-none">
                                                <div
                                                    :class="currentView === 'month'? 'h-7 w-7' : 'h-9 w-9'"
                                                    class="flex-shrink-0 flex my-auto ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black">
                                                    <p class="">
                                                        +{{ event.projectLeaders.length - 3 }}
                                                    </p>
                                                </div>
                                            </MenuButton>
                                        </div>
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
                                                        <img :class="currentView === 'month'? 'h-7 w-7' : 'h-9 w-9'"
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
                            <div v-else-if="event.created_by"
                                 class="mt-1 ml-3 flex flex-wrap w-full">
                                <div class="-mr-3 flex flex-wrap flex-row">
                                    <img :data-tooltip-target="event.created_by.id"
                                         :class="currentView === 'month'? 'h-7 w-7' : 'h-9 w-9'"
                                         class="rounded-full ring-2 ring-white object-cover"
                                         :src="event.created_by.profile_photo_url"
                                         alt=""/>
                                    <UserTooltip :user="event.created_by"/>
                                </div>
                            </div>

                        </div>
                    </div>
                    </div>
                </div>
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
        :project="project"
        :event="selectedEvent"
        :wantedRoomId="wantedSplit"
        :isAdmin=" $page.props.is_admin || $page.props.can.admin_rooms"
        :roomCollisions="roomCollisions"
    />
    <!-- Termine ohne Raum Modal -->
    <events-without-room-component
        v-if="showEventsWithoutRoomComponent"
        @closed="onEventsWithoutRoomComponentClose()"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :eventsWithoutRoom="this.eventsWithoutRoom"
        :isAdmin=" $page.props.is_admin || $page.props.can.admin_rooms"
    />
</template>

<script>

import VueCal from 'vue-cal'
import 'vue-cal/dist/vuecal.css'
import JetDialogModal from "@/Jetstream/DialogModal";
import {
    CalendarIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    DocumentTextIcon,
    DotsVerticalIcon,
    ExclamationIcon,
    FilterIcon,
    PencilAltIcon,
    PlusCircleIcon,
    TrashIcon,
    XCircleIcon,
    XIcon,
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
import {Link} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton";
import EventComponent from "@/Layouts/Components/EventComponent";
import CalendarFilterComponent from "@/Layouts/Components/CalendarFilterComponent";
import CalendarFilterTagComponent from "@/Layouts/Components/CalendarFilterTagComponent";
import Button from "@/Jetstream/Button";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent";

export default {
    name: 'CalendarComponent',
    components: {
        PlusCircleIcon,
        ExclamationIcon,
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
        EventsWithoutRoomComponent,
        UserTooltip
    },
    props: ['project', 'room', 'initialView', 'eventTypes'],
    data() {
        return {
            displayDate: '',
            filters: [],
            filterIds: {},
            filterName: '',
            wantedSplit: null,
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
                allDayFree: false
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
                    name: 'Mit Publikum',
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
            events: [],
            eventsWithoutRoom: [],
            displayedEvents: [],
            rooms: [],
            areas: [],
            displayedRooms: [],
            projects: [],
            selectedEvent: null,
            collision: 0,
            eventsSince: null,
            eventsUntil: null,
            deletingEvent: false,
            currentView: this.initialView ?? 'week',
            roomCategories: [],
            roomAttributes: [],
            eventComponentIsVisible: false,
            createEventComponentIsVisible: false,
            showEventsWithoutRoomComponent: false,
            roomCollisions: [],

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
                    if (entry[1].value === filterEntry) {
                        entry[1].checked = true
                    }
                })
            })

            this.changeDisplayedRooms();

            this.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil,
            });
        },
        changeChecked(array, filterName) {
            array.forEach(object => {
                if (this.calendarFilters[`${filterName}`].some(filterObj => filterObj.id === object.id)) {
                    object.checked = true
                }
            })
        },
        async saveFilter() {
            const filterIds = this.getFilterIds();
            await axios.post('/filters', {name: this.filterName, calendarFilters: filterIds}).then(() => {
                this.filterName = ""
            })
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
        async changeFilterElements(filterArray, arrayName, element) {

            if (element.checked) {
                filterArray.push(element)
            } else {
                // entry[0] is the key, e.g. room_categories. entry[1] is the array corresponding to the key.
                Object.entries(this.calendarFilters).forEach(entry => {
                    if (Array.isArray(entry[1]) && entry[1].length > 0 && arrayName === entry[0]) {
                        if(arrayName === 'rooms') {
                            const room = this.rooms.filter(room => element?.id === room.id)
                            if(room){
                                room[0].checked = false
                            }
                        }
                        if(arrayName === 'areas') {
                            const area = this.areas.filter(area => element?.id === area.id)
                            if(area){
                                area[0].checked = false
                            }
                        }
                        if(arrayName === 'roomCategories') {
                            const category = this.roomCategories.filter(category => element?.id === category.id)
                            if(category){
                                category[0].checked = false
                            }
                        }
                        if(arrayName === 'roomAttributes') {
                            const attribute = this.roomAttributes.filter(room => element?.id === room.id)
                            if(attribute){
                                attribute[0].checked = false
                            }
                        }
                        if(arrayName === 'eventTypes') {
                            const eventType = this.eventTypes.filter(type => element?.id === type.id)
                            if(eventType){
                                eventType[0].checked = false
                            }
                        }

                        this.calendarFilters[entry[0]] = filterArray.filter(elem => element?.id !== elem.id)
                    }
                })
            }
            await this.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil,
            });
            await this.changeDisplayedRooms()
        },
        async changeFilterBoolean(filter, variable) {
            this.calendarFilters[`${filter}`] = variable
            await this.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil,
            });
            await this.changeDisplayedRooms()
        },
        async changeDisplayedRooms() {

            let allRooms = this.rooms;
            if (this.calendarFilters.allDayFree === true) {
                allRooms = await this.getAllDayFreeRooms();
            }

            //decides for every room if it should be displayed in the calendar
            this.displayedRooms = allRooms.filter(room => {
                let include = false;

                this.areas.forEach(area => {
                    //if the current area is checked
                    if (area.checked) {
                        if (room.area.name === area.name) {
                            include = this.filterRooms(room)
                        }
                        //checks if no area is checked
                    } else if (this.zeroObjectsChecked(this.areas)) {
                        include = this.filterRooms(room)
                        //at least one area is checked, but not the current one
                    } else {
                        if (room.area.name === area.name) {
                            include = false;
                        }
                    }
                })
                return include
            });
            this.viewAdjoiningRooms()

        },
        async getAllDayFreeRooms() {
            let rooms = []
            await axios.get('/rooms/free', {
                params: {
                    start: this.eventsSince,
                    end: this.eventsUntil,
                }
            }).then(response => {
                rooms = response.data.rooms;
            })
            return rooms
        },
        filterRooms(room) {
            let include = false;
            if (!this.zeroObjectsChecked(this.rooms)) {
                if (room.checked) {
                    include = this.filterCategories(room);
                }
            } else {
                include = this.filterCategories(room)
            }
            return include
        },
        filterCategories(room) {
            let include = false;
            if (!this.zeroObjectsChecked(this.roomCategories)) {
                this.roomCategories.forEach(roomCategory => {
                    if (roomCategory.checked) {
                        const sameCategory = room.categories.filter(category => category.name === roomCategory.name)
                        if (sameCategory.length > 0) {
                            include = this.filterAttributes(room);
                        }
                    }
                })
            } else {
                include = this.filterAttributes(room);
            }
            return include
        },
        filterAttributes(room) {
            let include = false
            if (!this.zeroObjectsChecked(this.roomAttributes)) {
                this.roomAttributes.forEach(roomAttribute => {
                    if (roomAttribute.checked) {
                        const sameAttribute = room.attributes.filter(attribute => attribute.name === roomAttribute.name)
                        if (sameAttribute.length > 0) {
                            include = true;
                        }
                    }
                })
            } else {
                include = true;
            }
            return include
        },
        zeroObjectsChecked(array) {
            let zeroChecked = true;
            array.forEach(object => {
                if (object.checked === true) {
                    zeroChecked = false;
                }
            })
            return zeroChecked;
        },
        viewAdjoiningRooms() {
            let adjoiningRooms = [];

            if (this.roomFilters.showAdjoiningRooms) {
                this.displayedRooms.forEach(room => {
                    adjoiningRooms.push(...room.main_rooms)
                    adjoiningRooms.push(...room.adjoining_rooms)
                })

                for (const room of adjoiningRooms) {
                    if (this.displayedRooms.filter(r => r.name === room.label).length === 0) {
                        room.adjoining = true;
                        this.calendarFilters.rooms.push(room)
                        this.displayedRooms.push(room);
                    }
                }
            } else {
                this.calendarFilters.rooms = this.calendarFilters.rooms.filter(r => !r.adjoining)
                this.displayedRooms = this.displayedRooms.filter(r => !r.adjoining)
            }
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
                    this.calendarFilters[entry[0]] = null;
                }
            })

            Object.entries(this.eventAttributes).forEach(entry => {
                entry[1].checked = null;
            })

            this.displayedRooms = this.rooms

            this.fetchEvents({
                startDate: this.eventsSince,
                endDate: this.eventsUntil
            });
        },
        openEventComponent(event = null) {

            this.wantedSplit = event?.split;
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

            if(event?.start && event?.end){
                axios.post('/collision/room', {
                        params: {
                            start: event?.start,
                            end:  event?.end,
                        }
                    })
                    .then(response => this.roomCollisions = response.data);
            }
            this.selectedEvent = event;
            this.createEventComponentIsVisible = true;
        },
        openEventsWithoutRoomComponent() {
            this.showEventsWithoutRoomComponent = true;
        },

        onEventComponentClose() {
            this.createEventComponentIsVisible = false;
            this.fetchEvents({startDate: this.eventsSince, endDate: this.eventsUntil});
        },
        onEventsWithoutRoomComponentClose() {
            this.showEventsWithoutRoomComponent = false;
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
            let vuecal = document.querySelector('#vuecal .vuecal__bg');

            this.setDisplayDate(this.currentView, startDate)
            this.scrollToNine();

            this.eventsSince = startDate ?? this.eventsSince;
            this.eventsUntil = endDate ?? this.eventsUntil;

            const filters = this.getFilterIds();

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
                    this.events = response.data.events.events
                    this.projects = response.data.events.projects
                    this.filters = response.data.events.calendarFilters

                    this.eventsWithoutRoom = response.data.eventsWithoutRoom.events;

                    if (this.rooms.length === 0) {
                        this.rooms = response.data.events.rooms
                        this.displayedRooms = this.rooms;
                        this.addFilterableVariable(this.rooms, false)
                    }
                    if (this.areas.length === 0) {
                        this.areas = response.data.events.areas
                        this.addFilterableVariable(this.areas, false)
                    }
                    if (this.roomCategories.length === 0) {
                        this.roomCategories = response.data.events.roomCategories
                        this.addFilterableVariable(this.roomCategories, false)
                    }
                    if (this.roomAttributes.length === 0) {
                        this.roomAttributes = response.data.events.roomAttributes
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
                });
        },
        scrollToNine() {
            if (this.currentView === 'month') {
                return;
            }
            const calendar = document.querySelector('#vuecal .vuecal__bg')
            calendar.scrollTo({top: 9 * 120, behavior: 'smooth'})

        },
        areChecked(array) {
            let count = 0;
            array.forEach(object => {
                if (object.checked) {
                    count++;
                }
            })
            return count
        },
        getFilterIds() {
            this.filterIds = {};
            this.filterIds.roomCategoryIds = this.calendarFilters.roomCategories.map(elem => elem.id);
            this.filterIds.roomAttributeIds = this.calendarFilters.roomAttributes.map(elem => elem.id);
            this.filterIds.roomIds = this.calendarFilters.rooms.map(elem => elem.id);
            this.filterIds.areaIds = this.calendarFilters.areas.map(elem => elem.id);
            this.filterIds.eventTypeIds = this.calendarFilters.eventTypes.map(elem => elem.id);
            this.setFilterId("isLoud");
            this.setFilterId("isNotLoud");
            this.setFilterId("hasAudience");
            this.setFilterId("hasNoAudience");
            this.setFilterId("adjoiningNoAudience");
            this.setFilterId("adjoiningNotLoud");
            this.setFilterId("allDayFree");
            this.setFilterId("showAdjoiningRooms");

            return this.filterIds;
        },
        setFilterId(field) {
            if (this.calendarFilters[field] === false || this.calendarFilters[field] === null) {
                this.filterIds[field] = null
            } else {
                this.filterIds[field] = true;
            }
        },
        addFilterableVariable(dataArray, boolean) {
            dataArray.forEach(element => element.checked = boolean);
        },
        setDisplayDate(view, startDate) {

            if (view === 'day') {
                const options = {weekday: 'long', year: 'numeric', month: 'short', day: 'numeric'};
                this.displayDate = startDate.toLocaleDateString('de-DE', options)
            } else if (view === 'week') {
                let beginOfYear = new Date(startDate.getFullYear(), 0, 1);
                let days = Math.floor((startDate - beginOfYear) /
                    (24 * 60 * 60 * 1000));

                let weekNumber = Math.ceil(days / 7);
                this.displayDate = 'Woche - KW ' + weekNumber + ' ' + startDate.toLocaleDateString('de-DE', {year: 'numeric'})
            } else if (view === 'month') {
                this.displayDate = "Monat - " + startDate.toLocaleDateString('de-DE', {month: 'long', year: 'numeric'})
            } else {
                this.displayDate = "Jahr - " + startDate.toLocaleDateString('de-DE', {year: 'numeric'})
            }
        },
    }
}
</script>

<style>
/* Styling of Vue Cal */

.day-split-header {
    min-width: 200px;
    text-align: center;
    font: normal normal 13px/18px Inter;
    letter-spacing: -0.2px;
    color: #FCFCFB;
    opacity: 1;
}

.vuecal__no-event {
    display: none;
}

.vuecal__flex {
}
.vuecal__cell-events-count{
    color: #3017AD ;
    background-color: rgba(48,23,173,0.1);
    height: 18px;
    width: 18px;
    justify-content: center; /* Centering Horizantly */
    align-items: center;
    display: flex;


}

.vuecal__bg {

}

.vuecal__weekdays-headings {
    background-color: #D8D7DE;
    letter-spacing: -0.01em;
    line-height: 18px;
    text-align: center;
    color: #fcfcfb;
    z-index: 10;
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

.vuecal__flex .vuecal__cell-content .vuecal__cell-split {
    min-width: 200px !important;
}

.vuecal__event {
    font-size: 0.75rem; /* 14px */
    line-height: 1.25rem; /* 20px */
    margin-top: 3px;
    padding-top: 22px;
    background-color: white;
    border: 1px solid #D8D7DE;
    opacity: 1;
}

.vuecal__event-time {
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

.vuecal--month-view .vuecal__cell {
    height: 10rem;
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
    color: #ffffff;
}

.vuecal__flex.weekday-label {
    text-align: center;
    font: normal normal 600 13px/18px Inter;
    letter-spacing: -0.2px;
    color: #27233C;
    border-right: 2px solid #828190;
    opacity: 1;
}

.vuecal__header {
    background-color: #828190;
}

.vuecal__cell--today .vuecal__cell--current {
    background-color: rgba(48, 23, 173, 0.02) !important;
}

.vuecal__cell--selected {
    background-color: rgba(48, 23, 173, 0.02) !important;
}

.vuecal__cell-split {
    border: 1px solid #D8D7DE;
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

.vuecal--month-view .vuecal__cell {
    height: 10rem;
}

.vuecal--month-view .vuecal__no-event {
    display: none;
}


/* Custom Event Type Colors */

.vuecal__event.occupancy_option {
    background-image: url('data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBzdGFuZGFsb25lPSJubyI/PjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuX0tudFciIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHdpZHRoPSIxNyIgaGVpZ2h0PSIxNyIgcGF0dGVyblRyYW5zZm9ybT0icm90YXRlKDQ1KSI+PGxpbmUgeDE9IjAiIHk9IjAiIHgyPSIwIiB5Mj0iMTciIHN0cm9rZT0iI0YzRjRGNiIgc3Ryb2tlLXdpZHRoPSI2Ii8+PC9wYXR0ZXJuPjwvZGVmcz4gPHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNwYXR0ZXJuX0tudFcpIiBvcGFjaXR5PSIxIi8+PC9zdmc+')
}

.vuecal__event.eventType0 {
    border-left: solid #A7A6B1;
    border-width: 1px 1px 1px 3px;
}

.vuecal__event.eventType1 {
    border-left: solid #641a54;
    border-width: 1px 1px 1px 3px;
}

.vuecal__event.eventType2 {
    border-left: solid #da3f87;
    border-width: 1px 1px 1px 3px;
}

.vuecal__event.eventType3 {
    border-left: solid #eb7a3d;
    border-width: 1px 1px 1px 3px;
}

.vuecal__event.eventType4 {
    border-left: solid #f1b640;
    border-width: 1px 1px 1px 3px;
}

.vuecal__event.eventType5 {
    border-left: solid #86c554;
    border-width: 1px 1px 1px 3px;
}

.vuecal__event.eventType6 {
    border-left: solid #2eaa63;
    border-width: 1px 1px 1px 3px;
}

.vuecal__event.eventType7 {
    border-left: solid #3dc3cb;
    border-width: 1px 1px 1px 3px;
}

.vuecal__event.eventType8 {
    border-left: solid #168fc3;
    border-width: 1px 1px 1px 3px;
}

.vuecal__event.eventType9 {
    border-left: solid #4d908e;
    border-width: 1px 1px 1px 3px;
}

.vuecal__event.eventType10 {
    border-left: solid #21485c;
    border-width: 1px 1px 1px 3px;
}
</style>
