<template>
    <tr :draggable="isDraggable()"
        @dragstart="categoryDragStart"
        @dragend="categoryDragEnd"
        :class="'cursor-grab ' + trCls">
        <td :colspan="colspan"
            class="pl-3 p-2 bg-primary text-white subpixel-antialiased text-sm">
            <div class="w-full h-full flex flex-row items-center relative gap-x-2">
                <div
                    class="cursor-pointer overflow-hidden overflow-ellipsis whitespace-nowrap"
                    @dblclick="toggleCategoryEdit()">
                    {{ category.name }}
                </div>
                <div @click="toggleCategory()"
                     class="cursor-pointer">
                    <IconChevronUp v-if="categoryShown" class="w-5 h-5" />
                    <IconChevronDown v-else class="w-5 h-5"/>
                </div>
                <div :class="[categoryClicked ? '' : 'hidden', 'flex flex-row cursor-pointer items-center bg-primary text-white gap-x-2 w-full -left-[4px] z-10 absolute']">
                    <input
                        type="text"
                        ref="categoryInputRef"
                        class="w-full p-1 border-0 text-xs text-black"
                        v-model="categoryValue"
                        @keyup.enter="applyCategoryValueChange()"
                        @keyup.esc="denyCategoryValueChange()">
                    <IconCheck class="w-5 h-5 hover:text-green-500"
                               @click="applyCategoryValueChange()"/>
                    <IconX class="w-5 h-5 hover:text-red-500"
                           @click="denyCategoryValueChange()"/>
                </div>
            </div>
        </td>
    </tr>
    <AddNewGroup v-if="categoryShown"/>
    <DropGroup v-if="showFirstDropGroup"
               :colspan="colspan"
               :destination-index="0"
               @group-requests-drag-move="moveGroupToDestination"/>
    <template v-if="categoryShown" v-for="(group, index) in category.groups">
        <InventoryGroup :index="index"
                        :group="group"
                        :colspan="colspan"
                        :tr-cls="getOnDragCls(index)"
                        @group-dragging="handleGroupDragging"
                        @group-drag-end="handleGroupDragEnd"/>
        <DropGroup v-if="showTemplateDropGroup(index)"
                   :colspan="colspan"
                   :destination-index="(index + 1)"
                   @group-requests-drag-move="moveGroupToDestination"/>
    </template>
</template>

<script setup>
import InventoryGroup from "@/Pages/Inventory/InventoryGroup.vue";
import {IconChevronDown, IconChevronUp, IconCheck, IconX} from "@tabler/icons-vue";
import {computed, ref} from "vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import AddNewGroup from "@/Pages/Inventory/AddNewGroup.vue";
import DropGroup from "@/Pages/Inventory/DropGroup.vue";

const emits = defineEmits(['categoryDragging', 'categoryDragEnd']);
const props = defineProps({
        index: Number,
        category: Object,
        colspan: Number,
        trCls: String
    }),
    categoryInputRef = ref(null),
    categoryShown = ref(true),
    categoryClicked = ref(false),
    categoryValue = ref(props.category.name),
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
    getOnDragCls = (index) => {
        return groupDragging.value && draggedGroupIndex.value !== index ? 'onDragBackground' : '';
    },
    handleGroupDragging = (index) => {
        draggedGroupIndex.value = index;
        groupDragging.value = true;
    },
    handleGroupDragEnd = () => {
        draggedGroupIndex.value = null;
        groupDragging.value = false;
    },
    moveGroupToDestination = (groupId, fromIndex, toIndex) => {
        console.debug(
            'group requested move from to index',
            props.category.id,
            groupId,
            fromIndex,
            toIndex
        );
    },
    isDraggable = () => {
        return !categoryClicked.value;
    },
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
        props.category.name = categoryValue.value;
        toggleCategoryEdit();
    },
    denyCategoryValueChange = () => {
        categoryValue.value = props.category.name;
        toggleCategoryEdit();
    },
    categoryDragStart = (e) => {
        emits.call(this,'categoryDragging', props.index);

        e.dataTransfer.setData('categoryId', props.category.id);
        e.dataTransfer.setData('currentCategoryIndex', props.index.toString());
    },
    categoryDragEnd = () => emits.call(this, 'categoryDragEnd');
</script>

<style scoped>
.onDragBackground :deep(td) {
    opacity: 50%;
}
</style>
