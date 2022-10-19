<template>

    <div class="mt-10 ml-14">
        <div class="inline-flex mb-5 w-1/2">
            <Menu as="div" class="relative inline-block text-left w-auto">
                <div>
                    <MenuButton
                        class="-mt-2 w-72 border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white align-middle"
                    >
                        <CalendarIcon class="w-5 h-5 float-left mr-2"/>
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
            <button class="ml-2 -mt-2 text-black" @click="$refs.vuecal.previous()">
                <ChevronLeftIcon class="h-5 w-5 text-primary"/>
            </button>
            <button class="ml-2 -mt-2 text-black" @click="$refs.vuecal.next()">
                <ChevronRightIcon class="h-5 w-5 text-primary"/>
            </button>

            <div class="ml-5 flex text-error items-center cursor-pointer" @click="openEventsWithoutRoomComponent()"
                 v-if="eventsWithoutRoom.length > 0">

                <ExclamationIcon class="h-6 text-error mr-2"/>
                {{
                    eventsWithoutRoom.length
                }}{{ eventsWithoutRoom.length === 1 ? ' Termin ohne Raum!' : ' Termine ohne Raum!' }}

            </div>

        </div>

        <div class="inline-flex mb-5 justify-end w-1/2">

            <!-- Calendar Filter -->
            <Menu as="div" class="relative inline-block flex items-center text-left w-60">
                <div class="">
                    <MenuButton
                        class="border border-gray-300 w-60 bg-white px-4 py-2 text-sm font-medium text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
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
                        class="absolute right-0 top-12 w-60 origin-top-right divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
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
                                    <hr v-if="filters.length > 0"
                                        class="border-secondary rounded-full border-1 mt-4 mb-3">
                                    <button class="rounded-full bg-buttonBlue px-5 py-2 align-middle flex mb-1"
                                            v-for="filter of filters">
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
            style="height: 60rem"
            today-button
            :time-cell-height=120
            events-on-month-view="short"
            locale="de"
            hide-view-selector
            show-week-numbers
            :hideTitleBar="currentView !== 'year'"

            :click-to-navigate="true"
            :min-split-width=200
            stickySplitLabels
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
                <div class="mb-6">
                    {{ title }}
                </div>
            </template>
            <template #split-label="{ split, view }">
                <div class="text-base font-bold">
                    {{ split.label }}
                </div>
            </template>
            <template #event="{ event, view}">
                <div>
                    <div v-if="currentView !== 'month' && (event.audience || event.isLoud)"
                         class="flex absolute right-0 top-0">
                        <img v-if="event.audience" src="/Svgs/IconSvgs/icon_public.svg" class="h-6 w-6 mx-2"
                             alt="audienceIcon"/>
                        <img v-if="event.isLoud" src="/Svgs/IconSvgs/icon_adjustments.svg" class="h-5 w-5 mx-2"
                             alt="attributeIcon"/>
                    </div>

                    <div class="font-inter subpixel-antialiased text-base text-primary truncate tracking-wide">
                        {{ event.title }}
                    </div>
                    <span class="truncate"
                          v-if="event.eventName && event.eventName !== event.title"> {{ event.eventName }}</span>
                    <span class="flex text-xs w-full text-secondary">

                    <span v-if="event.start.getDay() === event.end.getDay()"  class="items-center mx-auto">{{ event.start.formatTime("HH:mm") }} - {{
                            event.end.formatTime("HH:mm")
                        }}  </span>
                        <span v-else>
                            <span>
                                {{event.start.format("DD.MM.YYYY HH:mm")}} - {{ event.end.format("DD.MM.YYYY HH:mm")}}
                            </span>

                        </span><br/>
                </span>
                    <div class="mt-3">
                        <div v-if="event.projectLeaders" class="mt-1 flex justify-center items-center flex-wrap w-full">
                            <div class="-mr-3 flex flex-wrap items-center flex-row"
                                 v-for="user in event.projectLeaders?.slice(0,3)">
                                <img :data-tooltip-target="user.id"
                                     :class="currentView === 'month'? 'h-7 w-7' : 'h-9 w-9'"
                                     class="rounded-full ring-2 ring-white object-cover"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                                <UserTooltip :user="user"/>
                            </div>
                            <div v-if="event.projectLeaders.length >= 3" class="my-auto">
                                <Menu as="div" class="relative">
                                    <div>
                                        <MenuButton class="flex items-center rounded-full focus:outline-none">
                                            <div
                                                :class="currentView === 'month'? 'h-7 w-7' : 'h-9 w-9'"
                                                class="mx-auto flex-shrink-0 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black">
                                                <p class="items-center mx-auto">
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
        :event="selectedEvent"
        :isAdmin=" $page.props.is_admin || $page.props.can.admin_rooms"
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
    CalendarIcon,
    ExclamationIcon
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
import UserTooltip from "@/Layouts/Components/UserTooltip";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent";

export default {
    name: 'CalendarComponent',
    components: {
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
            selectedEventType: null,
            events: [],
            eventsWithoutRoom: [],
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
            showEventsWithoutRoomComponent: false,
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
                    if (entry[1].value === filterEntry) {
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
                if (this.calendarFilters[`${filterName}`].some(filterObj => filterObj.id === object.id)) {
                    object.checked = true
                }
            })
        },
        async saveFilter() {
            const filterIds = this.getFilterIds();
            await axios.post('/filters', {name: this.filterName, calendarFilters: filterIds})
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
            console.log(this.currentView);
            let vuecal = document.querySelector('#vuecal .vuecal__bg');

            if (this.currentView === 'week') {
                console.log('moin');
                vuecal.onscroll = function () {
                    document.querySelector('.vuecal__weekdays-headings').style.transform = `translateY(${vuecal.scrollTop}px)`;
                }
            }
            if (this.currentView === 'day') {
                console.log('hello');
                vuecal.onscroll = function () {
                    document.querySelector('.vuecal__flex .vuecal__split-days-headers').style.transform = `translateY(${vuecal.scrollTop}px)`;
                }
            }
            this.scrollToNine();

            this.setDisplayDate(this.currentView, startDate)

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
                    this.events = response.data.events
                    this.projects = response.data.projects
                    this.filters = response.data.calendarFilters

                    this.eventsWithoutRoom = this.events.filter(event => event.roomId === null)

                    if (this.rooms.length === 0 || this.areChecked(this.rooms) === 0) {
                        this.rooms = response.data.rooms
                        this.displayedRooms = this.rooms;
                        this.addFilterableVariable(this.rooms, false)
                    }
                    if (this.areas.length === 0 || this.areChecked(this.areas) === 0) {
                        this.areas = response.data.areas
                        this.addFilterableVariable(this.areas, false)
                    }
                    if (this.roomCategories.length === 0 || this.areChecked(this.roomCategories) === 0) {
                        this.roomCategories = response.data.roomCategories
                        this.addFilterableVariable(this.roomCategories, false)
                    }
                    if (this.roomAttributes.length === 0 || this.areChecked(this.roomAttributes) === 0) {
                        this.roomAttributes = response.data.roomAttributes
                        this.addFilterableVariable(this.roomAttributes, false)
                    }
                    if (this.types.length === 0 || this.areChecked(this.types) === 0) {
                        this.types = this.eventTypes
                        this.addFilterableVariable(this.types, false)
                    }

                    // color coding of rooms
                    //this.events.map(event => event.class = colors[event.split % colors.length])

                    // fix timezone to current local
                    this.events.map(event => event.start = new Date(event.start))
                    this.events.map(event => event.end = new Date(event.end))

                    this.displayedEvents = this.events;

                    this.displayedRooms = (this.calendarFilters.rooms.length > 0 ? this.calendarFilters.rooms : this.rooms)
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
            this.selectedRoom = this.rooms.find((x) => x.id === event.roomId);
            if (event.title !== '') {
                const offset = new Date(event.start).getTimezoneOffset()
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
                this.displayDate = 'Woche - KW ' + weekNumber
            } else if (view === 'month') {
                this.displayDate = "Monat - " + startDate.toLocaleDateString('de-DE', {month: 'long', year: 'numeric'})
            } else {
                this.displayDate = "Jahr - " + startDate.toLocaleDateString('de-DE', {year: 'numeric'})
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
            event.declinedRoomId = this.selectedRoom.id;
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

.vuecal__flex {
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
.vuecal--month-view .vuecal__cell{
    height: 10rem ;
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
    font-size: 1rem; /* 16px */
    line-height: 1rem; /* 16px */
    color: #27233C;
    border-radius: 12px;
    font-weight: bolder;
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
