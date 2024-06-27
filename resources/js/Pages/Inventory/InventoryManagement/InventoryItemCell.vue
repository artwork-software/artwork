<template>
    <td :class="getCellCls()">
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
             :class="getInputCls()">
            <input ref="cellValueInputRef"
                   type="text"
                   class="w-full text-xs px-1 flex "
                   v-model="cellValue"
                   @focusout="applyCellValueChange()"/>
        </div>
        <!-- Datum -->
        <div v-else-if="isDateColumn() && cellClicked"
             :class="getInputCls()">
            <input ref="cellValueInputRef"
                   type="date"
                   class="w-full text-xs px-1 flex"
                   v-model="cellValue"
                   @focusout="applyCellValueChange()"/>
        </div>
        <!-- Checkbox -->
        <div v-else-if="isCheckboxColumn() && cellClicked"
             :class="getInputCls()">
            <input ref="cellValueInputRef"
                   type="checkbox"
                   class="w-5 h-5 text-xs px-1 flex"
                   v-model="cellValue"
                   @focusout="applyCellValueChange()"/>
        </div>
        <!-- Auswahlbox -->
        <div v-else-if="isSelectColumn() && cellClicked"
             :class="getInputCls()">
            <select ref="cellValueInputRef"
                    class="w-full text-xs px-1"
                    v-model="cellValue"
                    @change="applyCellValueChange">
                <option v-for="(option) in cell.column.type_options">
                    {{ option }}
                </option>
            </select>
        </div>
    </td>
</template>

<script setup>
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
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
    getCellCls = () => {
        return [
            getBackgroundCls(),
            'max-w-40 h-full px-3 border subpixel-antialiased relative text-xs overflow-ellipsis overflow-hidden whitespace-nowrap'
        ].join(' ');
    },
    getBackgroundCls = () => {
        return props.cell.column.background_color !== 'bg-secondary' ?
            props.cell.column.background_color :
            '';
    },
    getInputCls = () => {
        return [
            getBackgroundClsForInput(),
            'flex flex-row items-center gap-x-2 w-[calc(100%-0.8rem)] -translate-x-1 h-full top-0 z-50 absolute'
        ].join(' ');
    },
    getBackgroundClsForInput = () => {
        return props.cell.column.background_color !== 'bg-secondary' ?
            props.cell.column.background_color :
            'bg-white';
    },
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

        if (cellClicked.value) {
            setTimeout(() => {
                if (isTextColumn()) {
                    cellValueInputRef.value.select();
                    return;
                }

                cellValueInputRef.value.focus();
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
    };
</script>