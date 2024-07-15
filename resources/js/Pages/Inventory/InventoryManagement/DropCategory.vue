<template>
    <tr draggable="true"
        @dragover="categoryDragOver"
        @dragleave="categoryDragLeave"
        @drop="categoryDrop"
        :class="dragOverClass">
        <td :colspan="colspan">
            <div class="drop-category-container">
                <IconDragDrop class="icon"/>
                <span class="text">{{ $t('Place here') }}</span>
            </div>
        </td>
    </tr>
    <tr>
        <td :colspan="colspan" class="empty-row-xxs-td"/>
    </tr>
</template>
<script setup>
import {IconDragDrop} from "@tabler/icons-vue";
import {computed, ref} from "vue";

const emits = defineEmits(['categoryRequestsDragMove']),
    props = defineProps({
        colspan: Number,
        destinationIndex: Number
    }),
    draggedOver = ref(false),
    dragOverClass = computed(() => {
        return draggedOver.value ? 'bg-primary text-white' : 'text-black';
    }),
    categoryDragOver = (e) => {
        draggedOver.value = true;
        e.preventDefault()
    },
    categoryDragLeave = () => {
        draggedOver.value = false;
    },
    categoryDrop = (e) => {
        emits.call(
            this,
            'categoryRequestsDragMove',
            e.dataTransfer.getData('categoryId'),
            props.destinationIndex
        );
    };
</script>
