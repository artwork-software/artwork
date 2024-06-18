<template>
    <tr>
        <td :colspan="colspan" class="h-24 pl-3 border-4 subpixel-antialiased text-2xl font-bold">
            <div class="flex flex-row items-center gap-x-2">
                <div class="flex flex-row items-center w-auto gap-x-2 cursor-pointer"
                     @click="toggleCraft()">
                    <span>{{ craft.name }}</span>
                    <IconChevronUp v-if="craftShown" class="w-5 h-5"/>
                    <IconChevronDown v-else class="w-5 h-5"/>
                </div>
                <IconLink class="w-5 h-5 cursor-pointer" @click="openShiftSettingsInNewTab()"/>
            </div>
        </td>
    </tr>
    <AddNewCategory v-if="craftShown"/>
    <DropCategory v-if="showFirstDropCategory"
                  :colspan="colspan"
                  :destination-index="0"
                  @categroy-requests-drag-move="moveCategoryToDestination"/>
    <template v-if="craftShown"
              v-for="(category, index) in craft.categories">
        <InventoryCategory :index="index"
                           :category="category"
                           :colspan="colspan"
                           :tr-cls="getCategoryOnDragCls(index)"
                           @category-dragging="handleCategoryDragging"
                           @category-drag-end="handleCategoryDragEnd"
        />
        <DropCategory v-if="showTemplateDropCategory(index)"
                      :colspan="colspan"
                      :destination-index="(index + 1)"
                      @categroy-requests-drag-move="moveCategoryToDestination"/>
    </template>
    <AddNewCategory v-if="craftShown"/>
</template>

<script setup>
import InventoryCategory from "@/Pages/Inventory/InventoryCategory.vue";
import {IconChevronDown, IconChevronUp, IconLink} from "@tabler/icons-vue";
import {computed, ref} from "vue";
import AddNewCategory from "@/Pages/Inventory/AddNewCategory.vue";
import DropCategory from "@/Pages/Inventory/DropCategory.vue";

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
    moveCategoryToDestination = (categoryId, fromIndex, toIndex) => {
        console.debug(
            'category requested move from to index',
            props.craft.id,
            categoryId,
            fromIndex,
            toIndex
        );
    };
</script>
