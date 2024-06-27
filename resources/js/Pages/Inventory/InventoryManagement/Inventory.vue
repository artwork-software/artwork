<template>
    <InventoryHeader :title="$t('Inventory')">
        <div class="flex flex-col relative">
            <InventoryTopBar :craft-filters="getCraftFilters()"
                             @updates-search-value="updateSearchValue"
                             @updates-craft-filters="updateCraftFilters"/>
            <table>
                <thead class="sticky z-20 top-0 transition-all duration-300 shadow-sm text-white">
                <tr class="text-xs">
                    <th v-for="(column,index) in columns"
                        :key="column.id"
                        @mouseover="showMenu = column.id"
                        @mouseout="showMenu = null"
                        :class="getColumnCls(index, column)">
                        <div class="w-full h-full flex flex-row items-center relative">
                            <div class="flex flex-row w-full h-full py-2 text-left items-center cursor-pointer">
                                <div
                                    class="w-full indent-3 overflow-hidden overflow-ellipsis whitespace-nowrap"
                                    @click="toggleColumnEdit(column)">
                                    {{ column.name }}
                                </div>
                                <div
                                    :class="[column.clicked ? '' : 'hidden', getColumnBackgroundCls(column) + ' flex flex-row items-center px-1 top-[4px] text-white gap-x-2 w-full z-50 absolute']">
                                    <input
                                        type="text"
                                        :ref="(element) => createDynamicColumnNameInputRef(element, column.id)"
                                        class="w-full p-1 pl-2 border-0 text-xs text-black"
                                        v-model="column.newValue"
                                        @focusout="applyColumnValueChange(column)"
                                        @keyup.enter="applyColumnValueChange(column)">
                                </div>
                            </div>
                            <Menu v-show="showMenu === column.id && !column.showColorMenu" as="div"
                                  class="absolute right-0 cursor-pointer flex flex-row items-center">
                                <MenuButton as="div">
                                    <IconDotsVertical class="w-5 h-5 flex-shrink-0 z-50" stroke-width="1.5"
                                                      aria-hidden="true"/>
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
                                                <a @click="duplicateColumn(column.id)"
                                                   :class="[active ? 'rounded-xl bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 subpixel-antialiased']">
                                                    <IconCopy
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                    {{ $t('Duplicate') }}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-if="index > 2"
                                                      v-slot="{ active }"
                                                      as="div">
                                                <a @click="openConfirmDeleteColumnModal(column.id)"
                                                   :class="[active ? 'rounded-xl bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 subpixel-antialiased']">
                                                    <IconTrash
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                    {{ $t('Delete') }}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-if="column.type === 3"
                                                      v-slot="{ active }"
                                                      as="div">
                                                <a @click="openEditColumnSelectOptionsModal(column)"
                                                   :class="[active ? 'rounded-xl bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 subpixel-antialiased']">
                                                    <IconTrash
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                    {{ $t('Optionen bearbeiten') }}
                                                </a>
                                            </MenuItem>
                                        </MenuItems>
                                    </transition>
                                </div>
                            </Menu>
                            <Listbox v-if="column.showColorMenu === true"
                                     as="div"
                                     v-model="column.background_color">
                                <ListboxOptions :static="column.showColorMenu"
                                                class="absolute -translate-x-[107%] translate-y-[8px] z-40 flex flex-col gap-2 p-2  shadow-lg rounded-xl bg-artwork-navigation-background focus:outline-none">
                                    <ListboxOption as="template"
                                                   v-for="(color) in ['bg-secondary', 'bg-blue-500', 'bg-red-500', 'bg-green-500', 'bg-pink-500', 'bg-yellow-500', 'bg-cyan-500', 'bg-orange-500']"
                                                   :key="color"
                                                   :value="color"
                                                   v-slot="{ active, selected }">
                                        <li class="cursor-pointer flex flex-row p-2 items-center hover:bg-artwork-navigation-color/10 transition-all duration-300 rounded-md"
                                            @click="applyColumnBackgroundColorChange(color, column)">
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
                <template v-for="(craft, index) in filteredCrafts">
                    <tr v-if="index === 0">
                        <td :colspan="getColSpan()" class="h-5"/>
                    </tr>
                    <InventoryCraft :craft="craft"
                                    :colspan="getColSpan()"/>
                </template>
                </tbody>
            </table>
        </div>
    </InventoryHeader>
    <EditColumnSelectOptionsModal v-if="showEditColumnSelectOptionsModal"
                                  :show="showEditColumnSelectOptionsModal"
                                  :column="selectOptionsColumnToEdit"
                                  @closed="closeEditColumnSelectOptionsModal()"/>
    <ErrorComponent v-if="hasFlashError()"
                    :titel="$t('Unfortunately an error has occurred')"
                    :description="getFlashError()"
                    @closed="resetFlashError"/>
    <ConfirmationComponent v-if="showConfirmDeleteColumnModal"
                           :titel="$t('Wirklich löschen')"
                           :description="$t('Spalte wirklich löschen? Das kann nicht rückgängig gemacht werden. Alle Werte gehen verloren.')"
                           @closed="handleColumnDeletion"/>
    <div id="remove-icon-container"></div>
</template>

<script setup>
import InventoryHeader from "@/Pages/Inventory/InventoryHeader.vue";
import {
    IconCopy,
    IconDotsVertical,
    IconTrash,
    IconX
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
import {router, usePage} from "@inertiajs/vue3";
import {CheckIcon} from "@heroicons/vue/solid";
import {computed, ref} from "vue";
import InventoryCraft from "@/Pages/Inventory/InventoryManagement/InventoryCraft.vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import EditColumnSelectOptionsModal from "@/Pages/Inventory/InventoryManagement/EditColumnSelectOptionsModal.vue";
import InventoryTopBar from "@/Pages/Inventory/InventoryManagement/InventoryTopBar.vue";

const props = defineProps({
        columns: Array,
        crafts: Array,
        craftFilters: Array,
        colors: Array
    }),
    dynamicColumnNameInputRefs = ref({}),
    showMenu = ref(null),
    searchValue = ref(''),
    showEditColumnSelectOptionsModal  = ref(false),
    selectOptionsColumnToEdit = ref(null),
    showConfirmDeleteColumnModal = ref(false),
    columnIdToDelete = ref(null),
    getProps = () => usePage().props,
    hasFlashError = () => getProps().flash.error?.length > 0,
    getFlashError = () => getProps().flash.error,
    resetFlashError = () => getProps().flash.error = null,
    getColSpan = () => {
        return props.columns.length;
    },
    isTextColumn = (column) => {
        return column.type === 0;
    },
    isDateColumn = (column) => {
        return column.type === 1;
    },
    isCheckboxColumn = (column) => {
        return column.type === 2;
    },
    isSelectColumn = (column) => {
        return column.type === 3;
    },
    getCraftFilters = () => {
        return props.crafts.map((craft) => {
            return {
                id: craft.id,
                name: craft.name,
                checked: props.craftFilters.includes(craft.id)
            };
        });
    },
    getColumnCls = (index, column) => {
        return [
            getColumnWidthCls(index, column),
            getColumnBackgroundCls(column)
        ].join(' ');
    },
    getColumnWidthCls = (index, column) => {
        return index === 0 ? 'w-[0.75%] min-w-[0.75%]' :
            index === 1 ? 'w-[0.25%] min-w-[0.25%]' :
            index === 2 ? 'w-auto' :
            isTextColumn(column) ? 'w-[15%] min-w-[15%]' :
            isDateColumn(column) ? 'w-[7.5%] min-w-[7.5%]' :
            isSelectColumn(column) ? 'w-[10%] min-w-[10%]' :
            isCheckboxColumn(column) ? 'w-[1%] min-w-[1%]' : '';
    },
    getColumnBackgroundCls = (column) => {
        return column.background_color !== '' ? column.background_color : 'bg-secondary';
    },
    openEditColumnSelectOptionsModal = (column) => {
        selectOptionsColumnToEdit.value = column;
        showEditColumnSelectOptionsModal.value = true;
    },
    closeEditColumnSelectOptionsModal = () => {
        selectOptionsColumnToEdit.value = null;
        showEditColumnSelectOptionsModal.value = false;
    },
    createDynamicColumnNameInputRef = (element, columnId) => {
        dynamicColumnNameInputRefs[columnId] = ref(element);
    },
    toggleColumnEdit = (column) => {
        column.clicked = !column.clicked;

        if (column.clicked) {
            column.newValue = column.name;
            setTimeout(() => {
                dynamicColumnNameInputRefs[column.id].value.select();
            }, 5);
        }
    },
    applyColumnValueChange = (column) => {
        if (column.name === column.newValue) {
            toggleColumnEdit(column);
            return;
        }

        router.patch(
            route('inventory-management.inventory.column.update.name', {craftsInventoryColumn: column.id}),
            {
                name: column.newValue
            },
            {
                preserveScroll: true
            }
        );
    },
    applyColumnBackgroundColorChange = (backgroundColor, column) => {
        router.patch(
            route('inventory-management.inventory.column.update.background_color', {craftsInventoryColumn: column.id}),
            {
                background_color: backgroundColor
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    showMenu.value = false;
                    column.showColorMenu = false;
                }
            }
        );
    },
    handleColumnDeletion = (closedOnPurpose) => {
        if (closedOnPurpose) {
            router.delete(
                route(
                    'inventory-management.inventory.column.delete',
                    {craftsInventoryColumn: columnIdToDelete.value}
                )
            );
        }

        showConfirmDeleteColumnModal.value = false;
        columnIdToDelete.value = null;
    },
    openConfirmDeleteColumnModal = (id) => {
        showConfirmDeleteColumnModal.value = true;
        columnIdToDelete.value = id;
    },
    duplicateColumn = (columnId) => {
        router.post(
            route('inventory-management.inventory.column.duplicate'),
            {
                columnId: columnId
            },
            {
                preserveScroll: true
            }
        );
    },
    updateSearchValue = (value) => {
        searchValue.value = value;
    },
    updateCraftFilters = (args = {list: []}) => {
        router.patch(
            route('inventory-management.inventory.filter.update'),
            {
                filter: args.list
                    .filter((arg) => arg.checked === true)
                    .map((arg) => {
                        return {craftId: arg.id }
                    })
            },
            {
                preserveScroll: true
            }
        );
    },
    filteredCrafts = computed(() => {
        if (searchValue.value.length === 0) {
            props.crafts.forEach((craft) => craft.filtered_inventory_categories = craft.inventory_categories);
            return props.crafts.filter(
                (craft) => {
                    return props.craftFilters.length === 0 || props.craftFilters.includes(craft.id);
                }
            );
        }

        props.crafts.forEach((craft) => {
            if (props.craftFilters.length > 0 && !props.craftFilters.includes(craft.id) ) {
                return;
            }

            let filteredCategories = [];

            craft.inventory_categories.forEach((category) => {
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
                                (cell) => cell.cell_value.indexOf(searchValue.value) > -1
                            ))
                    )
                ) {
                    filteredCategories.push(category)
                }
            });

            craft.filtered_inventory_categories = filteredCategories;
        });

        return props.crafts;
    });
</script>
