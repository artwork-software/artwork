<template>
    <tr v-if="destinationIndex === 0">
        <td colspan="6" class="empty-row-xxs-td"/>
    </tr>
    <tr draggable="true"
        @dragover="groupDragOver"
        @dragleave="groupDragLeave"
        @drop="groupDrop"
        :class="dragOverClass">
        <td :colspan="colspan">
            <div class="drop-group-container">
                <IconDragDrop class="icon"/>
                <span class="text">{{ $t('Place here') }}</span>
            </div>
        </td>
    </tr>
    <tr v-if="destinationIndex > 0">
        <td :colspan="colspan" class="empty-row-xxs-td"/>
    </tr>
</template>
<script setup>
import {IconDragDrop} from "@tabler/icons-vue";
import {computed, ref} from "vue";

const emits = defineEmits(['groupRequestsDragMove']),
    props = defineProps({
        colspan: Number,
        destinationIndex: Number
    }),
    draggedOver = ref(false),
    dragOverClass = computed(() => {
        return draggedOver.value ? 'bg-secondary' : '';
    }),
    groupDragOver = (e) => {
        draggedOver.value = true;
        e.preventDefault()
    },
    groupDragLeave = () => {
        draggedOver.value = false;
    },
    groupDrop = (e) => {
        emits.call(
            this,
            'groupRequestsDragMove',
            e.dataTransfer.getData('groupId'),
            props.destinationIndex
        );
    };
</script>
