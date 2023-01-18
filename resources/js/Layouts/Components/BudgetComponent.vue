<template>
    <div class="mx-4">
        <div class="flex justify-end mb-10">
            <AddButton @click="openAddColumnModal()" text="Neue Spalte" mode="page"></AddButton>
        </div>
        <div class="w-full flex">
            <table class="w-full flex ml-16">
                <thead>
                <tr>
                    <th v-for="(column,index) in budget.columns"
                        :class="index <= 1 ? 'w-20' : index === 2 ? 'w-64' : 'w-44'" class="text-right">
                        <div class="flex items-center " @mouseover="showMenu = column.id" :key="column.id"
                             @mouseout="showMenu = null">
                            <div>
                                <div class="flex items-center justify-end pr-2">
                                    <p class="columnSubName xsLight">
                                        {{ column.subName }}
                                        <span v-if="column.calculateName" class="ml-1">
                                            ({{ column.calculateName }})
                                        </span>
                                    </p>
                                    <span class="ml-1"
                                          v-if="index > 2 && column.showColorMenu === true || column.color !== 'whiteColumn'">
                                        <Listbox as="div" class="flex mr-2" v-model="column.color">
                                                <ListboxButton>
                                                   <button class="w-4 h-4 flex justify-center items-center rounded-full"
                                                           :class="column.color === 'whiteColumn' ? 'whiteColumn border border-1' : column.color"
                                                           @click="column.openColor = !column.openColor">
                                                        <ChevronUpIcon v-if="column.openColor"
                                                                       class="h-3 w-3 my-auto"
                                                                       :class="column.color === 'whiteColumn' ? 'text-black' : 'text-white'"></ChevronUpIcon>
                                                        <ChevronDownIcon v-else
                                                                         class="h-3 w-3 text-white my-auto"
                                                                         :class="column.color === 'whiteColumn' ? 'text-black' : 'text-white'"></ChevronDownIcon>
                                                    </button>
                                                </ListboxButton>

                                                <transition leave-active-class="transition ease-in duration-100"
                                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                    <ListboxOptions
                                                        class="absolute w-24 z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                                        <ListboxOption as="template" class="max-h-32"
                                                                       v-for="color in colors"
                                                                       :key="color"
                                                                       :value="color" v-slot="{ active, selected }">
                                                            <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 text-sm subpixel-antialiased']"
                                                                @click="changeColumnColor(color, column.id)">
                                                                <div class="flex">
                                                                    <span
                                                                        :class="[selected ? 'xsWhiteBold' : 'font-normal', 'block truncate']">
                                                                        <span
                                                                            class="block truncate items-center ml-3 flex rounded-full h-10 w-10"
                                                                            :class="color">
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                                <span
                                                                    :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                                    <CheckIcon v-if="selected"
                                                                               class="h-5 w-5 flex text-success"
                                                                               aria-hidden="true"/>
                                                                </span>
                                                            </li>
                                                        </ListboxOption>
                                                    </ListboxOptions>
                                                </transition>
                                            </Listbox>
                                    </span>
                                </div>
                                <div @click="column.clicked = !column.clicked"
                                     :class="index <= 1 ? 'w-20' : index === 2 ? 'w-64' : 'w-44'" class="h-5 pr-2"
                                     v-if="!column.clicked">
                                    {{ column.name }}
                                </div>
                                <div v-else>
                                    <input
                                        :class="index <= 1 ? 'w-20' : index === 2 ? 'w-64' : 'w-44'"
                                        class="my-2 xsDark pr-2 text-right" type="text"
                                        v-model="column.name"
                                        @focusout="updateColumnName(column); column.clicked = !column.clicked">
                                </div>
                            </div>
                            <Menu as="div" v-show="showMenu === column.id">
                                <div class="flex">
                                    <MenuButton
                                        class="flex ">
                                        <DotsVerticalIcon
                                            class="flex-shrink-0 h-6 w-6 text-gray-600"
                                            aria-hidden="true"/>
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
                                        class="absolute w-56 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                        <div class="py-1">
                                            <MenuItem v-slot="{ active }">
                                                <a @click="column.showColorMenu = true"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <PencilAltIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Einfärben
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a @click="deleteColumn(column.id)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <TrashIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Löschen
                                                </a>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
                            <div v-if="showMenu !== column.id" class="w-6">

                            </div>
                        </div>
                    </th>
                </tr>
                </thead>
            </table>

        </div>

        <div class="flex my-8 ">
            <div class="flex w-full bg-secondaryHover border border-2 border-gray-300">
                <button class="bg-buttonBlue w-6"
                        @click="costsOpened = !costsOpened">
                    <ChevronUpIcon v-if="costsOpened"
                                   class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                    <ChevronDownIcon v-else
                                     class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                </button>

                <div class="bg-secondaryHover ml-8 w-full" v-if="costsOpened">
                    <div class="headline4 my-10">Ausgaben</div>
                    <div @click="addMainPosition('BUDGET_TYPE_COST', positionDefault)"
                         class="group w-11/12 bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                        <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                            Hauptposition
                            <PlusCircleIcon
                                class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                        </div>
                    </div>
                    <table class="w-11/12 mb-6">
                        <tbody class="">
                        <tr v-if="tablesToShow[0]?.length > 0" v-for="(mainPosition,mainIndex) in tablesToShow[0]">
                            <th class="p-0"
                                :class="mainPosition.verified?.requested === this.$page.props.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED' ? 'bg-buttonBlue' : 'bg-primary'">
                                <div class="flex" @mouseover="showMenu = 'MainPosition' + mainPosition.id"
                                     @mouseout="showMenu = null">
                                    <div class="pl-2 xsWhiteBold flex w-full items-center h-10"
                                         v-if="!mainPosition.clicked">
                                        <div @click="mainPosition.clicked = !mainPosition.clicked">
                                            {{ mainPosition.name }}
                                        </div>
                                        <button class="my-auto w-6 ml-3"
                                                @click="mainPosition.closed = !mainPosition.closed">
                                            <ChevronUpIcon v-if="!mainPosition.closed"
                                                           class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                            <ChevronDownIcon v-else
                                                             class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                        </button>
                                    </div>
                                    <div v-else class="flex items-center w-full">
                                        <input
                                            class="my-2 ml-1 xsDark" type="text"
                                            v-model="mainPosition.name"
                                            @focusout="updateMainPositionName(mainPosition); mainPosition.clicked = !mainPosition.clicked">
                                        <button class="my-auto w-6 ml-3"
                                                @click="mainPosition.closed = !mainPosition.closed">
                                            <ChevronUpIcon v-if="!mainPosition.closed"
                                                           class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                            <ChevronDownIcon v-else
                                                             class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-end">
                                        <div class="text-white w-28 flex items-center"
                                             v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && mainPosition.verified?.requested !== this.$page.props.user.id">
                                            <p class="xxsLight">wird verifiziert </p>
                                            <!-- TODO: SVG ersetzen mit IMG TAG -->
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                 xmlns:xlink="http://www.w3.org/1999/xlink" class="ml-1" width="19"
                                                 height="14.292" viewBox="0 0 19 14.292">
                                                <defs>
                                                    <clipPath id="clip-path">
                                                        <rect id="Rechteck_458" data-name="Rechteck 458" width="5.138"
                                                              height="3.634" fill="#fcfcfb"/>
                                                    </clipPath>
                                                </defs>
                                                <path id="Icon_awesome-lock" data-name="Icon awesome-lock"
                                                      d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z"
                                                      transform="translate(0 0.607)" fill="#fcfcfb"/>
                                                <g id="Gruppe_962" data-name="Gruppe 962"
                                                   transform="translate(-412 -311)">
                                                    <g id="Ellipse_147" data-name="Ellipse 147"
                                                       transform="translate(418 311)" fill="#27233c" stroke="#fcfcfb"
                                                       stroke-width="1">
                                                        <circle cx="6.5" cy="6.5" r="6.5" stroke="none"/>
                                                        <circle cx="6.5" cy="6.5" r="6" fill="none"/>
                                                    </g>
                                                    <g id="Gruppe_962-2" data-name="Gruppe 962"
                                                       transform="translate(423 314.945)" clip-path="url(#clip-path)">
                                                        <path id="Pfad_1344" data-name="Pfad 1344"
                                                              d="M5.1,1.418a.534.534,0,0,0-.7-.286L1.775,2.23,1.029.337a.533.533,0,1,0-.992.39L1.183,3.633,4.811,2.115a.533.533,0,0,0,.286-.7"
                                                              transform="translate(0 0)" fill="#fcfcfb"/>
                                                    </g>
                                                </g>
                                            </svg>

                                        </div>
                                        <div class="text-white w-44 flex items-center text-center cursor-pointer"
                                             @click="verifiedMainPosition(mainPosition.verified?.main_position_id)"
                                             v-if="mainPosition.verified?.requested === this.$page.props.user.id  && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED'">
                                            <p class="xxsLight">Als verifiziert markieren</p>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" class="ml-1" height="20"
                                                 viewBox="0 0 20 20">
                                                <g id="check_btn" transform="translate(-1234 -671.05)">
                                                    <g id="Pfad_1370" data-name="Pfad 1370"
                                                       transform="translate(1234 671.05)" fill="none">
                                                        <path d="M10,0A10,10,0,1,1,0,10,10,10,0,0,1,10,0Z"
                                                              stroke="none"/>
                                                        <path
                                                            d="M 10 1 C 5.037380218505859 1 1 5.037380218505859 1 10 C 1 14.96261978149414 5.037380218505859 19 10 19 C 14.96261978149414 19 19 14.96261978149414 19 10 C 19 5.037380218505859 14.96261978149414 1 10 1 M 10 0 C 15.52285003662109 0 20 4.477149963378906 20 10 C 20 15.52285003662109 15.52285003662109 20 10 20 C 4.477149963378906 20 0 15.52285003662109 0 10 C 0 4.477149963378906 4.477149963378906 0 10 0 Z"
                                                            stroke="none" fill="#fcfcfb"/>
                                                    </g>
                                                    <path id="Pfad_157" data-name="Pfad 157"
                                                          d="M-1151.25,4789.252l3.142,3.142,6.013-6.013"
                                                          transform="translate(2390.673 -4108.337)" fill="none"
                                                          stroke="#fcfcfb" stroke-width="1.5"/>
                                                </g>
                                            </svg>
                                        </div>
                                        <div class="text-white w-44 flex items-center text-center justify-end mr-2"
                                             v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED'">
                                            <p class="xxsLight">verifiziert</p>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11.975" height="13.686"
                                                 class="ml-1" viewBox="0 0 11.975 13.686">
                                                <path id="Icon_awesome-lock" data-name="Icon awesome-lock"
                                                      d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z"
                                                      fill="#fcfcfb"/>
                                            </svg>
                                        </div>
                                        <div class="flex flex-wrap w-8">
                                            <div class="flex w-full">
                                                <Menu as="div" class="my-auto relative"
                                                      v-show="showMenu === 'MainPosition' + mainPosition.id">
                                                    <div class="flex">
                                                        <MenuButton
                                                            class="flex">
                                                            <DotsVerticalIcon
                                                                class="mr-3 flex-shrink-0 h-6 w-6 text-secondaryHover my-auto"
                                                                aria-hidden="true"/>
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
                                                            class="origin-top-right absolute right-0 w-80 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                            <div class="py-1">
                                                                <MenuItem v-slot="{ active }"
                                                                          v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED'">
                                                                                <span
                                                                                    @click="openVerifiedModal(mainPosition)"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Von User verifizieren lassen
                                                                                </span>
                                                                </MenuItem>
                                                                <MenuItem v-slot="{ active }"
                                                                          v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' && mainPosition.verified?.requested === this.$page.props.user.id">
                                                                                <span
                                                                                    @click="removeVerification(mainPosition, 'main')"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Verifizierung aufheben
                                                                                </span>
                                                                </MenuItem>
                                                                <MenuItem v-slot="{ active }"
                                                                          v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && mainPosition.verified?.requested_by === this.$page.props.user.id">
                                                                                <span
                                                                                    @click="requestRemove(mainPosition, 'main')"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Verifizierungsanfrage zurücknehmen
                                                                                </span>
                                                                </MenuItem>
                                                                <MenuItem v-slot="{ active }">
                                                                                <span
                                                                                    @click="openDeleteMainPositionModal(mainPosition)"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Löschen
                                                                                </span>
                                                                </MenuItem>
                                                            </div>
                                                        </MenuItems>
                                                    </transition>
                                                </Menu>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div @click="addSubPosition(mainPosition.id)"
                                     class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                                    <div class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                                        Unterposition
                                        <PlusCircleIcon
                                            class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                    </div>
                                </div>
                                <table v-if="!mainPosition.closed" class="w-full ">
                                    <thead class="">
                                    <tr class="" v-for="(subPosition,subIndex) in mainPosition.sub_positions">
                                        <th class="bg-silverGray xxsDark w-full">
                                            <div class="flex" @mouseover="showMenu = 'subPosition' + subPosition.id"
                                                 @mouseout="showMenu = null">
                                                <div
                                                    class="pl-2 xxsDark w-full flex items-center h-10"
                                                    v-if="!subPosition.clicked">
                                                    <div @click="subPosition.clicked = !subPosition.clicked">
                                                        {{ subPosition.name }}
                                                    </div>
                                                    <button class="my-auto w-6 ml-3"
                                                            @click="subPosition.closed = !subPosition.closed">
                                                        <ChevronUpIcon v-if="!subPosition.closed"
                                                                       class="h-6 w-6 text-primary my-auto"></ChevronUpIcon>
                                                        <ChevronDownIcon v-else
                                                                         class="h-6 w-6 text-primary my-auto"></ChevronDownIcon>
                                                    </button>
                                                </div>
                                                <div v-else class="flex w-full">
                                                    <input
                                                        class="my-2 ml-1 xxsDark" type="text"
                                                        v-model="subPosition.name"
                                                        @focusout="updateSubPositionName(subPosition); subPosition.clicked = !subPosition.clicked">
                                                    <button class="my-auto w-6 ml-3"
                                                            @click="subPosition.closed = !subPosition.closed">
                                                        <ChevronUpIcon v-if="!subPosition.closed"
                                                                       class="h-6 w-6 text-primary my-auto"></ChevronUpIcon>
                                                        <ChevronDownIcon v-else
                                                                         class="h-6 w-6 text-primary my-auto"></ChevronDownIcon>
                                                    </button>
                                                </div>
                                                <div class="flex items-center justify-end">
                                                    <div class="text-white w-28 flex items-center"
                                                         v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && subPosition.verified?.requested !== this.$page.props.user.id">
                                                        <p class="xxsLight">wird verifiziert </p>
                                                        <!-- TODO: SVG ersetzen mit IMG TAG -->
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                             xmlns:xlink="http://www.w3.org/1999/xlink" class="ml-1"
                                                             width="19"
                                                             height="14.292" viewBox="0 0 19 14.292">
                                                            <defs>
                                                                <clipPath id="clip-path">
                                                                    <rect id="Rechteck_458" data-name="Rechteck 458"
                                                                          width="5.138"
                                                                          height="3.634" fill="#fcfcfb"/>
                                                                </clipPath>
                                                            </defs>
                                                            <path id="Icon_awesome-lock" data-name="Icon awesome-lock"
                                                                  d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z"
                                                                  transform="translate(0 0.607)" fill="#fcfcfb"/>
                                                            <g id="Gruppe_962" data-name="Gruppe 962"
                                                               transform="translate(-412 -311)">
                                                                <g id="Ellipse_147" data-name="Ellipse 147"
                                                                   transform="translate(418 311)" fill="#27233c"
                                                                   stroke="#fcfcfb"
                                                                   stroke-width="1">
                                                                    <circle cx="6.5" cy="6.5" r="6.5" stroke="none"/>
                                                                    <circle cx="6.5" cy="6.5" r="6" fill="none"/>
                                                                </g>
                                                                <g id="Gruppe_962-2" data-name="Gruppe 962"
                                                                   transform="translate(423 314.945)"
                                                                   clip-path="url(#clip-path)">
                                                                    <path id="Pfad_1344" data-name="Pfad 1344"
                                                                          d="M5.1,1.418a.534.534,0,0,0-.7-.286L1.775,2.23,1.029.337a.533.533,0,1,0-.992.39L1.183,3.633,4.811,2.115a.533.533,0,0,0,.286-.7"
                                                                          transform="translate(0 0)" fill="#fcfcfb"/>
                                                                </g>
                                                            </g>
                                                        </svg>
                                                    </div>
                                                    <div
                                                        class="text-white w-44 flex items-center text-center cursor-pointer"
                                                        @click="verifiedSubPosition(subPosition.verified?.sub_position_id)"
                                                        v-if="subPosition.verified?.requested === this.$page.props.user.id && subPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED'">
                                                        <p class="xxsLight">Als verifiziert markieren</p>
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" class="ml-1"
                                                             height="20" viewBox="0 0 20 20">
                                                            <g id="check_btn" transform="translate(-1234 -671.05)">
                                                                <g id="Pfad_1370" data-name="Pfad 1370"
                                                                   transform="translate(1234 671.05)" fill="none">
                                                                    <path d="M10,0A10,10,0,1,1,0,10,10,10,0,0,1,10,0Z"
                                                                          stroke="none"/>
                                                                    <path
                                                                        d="M 10 1 C 5.037380218505859 1 1 5.037380218505859 1 10 C 1 14.96261978149414 5.037380218505859 19 10 19 C 14.96261978149414 19 19 14.96261978149414 19 10 C 19 5.037380218505859 14.96261978149414 1 10 1 M 10 0 C 15.52285003662109 0 20 4.477149963378906 20 10 C 20 15.52285003662109 15.52285003662109 20 10 20 C 4.477149963378906 20 0 15.52285003662109 0 10 C 0 4.477149963378906 4.477149963378906 0 10 0 Z"
                                                                        stroke="none" fill="#fcfcfb"/>
                                                                </g>
                                                                <path id="Pfad_157" data-name="Pfad 157"
                                                                      d="M-1151.25,4789.252l3.142,3.142,6.013-6.013"
                                                                      transform="translate(2390.673 -4108.337)"
                                                                      fill="none" stroke="#fcfcfb" stroke-width="1.5"/>
                                                            </g>
                                                        </svg>
                                                    </div>
                                                    <div
                                                        class="text-white w-44 flex items-center text-center justify-end mr-2"
                                                        v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED'">
                                                        <p class="xxsLight">verifiziert</p>
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="11.975"
                                                             height="13.686" class="ml-1" viewBox="0 0 11.975 13.686">
                                                            <path id="Icon_awesome-lock" data-name="Icon awesome-lock"
                                                                  d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z"
                                                                  fill="#fcfcfb"/>
                                                        </svg>
                                                    </div>
                                                    <div class="flex flex-wrap w-8">
                                                        <div class="flex">
                                                            <Menu as="div" class="my-auto relative"
                                                                  v-show="showMenu === 'subPosition' + subPosition.id">
                                                                <div class="flex">
                                                                    <MenuButton
                                                                        class="flex ml-6">
                                                                        <DotsVerticalIcon
                                                                            class="mr-3 flex-shrink-0 h-6 w-6 text-darkGray my-auto"
                                                                            aria-hidden="true"/>
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
                                                                        class="origin-top-right absolute right-0 w-80 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                                        <div class="py-1">

                                                                            <MenuItem v-slot="{ active }"
                                                                                      v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED'">
                                                                                    <span
                                                                                        @click="openVerifiedModalSub(subPosition)"
                                                                                        :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                        <TrashIcon
                                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                            aria-hidden="true"/>
                                                                                        Von User verifizieren lassen
                                                                                    </span>
                                                                            </MenuItem>
                                                                            <MenuItem v-slot="{ active }"
                                                                                      v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && subPosition.verified?.requested_by === this.$page.props.user.id">
                                                                                <span
                                                                                    @click="requestRemove(subPosition, 'sub')"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Verifizierungsanfrage zurücknehmen
                                                                                </span>
                                                                            </MenuItem>
                                                                            <MenuItem v-slot="{ active }"
                                                                                      v-if="subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' && subPosition.verified?.requested === this.$page.props.user.id">
                                                                                <span
                                                                                    @click="removeVerification(subPosition, 'sub')"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Verifizierung aufheben
                                                                                </span>
                                                                            </MenuItem>
                                                                            <MenuItem v-slot="{ active }">
                                                                                    <span
                                                                                        @click="openDeleteSubPositionModal(subPosition)"
                                                                                        :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                        <TrashIcon
                                                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                            aria-hidden="true"/>
                                                                                        Löschen
                                                                                    </span>
                                                                            </MenuItem>


                                                                        </div>
                                                                    </MenuItems>
                                                                </transition>
                                                            </Menu>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <table class="w-full" v-if="!subPosition.closed">
                                                <tbody class="bg-secondaryHover w-full">
                                                <tr v-if="subPosition.sub_position_rows?.length > 0"
                                                    :class="[rowIndex !== 0 && hoveredRow !== row.id ? 'border-t-2 border-silverGray': '', hoveredRow === row.id ? 'border-buttonBlue ' : '']"
                                                    @mouseover="hoveredRow = row.id" @mouseout="hoveredRow = null"
                                                    class="bg-secondaryHover flex justify-between items-center border-2"
                                                    v-for="(row,rowIndex) in subPosition.sub_position_rows">
                                                    <div class="flex items-center">
                                                        <PlusCircleIcon @click="addRowToSubPosition(subPosition, row)"
                                                                        :class="hoveredRow === row.id ? '' : 'hidden'"
                                                                        class="h-6 w-6 absolute -ml-3 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                                        <td v-for="(cell,index) in row.cells"
                                                            :class="[index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48', checkCellColor(cell,mainPosition,subPosition)]">
                                                            <div
                                                                :class="[row.commented ? 'xsLight' : '', index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48', cell.value < 0 ? 'text-red-500' : '']"
                                                                class="my-4 h-6 flex items-center pr-2.5 ml-2 justify-end"
                                                                @click="cell.clicked = !cell.clicked"
                                                                v-if="!cell.clicked">
                                                                <img v-if="cell.linked_money_source_id !== null"
                                                                     src="/Svgs/IconSvgs/icon_linked_moneySource.svg"
                                                                     class="h-6 w-6"/>
                                                                {{ cell.value }}
                                                            </div>
                                                            <div class="flex items-center justify-end"
                                                                 :class="index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48 ml-10'"
                                                                 v-else-if="cell.clicked && cell.column.type === 'empty'">
                                                                <input
                                                                    :class="index <= 1 ? 'w-20 mr-0.5' : index === 2 ? 'w-60 mr-0.5' : 'w-44'"
                                                                    class="my-2 xsDark text-right"
                                                                    :type="index > 2 ? 'number' : 'text'"
                                                                    v-model="cell.value"
                                                                    @focusout="updateCellValue(cell, mainPosition.is_verified, subPosition.is_verified)">
                                                                <PlusCircleIcon v-if="index > 2"
                                                                                @click="openCellDetailModal(cell)"
                                                                                class="h-6 w-6 flex-shrink-0 -ml-3 relative cursor-pointer text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                                            </div>
                                                            <div
                                                                :class="[row.commented ? 'xsLight' : 'xsDark', index <= 1 ? 'w-24' : index === 2 ? 'w-72' : 'w-48', cell.value < 0 ? 'text-red-500' : '']"
                                                                class="my-4 h-6 flex items-center"
                                                                @click="cell.clicked = !cell.clicked"
                                                                v-else>
                                                                <img v-if="cell.linked_money_source_id !== null"
                                                                     src="/Svgs/IconSvgs/icon_linked_moneySource.svg"
                                                                     class="h-6 w-6"/>
                                                                {{ cell.value }}
                                                                <PlusCircleIcon v-if="index > 2 && cell.clicked"
                                                                                @click="openCellDetailModal(cell)"
                                                                                class="h-6 w-6 ml-3 cursor-pointer text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                                            </div>
                                                        </td>
                                                    </div>
                                                    <XCircleIcon @click="deleteRowFromSubPosition(row)"
                                                                 :class="hoveredRow === row.id ? '' : 'hidden'"
                                                                 class="h-6 w-6 -mr-3 cursor-pointer justify-end text-secondaryHover bg-error rounded-full"></XCircleIcon>
                                                </tr>
                                                <div v-else @click="addRowToSubPosition(subPosition, row)"
                                                     class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                                                    <div
                                                        class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                                                        Zeile
                                                        <PlusCircleIcon
                                                            class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                                    </div>
                                                </div>
                                                <tr class="bg-silverGray xsDark flex h-10 w-full text-right">
                                                    <td class="w-24"></td>
                                                    <td class="w-24"></td>
                                                    <td class="w-72 my-2">SUM</td>
                                                    <div v-if="subPosition.sub_position_rows.length > 0"
                                                         class="flex items-center"
                                                         v-for="column in budget.columns.slice(3)">
                                                        <td class="w-48 ml-0.5 my-4"
                                                            :class="subPosition.columnSums[column.id] < 0 ? 'text-red-500' : ''">
                                                            {{
                                                                subPosition.columnSums[column.id]
                                                            }}
                                                        </td>
                                                    </div>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div @click="addSubPosition(mainPosition.id, subPosition)"
                                                 class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                                                <div
                                                    class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                                                    Unterposition
                                                    <PlusCircleIcon
                                                        class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                                </div>
                                            </div>
                                        </th>
                                    </tr>

                                    <tr class=" xsWhiteBold flex h-10 w-full text-right"
                                        :class="mainPosition.verified?.requested === this.$page.props.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED' ? 'bg-buttonBlue' : 'bg-primary'">
                                        <td class="w-24"></td>
                                        <td class="w-24"></td>
                                        <td class="w-72 my-2">SUM</td>
                                        <div v-if="mainPosition.sub_positions.length > 0" class="flex items-center"
                                             v-for="column in budget.columns.slice(3)">
                                            <td class="w-48 ml-0.5 my-4"
                                                :class="mainPosition.columnSums[column.id] < 0 ? 'text-red-500' : ''">
                                                {{
                                                    mainPosition.columnSums[column.id]
                                                }}
                                            </td>
                                        </div>
                                    </tr>
                                    </thead>
                                    <div @click="addMainPosition('BUDGET_TYPE_COST', mainPosition)"
                                         class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                                        <div
                                            class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                                            Hauptposition
                                            <PlusCircleIcon
                                                class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                                        </div>
                                    </div>

                                </table>

                            </th>
                        </tr>
                        <tr class="bg-secondaryHover xsDark flex h-10 w-full text-right">
                            <td class="w-24"></td>
                            <td class="w-24"></td>
                            <td class="w-72 my-2">SUM</td>
                            <td class="flex items-center"
                                v-for="column in budget.columns.slice(3)">
                                <div class="w-48 my-2"
                                     :class="this.getSumOfTable(0,column.id) < 0 ? 'text-red-500' : ''">
                                    {{ this.getSumOfTable(0, column.id) }}
                                </div>
                            </td>

                        </tr>
                        <!-- TODO: Hier noch einfügen if(commented === true) -->
                        <tr v-if="true" class="bg-secondaryHover xsLight flex h-10 w-full text-right">
                            <td class="w-24"></td>
                            <td class="w-24"></td>
                            <td class="w-72 my-2">SUM ausgeklammerte Posten</td>
                            <td class="w-48 my-2">3000</td>
                        </tr>
                        </tbody>
                    </table>

                </div>
                <!-- View if not opened Event -->
                <div class="ml-2 w-full bg-secondaryHover" v-else>
                    <div class="headline4 my-10">Ausgaben</div>
                </div>
            </div>
        </div>

        <div class="flex my-8 ">
            <div class="flex w-full border border-2 border-gray-300">
                <button class="bg-buttonBlue w-6"
                        @click="earningsOpened = !earningsOpened">
                    <ChevronUpIcon v-if="earningsOpened"
                                   class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                    <ChevronDownIcon v-else
                                     class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                </button>

                <div class="ml-2 w-11/12" v-if="earningsOpened">
                    <h2>Einahmen</h2>
                    <table class="w-full">
                        <tbody>
                        <tr v-for="mainPosition in tablesToShow[1]">
                            <th class="bg-primary text-white">
                                <div class="pl-2 flex items-center h-10">
                                    {{ mainPosition.name }}
                                    <button class="my-auto w-6 ml-3"
                                            @click="mainPosition.closed = !mainPosition.closed">
                                        <ChevronUpIcon v-if="!mainPosition.closed"
                                                       class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                                        <ChevronDownIcon v-else class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                                    </button>
                                </div>
                                <table v-if="!mainPosition.closed" class="w-full">
                                    <thead>
                                    <tr v-for="subPosition in mainPosition.sub_positions">
                                        <th class="bg-lightBackgroundGray xsDark">
                                            <div class="pl-2 flex items-center h-10">
                                                {{ subPosition.name }}
                                                <button class="my-auto w-6 ml-3"
                                                        @click="subPosition.closed = !subPosition.closed">
                                                    <ChevronUpIcon v-if="!subPosition.closed"
                                                                   class="h-6 w-6 text-primary my-auto"></ChevronUpIcon>
                                                    <ChevronDownIcon v-else
                                                                     class="h-6 w-6 text-primary my-auto"></ChevronDownIcon>
                                                </button>
                                            </div>
                                            <table class="w-full" v-if="!subPosition.closed">
                                                <tbody>
                                                <tr v-for="row in subPosition.sub_position_rows">
                                                    <td v-for="cell in row.cells" class="w-40">
                                                        <div @click="cell.clicked = !cell.clicked"
                                                             v-if="!cell.clicked">{{ cell.value }}
                                                        </div>
                                                        <div v-else>
                                                            <input type="text" v-model="cell.value"
                                                                   @focusout="cell.clicked = !cell.clicked">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="bg-lightBackgroundGray xsDark h-10"
                                                    style="background-color: #ccc !important">
                                                    <td></td>
                                                    <td></td>
                                                    <td>SUM</td>
                                                    <td>3000</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </th>
                                    </tr>
                                    </thead>
                                </table>
                            </th>
                        </tr>
                        </tbody>
                    </table>

                </div>
                <!-- View if not opened Event -->
                <div class="ml-2 w-11/12" v-else>
                    <h2>Einahmen</h2>
                </div>
            </div>
        </div>
    </div>


    <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">

                <div class="headline1 my-2">
                    {{ successHeading }}
                </div>
                <XIcon @click="closeSuccessModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="successText">
                    {{ successDescription }}
                </div>
                <div class="mt-6">
                    <button class="bg-success focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover rounded-full"
                            @click="closeSuccessModal">
                        <CheckIcon class="h-6 w-6 text-secondaryHover"/>
                    </button>
                </div>
            </div>

        </template>
    </jet-dialog-modal>


    <jet-dialog-modal :show="showVerifiedModal" @close="closeVerifiedModal">
        <template #content>
            <img alt="Neue Spalte" src="/Svgs/Overlays/illu_budget_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ verifiedTexts.title }} <span class="xsDark">{{ verifiedTexts.positionTitle }}</span>
                </div>
                <XIcon @click="closeVerifiedModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="mb-2 xsLight">
                    {{ verifiedTexts.description }}
                </div>
                <p class="xsLight flex mb-2">
                    <!-- TODO: SVG ersetzen mit IMG TAG -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" class="mr-2">
                        <g id="warning" transform="translate(-453 -292)">
                            <rect id="Rechteck_468" data-name="Rechteck 468" width="20" height="20"
                                  transform="translate(453 292)" fill="#e0e000"/>
                            <path id="Icon_metro-warning" data-name="Icon metro-warning"
                                  d="M8.4,2.984l4.884,9.734H3.514L8.4,2.984Zm0-1.056a.842.842,0,0,0-.693.508L2.731,12.351c-.381.678-.057,1.232.72,1.232h9.894c.777,0,1.1-.554.72-1.232h0L9.091,2.436A.842.842,0,0,0,8.4,1.928ZM9.126,11.4a.728.728,0,1,1-.728-.728A.728.728,0,0,1,9.126,11.4ZM8.4,9.941a.728.728,0,0,1-.728-.728V7.027a.728.728,0,1,1,1.457,0V9.212A.728.728,0,0,1,8.4,9.941Z"
                                  transform="translate(454.602 294.245)" fill="#fcfcfb" stroke="#fcfcfb"
                                  stroke-width="0.2"/>
                        </g>
                    </svg>
                    Achtung: Du gibst der/dem Nutzer*in dadurch Budgetzugriff!
                </p>
                <div class="mb-2">
                    <div class="relative w-full">
                        <div class="w-full" v-if="showUserAdd">
                            <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                   placeholder="Wer soll deine Kalkulation verifizieren?*"
                                   class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                 class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(user, index) in user_search_results" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="addUserToVerifiedUserArray(user)"
                                               class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                {{ user.first_name }} {{ user.last_name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>

                    <div v-if="submitVerifiedModalData.user !== ''" class="mt-2 mb-4 flex items-center">
                        <span class="flex mr-5 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <img class="flex h-11 w-11 rounded-full object-cover"
                                     :src="usersToAdd.profile_photo_url" alt=""/>
                                <span class="flex ml-4 sDark">
                                    {{ usersToAdd.first_name }} {{ usersToAdd.last_name }}
                                </span>
                                <button type="button" @click="deleteUserFromVerifiedUserArray">
                                    <span class="sr-only">User aus Finanzierungsquelle entfernen</span>
                                    <XIcon
                                        class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-buttonBlue text-white border-0 "/>
                                </button>
                            </div>
                        </span>
                    </div>
                </div>
                <div class="mt-6">
                    <button class="bg-success focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover rounded-full"
                            @click="submitVerifiedModal">
                        <CheckIcon class="h-6 w-6 text-secondaryHover"/>
                    </button>
                </div>
            </div>

        </template>
    </jet-dialog-modal>
    <!-- Termin erstellen Modal-->
    <add-column-component
        v-if="showAddColumnModal"
        :columns="budget.columns"
        :project="project"
        @closed="closeAddColumnModal()"
    />
    <!-- Cell Detail Modal-->
    <cell-detail-component
        v-if="showCellDetailModal"
        :cell="cellToShow"
        :moneySources="moneySources"
        @closed="closeCellDetailModal()"
    />
    <confirmation-component
        v-if="showDeleteModal"
        confirm="Löschen"
        titel="Hauptposition löschen"
        :description="this.confirmationDescription"
        @closed="afterConfirm"/>


    <pre>
        {{ budget.table }}
    </pre>
</template>

<script>


import {PencilAltIcon, PlusCircleIcon, TrashIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import {ChevronUpIcon, ChevronDownIcon, DotsVerticalIcon, CheckIcon} from "@heroicons/vue/solid";
import AddButton from "@/Layouts/Components/AddButton.vue";
import AddColumnComponent from "@/Layouts/Components/AddColumnComponent.vue";
import CellDetailComponent from "@/Layouts/Components/CellDetailComponent.vue";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import JetDialogModal from "@/Jetstream/DialogModal";
import {useForm} from "@inertiajs/inertia-vue3";

export default {
    name: 'BudgetComponent',

    components: {
        ConfirmationComponent,
        CellDetailComponent,
        AddColumnComponent,
        AddButton,
        ChevronDownIcon,
        ChevronUpIcon,
        PlusCircleIcon,
        XCircleIcon,
        DotsVerticalIcon,
        Menu,
        MenuItem,
        MenuItems,
        MenuButton,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        JetDialogModal,
        CheckIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
    },

    data() {
        return {
            costsOpened: true,
            earningsOpened: true,
            hoveredBorder: null,
            showAddColumnModal: false,
            showCellDetailModal: false,
            cellToShow: null,
            hoveredRow: null,
            showMenu: null,
            showDeleteModal: false,
            mainPositionToDelete: null,
            subPositionToDelete: null,
            confirmationDescription: '',
            showSuccessModal: false,
            successHeading: '',
            successDescription: '',
            positionDefault: {
                position: 0
            },
            colors: {
                whiteColumn: 'whiteColumn',
                greenColumn: 'greenColumn',
                yellowColumn: 'yellowColumn',
                redColumn: 'redColumn',
                lightGreenColumn: 'lightGreenColumn'
            },
            verifiedTexts: {
                title: 'Verifizierung',
                positionTitle: '',
                description: 'Sind alle Zahlen richtig kalkuliert? Ist die Kalkulation plausibel? Lasse deine Hauptposition durch eine Nutzer*in verifizieren. '
            },
            showVerifiedModal: false,
            user_search_results: [],
            user_query: '',
            usersToAdd: '',
            showUserAdd: true,
            submitVerifiedModalData: useForm({
                is_main: false,
                is_sub: false,
                id: null,
                user: '',
                position: [],
                project_title: this.project.name,
                project_id: this.project.id
            })
        }
    },

    props: ['budget', 'project', 'moneySources'],

    computed: {
        tablesToShow: function () {
            let costTableArray = [];
            let earningTableArray = [];
            this.budget.table.forEach((mainPosition) => {
                if (mainPosition.type === 'BUDGET_TYPE_COST') {
                    costTableArray.push(mainPosition);
                } else {
                    earningTableArray.push(mainPosition);
                }
            })
            return [costTableArray, earningTableArray]
        },
    },
    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        this.user_search_results = response.data
                    })
                }
            },
            deep: true
        },
    },

    methods: {
        checkCellColor(cell, mainPosition, subPosition) {
            let cssString = '';
            if (cell.value !== cell.verified_value) {
                if (mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' || subPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED') {
                    cssString += ' bg-red-300 '
                    if (cell.column.color !== 'whiteColumn') {
                        cssString += ' xsWhiteBold '
                    }
                } else {
                    cssString += cell.column.color;
                }
            }
            if (cell.column.color === 'whiteColumn') {
                if (cell.value !== cell.verified_value) {
                    cssString += ' xsWhiteBold ';
                } else {
                    cssString += ' xsDark ';
                }
            } else {
                cssString += ' xsWhiteBold ';
            }
            return cssString
        },
        getSumOfTable(tableType, columnId) {
            let sum = 0;
            this.tablesToShow[tableType].forEach((mainPosition) => {
                sum += mainPosition.columnSums[columnId];
            })
            return sum;
        },
        addUserToVerifiedUserArray(user) {
            this.submitVerifiedModalData.user = user.id;
            this.usersToAdd = user
            this.user_query = '';
            this.showUserAdd = false;
        },
        deleteUserFromVerifiedUserArray() {
            this.submitVerifiedModalData.user = '';
            this.usersToAdd = ''
            this.showUserAdd = true
        },
        changeColumnColor(color, columnId) {
            this.$inertia.patch(route('project.budget.column-color.change'), {
                color: color,
                columnId: columnId
            })
        },
        deleteColumn(column) {
            this.$inertia.delete(route('project.budget.column.delete', column))
        },
        addRowToSubPosition(subPosition, row) {

            this.$inertia.post(route('project.budget.sub-position-row.add'), {
                project_id: this.project.id,
                sub_position_id: subPosition.id,
                positionBefore: row ? row.position : -1
            }, {
                preserveState: true,
                preserveScroll: true
            });
        },
        currencyFormat(number) {
            const formatter = new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR',
            });
            return formatter.format(number);
        },
        openAddColumnModal() {
            this.showAddColumnModal = true;
        },
        closeAddColumnModal() {
            this.showAddColumnModal = false;
        },
        updateCellValue(cell, mainPositionVerified, subPositionVerified) {
            cell.clicked = !cell.clicked;
            if (cell.value === null || cell.value === '') {
                cell.value = 0;
            }

            this.$inertia.patch(route('project.budget.cell.update'), {
                column_id: cell.column.id,
                value: cell.value,
                sub_position_row_id: cell.sub_position_row_id,
                is_verified: mainPositionVerified === 'BUDGET_VERIFIED_TYPE_CLOSED' || subPositionVerified === 'BUDGET_VERIFIED_TYPE_CLOSED'
            }, {
                preserveState: true,
                preserveScroll: true
            });
        },
        addSubPosition(mainPositionId, subPosition = null) {

            let subPositionBefore = subPosition

            if (!subPositionBefore) {
                subPositionBefore = {
                    position: 0
                }
            }

            this.$inertia.post(route('project.budget.sub-position.add'), {
                project_id: this.project.id,
                main_position_id: mainPositionId,
                positionBefore: subPositionBefore.position
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        addMainPosition(type, mainPosition) {
            this.$inertia.post(route('project.budget.main-position.add'), {
                project_id: this.project.id,
                type: type,
                positionBefore: mainPosition.position
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        updateColumnName(column) {
            this.$inertia.patch(route('project.budget.column.update-name'), {
                column_id: column.id,
                columnName: column.name
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        updateMainPositionName(mainPosition) {
            this.$inertia.patch(route('project.budget.main-position.update-name'), {
                mainPosition_id: mainPosition.id,
                mainPositionName: mainPosition.name
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        updateSubPositionName(subPosition) {
            this.$inertia.patch(route('project.budget.sub-position.update-name'), {
                subPosition_id: subPosition.id,
                subPositionName: subPosition.name
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        openCellDetailModal(column) {
            this.cellToShow = column;
            this.showCellDetailModal = true;
        },
        closeCellDetailModal() {
            this.showCellDetailModal = false;
        },
        deleteRowFromSubPosition(row) {
            this.$inertia.delete(`/project/budget/sub-position-row/${row.id}`, {
                preserveScroll: true,
                preserveState: true
            });
        },
        openDeleteMainPositionModal(mainPosition) {
            this.confirmationDescription = 'Bist du sicher, dass du die Hauptposition ' + mainPosition.name + ' löschen möchtest?'
            this.mainPositionToDelete = mainPosition;
            this.showDeleteModal = true;
        },
        openDeleteSubPositionModal(subPosition) {
            this.confirmationDescription = 'Bist du sicher, dass du die Unterposition ' + subPosition.name + ' löschen möchtest?'
            this.subPositionToDelete = subPosition;
            this.showDeleteModal = true;
        },
        afterConfirm(bool) {
            if (!bool) return this.showDeleteModal = false;

            this.deletePosition();

        },
        deletePosition() {
            if (this.mainPositionToDelete !== null) {
                this.$inertia.delete(route('project.budget.main-position.delete', this.mainPositionToDelete.id))
                this.successHeading = "Hauptposition gelöscht"
                this.successDescription = "Hauptposition " + this.mainPositionToDelete.name + " erfolgreich gelöscht"
            } else if (this.subPositionToDelete !== null) {
                this.$inertia.delete(route('project.budget.sub-position.delete', this.subPositionToDelete.id))
                this.successHeading = "Unterposition gelöscht"
                this.successDescription = "Unterposition " + this.subPositionToDelete.name + " erfolgreich gelöscht"
            }
            this.showDeleteModal = false;
            this.showSuccessModal = true;

            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal() {
            this.mainPositionToDelete = null;
            this.subPositionToDelete = null;
            this.showSuccessModal = false;
            this.successHeading = "";
            this.successDescription = "";
        },
        closeVerifiedModal() {
            this.showVerifiedModal = false;
            this.user_query = '';
            this.showUserAdd = true;
            this.submitVerifiedModalData.user = '';
            this.submitVerifiedModalData.id = null;
            this.submitVerifiedModalData.is_main = false;
            this.submitVerifiedModalData.is_sub = false;
            this.submitVerifiedModalData.position = [];
        },
        submitVerifiedModal() {
            if (this.submitVerifiedModalData.is_main) {
                this.submitVerifiedModalData.post(route('project.budget.verified.main-position.request'));
            } else {
                this.submitVerifiedModalData.post(route('project.budget.verified.sub-position.request'));
            }

            this.closeVerifiedModal();
        },
        openVerifiedModal(mainPosition) {
            this.verifiedTexts.positionTitle = mainPosition.name
            this.submitVerifiedModalData.is_main = true
            this.submitVerifiedModalData.id = mainPosition.id
            this.submitVerifiedModalData.position = mainPosition
            this.showVerifiedModal = true
        },
        openVerifiedModalSub(subPosition) {
            this.verifiedTexts.positionTitle = subPosition.name
            this.submitVerifiedModalData.is_sub = true
            this.submitVerifiedModalData.id = subPosition.id
            this.submitVerifiedModalData.position = subPosition
            this.showVerifiedModal = true
        },
        verifiedMainPosition(mainPositionId) {
            this.$inertia.patch(this.route('project.budget.verified.main-position'), {
                mainPositionId: mainPositionId,
                project_id: this.project.id
            })
        },
        verifiedSubPosition(subPositionId) {
            this.$inertia.patch(this.route('project.budget.verified.sub-position'), {
                subPositionId: subPositionId,
                project_id: this.project.id
            })
        },
        requestRemove(position, type){
            this.$inertia.post(this.route('project.budget.take-back.verification'), {
                position: position,
                type: type
            })
        },
        removeVerification(position, type){
            this.$inertia.post(this.route('project.budget.remove.verification'), {
                position: position,
                type: type
            })
        }
    },
}
</script>

<style scoped>

/*
 greenColumn: '#50908E',
                yellowColumn: '#F0B54C',
                redColumn: '#D84387',
                lightGreenColumn: '#35A965'
 */
.whiteColumn {
    background-color: #FCFCFBFF;
}

.greenColumn {
    background-color: #50908E;
    border: 2px solid #1FC687;
}

.yellowColumn {
    background-color: #F0B54C;
}

.redColumn {
    background-color: #D84387;
}

.lightGreenColumn {
    background-color: #35A965;
}
</style>
