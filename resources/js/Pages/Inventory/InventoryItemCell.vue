<template>
    <td class="max-w-16 overflow-hidden overflow-ellipsis px-3 border subpixel-antialiased relative text-xs">
        <span class="cursor-text" v-if="cell.cell_value.length > 0"  @click="toggleCellEdit()">
            {{ cell.cell_value }}
        </span>
        <template v-if="cell.column.type === 0 && cellClicked">
            <input ref="cellValueInputRef"
                   type="text"
                   class="z-50 text-xs px-1 flex w-[calc(100%-1rem)] -translate-x-1 h-7 top-1.5 absolute"
                   v-model="cellValue"
                   @focusout="handleCellValueChange()"
            />
        </template>
    </td>
</template>

<script setup>
import {ref} from "vue";
import {router} from "@inertiajs/vue3";

const emits = defineEmits(['isEditingCellValue']),
    props = defineProps({
        cell: Object,
    }),
    cellValueInputRef = ref(null),
    cellValue = ref(props.cell.cell_value),
    cellClicked = ref(false),
    toggleCellEdit = () => {
        //emit to prevent item from being dragged, causing input
        //events to not work properly if draggable while editing value
        emits.call(this, 'isEditingCellValue', cellClicked.value);

        cellClicked.value = !cellClicked.value;

        if (cellClicked.value) {
            setTimeout(() => {
                cellValueInputRef.value.select();
            }, 5);
        }
    },
    handleCellValueChange = () => {
        if (props.cell.cell_value === cellValue.value) {
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
                cell_value: cellValue.value
            },
            {
                preserveScroll: true,
                onSuccess: toggleCellEdit
            }
        );
    };
</script>
