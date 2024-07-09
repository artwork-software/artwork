<template>
    <tr :draggable="isDraggable"
        @dragstart="itemDragStart"
        @dragend="itemDragEnd"
        @mouseover="showItemMenu()"
        @mouseout="closeItemMenu()"
        :class="'cursor-grab ' + trCls">
        <template v-for="(cell) in item.cells"
                  :key="cell.id">
            <InventoryCell :cell="cell"
                           @is-editing-cell-value="handleCellIsEditing"/>
        </template>
        <td class="relative">
            <Menu v-show="itemMenuShown && !itemDragged"
                  as="div"
                  class="inventory-menu-container !bottom-2.5 !right-0">
                <MenuButton as="div">
                    <IconDotsVertical class="menu-button"
                                      stroke-width="1.5"
                                      aria-hidden="true"/>
                </MenuButton>
                <div class="inventory-menu">
                    <transition enter-active-class="transition-enter-active"
                                enter-from-class="transition-enter-from"
                                enter-to-class="transition-enter-to"
                                leave-active-class="transition-leave-active"
                                leave-from-class="transition-leave-from"
                                leave-to-class="transition-leave-to">
                        <MenuItems class="menu-items">
                            <MenuItem v-slot="{ active }"
                                      as="div">
                                <a @click="showItemDeleteConfirmModal()"
                                   :class="[active ? 'active' : 'not-active', 'default group']">
                                    <IconTrash class="icon group-hover:text-white"/>
                                    {{ $t('Delete') }}
                                </a>
                            </MenuItem>
                        </MenuItems>
                    </transition>
                </div>
            </Menu>
        </td>
    </tr>
    <tr>
        <td class="empty-row-xxs-td"></td>
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
import {IconDotsVertical, IconTrash, IconTrashXFilled} from "@tabler/icons-vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";

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

