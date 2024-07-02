<template>
    <tr :draggable="groupIsDraggable()"
        @dragstart="groupDragStart"
        @dragend="groupDragEnd"
        @mouseover="handleGroupMouseover()"
        @mouseout="handleGroupMouseout()"
        :class="'cursor-grab ' + trCls">
        <td :colspan="colspan" class="pl-3 p-2 cursor-pointer bg-secondary text-xs border border-secondary">
            <div class="w-full h-full flex flex-row items-center relative gap-x-2">
                <div
                    class="cursor-text overflow-hidden overflow-ellipsis whitespace-nowrap"
                    @click="toggleGroupEdit()">
                    {{ group.name }}
                </div>
                <div @click="toggleGroup()">
                    <IconChevronUp v-if="groupShown" class="w-5 h-5"/>
                    <IconChevronDown v-else class="w-5 h-5"/>
                </div>
                <div
                    :class="[groupClicked ? '' : 'hidden', 'flex flex-row items-center bg-secondary text-black gap-x-2 w-full -left-[4px] z-10 absolute']">
                    <input
                        type="text"
                        ref="groupInputRef"
                        class="w-full p-1 border-0 text-xs text-black"
                        v-model="groupValue"
                        @focusout="applyGroupValueChange()"
                        @keyup.enter="applyGroupValueChange()">
                </div>
            </div>
        </td>
    </tr>
    <IconTrashXFilled v-if="!groupClicked && groupMouseover && !groupDragged"
                      @mouseover="handleGroupDeleteMouseover"
                      @mouseout="handleGroupDeleteMouseout"
                      :class="[groupDeleteCls + ' absolute z-50 w-8 h-8 p-1 cursor-pointer border border-white rounded-full text-white bg-black right-0 -translate-y-[105%] translate-x-[40%]']"
                      @click="showGroupDeleteConfirmModal()"/>
    <AddNewItem v-if="groupShown" @click="addNewItem()"/>
    <tr>
        <td></td>
    </tr>
    <DropItem v-if="showFirstDropItem"
              :colspan="colspan"
              :destination-index="0"
              @item-requests-drag-move="moveItemToDestination"/>
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
                  @item-requests-drag-move="moveItemToDestination"/>
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
import {IconChevronDown, IconChevronUp, IconTrashXFilled} from "@tabler/icons-vue";
import AddNewItem from "@/Pages/Inventory/InventoryManagement/AddNewItem.vue";
import DropItem from "@/Pages/Inventory/InventoryManagement/DropItem.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";

const emits = defineEmits(['groupDragging', 'groupDragEnd']),
    props = defineProps({
        index: Number,
        colspan: Number,
        group: Object,
        trCls: String
    }),
    groupInputRef = ref(null),
    groupShown = ref(true),
    groupDragged = ref(false),
    groupClicked = ref(false),
    groupValue = ref(props.group.name),
    groupMouseover = ref(false),
    groupDeleteCls = ref(''),
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
        if (props.group.name === groupValue.value) {
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
    handleGroupMouseover = () => {
        groupMouseover.value = true;
    },
    handleGroupMouseout = () => {
        groupMouseover.value = false;
    },
    handleGroupDeleteMouseover = () => {
        groupMouseover.value = true;
        groupDeleteCls.value = 'bg-red-600';
    },
    handleGroupDeleteMouseout = () => {
        groupMouseover.value = false;
        groupDeleteCls.value = 'bg-black';
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
        emits.call(this, 'groupDragging', props.index);

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

<style scoped>
.onDragBackground :deep(td) {
    opacity: 35%;
}
</style>

