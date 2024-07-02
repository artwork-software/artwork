<template>
    <tr :draggable="categoryIsDraggable()"
        @dragstart="categoryDragStart"
        @dragend="categoryDragEnd"
        @mouseover="handleCategoryMouseover()"
        @mouseout="handleCategoryMouseout()"
        :class="'cursor-grab ' + trCls">
        <td :colspan="colspan"
            :class="[categoryShown ? 'rounded-t-xl' : 'rounded-xl', 'category-td']">
            <div class="category-td-container">
                <div class="name"
                    @click="toggleCategoryEdit()">
                    {{ category.name }}
                </div>
                <div @click="toggleCategory()"
                     class="cursor-pointer">
                    <IconChevronUp v-if="categoryShown" class="icon"/>
                    <IconChevronDown v-else class="icon"/>
                </div>
                <div :class="[categoryClicked ? '' : '!hidden', 'category-input-container']">
                    <input
                        type="text"
                        ref="categoryInputRef"
                        class="category-input"
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
                      :class="[categoryDeleteCls + ' remove-category-icon']"
                      @click="showCategoryDeleteConfirmModal()"/>
    <AddNewResource v-if="categoryShown"
            @click="openAddCategoryOrGroupModal()"
            :text="$t('Add new group')"
            :colspan="colspan"/>
    <DropGroup v-if="showFirstDropGroup"
               :colspan="colspan"
               :destination-index="0"
               @group-requests-drag-move="moveGroupToDestination"/>
    <tr>
        <td :colspan="colspan" class="empty-row-xxs-td"/>
    </tr>
    <template v-if="categoryShown"
              v-for="(group, index) in category.groups"
              :key="group.id">
        <InventoryGroup :index="index"
                        :group="group"
                        :colspan="colspan"
                        :tr-cls="getGroupOnDragCls(index)"
                        @group-dragging="handleGroupDragging"
                        @group-drag-end="handleGroupDragEnd"/>
        <tr>
            <td :colspan="colspan" class="empty-row-xxs-td"/>
        </tr>
        <DropGroup v-if="showTemplateDropGroup(index)"
                   :colspan="colspan"
                   :destination-index="(index + 1)"
                   @group-requests-drag-move="moveGroupToDestination"/>
    </template>
    <ConfirmDeleteModal v-if="categoryConfirmDeleteModalShown"
                        :title="$t('Delete category?')"
                        :button="$t('Yes')"
                        :description="$t('Really delete this category? This cannot be undone.')"
                        @delete="deleteCategory()"
                        @closed="closeCategoryDeleteConfirmModal()"
    />
</template>

<script setup>
import InventoryGroup from "@/Pages/Inventory/InventoryManagement/InventoryGroup.vue";
import {IconChevronDown, IconChevronUp, IconTrashXFilled} from "@tabler/icons-vue";
import {computed, ref} from "vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import DropGroup from "@/Pages/Inventory/InventoryManagement/DropGroup.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";
import AddNewResource from "@/Pages/Inventory/InventoryManagement/AddNewResource.vue";

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
        categoryDeleteCls.value = '!bg-red-600';
    },
    handleCategoryDeleteMouseout = () => {
        categoryMouseover.value = false;
        categoryDeleteCls.value = '!bg-black';
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
