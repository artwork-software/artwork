<template>
    <td :class="[cell.column.background_color + ' max-w-40 h-full px-3 border subpixel-antialiased relative text-xs overflow-ellipsis overflow-hidden whitespace-nowrap']">
        <span v-if="hasCellValue()"
              :class="getCellValueCls()"
              @click="toggleCellEdit()">
            <template v-if="isTextColumn() || isSelectColumn()">
                {{ cell.cell_value }}
            </template>
            <template v-else-if="isDateColumn()">
                {{ formatDate(cell.cell_value) }}
            </template>
            <template v-else-if="isCheckboxColumn()">
                {{ cell.cell_value === 'true' ? $t('Yes') : $t('No') }}
            </template>
        </span>
        <div v-else-if="!hasCellValue() && isCheckboxColumn()"
             class="w-full text-center cursor-text"
             @click="toggleCellEdit()">
            {{ $t('No') }}
        </div>
        <div v-else class="w-full h-full cursor-text" @click="toggleCellEdit()"/>
        <!-- Freitextfeld -->
        <div v-if="isTextColumn() && cellClicked"
            class="flex flex-row bg-white items-center gap-x-2 w-[calc(100%-0.8rem)] -translate-x-1 h-7 top-1.5 z-50 absolute">
            <input ref="cellValueInputRef"
                   type="text"
                   class="w-full text-xs px-1 flex "
                   v-model="cellValue"
                   @keyup.enter="applyCellValueChange()"
                   @keyup.esc="denyCellValueChange()"
            />
            <IconCheck class="w-5 h-5 hover:text-green-500 flex-shrink-0 cursor-pointer"
                       @click="applyCellValueChange()"/>
            <IconX class="w-5 h-5 hover:text-red-500 flex-shrink-0 cursor-pointer"
                   @click="denyCellValueChange()"/>
        </div>
        <!-- Datum -->
        <div v-else-if="isDateColumn() && cellClicked"
             class="flex flex-row bg-white items-center gap-x-2 w-[calc(100%-0.8rem)] -translate-x-1 h-7 top-1.5 z-50 absolute">
            <input ref="cellValueInputRef"
                   type="date"
                   class="w-full text-xs px-1 flex"
                   v-model="cellValue"
                   @keyup.enter="applyCellValueChange()"
                   @keyup.esc="denyCellValueChange()"
            />
            <IconCheck class="w-5 h-5 hover:text-green-500 flex-shrink-0 cursor-pointer"
                       @click="applyCellValueChange()"/>
            <IconX class="w-5 h-5 hover:text-red-500 flex-shrink-0 cursor-pointer"
                   @click="denyCellValueChange()"/>
        </div>
        <!-- Checkbox -->
        <div v-else-if="isCheckboxColumn() && cellClicked"
             class="flex flex-row bg-white items-center justify-center gap-x-2 w-[calc(100%-0.8rem)] -translate-x-0.5 h-7 top-1.5 z-50 absolute">
            <input ref="cellValueInputRef"
                   type="checkbox"
                   class="w-5 h-5 text-xs px-1 flex "
                   v-model="cellValue"
                   @keyup.enter="applyCellValueChange()"
                   @keyup.esc="denyCellValueChange()"
            />
            <IconCheck class="w-5 h-5 hover:text-green-500 flex-shrink-0 cursor-pointer"
                       @click="applyCellValueChange()"/>
            <IconX class="w-5 h-5 hover:text-red-500 flex-shrink-0 cursor-pointer"
                   @click="denyCellValueChange()"/>
        </div>
        <!-- Auswahlbox -->
        <div v-else-if="isSelectColumn() && cellClicked"
             class="flex flex-row bg-white items-center gap-x-2 w-[calc(100%-0.8rem)] -translate-x-1 h-7 top-1.5 z-50 absolute">
            <select ref="cellValueInputRef"
                   class="w-full text-xs px-1 flex "
                   v-model="cellValue"
                   @keyup.enter="applyCellValueChange()"
                   @keyup.esc="denyCellValueChange()">
                <option v-for="(option) in cell.column.type_options">
                    {{ option }}
                </option>
            </select>
            <IconCheck class="w-5 h-5 hover:text-green-500 flex-shrink-0 cursor-pointer"
                       @click="applyCellValueChange()"/>
            <IconX class="w-5 h-5 hover:text-red-500 flex-shrink-0 cursor-pointer"
                   @click="denyCellValueChange()"/>
        </div>
    </td>
</template>

<script setup>
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import {IconCheck, IconX} from "@tabler/icons-vue";
import Input from "@/Layouts/Components/InputComponent.vue";

const emits = defineEmits(['isEditingCellValue']),
    props = defineProps({
        cell: {
            type: Object,
            required: true
        },
    }),
    cellValueInputRef = ref(null),
    cellValue = ref(props.cell.cell_value),
    cellClicked = ref(false),
    formatDate = (date) => {
        let parts = date.split('-');

        return parts[2] + '.' + parts[1] + '.' + parts[0];
    },
    hasCellValue = () => {
        return props.cell.cell_value.length > 0;
    },
    getCellValueCls = () => {
        return isDateColumn() || isCheckboxColumn() ? 'text-center block cursor-text' : 'cursor-text';
    },
    isTextColumn = () => {
        return props.cell.column.type === 0;
    },
    isDateColumn = () => {
        return props.cell.column.type === 1;
    },
    isCheckboxColumn = () => {
        return props.cell.column.type === 2;
    },
    isSelectColumn = () => {
        return props.cell.column.type === 3;
    },
    toggleCellEdit = () => {
        cellClicked.value = !cellClicked.value;

        //emit to prevent item from being dragged, causing input
        //events to not work properly if draggable while editing value
        emits.call(this, 'isEditingCellValue', cellClicked.value, props.cell.id);

        if (cellClicked.value && isTextColumn()) {
            setTimeout(() => {
                cellValueInputRef.value?.select();
            }, 5);
        }
    },
    applyCellValueChange = () => {
        //compare as strings in case of checkbox which are preserved as string in database
        if (props.cell.cell_value.toString() === cellValue.value.toString()) {
            toggleCellEdit();
            return;
        }

        router.patch(
            route(
                'inventory-management.inventory.item-cell.update.cell-value',
                {
                    craftInventoryItemCell: props.cell.id
                }
            ),
            {
                cell_value: String(cellValue.value)
            },
            {
                preserveScroll: true,
                onSuccess: toggleCellEdit
            }
        );
    },
    denyCellValueChange = () => {
        cellValue.value = props.cell.cell_value;
        toggleCellEdit();
    };
</script>
