<template>
    <tr :draggable="categoryIsDraggable()"
        @dragstart="categoryDragStart"
        @dragend="categoryDragEnd"
        @mouseover="handleCategoryMouseover()"
        @mouseout="handleCategoryMouseout()"
        :class="'cursor-grab ' + trCls">
        <td :colspan="colspan"
            :class="[categoryShown ? 'rounded-t-xl' : 'rounded-xl', 'px-3 py-2 bg-primary text-white subpixel-antialiased text-sm']">
            <div class="w-full h-full flex flex-row items-center relative gap-x-2">
                <div
                    class="cursor-text overflow-hidden overflow-ellipsis whitespace-nowrap"
                    @click="toggleCategoryEdit()">
                    {{ category.name }}
                </div>
                <div @click="toggleCategory()"
                     class="cursor-pointer">
                    <IconChevronUp v-if="categoryShown" class="w-5 h-5"/>
                    <IconChevronDown v-else class="w-5 h-5"/>
                </div>
                <div
                    :class="[categoryClicked ? '' : 'hidden', 'flex flex-row cursor-pointer items-center bg-primary text-white gap-x-2 w-full -left-[4px] z-10 absolute']">
                    <input
                        type="text"
                        ref="categoryInputRef"
                        class="w-full p-1 border-0 text-xs text-black"
                        v-model="categoryValue"
                        @focusout="applyCategoryValueChange()"
                        @keyup.enter="applyCategoryValueChange()">
                </div>
            </div>
        </td>
    </tr>
    <IconTrashXFilled v-if="!categoryClicked && categoryMouseover && !categoryDragged"
                      @mouseover="handleCategoryDeleteMouseover"
                      @mouseout="handleCategoryDeleteMouseout"
                      :class="[categoryDeleteCls + ' absolute z-50 w-8 h-8 p-1 cursor-pointer border border-white rounded-full text-white bg-black right-0 -translate-y-[105%] translate-x-[40%]']"
                      @click="showCategoryDeleteConfirmModal()"/>
    <ConfirmDeleteModal v-if="categoryConfirmDeleteModalShown"
                        :title="$t('Delete category?')"
                        :button="$t('Yes')"
                        :description="$t('Really delete a category? This cannot be undone and is only possible if no items in the associated groups are scheduled.')"
                        @delete="deleteCategory()"
                        @closed="closeCategoryDeleteConfirmModal()"
    />
    <AddNewGroup v-if="categoryShown" @click="openAddCategoryOrGroupModal()"/>
    <DropGroup v-if="showFirstDropGroup"
               :colspan="colspan"
               :destination-index="0"
               @group-requests-drag-move="moveGroupToDestination"/>
    <tr>
        <td :colspan="colspan" class="h-0.5"/>
    </tr>
    <template v-if="categoryShown" v-for="(group, index) in category.groups">
        <InventoryGroup :index="index"
                        :group="group"
                        :colspan="colspan"
                        :tr-cls="getGroupOnDragCls(index)"
                        @group-dragging="handleGroupDragging"
                        @group-drag-end="handleGroupDragEnd"/>
        <tr>
            <td :colspan="colspan" class="h-0.5"/>
        </tr>
        <DropGroup v-if="showTemplateDropGroup(index)"
                   :colspan="colspan"
                   :destination-index="(index + 1)"
                   @group-requests-drag-move="moveGroupToDestination"/>
    </template>
</template>

<script setup>
import InventoryGroup from "@/Pages/Inventory/InventoryManagement/InventoryGroup.vue";
import {IconChevronDown, IconChevronUp, IconTrashXFilled} from "@tabler/icons-vue";
import {computed, ref} from "vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import AddNewGroup from "@/Pages/Inventory/InventoryManagement/AddNewGroup.vue";
import DropGroup from "@/Pages/Inventory/InventoryManagement/DropGroup.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";

const emits = defineEmits(['categoryDragging', 'categoryDragEnd', 'wantsToAddNewGroup']),
    props = defineProps({
        index: Number,
        category: Object,
        colspan: Number,
        trCls: String
    }),
    categoryInputRef = ref(null),
    categoryShown = ref(true),
    categoryDragged = ref(false),
    categoryClicked = ref(false),
    categoryValue = ref(props.category.name),
    categoryMouseover = ref(false),
    categoryDeleteCls = ref(''),
    categoryConfirmDeleteModalShown = ref(false),
    groupDragging = ref(false),
    draggedGroupIndex = ref(null),
    showFirstDropGroup = computed(() => {
        return groupDragging.value && draggedGroupIndex.value > 0;
    }),
    showTemplateDropGroup = computed(() => {
        return (index) => groupDragging.value &&
            index !== draggedGroupIndex.value &&
            index !== (draggedGroupIndex.value - 1);
    }),
    toggleCategory = () => {
        categoryShown.value = !categoryShown.value;
    },
    toggleCategoryEdit = () => {
        categoryClicked.value = !categoryClicked.value;

        if (categoryClicked.value) {
            setTimeout(() => {
                categoryInputRef.value.select();
            }, 5);
        }
    },
    applyCategoryValueChange = () => {
        if (props.category.name === categoryValue.value) {
            toggleCategoryEdit();
            return;
        }
        router.patch(
            route(
                'inventory-management.inventory.category.update.name',
                {
                    craftInventoryCategory: props.category.id
                }
            ),
            {
                name: categoryValue.value
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toggleCategoryEdit();
                }
            }
        );
    },
    handleCategoryMouseover = () => {
        categoryMouseover.value = true;
    },
    handleCategoryMouseout = () => {
        categoryMouseover.value = false;
    },
    handleCategoryDeleteMouseover = () => {
        categoryMouseover.value = true;
        categoryDeleteCls.value = 'bg-red-600';
    },
    handleCategoryDeleteMouseout = () => {
        categoryMouseover.value = false;
        categoryDeleteCls.value = 'bg-black';
    },
    showCategoryDeleteConfirmModal = () => {
        categoryConfirmDeleteModalShown.value = true;
    },
    deleteCategory = () => {
        router.delete(
            route('inventory-management.inventory.category.delete',
                {
                    craftInventoryCategory: props.category.id
                }
            ),
            {
                onSuccess: closeCategoryDeleteConfirmModal
            }
        )
    },
    closeCategoryDeleteConfirmModal = () => {
        categoryConfirmDeleteModalShown.value = false;
    },
    categoryIsDraggable = () => {
        return !categoryClicked.value;
    },
    categoryDragStart = (e) => {
        categoryDragged.value = true;
        emits.call(this, 'categoryDragging', props.index);

        e.dataTransfer.setData('categoryId', props.category.id);
        e.dataTransfer.setData('currentCategoryIndex', props.index.toString());
    },
    categoryDragEnd = () => {
        categoryDragged.value = false;
        emits.call(this, 'categoryDragEnd')
    },
    handleGroupDragging = (index) => {
        draggedGroupIndex.value = index;
        groupDragging.value = true;
    },
    getGroupOnDragCls = (index) => {
        return groupDragging.value && draggedGroupIndex.value !== index ? 'onDragBackground' : '';
    },
    handleGroupDragEnd = () => {
        draggedGroupIndex.value = null;
        groupDragging.value = false;
    },
    openAddCategoryOrGroupModal = () => {
        emits.call(this, 'wantsToAddNewGroup', 'group', props.category.id);
    },
    moveGroupToDestination = (groupId, toIndex) => {
        router.patch(
            route(
                'inventory-management.inventory.group.update.order',
                {
                    craftInventoryGroup: groupId
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
    opacity: 50%;
}
</style>
