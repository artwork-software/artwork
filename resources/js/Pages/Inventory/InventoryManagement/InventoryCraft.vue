<template>
    <tr>
        <td :colspan="colspan" class="craft-td">
            <div class="craft-container">
                <div class="title"
                     @click="toggleCraft()">
                    <span>{{ craft.value.name }}</span>
                    <IconChevronUp v-if="craftShown" class="icon"/>
                    <IconChevronDown v-else class="icon"/>
                </div>
                <IconLink class="icon" @click="openShiftSettingsInNewTab()"/>
            </div>
        </td>
    </tr>
    <DropCategory v-if="showFirstDropCategory"
                  :colspan="colspan"
                  :destination-index="0"
                  @category-requests-drag-move="moveCategoryToDestination"/>
    <template v-if="craftShown && craft.value.filtered_inventory_categories.length > 0"
              v-for="(category, index) in craft.value.filtered_inventory_categories"
              :key="category.id">
        <AddNewCategory v-if="craftShown && index === 0"
                        @click="openAddCategoryOrGroupModal('category', craft.value.id)"/>
        <InventoryCategory :index="index"
                           :category="category"
                           :colspan="colspan"
                           :tr-cls="getCategoryOnDragCls(index)"
                           @wants-to-add-new-group="openAddCategoryOrGroupModal"
                           @category-dragging="handleCategoryDragging"
                           @category-drag-end="handleCategoryDragEnd"
        />
        <DropCategory v-if="showTemplateDropCategory(index)"
                      :colspan="colspan"
                      :destination-index="(index + 1)"
                      @category-requests-drag-move="moveCategoryToDestination"/>
        <template v-if="(index + 1) === craft.value.inventory_categories.length">
            <tr>
                <td :colspan="colspan" class="empty-row-td"/>
            </tr>
        </template>
    </template>
    <tr v-else-if="craftShown && craft.value.inventory_categories.length === 0">
        <td :colspan="colspan">
            <div @click="openAddCategoryOrGroupModal('category', craft.value.id)"
                 class="add-category-container">
                <IconCirclePlus class="icon"/>
                {{ $t('Add category') }}
            </div>
        </td>
    </tr>
    <AddCategoryOrGroupModal :resource-id="addCategoryOrGroupResourceId"
                             :show="showAddCategoryOrGroupModal"
                             :type="addCategoryOrGroupModalType"
                             @closed="closeAddCategoryOrGroupModal"/>
</template>

<script setup>
import InventoryCategory from "@/Pages/Inventory/InventoryManagement/InventoryCategory.vue";
import {IconCirclePlus, IconChevronDown, IconChevronUp, IconLink} from "@tabler/icons-vue";
import {computed, ref} from "vue";
import AddNewCategory from "@/Pages/Inventory/InventoryManagement/AddNewCategory.vue";
import DropCategory from "@/Pages/Inventory/InventoryManagement/DropCategory.vue";
import AddCategoryOrGroupModal from "@/Pages/Inventory/InventoryManagement/AddCategoryOrGroupModal.vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
        colspan: Number,
        craft: Object,
        selectInput: Function,
        createDynamicColumnNameInputRef: Function
    }),
    craftShown = ref(true),
    categoryDragging = ref(false),
    draggedCategoryIndex = ref(null),
    showFirstDropCategory = computed(() => {
        return categoryDragging.value && draggedCategoryIndex.value > 0;
    }),
    showTemplateDropCategory = computed(() => {
        return (index) =>
            categoryDragging.value &&
            index !== draggedCategoryIndex.value &&
            index !== (draggedCategoryIndex.value - 1);
    }),
    showAddCategoryOrGroupModal = ref(false),
    addCategoryOrGroupModalType = ref(''),
    addCategoryOrGroupResourceId = ref(0),
    openAddCategoryOrGroupModal = (type, resourceId) => {
        addCategoryOrGroupModalType.value = type;
        addCategoryOrGroupResourceId.value = resourceId;
        showAddCategoryOrGroupModal.value = true;
    },
    closeAddCategoryOrGroupModal = () => {
        addCategoryOrGroupModalType.value = '';
        addCategoryOrGroupResourceId.value = 0;
        showAddCategoryOrGroupModal.value = false;
    },
    toggleCraft = () => {
        craftShown.value = !craftShown.value;
    },
    openShiftSettingsInNewTab = () => {
        window.open(route('shift.settings'), '_blank');
    },
    handleCategoryDragging = (index) => {
        draggedCategoryIndex.value = index;
        categoryDragging.value = true;
    },
    getCategoryOnDragCls = (index) => {
        return categoryDragging.value && draggedCategoryIndex.value !== index ? 'onDragBackground' : '';
    },
    handleCategoryDragEnd = () => {
        draggedCategoryIndex.value = null;
        categoryDragging.value = false;
    },
    moveCategoryToDestination = (categoryId, toIndex) => {
        router.patch(
            route(
                'inventory-management.inventory.category.update.order',
                {
                    craftInventoryCategory: categoryId
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
