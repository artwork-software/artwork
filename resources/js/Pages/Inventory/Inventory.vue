<template>
    <InventoryHeader :title="this.$t('Inventory')">
        <div class="flex flex-col relative">
            <div class="absolute right-0 -translate-y-full text-xs z-30 font-bold rounded-t-md subpixel-antialiased text-white flex flex-row items-center h-20">
                <BaseFilter :only-icon="true" class="mr-3">
                    <div class="flex flex-col w-full gap-y-2">
                        <div class="flex justify-between">
                            <span> {{ $t('Filter') }}</span>
                            <span class="xxsLight cursor-pointer text-right w-full" @click="dummyFn()">
                                {{ $t('Reset') }}
                            </span>
                        </div>
                        <div class="text-sm border-b">{{ $t('Crafts') }}</div>
                        <div class="craft-checkbox-filter">
                            <BaseFilterCheckboxList :list="getCrafts()"
                                                    filter-name="inventory-management-crafts-filter"
                                                    @change-filter-items="changeCraftFilter"/>
                        </div>
                    </div>
                </BaseFilter>
                <div class="flex flex-row h-full">
                    <div class="inventory-search-container p-4 flex flex-row h-full items-center justify-center gap-x-2 bg-gradient-to-t from-gray-600 to-gray-500">
                        <TextInputComponent id="inventory-search-input"
                                            aria-label="ajax search text input"
                                            :label="$t('Search')"
                                            v-model="searchValue"
                                            class="!mt-0 !w-52 !h-9"/>
                        <IconSearch class="cursor-pointer w-6 h-6 hover:text-blue-500"/>
                    </div>
                    <div class="w-[1px] h-8 bg-white"/>
                    <div class="cursor-pointer p-4 flex flex-row h-full items-center gap-x-2 bg-gradient-to-t from-gray-600 to-gray-500 hover:from-blue-700 hover:to-blue-600">
                        <span class="drop-shadow-sm shadow-black">
                            {{ $t('New column') }}
                        </span>
                        <button
                            class="p-1 border-white border-2 rounded-full items-center shadow-sm"
                            @click="dummyFn()">
                            <PlusIcon stroke-width="1.5" class="h-4 w-4"/>
                        </button>
                    </div>
                </div>
            </div>
            <table class="table-auto border-4">
                <thead class="sticky top-0 z-20 bg-gray-500 transition-all duration-300 shadow-sm text-white">
                <tr class="table-row text-xs font-light w-full h-full">
                    <th v-for="(column,index) in columns"
                        @mouseover="showMenu = column.id" :key="column.id"
                        @mouseout="showMenu = null"
                        class="max-w-20 h-full">
                        <div class="w-full h-full flex flex-row items-center relative">
                            <div class="flex flex-row w-full h-full py-2 text-left items-center cursor-pointer">
                                <div
                                    class="w-[calc(100%-0.8rem)] indent-3 overflow-hidden overflow-ellipsis whitespace-nowrap"
                                    @dblclick="column.clicked = !column.clicked; selectInput(column.id)">
                                    {{ column.name }}
                                </div>
                                <input
                                    type="text"
                                    :ref="(element) => createDynamicColumnNameInputRef(element, column.id)"
                                    :class="[column.clicked ? '' : 'hidden', 'text-black w-[calc(100%-0.9rem)] p-1 top-[4px] left-[8px] z-50 absolute border-0 text-xs']"
                                    v-model="column.name"
                                    @focusout="column.clicked = !column.clicked">
                            </div>
                            <Menu v-show="showMenu === column.id"
                                  as="div"
                                  class="absolute z-30 right-0 cursor-pointer flex flex-row items-center">
                                <MenuButton class="z-50">
                                    <IconDotsVertical stroke-width="1.5" class=" flex-shrink-0" aria-hidden="true"/>
                                </MenuButton>
                                <div class="w-full h-full relative">
                                    <transition enter-active-class="transition ease-out duration-100"
                                                enter-from-class="transform opacity-0 scale-95"
                                                enter-to-class="transform opacity-100 scale-100"
                                                leave-active-class="transition ease-in duration-75"
                                                leave-from-class="transform opacity-100 scale-100"
                                                leave-to-class="transform opacity-0 scale-95">
                                        <MenuItems
                                            class="absolute -translate-x-[110%] z-40 top-2 shadow-lg rounded-xl bg-artwork-navigation-background focus:outline-none">
                                            <MenuItem v-slot="{ active }" as="div">
                                                <a @click="column.showColorMenu = true"
                                                   :class="[active ? 'rounded-xl bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 subpixel-antialiased']">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                         viewBox="0 0 24 24"
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
                                                    <IconCopy
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                    {{ $t('Duplicate') }}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-if="index > 2"
                                                      v-slot="{ active }"
                                                      as="div">
                                                <a @click="dummyFn()"
                                                   :class="[active ? 'rounded-xl bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 subpixel-antialiased']">
                                                    <IconTrash
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
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
                                    <li class="cursor-pointer flex flex-row p-2 items-center justify-center hover:bg-artwork-navigation-color/10 transition-all duration-300 rounded-md"
                                        @click="column.showColorMenu = false;">
                                        <IconX class="w-4 h-4 text-gray-400"/>
                                    </li>
                                </ListboxOptions>
                            </Listbox>
                        </div>
                    </th>
                </tr>
                </thead>
                <tbody>
                <template v-for="(craft) in filteredCrafts">
                    <InventoryCraft :craft="craft" :colspan="6"/>
                </template>
                </tbody>
            </table>
        </div>
    </InventoryHeader>
</template>

<script setup>
import InventoryHeader from "@/Pages/Inventory/InventoryHeader.vue";
import {
    IconSearch,
    IconCopy,
    IconDotsVertical,
    IconTrash,
    IconX,
    IconChevronUp,
    IconChevronDown
} from "@tabler/icons-vue";
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
import {computed, ref} from "vue";
import InventoryCraft from "@/Pages/Inventory/InventoryCraft.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import BaseFilterCheckboxList from "@/Layouts/Components/BaseFilterCheckboxList.vue";
import Input from "@/Layouts/Components/InputComponent.vue";

const props = defineProps({
        columns: Array,
        crafts: Array,
        colors: Array
    }),
    dynamicColumnNameInputRefs = ref({}),
    showMenu = ref(null),
    searchValue = ref(''),
    dummyFn = () => true,
    createDynamicColumnNameInputRef = (element, columnId) => {
        dynamicColumnNameInputRefs[columnId] = ref(element);
    },
    selectInput = (columnId) => {
        setTimeout(() => {
            dynamicColumnNameInputRefs[columnId].value.select();
        }, 5);

    },
    changeCraftFilter = (args) => {
        //args.list.forEach.checked -> sync in backend to user (InventoryManagementFilter)
        console.debug(args);
    },
    getCrafts = () => {
        let crafts = [];

        props.crafts.forEach((craft) => {
            craft.categories = getCategories();
            crafts.push(craft);
        })

        return crafts;
    },
    filteredCrafts = computed(() => {
        let crafts = getCrafts();
        if (searchValue.value.length === 0) {
            return crafts;
        }

        crafts.forEach((craft) => {
            let filteredCategories = [];

            craft.categories.forEach((category) => {
                if (category.name.indexOf(searchValue.value) > -1) {
                    filteredCategories.push(category);

                    //show category if search value included in name
                    return;
                }

                if (category.groups.some((group) => group.name.indexOf(searchValue.value) > -1)) {
                    filteredCategories.push(category);

                    //show category if some group includes search value
                    return;
                }

                if (
                    category.groups.some(
                        //category name matches
                        (group) => group.name.indexOf(searchValue.value) > -1 ||
                        //or some items have some matching cell values
                        group.items.some((item) => item.cells.some(
                            //@todo: implement all types of inputs
                            (cell) => cell.value.indexOf(searchValue.value) > -1
                        ))
                    )
                ) {
                    filteredCategories.push(category)
                }
            });

            craft.categories = filteredCategories;
        });

        return crafts;
    }),
    getCategories = () => {
        return [
            {
                id: Math.floor(Math.random() * 100000),
                name: 'Test Kategorie 1',
                groups: [
                    {
                        id: Math.floor(Math.random() * 100000),
                        name: 'Gruppe 1',
                        items: [
                            {
                                id: Math.floor(Math.random() * 100000),
                                cells: [
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 1',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 2',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 3',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 4',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 5',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 6',
                                        type: 'text'
                                    }
                                ]
                            },
                            {
                                id: Math.floor(Math.random() * 100000),
                                cells: [
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 7',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 8',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 9',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 10',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 11',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 12',
                                        type: 'text'
                                    }
                                ]
                            },
                        ]
                    },
                    {
                        id: Math.floor(Math.random() * 100000),
                        name: 'Gruppe 2',
                        items: [
                            {
                                id: Math.floor(Math.random() * 100000),
                                cells: [
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 13',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 14',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 15',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 16',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 17',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 18',
                                        type: 'text'
                                    }
                                ]
                            },
                            {
                                id: Math.floor(Math.random() * 100000),
                                cells: [
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 19',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 20',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 21',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 22',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 23',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 24',
                                        type: 'text'
                                    }
                                ]
                            },
                        ]
                    },
                ]
            },
            {
                id: Math.floor(Math.random() * 100000),
                name: 'Test Kategorie 2',
                groups: [
                    {
                        id: Math.floor(Math.random() * 100000),
                        name: 'Gruppe 5',
                        items: [
                            {
                                id: Math.floor(Math.random() * 100000),
                                cells: [
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 1',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 2',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 3',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 4',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 5',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 6',
                                        type: 'text'
                                    }
                                ]
                            },
                            {
                                id: Math.floor(Math.random() * 100000),
                                cells: [
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 7',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 8',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 9',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 10',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 11',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 12',
                                        type: 'text'
                                    }
                                ]
                            },
                        ]
                    },
                    {
                        id: Math.floor(Math.random() * 100000),
                        name: 'Gruppe 2',
                        items: [
                            {
                                id: Math.floor(Math.random() * 100000),
                                cells: [
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 13',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 14',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 15',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 16',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 17',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 18',
                                        type: 'text'
                                    }
                                ]
                            },
                            {
                                id: Math.floor(Math.random() * 100000),
                                cells: [
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 19',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 20',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 21',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 22',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 23',
                                        type: 'text'
                                    },
                                    {
                                        id: Math.floor(Math.random() * 100000),
                                        value: 'Test 24',
                                        type: 'text'
                                    }
                                ]
                            },
                        ]
                    },
                ]
            }
        ]
    }
</script>

<style scoped>
.inventory-search-container :deep(label) {
    color: white;
}

.inventory-search-container :deep(input) {
    color: rgb(37 99 235 / 1);
}

.inventory-search-container :deep(input):focus {
    border-color: rgb(37 99 235 / 1);
}

.craft-checkbox-filter {
    display:flex;
    flex-direction: column;
    row-gap: 0.5rem;
}

.craft-checkbox-filter :deep(div) {
    margin-bottom: 0;
}
</style>
