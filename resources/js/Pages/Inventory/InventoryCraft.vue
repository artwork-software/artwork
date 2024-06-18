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
    <DropCategory v-if="categoryDragging && draggedCategoryIndex > 0"
                  :colspan="6"/>
    <template v-if="craftShown"
              v-for="(category, index) in craft.categories">
        <InventoryCategory :index="index"
                           :category="category"
                           :colspan="6"
                           :tr-cls="getOnDragCls(index)"
                           @category-dragging="handleCategoryDragging"
                           @category-drag-end="handleCategoryDragend"
        />
        <DropCategory v-if="categoryDragging && index !== draggedCategoryIndex && index !== (draggedCategoryIndex - 1)"
                      :colspan="6"/>
    </template>
    <AddNewCategory v-if="craftShown"/>
</template>

<script setup>
import InventoryCategory from "@/Pages/Inventory/InventoryCategory.vue";
import {IconChevronDown, IconChevronUp, IconLink} from "@tabler/icons-vue";
import {ref} from "vue";
import AddNewCategory from "@/Pages/Inventory/AddNewCategory.vue";
import DropCategory from "@/Pages/Inventory/DropCategory.vue";

const props = defineProps({
        colspan: Number,
        craft: Object,
        selectInput: Function,
        createDynamicColumnNameInputRef: Function
    }),
    categoryDragging = ref(false),
    draggedCategoryIndex = ref(null),
    craftShown = ref(true),
    getOnDragCls = (index) => {
        return categoryDragging.value && draggedCategoryIndex.value !== index ? 'opacity-50' : '';
    },
    handleCategoryDragging = (index) => {
        draggedCategoryIndex.value = index;
        categoryDragging.value = true;
    },
    handleCategoryDragend = () => {
        draggedCategoryIndex.value = null;
        categoryDragging.value = false;
    },
    toggleCraft = () => {
        craftShown.value = !craftShown.value;
    },
    openShiftSettingsInNewTab = () => {
        window.open(route('shift.settings'), '_blank');
    };
</script>
