<template>
    <tr :draggable="groupIsDraggable()"
        @dragstart="groupDragStart"
        @dragend="groupDragEnd"
        @mouseover="showGroupMenu()"
        @mouseout="closeGroupMenu()" class="group"
        @dragover="onDragOver" @drop="onDrop"
        :class="'cursor-grab ' + trCls">
        <td :colspan="colspan" class="group-td">
            <div class="group-td-container">
                <div
                    class="name"
                    @click="toggleGroupEdit()">
                    <component :is="IconCornerDownRight" class="icon"/>
                    {{ group.name }}
                </div>
                <div @click="toggleGroup()">
                    <IconChevronUp v-if="groupShown" class="icon"/>
                    <IconChevronDown v-else class="icon"/>
                </div>
                <div class="flex items-center w-full gap-x-4" v-if="can('can manage inventory stock') || hasAdminRole()">
                    <ToolTipComponent
                        :tooltip-text="$t('Add new item')"
                        direction="bottom"
                        :icon="IconCirclePlus"
                        icon-size="h-5 w-5"
                        stroke="1.5"
                        classes="text-black cursor-pointer hover:text-artwork-buttons-create duration-150 ease-in-out transition-colors"
                        @click="addNewItem()"
                        v-if="!groupClicked"
                    />
                    <ToolTipComponent
                        :tooltip-text="$t('New folder')"
                        direction="bottom"
                        :icon="IconFolderPlus"
                        icon-size="h-5 w-5"
                        stroke="1.5"
                        classes="text-black cursor-pointer hover:text-artwork-buttons-create duration-150 ease-in-out transition-colors"
                        @click="showAddCategoryOrGroupModal = true"
                        v-if="!groupClicked"
                    />
                    <ToolTipComponent
                        :tooltip-text="$t('Change the order of the folders')"
                        direction="bottom"
                        :icon="IconFolderCog"
                        icon-size="h-5 w-5"
                        stroke="1.5"
                        classes="text-black cursor-pointer hover:text-artwork-buttons-create duration-150 ease-in-out transition-colors"
                        @click="showReorderFoldersModal = true"
                        v-if="!groupClicked && group.folders.length > 1"
                    />
                </div>
                <div :class="[groupClicked ? '' : '!hidden', 'group-input-container']">
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
            <BaseMenu has-no-offset class="invisible group-hover:visible" v-if="can('can manage inventory stock') || hasAdminRole()">
                <MenuItem v-slot="{ active }"
                          as="div">
                    <a @click="showGroupDeleteConfirmModal()"
                       :class="[active ? 'active' : 'not-active', 'default group cursor-pointer text-white flex items-center px-4 py-2 subpixel-antialiased text-sm']">
                        <IconTrash class="h-5 w-5 mr-3 group-hover:text-artwork-buttons-hover"/>
                        {{ $t('Delete') }}
                    </a>
                </MenuItem>
            </BaseMenu>
        </td>
    </tr>
    <tr v-if="group.items.length > 0 && groupShown">
        <td class="empty-row-xxs-td"></td>
    </tr>
    <template v-if="groupShown"
              v-for="(item, index) in group.items"
              :key="item.id">
        <InventoryItem :index="index"
                       :item="item"
                       :colspan="colspan"
                       :tr-cls="getItemOnDragCls(index)"/>
        <!--@item-dragging="handleItemDragging"
        @item-drag-end="handleItemDragEnd" -->

        <tr v-if="(index + 1) < group.items.length">
            <td class="empty-row-xxs-td"></td>
        </tr>
        <!--<DropItem v-if="showTemplateDropItem(index)"
                  :colspan="colspan"
                  :destination-index="(index + 1)"
                  @item-requests-drag-move="moveItemToDestination"
                  :max-index="group.items.length"/>-->
    </template>
    <tr v-if="group.items.length > 0 && groupShown">
        <td class="empty-row-xxs-td"></td>
    </tr>
    <template v-if="groupShown"
              v-for="(folder, index) in group.folders"
              :key="folder.id">
        <InventoryGroupFolder
            :index="index"
            :folder="folder"
            :colspan="colspan"
            :tr-cls="getItemOnDragCls(index)"
            :ref="`folder-${folder.id}`"
            :trCls="getItemOnDragCls(index)"
        />
    </template>
    <!--<DropItem v-if="showFirstDropItem"
              :colspan="colspan"
              :destination-index="0"
              @item-requests-drag-move="moveItemToDestination"
              :max-index="1"/>-->

    <ConfirmDeleteModal v-if="groupConfirmDeleteModalShown"
                        :title="$t('Delete group?')"
                        :button="$t('Yes')"
                        :description="$t('Really delete this group? This cannot be undone.')"
                        @delete="deleteGroup()"
                        @closed="closeGroupDeleteConfirmModal()"/>

    <AddCategoryOrGroupModal :resource-id="props.group.id"
                             :show="showAddCategoryOrGroupModal"
                             type="folder"
                             @closed="closeAddCategoryOrGroupModal"/>

    <ReorderFolderModal
        :folders="group.folders"
        v-if="showReorderFoldersModal"
        @closed="showReorderFoldersModal = false"
    />
</template>

<script setup>
import InventoryItem from "@/Pages/Inventory/InventoryManagement/InventoryItem.vue";
import {computed, ref} from "vue";
import {
    IconChevronDown,
    IconChevronUp, IconCirclePlus,
    IconCornerDownRight,
    IconFolderCog,
    IconFolderPlus,
    IconTrash
} from "@tabler/icons-vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";
import { MenuItem } from "@headlessui/vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import InventoryGroupFolder from "@/Pages/Inventory/InventoryManagement/InventoryGroupFolder.vue";
import DropGroup from "@/Pages/Inventory/InventoryManagement/DropGroup.vue";
import AddCategoryOrGroupModal from "@/Pages/Inventory/InventoryManagement/AddCategoryOrGroupModal.vue";
import ReorderFolderModal from "@/Pages/Inventory/Components/ReorderFolderModal.vue";
import {usePermission} from "@/Composeables/Permission.js";
import {usePage} from "@inertiajs/vue3";
const { can, canAny, hasAdminRole } = usePermission(usePage().props);

const emits = defineEmits(['groupDragging', 'groupDragEnd']),
    props = defineProps({
        index: Number,
        colspan: Number,
        group: Object,
        trCls: String
    }),
    groupMenuShown = ref(false),
    showAddCategoryOrGroupModal = ref(false),
    groupInputRef = ref(null),
    groupShown = ref(true),
    groupDragged = ref(false),
    groupClicked = ref(false),
    droppedItem = ref(null),
    groupValue = ref(props.group.name),
    groupConfirmDeleteModalShown = ref(false),
    itemDragging = ref(false),
    showReorderFoldersModal = ref(false),
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
        if(!can('can manage inventory stock') || !hasAdminRole()){
            return;
        }
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
    addNewItem = (folder = null) => {
        router.post(
            route('inventory-management.inventory.item.create'),
            {
                groupId: folder ? null : props.group.id,
                folderId: folder,
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

const onDragOver = (e) => {
    e.preventDefault();
};

const onDrop = (e) => {
    e.preventDefault();
    const jsonObject = e.dataTransfer.getData('application/json');
    if(jsonObject) {
        droppedItem.value = JSON.parse(jsonObject);
        router.patch(
            route(
                'inventory-management.inventory.item.add.to.group',
                {
                    craftInventoryItem: droppedItem.value.id
                }
            ),
            {
                groupId: props.group.id
            },
            {
                preserveScroll: true
            }
        );
    }

};

const closeAddCategoryOrGroupModal = () => {
    showAddCategoryOrGroupModal.value = false;
};
</script>

