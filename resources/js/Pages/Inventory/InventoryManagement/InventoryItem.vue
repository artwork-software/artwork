<template>
    <tr @mouseover="showItemMenu()" @mouseout="closeItemMenu()" class="cursor-grab group" :id="'item_' + item.id"  :draggable="canDraggable" @dragstart="onDragStart">
        <template v-for="(cell) in item.cells"
                  :key="cell.id">
            <InventoryCell :cell="cell" @is-editing-cell-value="handleCellIsEditing"/>
        </template>
        <td class="relative">
            <div class="absolute right-14 group-hover:visible invisible top-2" v-if="can('can manage inventory stock') || hasAdminRole()">
                <BaseMenu has-no-offset>
                    <MenuItem v-slot="{ active }" as="div">
                        <a @click="showItemDeleteConfirmModal()"
                           :class="[active ? 'active' : 'not-active', 'default group cursor-pointer text-white flex items-center px-4 py-2 subpixel-antialiased text-sm']">
                            <IconTrash class="h-5 w-5 mr-3 group-hover:text-artwork-buttons-hover"/>
                            {{ $t('Delete') }}
                        </a>
                    </MenuItem>
                </BaseMenu>
            </div>

        </td>
    </tr>
    <ConfirmDeleteModal
        v-if="itemConfirmDeleteModalShown"
        :title="$t('Delete item?')"
        :button="$t('Yes')"
        :description="$t('Really delete this item? This cannot be undone.')"
        @delete="deleteItem()"
        @closed="closeItemDeleteConfirmModal()"/>
</template>

<script setup>
import InventoryCell from "@/Pages/Inventory/InventoryManagement/InventoryItemCell.vue";
import {computed, ref} from "vue";
import {IconTrash} from "@tabler/icons-vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";
import {MenuItem} from "@headlessui/vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {usePermission} from "@/Composeables/Permission.js";
import {usePage} from "@inertiajs/vue3";
const { can, canAny, hasAdminRole } = usePermission(usePage().props);

const canDraggable = computed(() => {
    return inventoryCellsEditing.value.length === 0;
});
const onDragStart = (event) => {
    if(!can('can manage inventory stock') || !hasAdminRole()){
        return;
    }

    if (inventoryCellsEditing.value.length > 0) {
        return;
    }

    document.body.classList.add('select-none');
    event.dataTransfer.setData(
        'application/json',
        JSON.stringify(
            {
                id: props.item.id,
            }
        )
    );
};

const emits = defineEmits(['itemDragging', 'itemDragEnd']),
    props = defineProps({
        index: Number,
        colspan: Number,
        item: Object,
        trCls: String
    }),
    itemMenuShown = ref(false),
    inventoryCellsEditing = ref([]),
    itemDragged = ref(false),
    itemConfirmDeleteModalShown = ref(false),
    isDraggable = computed(() => inventoryCellsEditing.value.length === 0),
    handleCellIsEditing = (isEditing, cellId) => {
        if (isEditing) {
            inventoryCellsEditing.value.push({cellId: cellId, isEditing: true});
            return
        }

        inventoryCellsEditing.value = inventoryCellsEditing.value.filter(
            (editingCell) => editingCell.cellId !== cellId
        );
    },
    showItemMenu = () => {
        itemMenuShown.value = true;
    },
    closeItemMenu = () => {
        itemMenuShown.value = false;
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

        //fix for chrome engine, timeout 1ms before emit otherwise dragend is called immediately
        //causing drag and drop not working properly if items in between are dragged
        //@see: https://stackoverflow.com/a/36617714
        setTimeout(() => emits.call(this,'itemDragging', props.index), 1);

        e.dataTransfer.setData('itemId', props.item.id);
        e.dataTransfer.setData('currentItemIndex', props.index.toString());
    },
    itemDragEnd = () => {
        itemDragged.value = false;
        emits.call(this, 'itemDragEnd');
    }
</script>

