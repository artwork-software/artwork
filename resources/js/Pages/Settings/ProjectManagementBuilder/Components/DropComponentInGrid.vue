<template>
    <div
        @dragover="onDragOver"
        @drop="onDrop"
        @dragleave="dragLeave"
        class="mx-2 h-full rounded-lg duration-100 ease-in-out border-2 border-dashed"
        :class="{
        'bg-artwork-buttons-create/30 border-artwork-buttons-create w-44': dropOver,
        'bg-artwork-buttons-create/10 border-artwork-buttons-create w-8': isDragging,
        'border-transparent': !dropOver && !isDragging,
    }">
        <div class="flex items-center justify-center pointer-events-none h-full xsDark" v-show="dropOver">
            {{ $t("Drop here") }}
        </div>
    </div>
</template>

<script setup>

import { ref, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import { EventListenerForDragging } from "@/Composeables/EventListenerForDragging.js";
const { isDragging, addEventListenerForDraggingStart, removeEventListenerForDraggingStart } = EventListenerForDragging();

const props = defineProps({
    order: {
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
        const component = JSON.parse(event.dataTransfer.getData("component"));
        if (!component || !component.id) {
            console.warn("Component data is invalid", component);
            return;
        }
        dropOver.value = false;
        router.post(route("project-management-builder.store", { component: component.id }), {
            order: props.order,
        });
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
