<template>
    <InventoryHeader :title="$t('Inventory')">
        <div class="inventory-container">
            <InventoryTopBar :craft-filters="getCraftFilters()"
                             @updates-search-value="updateSearchValue"
                             @updates-craft-filters="updateCraftFilters"
                             :crafts="filteredCrafts"/>
            <table>
                <thead class="inventory-table-head">
                <tr>
                    <th v-for="(column,index) in columns"
                        :key="column.id"
                        @mouseover="showMenu = column.id"
                        @mouseout="showMenu = null"
                        :class="getColumnCls(index, column) + ' bg-secondary'">
                        <div class="inventory-th-container">
                            <div class="inventory-th-edit-container">
                                <div
                                    class="column-name"
                                    @click="toggleColumnEdit(column)">
                                    {{ column.name }}
                                </div>
                                <div
                                    :class="[column.clicked ? '' : '!hidden', ' column-input-container']">
                                    <input
                                        type="text"
                                        :ref="(element) => createDynamicColumnNameInputRef(element, column.id)"
                                        class="column-input"
                                        v-model="column.newValue"
                                        @focusout="applyColumnValueChange(column)">
                                </div>
                            </div>
                            <Menu v-show="showMenu === column.id && !column.showColorMenu"
                                  as="div"
                                  class="inventory-th-menu-container">
                                <MenuButton as="div">
                                    <IconDotsVertical class="menu-button"
                                                      stroke-width="1.5"
                                                      aria-hidden="true"/>
                                </MenuButton>
                                <div class="inventory-th-menu">
                                    <transition enter-active-class="transition ease-out duration-100"
                                                enter-from-class="transform opacity-0 scale-95"
                                                enter-to-class="transform opacity-100 scale-100"
                                                leave-active-class="transition ease-in duration-75"
                                                leave-from-class="transform opacity-100 scale-100"
                                                leave-to-class="transform opacity-0 scale-95">
                                        <MenuItems class="menu-items">
                                            <MenuItem v-slot="{ active }" as="div">
                                                <a @click="column.showColorMenu = true"
                                                   :class="[active ? 'active' : 'not-active', 'default group']">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                         viewBox="0 0 24 24"
                                                         stroke-width="1.5" stroke="currentColor"
                                                         class="icon group-hover:text-white">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/>
                                                    </svg>
                                                    {{ $t('Coloring') }}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }" as="div">
                                                <a @click="duplicateColumn(column.id)"
                                                   :class="[active ? 'active' : 'not-active', 'default group']">
                                                    <IconCopy class="icon group-hover:text-white"/>
                                                    {{ $t('Duplicate') }}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-if="index > 2"
                                                      v-slot="{ active }"
                                                      as="div">
                                                <a @click="openConfirmDeleteColumnModal(column.id)"
                                                   :class="[active ? 'active' : 'not-active', 'default group']">
                                                    <IconTrash class="icon group-hover:text-white"/>
                                                    {{ $t('Delete') }}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-if="column.type === 3"
                                                      v-slot="{ active }"
                                                      as="div">
                                                <a @click="openEditColumnSelectOptionsModal(column)"
                                                   :class="[active ? 'active' : 'not-active', 'default group']">
                                                    <IconEdit class="icon group-hover:text-white"/>
                                                    {{ $t('Edit') }}
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
                                                class="inventory-th-color-menu-container">
                                    <ListboxOption as="template"
                                                   v-for="(color) in columnBgColors"
                                                   :key="color"
                                                   :value="color"
                                                   v-slot="{ active, selected }">
                                        <li class="menu-item"
                                            @click="applyColumnBackgroundColorChange(color, column)">
                                                <span :class="'icon-container ' + color">
                                                     <CheckIcon v-if="selected"
                                                                class="icon"
                                                                aria-hidden="true"/>
                                                </span>
                                        </li>
                                    </ListboxOption>
                                    <li class="menu-item-close"
                                        @click="column.showColorMenu = false;">
                                        <IconX class="icon"/>
                                    </li>
                                </ListboxOptions>
                            </Listbox>
                        </div>
                    </th>
                </tr>
                </thead>
                <tbody>
                <template v-for="(craft, index) in filteredCrafts"
                          :key="craft.id">
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
                           :titel="$t('Really delete?')"
                           :description="$t('Really delete a column? This cannot be undone. All values will be lost.')"
                           @closed="handleColumnDeletion"/>
    <div id="remove-icon-container"></div>
</template>

<script setup>
import InventoryHeader from "@/Pages/Inventory/InventoryHeader.vue";
import {
    IconCopy,
    IconDotsVertical,
    IconTrash,
    IconX,
    IconEdit
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
import {onMounted, onUpdated, ref} from "vue";
import InventoryCraft from "@/Pages/Inventory/InventoryManagement/InventoryCraft.vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import EditColumnSelectOptionsModal from "@/Pages/Inventory/InventoryManagement/EditColumnSelectOptionsModal.vue";
import InventoryTopBar from "@/Pages/Inventory/InventoryManagement/InventoryTopBar.vue";
import useCraftFilterAndSearch from "@/Pages/Inventory/Composeables/useCraftFilterAndSearch.js";

const props = defineProps({
        columns: Array,
        crafts: Array,
        craftFilters: Array
    }),
    columnBgColors = [
        'whiteColumn',
        'darkBlueColumn',
        'darkGreenColumn',
        'darkLightBlueColumn',
        'lightBlueNew',
        'greenColumn',
        'lightGreenColumn',
        'orangeColumn',
        'redColumn',
        'pinkColumn'
    ],
    dynamicColumnNameInputRefs = ref({}),
    showMenu = ref(null),
    showEditColumnSelectOptionsModal  = ref(false),
    selectOptionsColumnToEdit = ref(null),
    showConfirmDeleteColumnModal = ref(false),
    columnIdToDelete = ref(null),
    { searchValue, craftFilters, crafts, filteredCrafts } = useCraftFilterAndSearch(),
    getPageProps = () => usePage().props,
    hasFlashError = () => getPageProps().flash.error?.length > 0,
    getFlashError = () => getPageProps().flash.error,
    resetFlashError = () => getPageProps().flash.error = null,
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
        return getColumnWidthCls(index, column);
    },
    getColumnWidthCls = (index, column) => {
        return index === 0 ? 'w-[0.75%] min-w-[0.75%]' :
            index === 1 ? 'w-[5%] min-w-[5%]' :
            index === 2 ? 'w-auto' :
            isTextColumn(column) ? 'w-[15%] min-w-[15%]' :
            isDateColumn(column) ? 'w-[9%] min-w-[9%]' :
            isSelectColumn(column) ? 'w-[10%] min-w-[10%]' :
            isCheckboxColumn(column) ? 'w-[5%] min-w-[5%]' : '';
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
            route(
                'inventory-management.inventory.column.update.name',
                {
                    craftsInventoryColumn: column.id
                }
            ),
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
            route(
                'inventory-management.inventory.column.update.background_color',
                {
                    craftsInventoryColumn: column.id
                }
            ),
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
                    {
                        craftsInventoryColumn: columnIdToDelete.value
                    }
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
                        return {craftId: arg.id}
                    })
            },
            {
                preserveScroll: true
            }
        );
    },
    setFilterAndSearchData = () => {
        crafts.value = props.crafts;
        craftFilters.value = props.craftFilters;
    };

onMounted(() => {
    setFilterAndSearchData();
});

onUpdated(() => {
    setFilterAndSearchData();
});
</script>
