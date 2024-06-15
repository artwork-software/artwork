<template>
    <InventoryHeader :title="this.$t('Inventory')">
        <div class="flex flex-col">
            <table class="table-auto">
                <thead class="sticky top-0 z-20 bg-artwork-messages-info transition-all duration-300 shadow-sm">
                    <tr class="table-row text-xs font-light w-full h-full">
                        <th v-for="(column,index) in columns"
                            @mouseover="showMenu = column.id" :key="column.id"
                            @mouseout="showMenu = null"
                            class="max-w-20 h-full">
                            <div class="w-full h-full flex flex-row items-center relative">
                                <div class="flex flex-row w-full h-full py-2 text-left items-center cursor-pointer">
                                    <div class="w-[calc(100%-0.8rem)] indent-3 overflow-hidden overflow-ellipsis whitespace-nowrap" @dblclick="column.clicked = !column.clicked; selectInput(column.id)">
                                        {{ column.name }}
                                    </div>
                                    <input
                                        type="text"
                                        :ref="(element) => createDynamicColumnNameInputRef(element, column.id)"
                                        :class="[column.clicked ? '' : 'hidden', 'w-[calc(100%-1rem)] p-2 top-[5px] left-1 z-50 absolute border-0 text-xs']"
                                        v-model="column.name"
                                        @focusout="column.clicked = !column.clicked">
                                </div>
                                <Menu v-show="showMenu === column.id"
                                      as="div"
                                      class="absolute z-30 -right-2 cursor-pointer flex flex-row items-center">
                                    <MenuButton class="z-50">
                                        <IconDotsVertical stroke-width="1.5" class=" flex-shrink-0" aria-hidden="true"/>
                                    </MenuButton>
                                    <div class="w-full h-full relative">
                                        <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                            <MenuItems class="absolute -translate-x-[110%] z-40 top-2 shadow-lg rounded-xl bg-artwork-navigation-background focus:outline-none">
                                                <MenuItem v-slot="{ active }" as="div">
                                                    <a @click="column.showColorMenu = true"
                                                       :class="[active ? 'rounded-xl bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 subpixel-antialiased']">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                             stroke-width="1.5" stroke="currentColor"
                                                             class="mr-3 h-5 w-5 text-primaryText group-hover:text-white">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                  d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/>
                                                        </svg>
                                                        {{ $t('Coloring') }}
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }" as="div">
                                                    <a @click="dummyFn()"
                                                       :class="[active ? 'rounded-xl bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 subpixel-antialiased']">
                                                        <IconCopy class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                        {{ $t('Duplicate') }}
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-if="index > 2"
                                                          v-slot="{ active }"
                                                          as="div">
                                                    <a @click="dummyFn()"
                                                       :class="[active ? 'rounded-xl bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 subpixel-antialiased']">
                                                        <IconTrash class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                        {{ $t('Delete') }}
                                                    </a>
                                                </MenuItem>
                                            </MenuItems>
                                        </transition>
                                    </div>
                                </Menu>
                                <Listbox v-if="column.showColorMenu === true"
                                         as="div"
                                         v-model="column.color">
                                    <ListboxOptions :static="column.showColorMenu"
                                                    class="absolute -translate-x-[107%] translate-y-[8px] z-40 flex flex-col gap-2 p-2  shadow-lg rounded-xl bg-artwork-navigation-background focus:outline-none">
                                        <ListboxOption as="template"
                                                       v-for="color in ['bg-blue-700', 'bg-red-700', 'bg-green-700', 'bg-pink-700']"
                                                       :key="color"
                                                       :value="color"
                                                       v-slot="{ active, selected }">
                                            <li class="cursor-pointer flex flex-row p-2 items-center hover:bg-artwork-navigation-color/10 transition-all duration-300 rounded-md"
                                                @click="dummyFn(); column.showColorMenu = false;">
                                                <span class="flex rounded-full h-10 w-10 relative" :class="color">
                                                     <CheckIcon v-if="selected"
                                                                :class="'h-5 w-5 flex absolute top-2.5 left-2.5 bg-black bg-opacity-25 p-1 text-white rounded-full'"
                                                                aria-hidden="true"/>
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </Listbox>
                            </div>
                        </th>
                        <th class="flex flex-row items-center p-2">
                            <div class="flex flex-row items-center gap-x-2">
                                <div class="text-white">
                                    {{ $t('New column') }}
                                </div>
                                <button
                                    class="font-bold text-xl hover:bg-artwork-buttons-hover p-1 bg-secondary border-white border-2 rounded-full items-center shadow-sm text-secondaryHover transition-all duration-150"
                                    @click="dummyFn()">
                                    <PlusIcon stroke-width="1.5" class="h-4 w-4"/>
                                </button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="bg-red-700 h-10"></td>
                    </tr>
                    <tr>
                        <td colspan="7" class="bg-blue-700 h-10"></td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td colspan="7" class="bg-red-700 h-10"></td>
                    </tr>
                    <tr>
                        <td colspan="7" class="bg-blue-700 h-10"></td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td colspan="7" class="bg-red-700 h-10"></td>
                    </tr>
                    <tr>
                        <td colspan="7" class="bg-blue-700 h-10"></td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td colspan="7" class="py-10">
                            <div class="bg-red-700 w-full h-10"/>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7" class="bg-blue-700 h-10"></td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                    <tr>
                        <td class="bg-green-700">A</td>
                        <td class="bg-green-700">B</td>
                        <td class="bg-green-700">C</td>
                        <td class="bg-green-700">D</td>
                        <td class="bg-green-700">E</td>
                        <td class="bg-green-700">F</td>
                        <td class="bg-green-700">G</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </InventoryHeader>
</template>

<script setup>
import InventoryHeader from "@/Pages/Inventory/InventoryHeader.vue";
import {IconCopy, IconDotsVertical, IconTrash} from "@tabler/icons-vue";
import {
    Listbox,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import {PlusIcon, CheckIcon} from "@heroicons/vue/solid";
import {ref} from "vue";

const props = defineProps({
        columns: Array,
        crafts: Array,
        colors: Array
    }),
    dynamicColumnNameInputRefs = ref({}),
    showMenu = ref(null),
    dummyFn = () => true,
    createDynamicColumnNameInputRef = (element, columnId) => {
        dynamicColumnNameInputRefs[columnId] = ref(element);
    },
    selectInput = (columnId) => {
        setTimeout(() => {
            dynamicColumnNameInputRefs[columnId].value.select();
        }, 5);

    };
</script>
