<template>
    <tr @mouseover="showGroupMenu()"
        @mouseout="closeGroupMenu()" class="group"
        @dragover="onDragOver" @drop="onDrop"
        :class="trCls">
        <td :colspan="colspan" class="group-td-folder">
            <div class="group-td-container">
                <div
                    class="name flex items-center gap-x-1"
                    @click="toggleFolderEdit()">
                    <component :is="IconFolderSymlink" class="icon"/>
                    {{ folder.name }}
                </div>
                <div @click="toggleGroup()">
                    <IconChevronUp v-if="groupShown" class="icon"/>
                    <IconChevronDown v-else class="icon"/>
                </div>
                <ToolTipComponent
                    :tooltip-text="$t('Add new item')"
                    direction="bottom"
                    :icon="IconCirclePlus"
                    icon-size="h-5 w-5"
                    stroke="1.5"
                    classes="text-black cursor-pointer hover:text-artwork-buttons-create duration-150 ease-in-out transition-colors"
                    @click="addNewItem(folder.id)"
                    v-if="!folderClicked && can('can manage inventory stock') || hasAdminRole()"
                />
                <div
                    :class="[folderClicked ? '' : '!hidden', 'group-input-container']">
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
    <tr v-if="folder.items.length > 0 && groupShown">
        <td class="empty-row-xxs-td"></td>
    </tr>
    <template v-if="groupShown"
              v-for="(item, index) in folder.items"
              :key="item.id">
        <InventoryItem :index="index"
                       :item="item"
                       :colspan="colspan"
                       :tr-cls="getItemOnDragCls(index)"/>

        <tr v-if="(index + 1) < folder.items.length">
            <td class="empty-row-xxs-td"></td>
        </tr>
    </template>
    <tr v-if="folder.items.length > 0 && groupShown">
        <td class="empty-row-xxs-td"></td>
    </tr>

    <ConfirmDeleteModal
        v-if="groupConfirmDeleteModalShown"
        :title="$t('Delete Folder?')"
        :button="$t('Yes')"
        :description="$t('Really delete this Folder? This cannot be undone.')"
        @delete="deleteFolder()"
        @closed="closeGroupDeleteConfirmModal()"/>

</template>

<script setup>

import InventoryItem from "@/Pages/Inventory/InventoryManagement/InventoryItem.vue";
import {computed, ref} from "vue";
import {IconChevronDown, IconChevronUp, IconCirclePlus, IconFolderSymlink, IconTrash} from "@tabler/icons-vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";
import { MenuItem } from "@headlessui/vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import InventoryGroupFolder from "@/Pages/Inventory/InventoryManagement/InventoryGroupFolder.vue";
import {presets} from "@soketi/soketi/babel.config.js";
import {usePermission} from "@/Composeables/Permission.js";
import {usePage} from "@inertiajs/vue3";
const { can, canAny, hasAdminRole } = usePermission(usePage().props);
const emits = defineEmits(['groupDragging', 'groupDragEnd']),
    props = defineProps({
        index: Number,
        colspan: Number,
        folder: Object,
        trCls: String
    }),
    groupMenuShown = ref(false),
    groupInputRef = ref(null),
    groupShown = ref(true),
    groupDragged = ref(false),
    folderClicked = ref(false),
    groupValue = ref(props.folder.name),
    droppedItem = ref(null),
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
    toggleFolderEdit = () => {
        if(!can('can manage inventory stock') || !hasAdminRole()){
            return;
        }
        folderClicked.value = !folderClicked.value;

        if (folderClicked.value) {
            setTimeout(() => {
                groupInputRef.value.select();
            }, 5);
        }
    },
    applyGroupValueChange = () => {
        if (props.folder.name === groupValue.value || groupValue.value.length === 0) {
            toggleFolderEdit();
            return;
        }
        router.patch(
            route(
                'inventory-management.inventory.folder.update.name',
                {
                    craftInventoryGroupFolder: props.folder.id
                }
            ),
            {
                name: groupValue.value
            },
            {
                preserveScroll: true,
                onSuccess: toggleFolderEdit
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
    deleteFolder = () => {
        router.delete(
            route(
                'inventory-management.inventory.folder.delete',
                {
                    craftInventoryGroupFolder: props.folder.id
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
                groupId: folder ? null : props.folder.id,
                folderId: folder,
                //as length is already the "next" index cause it counts from 1, no need to add 1
                order: props.folder.items.length
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
        return !folderClicked.value;
    },
    groupDragStart = (e) => {
        groupDragged.value = true;

        //fix for chrome engine, timeout 1ms before emit otherwise dragend is called immediately
        //causing drag and drop not working properly if items in between are dragged
        //@see: https://stackoverflow.com/a/36617714
        setTimeout(() => emits.call(this, 'groupDragging', props.index), 1);

        e.dataTransfer.setData('groupId', props.folder.id);
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
    if(!can('can manage inventory stock') || !hasAdminRole()){
        return;
    }
    e.preventDefault();
    const jsonObject = e.dataTransfer.getData('application/json');
    if(jsonObject) {
        droppedItem.value = JSON.parse(jsonObject);

        router.patch(
            route(
                'inventory-management.inventory.item.add.to.folder',
                {
                    craftInventoryItem: droppedItem.value.id
                }
            ),
            {
                folderId: props.folder.id
            },
            {
                preserveScroll: true
            }
        );
    }

    return;
};
</script>

<style scoped>

</style>