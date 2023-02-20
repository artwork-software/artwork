<template>

    <div :class="table.is_template ? '' : 'bg-lightBackgroundGray'" class="mx-1 pr-10 pt-6">
        <div class="flex justify-between ">
            <div v-if="table.is_template" class="flex justify-start mb-6 headline2">
                {{ table.name }}
                <Menu as="div" class="ml-4">
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
                                    <a @click="openRenameTableModal()"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Umbenennen
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a v-show="tableIsEmpty && !table.is_template" @click="openUseTemplateModal()"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Vorlage einlesen
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a v-show="tableIsEmpty && !table.is_template" @click="openUseTemplateFromProjectModal()"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Aus Projekt einlesen
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a v-show="!tableIsEmpty && !table.is_template" @click="openAddBudgetTemplateModal()"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Als Vorlage speichern
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a v-show="!tableIsEmpty && !table.is_template" @click="resetBudgetTable"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Zurücksetzen
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a v-show="table.is_template" @click="deleteBudgetTemplate()"
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
            </div>
            <div v-else class="flex">

            </div>
            <div class=" mb-5">

            </div>
        </div>
        <div class="w-full flex">
            <table class="w-full flex ml-6">
                <thead>
                <tr>
                    <th v-for="(column,index) in table.columns"
                        :class="index <= 1 ? 'pl-2 w-28 text-left' : index === 2 ? 'w-64 text-left pl-2' : index === 3 ? 'w-52 text-right' : 'w-48 px-1 text-right'">
                        <div class="flex items-center " @mouseover="showMenu = column.id" :key="column.id"
                             @mouseout="showMenu = null">
                            <div>
                                <div class="flex items-center justify-end pr-2">
                                    <p v-if="column.subName" class="columnSubName -mt-4 xsLight">
                                        {{ column.subName }}
                                        <span v-if="column.calculateName" class="ml-1 truncate">
                                            ({{ column.calculateName }})
                                        </span>
                                    </p>
                                    <span class="ml-1 -mt-4"
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
                                                        class="absolute w-24 z-10 mt-12 bg-primary shadow-lg max-h-64 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                                        <ListboxOption as="template" class=""
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
                                     :class="index <= 1 ? 'w-16 justify-start' : index === 2 ? 'w-64 justify-start' : index === 3 ? 'w-48 justify-end' : 'w-40 px-3 justify-end'" class="h-5 pr-1 mr-1 xsDark flex "
                                     v-if="!column.clicked">
                                    <svg v-if="column.is_locked" xmlns="http://www.w3.org/2000/svg" width="11.975"
                                         height="13.686" class="mr-2 flex items-center mt-0.5" viewBox="0 0 11.975 13.686">
                                        <path id="Icon_awesome-lock" data-name="Icon awesome-lock"
                                              d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z"
                                              fill="#27233C"/>
                                    </svg>
                                    {{ column.name }}

                                </div>
                                <div v-else>
                                    <input
                                        :class="index <= 1 ? 'w-16 text-left' : index === 2 ? 'w-64 text-left' : index === 3 ? 'w-48 text-right' : 'w-40 text-right'"
                                        class="xsDark h-5  pr-1 mr-1 flex " type="text"
                                        v-model="column.name"
                                        @focusout="updateColumnName(column); column.clicked = !column.clicked">
                                </div>
                            </div>
                            <Menu as="div" v-show="showMenu === column.id">
                                <div class="flex">
                                    <MenuButton
                                        class="flex ">
                                        <DotsVerticalIcon
                                            class="flex-shrink-0 h-5 w-5 text-gray-600"
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
                                            <MenuItem v-slot="{ active }" v-if="!column.is_locked">
                                                <a @click="lockColumn(column.id)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <PencilAltIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Sperren
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }" v-if="column.is_locked">
                                                <a @click="unlockColumn(column.id)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <PencilAltIcon
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    Entsperren
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a v-show="index > 2" @click="deleteColumn(column.id)"
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
                            <div v-if="showMenu !== column.id" class="w-5">

                            </div>
                        </div>
                    </th>
                    <button @click="openAddColumnModal()" class="text-white font-bold ml-4 text-xl bg-buttonBlue p-1 hover:bg-buttonHover rounded-full items-center uppercase shadow-sm text-secondaryHover">
                        <PlusIcon class="h-4 w-4"></PlusIcon>
                    </button>
                </tr>
                </thead>
            </table>
        </div>
        <div class="w-full flex my-8">
            <div class="flex flex-wrap w-full bg-secondaryHover border border-2 border-gray-300">
                <div class="w-full flex">
                    <div class="bg-secondaryHover ml-5 w-full" v-if="costsOpened">
                        <div :class="table.columns?.length > 5 ? 'mr-5' : 'w-[97%]'" class="flex justify-between my-10">
                        <div class="headline4  flex">Ausgaben
                            <button class="w-6"
                                    @click="costsOpened = !costsOpened">
                                <ChevronUpIcon v-if="costsOpened"
                                               class="h-6 w-6 text-primary my-auto"></ChevronUpIcon>
                                <ChevronDownIcon v-else
                                                 class="h-6 w-6 text-primary my-auto"></ChevronDownIcon>
                            </button>
                        </div>
                        <Menu v-if="!table.is_template" as="div" class="">
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
                                    class="absolute w-56 -translate-x-full shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                    <div class="py-1">
                                        <MenuItem v-slot="{ active }">
                                            <a v-show="tableIsEmpty && !table.is_template" @click="openUseTemplateModal()"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <TrashIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Vorlage einlesen
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a v-show="tableIsEmpty && !table.is_template" @click="openUseTemplateFromProjectModal()"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <TrashIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Aus Projekt einlesen
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a v-show="!tableIsEmpty && !table.is_template" @click="openAddBudgetTemplateModal()"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <TrashIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Als Vorlage speichern
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a v-show="!tableIsEmpty && !table.is_template" @click="resetBudgetTable"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <TrashIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Zurücksetzen
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a v-show="table.is_template" @click="deleteBudgetTemplate()"
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
                        </div>
                        <div @click="addMainPosition('BUDGET_TYPE_COST', positionDefault)"
                             class="group w-[97%] bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                            <div class="group-hover:block hidden uppercase text-buttonBlue text-sm -mt-8">
                                Hauptposition
                                <PlusCircleIcon
                                    class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                            </div>
                        </div>
                        <table class="w-[97%] mb-6">
                            <tbody class="">
                            <tr v-if="tablesToShow[0]?.length > 0" v-for="(mainPosition,mainIndex) in tablesToShow[0]">
                                <MainPositionComponent @openRowDetailModal="openRowDetailModal"
                                                       @openVerifiedModal="openVerifiedModal"
                                                       @openCellDetailModal="openCellDetailModal"
                                                       @openDeleteModal="openDeleteModal"
                                                       @open-error-modal="openErrorModal"
                                                       :table="table"
                                                       :project="project"
                                                       :main-position="mainPosition"></MainPositionComponent>
                            </tr>
                            <tr class="bg-secondaryHover xsDark flex h-10 w-full text-right">
                                <td class="w-28"></td>
                                <td class="w-28"></td>
                                <td class="w-72 my-2">SUM</td>
                                <td class="flex items-center w-48"
                                    v-for="column in table.columns?.slice(3)">
                                    <div class="w-48 my-2 p-1"
                                         :class="this.getSumOfTable(0,column.id) < 0 ? 'text-red-500' : ''">
                                        {{ this.getSumOfTable(0, column.id)?.toLocaleString()}}
                                    </div>
                                </td>

                            </tr>
                            <tr class="bg-secondaryHover xsLight flex h-10 w-full text-right">
                                <td class="w-28"></td>
                                <td class="w-28"></td>
                                <td class="w-72 my-2">SUM ausgeklammerte Posten</td>
                                <td class="flex items-center w-48"
                                    v-for="column in table.columns.slice(3)">
                                    <div class="w-48 my-2 p-1">
                                        {{ table.commentedCostSums[column.id]?.toLocaleString() }}
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                    <!-- View if not opened Event -->
                    <div class="ml-5 w-full bg-secondaryHover" v-else>
                        <div class="headline4 my-10 flex">Ausgaben
                            <button class="w-6"
                                    @click="costsOpened = !costsOpened">
                                <ChevronUpIcon v-if="costsOpened"
                                               class="h-6 w-6 text-primary my-auto"></ChevronUpIcon>
                                <ChevronDownIcon v-else
                                                 class="h-6 w-6 text-primary my-auto"></ChevronDownIcon>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Divider -->
                <div class="border-t-2 border-b-2 h-1.5 w-full ml-5 mr-12" />
                    <div class="w-full flex">
                    <div class="ml-5 w-full bg-secondaryHover" v-if="earningsOpened">
                        <div class="headline4 my-10 flex">Einnahmen
                            <button class="w-6"
                                    @click="earningsOpened = !earningsOpened">
                                <ChevronUpIcon v-if="earningsOpened"
                                               class="h-6 w-6 text-primary my-auto"></ChevronUpIcon>
                                <ChevronDownIcon v-else
                                                 class="h-6 w-6 text-primary my-auto"></ChevronDownIcon>
                            </button>
                        </div>
                        <table class="w-[97%] mb-6">
                            <tbody class="">
                            <tr v-if="tablesToShow[1]?.length > 0" v-for="(mainPosition,mainIndex) in tablesToShow[1]">
                                <MainPositionComponent @openRowDetailModal="openRowDetailModal"
                                                       @openVerifiedModal="openVerifiedModal"
                                                       @openCellDetailModal="openCellDetailModal"
                                                       @openDeleteModal="openDeleteModal"
                                                       @open-error-modal="openErrorModal" :table="table"
                                                       :project="project"
                                                       :main-position="mainPosition"></MainPositionComponent>
                            </tr>
                            <tr class="bg-secondaryHover xsDark flex h-10 w-full text-right">
                                <td class="w-28"></td>
                                <td class="w-28"></td>
                                <td class="w-72 my-2">SUM</td>
                                <td class="flex items-center w-48"
                                    v-for="column in table.columns.slice(3)">
                                    <div class="w-48 my-2 p-1"
                                         :class="this.getSumOfTable(1,column.id) < 0 ? 'text-red-500' : ''">
                                        {{ this.getSumOfTable(1, column.id)?.toLocaleString() }}
                                    </div>
                                </td>

                            </tr>
                            <tr class="bg-secondaryHover xsLight flex h-10 w-full text-right">
                                <td class="w-28"></td>
                                <td class="w-28"></td>
                                <td class="w-72 my-2">SUM ausgeklammerte Posten</td>
                                <td class="flex items-center w-48"
                                    v-for="column in table.columns.slice(3)">
                                    <div class="w-48 my-2 p-1">
                                        {{ table.commentedEarningSums[column.id]?.toLocaleString() }}
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- View if not opened Event -->
                    <div class="ml-5 w-full bg-secondaryHover" v-else>
                        <div class="headline4 my-10 flex">Einnahmen
                            <button class="w-6"
                                    @click="earningsOpened = !earningsOpened">
                                <ChevronUpIcon v-if="earningsOpened"
                                               class="h-6 w-6 text-primary my-auto"></ChevronUpIcon>
                                <ChevronDownIcon v-else
                                                 class="h-6 w-6 text-primary my-auto"></ChevronDownIcon>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Divider -->
                <div class="border-t-2 border-b-2 h-1.5 w-full ml-5 mr-12" />
                <tr class="bg-secondaryHover items-center xsDark flex h-10 mt-4 mb-2 w-full text-right">
                    <td class="w-44 xsDark uppercase flex ml-6">
                        Einnahmen - Ausgaben
                    </td>
                    <td class="w-10 mr-1"></td>
                    <td class="w-72 my-2">SUM</td>
                    <td class="flex items-center w-48"
                        v-for="column in table.columns.slice(3)">
                        <div class="w-48 my-2 p-1"
                             :class="this.getSumOfTable(1, column.id) - this.getSumOfTable(0, column.id) < 0 ? 'text-red-500' : ''">
                            {{ (this.getSumOfTable(1, column.id) - this.getSumOfTable(0, column.id)).toLocaleString() }}
                        </div>
                    </td>

                </tr>

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
                <div class="mb-3 xsLight" v-html="verifiedTexts.description">

                </div>
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
                                    <div v-for="(user, index) in user_search_results" :key="index" class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4" v-if="budgetAccess.includes(user.id)">
                                            <p @click="addUserToVerifiedUserArray(user)"
                                               class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                {{ user.first_name }} {{ user.last_name }}
                                            </p>
                                        </div>
                                        <!-- Project Members -->
                                        <div class="flex-1 text-sm py-4" v-if="projectMembers.includes(user.id) && !budgetAccess.includes(user.id)">
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
                <div class="mt-6 flex justify-center">
                    <button class="focus:outline-none my-auto inline-flex items-center px-10 py-3 border border-transparent
                            text-xs font-bold uppercase shadow-sm text-secondaryHover rounded-full bg-buttonBlue"
                            @click="submitVerifiedModal">
                        Zur Verifizierung auffordern
                    </button>
                </div>
            </div>

        </template>
    </jet-dialog-modal>

    <jet-dialog-modal :show="showBudgetAccessModal" @close="closeBudgetAccessModal">
        <template #content>
            <img alt="Neue Spalte" src="/Svgs/Overlays/illu_budget_access.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Budgetzugriff erteilen
                </div>
                <p>
                    Die/der von dir zur Verifizierung angefragte Nutzer*in hat bisher keinen Budgetzugriff auf dein Projekt. Mit der Verifizierungsanfrage erteilst du ihr/ihm dieses Recht. Bist du sicher, dass du ihr/ihm dieses Recht geben möchtest?
                </p>
                <XIcon @click="closeBudgetAccessModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>

                <div class="mt-6">
                    <button class="focus:outline-none my-auto inline-flex items-center px-10 py-3 border border-transparent
                            text-xs font-bold uppercase shadow-sm text-secondaryHover rounded-full bg-buttonBlue"
                            @click="submitVerifiedModalWithBudgetAccess">
                        Anfragen & Budgetzugriff erteilen
                    </button>
                </div>
            </div>

        </template>
    </jet-dialog-modal>
    <!-- Termin erstellen Modal-->
    <add-column-component
        v-if="showAddColumnModal"
        :project="project"
        :table="table"
        @closed="closeAddColumnModal()"
    />
    <!-- Cell Detail Modal-->
    <cell-detail-component
        v-if="showCellDetailModal"
        :cell="selectedCell"
        :moneySources="moneySources"
        @closed="closeCellDetailModal()"
    />
    <!-- Vorlage einlesen Modal-->
    <use-template-component
        v-if="showUseTemplateModal"
        :projectId="project?.id"
        :templates="templates"
        @closed="closeUseTemplateModal()"
    />
    <!-- Aus Projekt einlesen Modal-->
    <use-template-from-project-budget-component
        v-if="showUseTemplateFromProjectModal"
        :projectId="project?.id"
        @closed="closeUseTemplateFromProjectModal()"
    />
    <!-- Als Vorlage speichern Modal-->
    <add-budget-template-component
        v-if="showAddBudgetTemplateModal"
        :table-id="table.id"
        @closed="closeAddBudgetTemplateModal()"
    />
    <!-- Tabelle umbenennen Modal-->
    <rename-table-component
        v-if="showRenameTableModal"
        :table="table"
        @closed="closeRenameBudgetTemplateModal()"
    />
    <!-- Row Detail Modal-->
    <row-detail-component
        v-if="showRowDetailModal"
        :row="selectedRow"
        :moneySources="moneySources"
        @closed="closeRowDetailModal()"
    />
    <!-- Nachfrage-Modal bei Löschfunktionalitäten -->
    <confirmation-component
        v-if="showDeleteModal"
        confirm="Löschen"
        :titel="this.confirmationTitle"
        :description="this.confirmationDescription"
        @closed="afterConfirm"/>
    <!-- Modal für Error-Info -->
    <error-component
        v-if="showErrorModal"
        confirm="Ok"
        :titel="this.errorTitle"
        :description="this.errorDescription"
        @closed="afterErrorConfirm"/>

</template>

<script>


import {PencilAltIcon, PlusCircleIcon, TrashIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import {ChevronUpIcon, ChevronDownIcon,PlusIcon, DotsVerticalIcon, CheckIcon} from "@heroicons/vue/solid";
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
import {useForm, usePage} from "@inertiajs/inertia-vue3";
import {Inertia} from "@inertiajs/inertia";
import MainPositionComponent from "@/Layouts/Components/MainPositionComponent.vue";
import RowDetailComponent from "@/Layouts/Components/RowDetailComponent.vue";
import UseTemplateComponent from "@/Layouts/Components/UseTemplateComponent.vue";
import UseTemplateFromProjectBudgetComponent from "@/Layouts/Components/UseTemplateFromProjectBudgetComponent.vue";
import AddBudgetTemplateComponent from "@/Layouts/Components/AddBudgetTemplateComponent.vue";
import Button from "@/Jetstream/Button.vue";
import RenameTableComponent from "@/Layouts/Components/RenameTableComponent.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";

export default {
    name: 'BudgetComponent',

    components: {
        Button,
        UseTemplateFromProjectBudgetComponent,
        MainPositionComponent,
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
        RowDetailComponent,
        UseTemplateComponent,
        AddBudgetTemplateComponent,
        PlusIcon,
        RenameTableComponent,
        ErrorComponent
    },

    data() {
        return {
            showBudgetAccessModal: false,
            costsOpened: true,
            earningsOpened: true,
            hoveredBorder: null,
            showAddColumnModal: false,
            showCellDetailModal: false,
            showRowDetailModal: false,
            showUseTemplateModal: false,
            showRenameTableModal: false,
            showUseTemplateFromProjectModal: false,
            showAddBudgetTemplateModal: false,
            resetWanted:false,
            hoveredRow: null,
            showMenu: null,
            showDeleteModal: false,
            showErrorModal: false,
            mainPositionToDelete: null,
            subPositionToDelete: null,
            rowToDelete: null,
            confirmationTitle: '',
            confirmationDescription: '',
            errorTitle:'',
            errorDescription:'',
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
                description: 'Sind alle Zahlen richtig kalkuliert? Ist die Kalkulation plausibel? <br> Lasse deine Hauptposition durch eine Nutzer*in verifizieren. '
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
                giveBudgetAccess: false,
                project_title: this.project?.name,
                table_id: this.table?.id
            }),
        }
    },

    props: ['table', 'project', 'moneySources','selectedCell','selectedRow','templates', 'budgetAccess', 'projectManager'],

    computed: {
        tablesToShow: function () {
            let costTableArray = [];
            let earningTableArray = [];
            this.table.main_positions.forEach((mainPosition) => {
                if (mainPosition.type === 'BUDGET_TYPE_COST') {
                    costTableArray.push(mainPosition);
                } else {
                    earningTableArray.push(mainPosition);
                }
            })
            return [costTableArray, earningTableArray]
        },
        tableIsEmpty: function () {
            if(this.table.main_positions.length === 2 && this.table.main_positions[0].sub_positions.length === 1 && this.table.main_positions[0].sub_positions[0].sub_position_rows.length === 1 && this.table.columns?.length === 4){
                return true;
            }else{
                return false;
            }
        },
        projectMembers: function () {

            let projectMemberArray = [];
            this.project.users.forEach(member => {
                    projectMemberArray.push(member.id)
                }
            )
            return projectMemberArray;
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
            if (cell.column.color === 'whiteColumn') {
                if (cell.value !== cell.verified_value) {
                    cssString += ' xsWhiteBold ';
                } else {
                    cssString += ' xsDark ';
                }
            } else {
                cssString += ' xsWhiteBold ';
                if (cell.value !== cell.verified_value) {
                    cssString += ' bg-red-300 '
                } else {
                    cssString += cell.column.color;
                }
            }

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

            return cssString
        },
        getSumOfTable(tableType, columnId, isCommented) {
            let sum = 0;
            this.tablesToShow[tableType].forEach((mainPosition) => {
                sum += mainPosition.columnSums[columnId];
            })
            if(isNaN(sum)){
                return 0;
            }else{
                return sum;
            }

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
                table_id: this.table.id,
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
        openUseTemplateModal() {

            Inertia.reload({
                data: {
                    useTemplates: true
                },
                onSuccess: () => {
                    this.showUseTemplateModal = true;
                }
            })
        },
        openRenameTableModal(){
          this.showRenameTableModal = true;
        },
        closeUseTemplateModal() {
            this.showUseTemplateModal = false;
        },
        openUseTemplateFromProjectModal() {
            this.showUseTemplateFromProjectModal = true;
        },
        closeUseTemplateFromProjectModal() {
            this.showUseTemplateFromProjectModal = false;
        },
        openAddBudgetTemplateModal() {
            this.showAddBudgetTemplateModal = true;
        },
        closeAddBudgetTemplateModal() {
            this.showAddBudgetTemplateModal = false;
        },
        closeRenameBudgetTemplateModal(){
          this.showRenameTableModal = false;
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
                table_id: this.table.id,
                main_position_id: mainPositionId,
                positionBefore: subPositionBefore.position
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        addMainPosition(type, mainPosition) {
            this.$inertia.post(route('project.budget.main-position.add'), {
                table_id: this.table.id,
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
            Inertia.reload({
                data: {
                    selectedCell: column.id,
                },
                onSuccess: () => {
                    this.showCellDetailModal = true;
                }
            })
        },
        openRowDetailModal(row) {
            Inertia.reload({
                data: {
                    selectedRow: row.id,
                },
                onSuccess: () => {
                    this.showRowDetailModal = true;
                }
            })
        },
        closeCellDetailModal() {
            this.showCellDetailModal = false;
        },
        closeRowDetailModal() {
            this.showRowDetailModal = false;
        },
        openDeleteRowModal(row) {
            this.confirmationTitle = 'Zeile löschen';
            this.confirmationDescription = 'Bist du sicher, dass du diese Zeile löschen möchtest? Sämtliche Verlinkungen etc. werden ebenfalls gelöscht.';
            this.rowToDelete = row;
            this.showDeleteModal = true;
        },
        openDeleteModal(title, description, position, type) {
            this.confirmationTitle = title;
            this.confirmationDescription = description
            if (type === 'main') {
                this.mainPositionToDelete = position;
            } else if (type === 'sub') {
                this.subPositionToDelete = position;
            } else {
                this.rowToDelete = position;
            }
            this.showDeleteModal = true;
        },
        openDeleteSubPositionModal(subPosition) {
            this.confirmationTitle = 'Unterposition löschen';
            this.confirmationDescription = 'Bist du sicher, dass du die Unterposition ' + subPosition.name + ' löschen möchtest?'
            this.subPositionToDelete = subPosition;
            this.showDeleteModal = true;
        },
        afterConfirm(bool) {
            if (!bool){
                this.resetWanted = false;
                return this.showDeleteModal = false;
            }
            if(this.resetWanted === true)
            {
                this.resetBudgetTable();
            }else{
                this.deletePosition();
            }
        },
        afterErrorConfirm(bool){
          this.showErrorModal = false;
        },
        deletePosition() {
            if (this.mainPositionToDelete !== null) {
                this.$inertia.delete(route('project.budget.main-position.delete', this.mainPositionToDelete.id),{preserveState: true, preserveScroll: true})
                this.successHeading = "Hauptposition gelöscht"
                this.successDescription = "Hauptposition " + this.mainPositionToDelete.name + " erfolgreich gelöscht"
            } else if (this.subPositionToDelete !== null) {
                this.$inertia.delete(route('project.budget.sub-position.delete', this.subPositionToDelete.id),{preserveState: true, preserveScroll: true})
                this.successHeading = "Unterposition gelöscht"
                this.successDescription = "Unterposition " + this.subPositionToDelete.name + " erfolgreich gelöscht"
            } else {
                this.$inertia.delete(`/project/budget/sub-position-row/${this.rowToDelete.id}`, {
                    preserveScroll: true,
                    preserveState: true
                });
                this.successHeading = "Zeile gelöscht"
                this.successDescription = "Zeile erfolgreich gelöscht"
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
        closeVerifiedModal(deleteAll = false) {
            this.showVerifiedModal = false;
            if(deleteAll){
                this.user_query = '';
                this.showUserAdd = true;
                this.submitVerifiedModalData.user = '';
                this.submitVerifiedModalData.id = null;
                this.submitVerifiedModalData.is_main = false;
                this.submitVerifiedModalData.is_sub = false;
                this.submitVerifiedModalData.position = [];
            }
        },
        closeBudgetAccessModal(){
            this.showBudgetAccessModal = false;
            this.user_query = '';
            this.showUserAdd = true;
            this.submitVerifiedModalData.user = '';
            this.submitVerifiedModalData.id = null;
            this.submitVerifiedModalData.is_main = false;
            this.submitVerifiedModalData.is_sub = false;
            this.submitVerifiedModalData.position = [];
        },
        submitVerifiedModalWithBudgetAccess(){
            this.submitVerifiedModalData.giveBudgetAccess = true;
            this.submitVerifiedModal();
        },
        submitVerifiedModal() {
            if(this.budgetAccess.includes(this.submitVerifiedModalData.user) || this.submitVerifiedModalData.giveBudgetAccess){
                if (this.submitVerifiedModalData.is_main) {
                    this.submitVerifiedModalData.post(route('project.budget.verified.main-position.request'),{preserveState: true, preserveScroll: true});
                } else {
                    this.submitVerifiedModalData.post(route('project.budget.verified.sub-position.request'),{preserveState: true, preserveScroll: true});
                }
                this.closeVerifiedModal(true);
            } else {
                this.showBudgetAccessModal = true;
                this.closeVerifiedModal(false);
            }

            if(this.submitVerifiedModalData.giveBudgetAccess){
                this.closeBudgetAccessModal()
            }


        },
        openVerifiedModal(is_main, is_sub, id, position) {
            this.verifiedTexts.positionTitle = position.name
            this.submitVerifiedModalData.is_main = is_main
            this.submitVerifiedModalData.is_sub = is_sub
            this.submitVerifiedModalData.id = id
            this.submitVerifiedModalData.position = position
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
                table_id: this.table.id,
            }, {preserveState: true, preserveScroll: true})
        },
        verifiedSubPosition(subPositionId) {
            this.$inertia.patch(this.route('project.budget.verified.sub-position'), {
                subPositionId: subPositionId,
                table_id: this.table.id,
            }, {preserveState: true, preserveScroll: true})
        },
        requestRemove(position, type) {
            this.$inertia.post(this.route('project.budget.take-back.verification'), {
                position: position,
                type: type
            }, {preserveState: true, preserveScroll: true})
        },
        removeVerification(position, type) {
            this.$inertia.post(this.route('project.budget.remove.verification'), {
                position: position,
                type: type
            }, {preserveState: true, preserveScroll: true})
        },
        lockColumn(columnId) {
            this.$inertia.patch(this.route('project.budget.lock.column'), {
                columnId: columnId
            }, {preserveState: true, preserveScroll: true});
        },
        unlockColumn(columnId) {
            this.$inertia.patch(this.route('project.budget.unlock.column'), {
                columnId: columnId
            }, {preserveState: true, preserveScroll: true});
        },
        openResetConfirmation(){
            this.confirmationTitle = 'Budgettabellen zurücksetzen';
            this.confirmationDescription = 'Bist du sicher, dass du diese Tabellen zurücksetzen möchtest? Sämtliche Verlinkungen etc. werden ebenfalls gelöscht.';
            this.resetWanted = true;
            this.showDeleteModal = true;
        },
        resetBudgetTable(){
            this.$inertia.patch(this.route('project.budget.reset.table', this.project.id),{}, {preserveState: true, preserveScroll: true})
            this.resetWanted= false;
            this.showDeleteModal = false;
        },
        deleteBudgetTemplate(){
          this.$inertia.delete(this.route('project.budget.table.delete', this.table.id), {preserveState: true, preserveScroll: true})
        },
        openErrorModal(title, description) {
            this.errorTitle = title;
            this.errorDescription = description
            this.showErrorModal = true;
        },
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
