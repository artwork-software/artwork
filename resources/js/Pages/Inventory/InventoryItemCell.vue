<template>
    <td class="max-w-16 h-full overflow-hidden overflow-ellipsis px-3 border subpixel-antialiased relative text-xs">
        <div class="flex flex-row w-full h-full items-center" @click="toggleCellEdit()">
            <span class="cursor-text">{{ cell.cell_value }}</span>
        </div>
        <div v-if="cell.column.type === 0 && cellClicked"
            :class="[cellClicked ? '' : 'hidden', 'flex flex-row items-center gap-x-2 w-[calc(100%-1rem)] -translate-x-1 h-7 top-1.5 z-50 absolute']">
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
    </td>
</template>

<script setup>
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import {IconCheck, IconX} from "@tabler/icons-vue";
import Input from "@/Layouts/Components/InputComponent.vue";

const emits = defineEmits(['isEditingCellValue']),
    props = defineProps({
        cell: Object,
    }),
    cellValueInputRef = ref(null),
    cellValue = ref(props.cell.cell_value),
    cellClicked = ref(false),
    toggleCellEdit = () => {
        cellClicked.value = !cellClicked.value;

        //emit to prevent item from being dragged, causing input
        //events to not work properly if draggable while editing value
        emits.call(this, 'isEditingCellValue', cellClicked.value);

        if (cellClicked.value) {
            setTimeout(() => {
                cellValueInputRef.value.select();
            }, 5);
        }
    },
    applyCellValueChange = () => {
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
    },
    denyCellValueChange = () => {
        cellValue.value = props.cell.cell_value;
        toggleCellEdit();
    };
</script>
