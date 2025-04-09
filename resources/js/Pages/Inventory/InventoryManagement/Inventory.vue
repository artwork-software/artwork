<template>
    <InventoryHeader :title="$t('Inventory')">
        <div class="inventory-container">
            <div class="flex items-center justify-between mr-5 mb-3">
                <div>
                    <div v-if="usePage().props.auth.user.inventory_sort_column_id">
                        <span class="text-xs">
                            {{ $t('Sorting') }}:
                        </span>
                        <span class="inline-flex items-center rounded-md bg-artwork-buttons-create/20 px-2 py-1 text-xs font-medium text-artwork-buttons-create ring-1 ring-inset ring-artwork-buttons-create/40 space-x-1">
                            <span>
                                {{ columns.find(column => column.id === usePage().props.auth.user.inventory_sort_column_id).name }}
                            </span>
                            <span v-if="usePage().props.auth.user.inventory_sort_direction === 'asc'">
                                {{ $t('ascending') }}
                            </span>
                            <span v-else>
                                absteigend
                            </span>
                            <component is="IconX" class="h-4 w-4 cursor-pointer hover:text-red-600 transition-colors duration-300 ease-in-out" @click="updateSort({ id: null })"/>
                        </span>

                    </div>
                </div>
                <InventoryTopBar :craft-filters="getCraftFilters()"
                                 @updates-search-value="updateSearchValue"
                                 @updates-craft-filters="updateCraftFilters"
                                 :crafts="filteredCrafts"/>
            </div>
            <table>
                <thead class="inventory-table-head">
                <tr>
                    <th v-for="(column,index) in columns"
                        :key="column.id"
                        @mouseover="showMenu = column.id"
                        @mouseout="showMenu = null"
                        :class="getColumnCls(index, column) + ' bg-secondary'" class="group">
                        <div class="inventory-th-container">
                            <div class="inventory-th-edit-container" :class="!column.deletable ? '!cursor-default' : ''">
                                <div class="column-name" @click="toggleColumnEdit(column)">
                                    {{ column.name }}
                                </div>
                                <div :class="[column.clicked ? '' : '!hidden', ' column-input-container']">
                                    <input
                                        type="text"
                                        :ref="(element) => createDynamicColumnNameInputRef(element, column.id)"
                                        class="column-input"
                                        v-model="column.newValue"
                                        @focusout="applyColumnValueChange(column)">
                                </div>
                            </div>
                            <div class="flex items-center space-x-1">
                                <div @click="updateSort(column)" class="w-fit">
                                    <component is="IconSortDescending" class="h-4 w-4 cursor-pointer" v-if="usePage().props.auth.user.inventory_sort_column_id === column.id && usePage().props.auth.user.inventory_sort_direction === 'desc'"/>
                                    <component is="IconSortAscending" class="h-4 w-4 cursor-pointer" v-if="usePage().props.auth.user.inventory_sort_column_id === column.id && usePage().props.auth.user.inventory_sort_direction === 'asc'"/>
                                    <component is="IconArrowsSort" class="h-4 w-4 invisible group-hover:visible cursor-pointer" v-if="usePage().props.auth.user.inventory_sort_column_id !== column.id"/>
                                </div>
                                <div class="inventory-th-menu-container invisible group-hover:visible">
                                    <BaseMenu white-icon v-if="can('can manage inventory stock') || hasAdminRole()">
                                        <MenuItem v-slot="{ active }" as="div">
                                            <a @click="column.showColorMenu = true"
                                               :class="[active ? 'active' : 'not-active', 'default group cursor-pointer text-white flex items-center px-4 py-2 subpixel-antialiased text-sm']">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                     viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor"
                                                     class="mr-3 h-5 w-5 group-hover:text-white">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/>
                                                </svg>
                                                {{ $t('Coloring') }}
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }" as="div">
                                            <a @click="duplicateColumn(column.id)"
                                               :class="[active ? 'active' : 'not-active', 'default group cursor-pointer text-white flex items-center px-4 py-2 subpixel-antialiased text-sm']">
                                                <IconCopy class="mr-3 h-5 w-5 group-hover:text-white"/>
                                                {{ $t('Duplicate') }}
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-if="column.deletable"
                                                  v-slot="{ active }"
                                                  as="div">
                                            <a @click="openConfirmDeleteColumnModal(column.id)"
                                               :class="[active ? 'active' : 'not-active', 'default group cursor-pointer text-white flex items-center px-4 py-2 subpixel-antialiased text-sm']">
                                                <IconTrash class="mr-3 h-5 w-5 group-hover:text-white"/>
                                                {{ $t('Delete') }}
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-if="column.type === 3"
                                                  v-slot="{ active }"
                                                  as="div">
                                            <a @click="openEditColumnSelectOptionsModal(column)"
                                               :class="[active ? 'active' : 'not-active', 'default group cursor-pointer text-white flex items-center px-4 py-2 subpixel-antialiased text-sm']">
                                                <IconEdit class="mr-3 h-5 w-5 group-hover:text-white"/>
                                                {{ $t('Edit') }}
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }"
                                                  as="div">
                                            <a @click="openEditColumnOrderModal()"
                                               :class="[active ? 'active' : 'not-active', 'default group cursor-pointer text-white flex items-center px-4 py-2 subpixel-antialiased text-sm']">
                                                <IconEdit class="mr-3 h-5 w-5 group-hover:text-white"/>
                                                {{ $t('Column Order') }}
                                            </a>
                                        </MenuItem>
                                    </BaseMenu>
                                </div>
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
                        </div>
                    </th>
                    <th class="w-5"></th>
                </tr>
                </thead>
                <tbody>
                <template v-for="(craft, index) in filteredCrafts"
                          :key="craft.id">
                    <tr v-if="index === 0">
                        <td :colspan="getColSpan()"
                            class="empty-row-td"/>
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
    <EditColumnOrderModal v-if="showEditColumnOrderModal"
                          :show="showEditColumnOrderModal"
                          :columns="JSON.parse(JSON.stringify(columns))"
                          @closed="closeEditColumnOrderModal"/>
    <ErrorComponent v-if="hasFlashError()"
                    :titel="$t('Unfortunately an error has occurred')"
                    :description="getFlashError()"
                    @closed="resetFlashError"/>
    <ConfirmationComponent v-if="showConfirmDeleteColumnModal"
                           :titel="$t('Really delete?')"
                           :description="$t('Really delete a column? This cannot be undone. All values will be lost.')"
                           @closed="handleColumnDeletion"/>
</template>

<script setup>
import InventoryHeader from "@/Pages/Inventory/InventoryHeader.vue";
import {IconCopy, IconEdit, IconTrash, IconX} from "@tabler/icons-vue";
import {Listbox, ListboxOption, ListboxOptions, MenuItem} from "@headlessui/vue";
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
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {usePermission} from "@/Composeables/Permission.js";
import EditColumnOrderModal from "@/Pages/Inventory/InventoryManagement/EditColumnOrderModal.vue";
const { can, canAny, hasAdminRole } = usePermission(usePage().props);

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
    showEditColumnOrderModal = ref(false),
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
    isNumberColumn = (column) => {
        return column.type === 4;
    },
    isLastEditColumn = (column) => {
        return column.type === 99;
    },
    isUploadColumn = (column) => {
        return column.type === 5;
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
        return isLastEditColumn(column) ? 'w-80' :
            isTextColumn(column) ? 'w-[10%] min-w-[10%]' :
            isNumberColumn(column) ? 'w-[8%] min-w-[8%]' :
            isTextColumn(column) ? 'w-[10%] min-w-[10%]' :
            isDateColumn(column) ? 'w-[9%] min-w-[9%]' :
            isUploadColumn(column) ? 'w-auto min-w-44' :
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
    openEditColumnOrderModal = () => {
        showEditColumnOrderModal.value = true;
    },
    closeEditColumnOrderModal = (closedOnPurpose, columns) => {
        if (closedOnPurpose) {
            router.patch(
                route('inventory-management.inventory.columns.reorder'),
                {
                    columns: columns.map((column) => column.id)
                },
                {
                    preserveScroll: true,
                    onFinish: showEditColumnOrderModal.value = false
                }
            );

            return;
        }
        showEditColumnOrderModal.value = false;
    },
    createDynamicColumnNameInputRef = (element, columnId) => {
        dynamicColumnNameInputRefs[columnId] = ref(element);
    },
    toggleColumnEdit = (column) => {
        if(!can('can manage inventory stock') || !hasAdminRole()){
            return;
        }

        if (!column.deletable){
            return;
        }

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
    },
    updateSort = (column) => {
        // If the column is already sorted by this column, reverse the sort direction
        if (usePage().props.auth.user.inventory_sort_direction === 'asc' && usePage().props.auth.user.inventory_sort_column_id === column.id) {
            usePage().props.auth.user.inventory_sort_direction = 'desc';
        } else {
            usePage().props.auth.user.inventory_sort_direction = 'asc';
        }

        usePage().props.auth.user.inventory_sort_column_id = column.id;

        if(column.id === null) {
            usePage().props.auth.user.inventory_sort_column_id = null;
            usePage().props.auth.user.inventory_sort_direction = null;
        }

        // update user data
        router.patch(
            route('user.update.inventory.sort', usePage().props.auth.user.id),
            {
                inventory_sort_column_id: column.id,
                inventory_sort_direction: usePage().props.auth.user.inventory_sort_direction
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    setTimeout(() => {
                        router.reload()
                    }, 5000);
                }
            }
        );
    };

onMounted(() => {
    setFilterAndSearchData();
});

onUpdated(() => {
    setFilterAndSearchData();
});
</script>
