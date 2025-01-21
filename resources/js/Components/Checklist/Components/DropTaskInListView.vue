<template>
    <div
        v-if="isDragging || dropOver"
        @dragover="onDragOver"
        @drop="onDrop"
        @dragleave="dragLeave"
        class="rounded-lg duration-100 ease-in-out border-2 border-dashed w-full mb-4"
        :class="{
        'bg-artwork-buttons-create/10 border-artwork-buttons-create h-8 xsDark': dropOver,
        'bg-gray-50 border-gray-400 h-8 xsLight': isDragging,
        'border-transparent xsLight h-8': !dropOver && !isDragging,
    }"
       >
        <div class="flex items-center justify-center pointer-events-none h-full">
            {{ $t("Add the task here") }}
        </div>
    </div>
</template>

<script setup>

import {ref, onMounted, onUnmounted} from "vue";
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
    console.log('onDragOver');
    dropOver.value = true;
    isDragging.value = false;
    event.preventDefault();
};

const dragLeave = () => {
    console.log('dragLeave');
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
        router.patch(route("checklists.change.task", { task: task.id, checklist: props.checklistId }), {
        }, {
            preserveState: false,
            preserveScroll: true,
        });
    } catch (error) {
        console.error("Invalid JSON in drag data", error);
        dropOver.value = false;
    }
};

onMounted(() => {
    console.log('onMounted');
    const listeners = addEventListenerForDraggingStart();

    onUnmounted(() => {
        console.log('onUnmounted');
        removeEventListenerForDraggingStart(listeners);
    });
});
</script>
