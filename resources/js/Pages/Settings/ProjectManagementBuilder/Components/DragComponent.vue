<template>
    <div draggable="true"
         @dragstart="onDragStart"
         @dragend="onDragEnd"
         class="bg-surface-sunken rounded-lg px-4 py-3 h-32 flex items-center justify-center w-full">
        <div>
            <div class="text-sm/5 font-semibold text-text">
                {{ $t(component.name) ?? component.name }}
            </div>
            <div class="text-sm/5 font-bold text-text-subtle">
                {{ $t(component.type) ?? component.type }}
            </div>
        </div>
    </div>
</template>

<script setup>

import {provide, reactive} from "vue";

import { EventListenerForDragging } from "@/Composeables/EventListenerForDragging.js";
const { dispatchEventStart, dispatchEventEnd } = EventListenerForDragging();
const props = defineProps({
    component: {
        type: Object,
        required: true,
    },
})


const onDragStart = (event) => {
    event.dataTransfer.setData('component', JSON.stringify(props.component));
    dispatchEventStart()
};

const onDragEnd = () => {
    // Dispatch ein globales Event zum Beenden
   dispatchEventEnd()
};

</script>

<style scoped>

</style>