<template>
    <div
        v-if="isDragging || dropOver"
        @dragover="onDragOver"
        @drop="onDrop"
        @dragleave="dragLeave"
        class="rounded-lg duration-100 ease-in-out border-2 border-dashed w-full mb-4"
        :class="{
        'bg-artwork-buttons-create/10 border-artwork-buttons-create h-12 xsDark': dropOver,
        'bg-gray-50 border-gray-400 h-12 xsLight': isDragging,
        'border-transparent xsLight': !dropOver && !isDragging,
    }">
        <div class="flex items-center justify-center pointer-events-none h-full ">
            {{ $t("Add the task here") }}
        </div>
    </div>
</template>

<script setup>

import {ref, onMounted, onUnmounted, watch} from "vue";
import { router } from "@inertiajs/vue3";
import { EventListenerForDragging } from "@/Composeables/EventListenerForDragging.js";
const { isDragging, addEventListenerForDraggingStart, removeEventListenerForDraggingStart } = EventListenerForDragging();

const props = defineProps({
    checklistId: {
        type: Number,
        required: true,
    }
});

const dropOver = ref(false);

const onDragOver = (event) => {
    dropOver.value = true;
    isDragging.value = false;
    event.preventDefault();
};

const dragLeave = () => {
    dropOver.value = false;
    isDragging.value = true;
};

const onDrop = (event) => {
    event.preventDefault();
    try {
        const task = JSON.parse(event.dataTransfer.getData("task"));
        if (!task || !task.id) {
            console.warn("Component data is invalid", task);
            return;
        }
        dropOver.value = false;
        if(task.checklist_id === props.checklistId) {
            return;
        }
        router.patch(route("checklists.change.task", { task: task.id, checklist: props.checklistId }));
    } catch (error) {
        console.error("Invalid JSON in drag data", error);
        dropOver.value = false;
    }
};

onMounted(() => {
    const listeners = addEventListenerForDraggingStart();

    onUnmounted(() => {
        removeEventListenerForDraggingStart(listeners);
    });
});
</script>
