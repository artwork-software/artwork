<template>
    <tr :draggable="groupIsDraggable()"
        @dragstart="groupDragStart"
        @dragend="groupDragEnd"
        @mouseover="showGroupMenu()"
        @mouseout="closeGroupMenu()"
        :class="'cursor-grab ' + trCls">
        <td :colspan="colspan" class="group-td">
            <div class="group-td-container">
                <div
                    class="name"
                    @click="toggleGroupEdit()">
                    {{ group.name }}
                </div>
                <div @click="toggleGroup()">
                    <IconChevronUp v-if="groupShown" class="icon"/>
                    <IconChevronDown v-else class="icon"/>
                </div>
                <AddNewResource @click="addNewItem()"
                                :text="$t('Add new item')"
                                :colspan="colspan"/>
                <div
                    :class="[groupClicked ? '' : '!hidden', 'group-input-container']">
                    <input
                        type="text"
                        ref="groupInputRef"
                        class="group-input"
                        v-model="groupValue"
                        @focusout="applyGroupValueChange()"
                        @keyup.enter="applyGroupValueChange()">
                </div>
            </div>
        </td>
        <td class="relative">
            <Menu v-show="groupMenuShown && !groupDragged"
                  as="div"
                  class="inventory-menu-container">
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
                                <a @click="showGroupDeleteConfirmModal()"
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

    <tr v-if="group.items.length > 0">
        <td class="empty-row-xxs-td"></td>
    </tr>
    <DropItem v-if="showFirstDropItem"
              :colspan="colspan"
              :destination-index="0"
              @item-requests-drag-move="moveItemToDestination"
              :max-index="1"/>
    <template v-if="groupShown"
              v-for="(item, index) in group.items"
              :key="item.id">
        <InventoryItem :index="index"
                       :item="item"
                       :colspan="colspan"
                       :tr-cls="getItemOnDragCls(index)"
                       @item-dragging="handleItemDragging"
                       @item-drag-end="handleItemDragEnd"/>
        <DropItem v-if="showTemplateDropItem(index)"
                  :colspan="colspan"
                  :destination-index="(index + 1)"
                  @item-requests-drag-move="moveItemToDestination"
                  :max-index="group.items.length"/>
    </template>
    <ConfirmDeleteModal v-if="groupConfirmDeleteModalShown"
                        :title="$t('Delete group?')"
                        :button="$t('Yes')"
                        :description="$t('Really delete this group? This cannot be undone.')"
                        @delete="deleteGroup()"
                        @closed="closeGroupDeleteConfirmModal()"/>
</template>

<script setup>
import InventoryItem from "@/Pages/Inventory/InventoryManagement/InventoryItem.vue";
import {computed, ref} from "vue";
import {IconChevronDown, IconChevronUp, IconDotsVertical, IconTrash, IconTrashXFilled} from "@tabler/icons-vue";
import DropItem from "@/Pages/Inventory/InventoryManagement/DropItem.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";
import AddNewResource from "@/Pages/Inventory/InventoryManagement/AddNewResource.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";

const emits = defineEmits(['groupDragging', 'groupDragEnd']),
    props = defineProps({
        index: Number,
        colspan: Number,
        group: Object,
        trCls: String
    }),
    groupMenuShown = ref(false),
    groupInputRef = ref(null),
    groupShown = ref(true),
    groupDragged = ref(false),
    groupClicked = ref(false),
    groupValue = ref(props.group.name),
    groupConfirmDeleteModalShown = ref(false),
    itemDragging = ref(false),
    draggedItemIndex = ref(null),
    showFirstDropItem = computed(() => {
        return itemDragging.value && draggedItemIndex.value > 0;
    }),
    showTemplateDropItem = computed(() => {
        return (index) => itemDragging.value &&
            index !== draggedItemIndex.value &&
            index !== (draggedItemIndex.value - 1);
    }),
    toggleGroup = () => {
        groupShown.value = !groupShown.value;
    },
    toggleGroupEdit = () => {
        groupClicked.value = !groupClicked.value;

        if (groupClicked.value) {
            setTimeout(() => {
                groupInputRef.value.select();
            }, 5);
        }
    },
    applyGroupValueChange = () => {
        if (props.group.name === groupValue.value || groupValue.value.length === 0) {
            toggleGroupEdit();
            return;
        }
        router.patch(
            route(
                'inventory-management.inventory.group.update.name',
                {
                    craftInventoryGroup: props.group.id
                }
            ),
            {
                name: groupValue.value
            },
            {
                preserveScroll: true,
                onSuccess: toggleGroupEdit
            }
        );
    },
    showGroupMenu = () => {
        groupMenuShown.value = true;
    },
    closeGroupMenu = () => {
        groupMenuShown.value = false;
    },
    showGroupDeleteConfirmModal = () => {
        groupConfirmDeleteModalShown.value = true;
    },
    deleteGroup = () => {
        router.delete(
            route(
                'inventory-management.inventory.group.delete',
                {
                    craftInventoryGroup: props.group.id
                }
            ),
            {
                preserveScroll: true
            }
        );
        closeGroupDeleteConfirmModal();
    },
    addNewItem = () => {
        router.post(
            route('inventory-management.inventory.item.create'),
            {
                groupId: props.group.id,
                //as length is already the "next" index cause it counts from 1, no need to add 1
                order: props.group.items.length
            },
            {
                preserveScroll: true
            }
        )
    },
    closeGroupDeleteConfirmModal = () => {
        groupConfirmDeleteModalShown.value = false;
    },
    groupIsDraggable = () => {
        return !groupClicked.value;
    },
    groupDragStart = (e) => {
        groupDragged.value = true;

        //fix for chrome engine, timeout 1ms before emit otherwise dragend is called immediately
        //causing drag and drop not working properly if items in between are dragged
        //@see: https://stackoverflow.com/a/36617714
        setTimeout(() => emits.call(this, 'groupDragging', props.index), 1);

        e.dataTransfer.setData('groupId', props.group.id);
        e.dataTransfer.setData('currentGroupIndex', props.index.toString());
    },
    groupDragEnd = () => {
        groupDragged.value = false;
        emits.call(this, 'groupDragEnd');
    },
    handleItemDragging = (index) => {
        draggedItemIndex.value = index;
        itemDragging.value = true;
    },
    getItemOnDragCls = (index) => {
        return itemDragging.value && draggedItemIndex.value !== index ? 'onDragBackground' : '';
    },
    handleItemDragEnd = () => {
        draggedItemIndex.value = null;
        itemDragging.value = false;
    },
    moveItemToDestination = (itemId, fromIndex, toIndex) => {
        router.patch(
            route(
                'inventory-management.inventory.item.update.order',
                {
                    craftInventoryItem: itemId
                }
            ),
            {
                order: toIndex
            },
            {
                preserveScroll: true
            }
        );
    };
</script>

