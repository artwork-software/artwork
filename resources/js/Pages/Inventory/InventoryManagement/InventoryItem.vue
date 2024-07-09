<template>
    <tr>
        <td class="empty-row-xxs-td"></td>
    </tr>
    <tr :draggable="isDraggable"
        @dragstart="itemDragStart"
        @dragend="itemDragEnd"
        @mouseover="handleItemMouseover()"
        @mouseout="handleItemMouseout()"
        :class="'cursor-grab h-10 ' + trCls">
        <template v-for="(cell) in item.cells"
                  :key="cell.id">
            <InventoryCell :cell="cell"
                           @is-editing-cell-value="handleCellIsEditing"/>
        </template>
    </tr>
    <tr>
        <td>
            <IconTrashXFilled v-if="!isAnyCellEditing && itemMouseover && !itemDragged"
                              @mouseover="handleItemDeleteMouseover"
                              @mouseout="handleItemDeleteMouseout"
                              :class="[itemDeleteCls + ' remove-item-icon']"
                              @click="showItemDeleteConfirmModal()"/>
        </td>
    </tr>
    <ConfirmDeleteModal v-if="itemConfirmDeleteModalShown"
                        :title="$t('Delete item?')"
                        :button="$t('Yes')"
                        :description="$t('Really delete this item? This cannot be undone.')"
                        @delete="deleteItem()"
                        @closed="closeItemDeleteConfirmModal()"/>
</template>

<script setup>
import InventoryCell from "@/Pages/Inventory/InventoryManagement/InventoryItemCell.vue";
import {computed, ref} from "vue";
import {IconTrashXFilled} from "@tabler/icons-vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";

const emits = defineEmits(['itemDragging', 'itemDragEnd']),
    props = defineProps({
        index: Number,
        colspan: Number,
        item: Object,
        trCls: String
    }),
    inventoryCellsEditing = ref([]),
    itemDragged = ref(false),
    itemMouseover = ref(false),
    itemDeleteCls = ref(''),
    itemConfirmDeleteModalShown = ref(false),
    isAnyCellEditing = computed(() => {
        return inventoryCellsEditing.value.length > 0;
    }),
    isDraggable = computed(() => inventoryCellsEditing.value.length === 0),
    handleCellIsEditing = (isEditing, cellId) => {
        if (isEditing) {
            //append
            inventoryCellsEditing.value.push({cellId: cellId, isEditing: true});
            return
        }

        inventoryCellsEditing.value = inventoryCellsEditing.value.filter(
            (editingCell) => editingCell.cellId !== cellId
        );
    },
    handleItemMouseover = () => {
        itemMouseover.value = true;
    },
    handleItemMouseout = () => {
        itemMouseover.value = false;
    },
    handleItemDeleteMouseover = () => {
        itemMouseover.value = true;
        itemDeleteCls.value = 'bg-red-600';
    },
    handleItemDeleteMouseout = () => {
        itemMouseover.value = false;
        itemDeleteCls.value = 'bg-black';
    },
    showItemDeleteConfirmModal = () => {
        itemConfirmDeleteModalShown.value = true;
    },
    deleteItem = () => {
        router.delete(
            route(
                'inventory-management.inventory.item.delete',
                {
                    craftInventoryItem: props.item.id
                }
            ),
            {
                preserveScroll: true
            }
        );
        closeItemDeleteConfirmModal();
    },
    closeItemDeleteConfirmModal = () => {
        itemConfirmDeleteModalShown.value = false;
    },
    itemDragStart = (e) => {
        itemDragged.value = true;

        emits.call(this,'itemDragging', props.index);

        e.dataTransfer.setData('itemId', props.item.id);
        e.dataTransfer.setData('currentItemIndex', props.index.toString());
    },
    itemDragEnd = () => {
        itemDragged.value = false;
        emits.call(this, 'itemDragEnd');
    }
</script>

